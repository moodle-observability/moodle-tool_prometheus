Usage
=====

Prometheus
----------

Example ``scrape_config`` (also in ``examples/prometheus.yml`` of the repository):

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

The token file must contain only the token. To scrape an internal address while keeping the host of ``wwwroot``, add ``proxy_url: http://<internal address>:80`` and keep the public host in ``targets``.

To tell sites apart (production, staging, other Moodle sites), add a ``site`` label on the scraping side: see :doc:`site`.

Check the output with ``promtool``:

.. code-block:: bash

   curl -s -H "Authorization: Bearer $TOKEN" https://moodle.example.com/admin/tool/prometheus/metrics.php | promtool check metrics

Grafana
-------

Import ``examples/grafana-dashboard.json`` (*Dashboards > New > Import*) and choose your Prometheus data source. It shows active users, sessions, the adhoc task queue, quiz attempts, cache stores, moodledata usage and the health of the collectors.

Alerts
------

``examples/alerts.yml`` has alert rules for a cron that stopped, a stuck adhoc queue, failing tasks, cache stores that are not ready and failing collectors. Load it in ``rule_files`` of Prometheus.

All metrics are described in :doc:`metrics`.
