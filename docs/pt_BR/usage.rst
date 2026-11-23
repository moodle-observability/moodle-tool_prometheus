Uso
===

Prometheus
----------

Exemplo de ``scrape_config`` (também em ``examples/prometheus.yml`` do repositório):

.. code-block:: yaml

   scrape_configs:
     - job_name: moodle
       scrape_interval: 60s
       scheme: https
       metrics_path: /admin/tool/prometheus/metrics.php
       authorization:
         type: Bearer
         credentials_file: /etc/prometheus/secrets/moodle_token
       static_configs:
         - targets: ["moodle.example.com"]

O arquivo do token deve conter só o token. Para coletar por um endereço interno mantendo o host do ``wwwroot``, acrescente ``proxy_url: http://<endereço interno>:80`` e mantenha o host público em ``targets``.

Para distinguir sites (produção, homologação, outros Moodle), acrescente um rótulo ``site`` do lado da coleta: veja :doc:`site`.

Confira a saída com o ``promtool``:

.. code-block:: bash

   curl -s -H "Authorization: Bearer $TOKEN" https://moodle.example.com/admin/tool/prometheus/metrics.php | promtool check metrics

Grafana
-------

Importe ``examples/grafana-dashboard.json`` (*Dashboards > New > Import*) e escolha o datasource do Prometheus. Ele mostra usuários ativos, sessões, a fila de adhoc tasks, tentativas de questionário, cache stores, uso do moodledata e a saúde dos coletores.

Alertas
-------

``examples/alerts.yml`` traz regras de alerta para cron parado, fila de adhoc tasks travada, tarefas com falha, cache stores indisponíveis e coletores com falha. Carregue-o em ``rule_files`` do Prometheus.

Todas as métricas estão descritas em :doc:`metrics`.
