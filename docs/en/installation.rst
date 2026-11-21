Installation
============

.. note::

   There is no release yet. These steps install the development version.

With Git
--------

From the Moodle root folder (``public`` on Moodle 5.1 and later):

.. code-block:: bash

   git clone https://github.com/moodle-observability/moodle-tool_prometheus.git admin/tool/prometheus

Then open *Site administration > Notifications* to finish the installation, or run ``php admin/cli/upgrade.php``.

Uninstall
---------

*Site administration > Plugins > Plugins overview*, find ``tool_prometheus`` and click *Uninstall*.
