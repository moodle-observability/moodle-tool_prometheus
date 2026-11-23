moodle-tool_prometheus
======================

**Exportador OpenMetrics / Prometheus para o Moodle.**

Expõe métricas da plataforma Moodle num endpoint protegido por token, para o Prometheus coletar e o Grafana mostrar em gráficos e alertas.

.. note::

   Versão 0.1.0 (alfa). Acompanhe o andamento nas `issues <https://github.com/moodle-observability/moodle-tool_prometheus/issues>`_.

Funcionalidades
---------------

* Endpoint ``/admin/tool/prometheus/metrics.php``, autenticado por bearer token, com lista opcional de IPs permitidos. Responde em OpenMetrics ou no formato texto do Prometheus (negociação de conteúdo).
* Usuários ativos por janela de tempo, usuários cadastrados e sessões abertas (handlers de sessão no banco, Redis ou memcached).
* Fila de adhoc tasks: na fila, vencidas, em execução e com falha, e a idade da mais antiga; tarefas agendadas com falha; último início do cron.
* Tentativas de questionário em andamento.
* Prontidão de cada instância de cache store (MUC).
* Uso de disco do moodledata por pasta de primeiro nível, calculado por tarefa agendada (nunca durante a coleta).
* Duração e sucesso de cada coletor: um coletor com falha nunca quebra a coleta.
* Ponto de extensão: outros plugins acrescentam métricas próprias por um hook (:doc:`extending`).
* Exemplos de ``scrape_config``, regras de alerta e dashboard do Grafana.

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
   site
   metrics
   extending
