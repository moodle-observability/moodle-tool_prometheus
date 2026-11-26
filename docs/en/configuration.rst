Configuration
=============

The settings are in *Site administration > Plugins > Admin tools > Prometheus exporter*.

Token
-----

Prometheus sends the token in the ``Authorization: Bearer <token>`` header. Use a long random value (for example ``openssl rand -hex 32``). The token is never accepted in the query string, which ends up in access logs.

* Without a token the endpoint answers ``503`` (not configured).
* Missing or wrong token: ``401``.

To keep the token out of the database, force it in ``config.php`` (for example from an environment variable):

.. code-block:: php

   $CFG->forced_plugin_settings['tool_prometheus']['token'] = getenv('MOODLE_PROMETHEUS_TOKEN');

Allowed IP addresses
--------------------

Optional. One address or range per line (CIDR, or partial addresses such as ``10.1.``). Requests from other addresses get ``403``, even with a valid token. Behind a reverse proxy, configure Moodle (``$CFG->reverseproxy`` and ``$CFG->getremoteaddrconf``) so it sees the real client address.

Active user windows
-------------------

Comma separated windows, in seconds, of the ``moodle_users_active`` gauge (one ``window`` label each, at most ten). Default: ``300,3600,86400``.

Scheduled task
--------------

*Compute moodledata disk usage for the Prometheus exporter* runs every hour (minute 23). It only reads moodledata and stores the sizes in the plugin configuration; the endpoint reports the last result.

Host name used by Prometheus
----------------------------

Moodle redirects requests whose host differs from ``$CFG->wwwroot``. Prometheus must reach the endpoint with the host of ``wwwroot``:

* scrape the public URL of the site; or
* scrape an internal address through ``proxy_url`` (the target keeps the ``wwwroot`` host while the connection goes to the internal address), as in the example in :doc:`usage`.

Folders
-------

* **Folder size bands** (``folderbands``, default ``25,50,100``): Limits, in MiB, of the size bands of ``moodle_folders``.
* **Folder download limit** (``folderdownloadlimit``, default ``50``): Size, in MiB, above which a folder with the download button enabled is counted in ``moodle_folders_large_download_enabled``.

Adhoc tasks
-----------

* **Adhoc task log window** (``adhocclasswindow``, default ``3600``): seconds of the task log read by the adhoc metrics by class.

Forums
------

* **Forum discussion bands** (``forumbands``, default ``1000,5000,10000``): limits, in posts, of the bands of ``moodle_forum_discussions``.
* **Large discussion limit** (``forumlargelimit``, default ``10000``): posts above which a discussion is counted in ``moodle_forum_discussions_large``.

Large files
-----------

* **Large file bands** (``largefilebands``, default ``50,100``): limits, in MiB, of the bands of ``moodle_files_large``; the first one is the floor.
* **Large file components** (``largefilecomponents``, default ``mod_page,mod_resource,mod_folder``): components whose files are counted.
