moodle-tool_prometheus
======================

**OpenMetrics / Prometheus exporter for Moodle.**

Exposes Moodle platform metrics on a token-protected endpoint, so Prometheus can scrape them and Grafana can chart and alert on them.

.. note::

   Version 0.1.0 (alpha). Follow the progress in the `issues <https://github.com/moodle-observability/moodle-tool_prometheus/issues>`_.

Features
--------

* ``/admin/tool/prometheus/metrics.php`` endpoint, authenticated by a bearer token, with an optional IP allow list. It answers in OpenMetrics or in the Prometheus text format (content negotiation).
* Active users per time window, registered users and open sessions (database, Redis or memcached session handlers).
* Adhoc task queue: queued, due, running and failing tasks and the age of the oldest one; failing scheduled tasks; last cron start.
* Quiz attempts in progress.
* Readiness of each cache (MUC) store instance.
* moodledata disk usage per top level folder, computed by a scheduled task (never during a scrape).
* Duration and success of each collector: a failing collector never breaks the scrape.
* Extension point: other plugins add their own metrics through a hook (:doc:`extending`).
* Sample Prometheus ``scrape_config``, alert rules and Grafana dashboard.

Requirements
------------

* Moodle 5.3 or later.
* PHP version supported by your Moodle release.
* PostgreSQL or MariaDB.

Links
-----

* `Source code <https://github.com/moodle-observability/moodle-tool_prometheus>`_
* `Issues <https://github.com/moodle-observability/moodle-tool_prometheus/issues>`_
* `Moodle Observability <https://moodle-observability.github.io/>`_

.. toctree::
   :hidden:
   :maxdepth: 1

   installation
   configuration
   usage
   metrics
   extending
