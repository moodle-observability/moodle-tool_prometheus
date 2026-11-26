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

Shared with moodle_ops_exporter
-------------------------------

The external ``moodle_ops_exporter`` (an alternative that only reads the database) uses the same names for these alerts, so that one Prometheus setup works with either. Their contract is kept equal in both projects and checked by the exporter CI against a tag of this file: name, ``expr``, ``for`` and ``severity``. The summaries stay in the language of each project. ``MoodleLargeFolderDirectDownload`` differs in the expression by design (a counter here, a count in a window there), so only its name, ``for`` and ``severity`` are compared. Change these alerts in both projects at once.

.. list-table::
   :header-rows: 1

   * - Alert
     - for
     - severity
   * - ``MoodleAdhocClassFailing``
     - 15m
     - warning
   * - ``MoodleAdhocClassSlow``
     - 15m
     - info
   * - ``MoodleLargeFolderDownloadEnabled``
     - 1h
     - warning
   * - ``MoodleLargeFolderDirectDownload``
     - 15m
     - warning
   * - ``MoodleForumDiscussionTooLarge``
     - 1h
     - info


All metrics are described in :doc:`metrics`.
