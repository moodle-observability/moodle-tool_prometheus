moodle-tool_prometheus
======================

**Exportador OpenMetrics / Prometheus para o Moodle.**

Expõe métricas da plataforma Moodle no formato texto OpenMetrics, para que o Prometheus as colete e o Grafana as mostre em gráficos.

.. note::

   Em desenvolvimento, com foco no milestone ``v0.1.0 – MVP``. Ainda não há versão publicada.
   Acompanhe o andamento nas `issues <https://github.com/moodle-observability/moodle-tool_prometheus/issues>`_.

Funcionalidades previstas
-------------------------

* Endpoint ``/metrics`` protegido por token, com lista opcional de IPs permitidos.
* Usuários ativos e sessões abertas (gauges).
* Tamanho da fila de adhoc tasks, idade da tarefa pendente mais antiga e tarefas com falha.
* Tentativas de questionário em andamento.
* Taxa de acerto do cache (MUC) por store.
* Uso de disco do moodledata, calculado por tarefa agendada (nunca durante a coleta).
* Dashboard de exemplo para o Grafana e ``scrape_config`` para o Prometheus.

Requisitos
----------

* Moodle 5.3 ou posterior.
* Versão do PHP suportada pela sua versão do Moodle.
* PostgreSQL ou MariaDB.

Links
-----

* `Código-fonte <https://github.com/moodle-observability/moodle-tool_prometheus>`_
* `Issues <https://github.com/moodle-observability/moodle-tool_prometheus/issues>`_
* `Moodle Observability <https://moodle-observability.github.io/>`_

.. toctree::
   :hidden:
   :maxdepth: 1

   installation
   configuration
   usage
