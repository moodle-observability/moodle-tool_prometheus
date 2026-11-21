moodle-tool_prometheus
======================

**OpenMetrics / Prometheus exporter for Moodle.**

Exposes Moodle platform metrics in the OpenMetrics text format, so Prometheus can scrape them and Grafana can chart them.

.. note::

   Under development, targeting the ``v0.1.0 – MVP`` milestone. There is no release yet.
   Follow the progress in the `issues <https://github.com/moodle-observability/moodle-tool_prometheus/issues>`_.

Planned features
----------------

* ``/metrics`` endpoint protected by a token, with optional IP allow list.
* Active users and open sessions (gauges).
* Adhoc task queue size, oldest pending task age and failing tasks.
* Quiz attempts in progress.
* MUC cache hit ratio per store.
* moodledata disk usage, computed by a scheduled task (never during a scrape).
* Sample Grafana dashboard and Prometheus ``scrape_config``.

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
