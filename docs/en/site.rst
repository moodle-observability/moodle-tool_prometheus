Identifying the site
====================

The exporter does **not** add a ``site`` label (nor ``env``, ``cluster`` or similar) to its metrics. The site is identified by whoever scrapes it: Prometheus or Grafana Alloy.

Why
---

* **It is the Prometheus convention.** Prometheus already attaches ``job`` and ``instance`` to every series it scrapes, and the documentation recommends adding labels that describe *where* a target runs (site, environment, region) as target labels on the scraping side, not inside the exported metrics. See `Jobs and instances <https://prometheus.io/docs/concepts/jobs_instances/>`_ and `Writing exporters <https://prometheus.io/docs/instrumenting/writing_exporters/#target-labels-not-static-scraped-labels>`_.
* **One label for every exporter of the site.** The same ``site`` value reaches ``tool_prometheus``, ``node_exporter``, ``postgres_exporter``, Redis, the web server and the metrics of other plugins (``collect_metrics`` hook) because it comes from the same scrape configuration. Dashboards and alerts can then join Moodle metrics with database and host metrics.
* **No conflicting labels.** If the exporter sent ``site`` and Prometheus also added ``site``, Prometheus would rename the exported one to ``exported_site`` (``honor_labels: false``, the default) and the series would carry two different site labels.
* **The identity stays outside Moodle.** Cloning a site (for staging or tests) copies its database and settings; a label stored in Moodle would follow the clone and mix its metrics with production. On the scraping side, each target gets the right label.
* **Nothing to configure in Moodle.** There is no setting to keep in sync between the Moodle configuration and the monitoring stack.

How
---

**Labels per target** (one Prometheus scrapes several sites):

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

**External labels** (one Prometheus or Alloy per site; they are added to everything it sends with ``remote_write``, for example to Grafana Cloud):

.. code-block:: yaml

   global:
     external_labels:
       site: example
       env: production

**Relabelling** (derive the label from the target address):

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

Use the same label names (``site``, ``env``) in every scrape configuration of your organisation, so dashboards work for all sites.
