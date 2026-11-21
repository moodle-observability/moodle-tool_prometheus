Installation
============

From a release
--------------

1. Download ``tool_prometheus-<version>.zip`` from the `releases <https://github.com/moodle-observability/moodle-tool_prometheus/releases>`_.
2. Install it in *Site administration > Plugins > Install plugins*, or unzip it into ``public/admin/tool/prometheus`` (Moodle 5.1 and later serve from ``public/``).
3. Open *Site administration > Notifications* to finish the installation, or run ``php admin/cli/upgrade.php``.

With Git
--------

From the Moodle root folder:

.. code-block:: bash

   git clone https://github.com/moodle-observability/moodle-tool_prometheus.git public/admin/tool/prometheus
   php admin/cli/upgrade.php

Then configure the token (:doc:`configuration`).

Uninstall
---------

*Site administration > Plugins > Plugins overview*, find ``tool_prometheus`` and click *Uninstall*.
