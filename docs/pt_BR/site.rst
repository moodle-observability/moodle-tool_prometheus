Identificação do site
=====================

O exportador **não** acrescenta um rótulo ``site`` (nem ``env``, ``cluster`` ou parecidos) às métricas. Quem identifica o site é quem coleta: o Prometheus ou o Grafana Alloy.

Por quê
-------

* **É a convenção do Prometheus.** O Prometheus já acrescenta ``job`` e ``instance`` a toda série coletada, e a documentação recomenda que rótulos que descrevem *onde* um alvo roda (site, ambiente, região) sejam definidos como rótulos do alvo, do lado de quem coleta, e não dentro das métricas exportadas. Veja `Jobs and instances <https://prometheus.io/docs/concepts/jobs_instances/>`_ e `Writing exporters <https://prometheus.io/docs/instrumenting/writing_exporters/#target-labels-not-static-scraped-labels>`_.
* **Um rótulo para todos os exportadores do site.** O mesmo ``site`` chega ao ``tool_prometheus``, ao ``node_exporter``, ao ``postgres_exporter``, ao Redis, ao servidor web e às métricas de outros plugins (hook ``collect_metrics``), porque vem da mesma configuração de coleta. Assim, dashboards e alertas cruzam as métricas do Moodle com as do banco e do servidor.
* **Sem rótulos em conflito.** Se o exportador enviasse ``site`` e o Prometheus também acrescentasse ``site``, o Prometheus renomearia o exportado para ``exported_site`` (``honor_labels: false``, o padrão), e as séries ficariam com dois rótulos de site diferentes.
* **A identidade fica fora do Moodle.** Clonar um site (para homologação ou testes) copia banco e configurações; um rótulo guardado no Moodle iria junto com o clone e misturaria as métricas dele com as de produção. Do lado da coleta, cada alvo recebe o rótulo certo.
* **Nada para configurar no Moodle.** Não há configuração para manter sincronizada entre o Moodle e a pilha de monitoramento.

Como fazer
----------

**Rótulos por alvo** (um Prometheus coleta vários sites):

.. code-block:: yaml

   scrape_configs:
     - job_name: moodle
       metrics_path: /admin/tool/prometheus/metrics.php
       authorization:
         type: Bearer
         credentials_file: /etc/prometheus/secrets/moodle_token
       static_configs:
         - targets: ["moodle.example.com"]
           labels:
             site: example
             env: production
         - targets: ["staging.moodle.example.com"]
           labels:
             site: example
             env: staging

**Rótulos externos** (um Prometheus ou Alloy por site; vão em tudo o que ele envia por ``remote_write``, por exemplo ao Grafana Cloud):

.. code-block:: yaml

   global:
     external_labels:
       site: example
       env: production

**Relabeling** (deriva o rótulo do endereço do alvo):

.. code-block:: yaml

   relabel_configs:
     - source_labels: [__address__]
       regex: "(.+?)\\..*"
       target_label: site

**Grafana Alloy**:

.. code-block:: text

   prometheus.scrape "moodle" {
     targets         = [{"__address__" = "moodle.example.com", "site" = "example", "env" = "production"}]
     metrics_path    = "/admin/tool/prometheus/metrics.php"
     scheme          = "https"
     bearer_token_file = "/etc/alloy/moodle_token"
     forward_to      = [prometheus.remote_write.default.receiver]
   }

Use os mesmos nomes de rótulo (``site``, ``env``) em todas as configurações de coleta da sua organização, para que os dashboards sirvam a todos os sites.
