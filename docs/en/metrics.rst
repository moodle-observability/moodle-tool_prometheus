Metrics
=======

All metrics are gauges, except ``moodle_folder_downloads_total`` (counter). Session counts are cached for one minute; disk usage comes from the hourly scheduled task.

.. list-table::
   :header-rows: 1

   * - Metric
     - Labels
     - Description
   * - ``moodle_info``
     - ``release``, ``version``, ``branch``
     - Moodle version (always 1).
   * - ``moodle_maintenance_mode``
     - 
     - 1 when the site is in maintenance mode.
   * - ``moodle_cron_last_start_timestamp_seconds``
     - 
     - Unix time of the last cron start (0 if unknown).
   * - ``moodle_users_active``
     - ``window`` (seconds/segundos)
     - Users whose last access is within the window.
   * - ``moodle_users``
     - 
     - Users that are not deleted (suspended included).
   * - ``moodle_sessions_open``
     - 
     - Open sessions in the session handler.
   * - ``moodle_sessions_authenticated``
     - 
     - Open sessions of logged in users (guests excluded).
   * - ``moodle_adhoc_tasks_queued``
     - 
     - Adhoc tasks waiting to run.
   * - ``moodle_adhoc_tasks_due``
     - 
     - Waiting adhoc tasks whose next run time has passed.
   * - ``moodle_adhoc_tasks_running``
     - 
     - Adhoc tasks marked as running.
   * - ``moodle_adhoc_tasks_failing``
     - 
     - Adhoc tasks with a fail delay.
   * - ``moodle_adhoc_task_oldest_age_seconds``
     - 
     - Age of the oldest waiting adhoc task.
   * - ``moodle_scheduled_tasks_failing``
     - 
     - Enabled scheduled tasks with a fail delay.
   * - ``moodle_quiz_attempts_in_progress``
     - 
     - Quiz attempts in progress (previews excluded).
   * - ``moodle_cache_store_ready``
     - ``store``, ``plugin``
     - 1 when the cache store instance is ready.
   * - ``moodle_moodledata_bytes``
     - ``dir``
     - Size of each top level folder of moodledata (``_files``: loose files).
   * - ``moodle_moodledata_scan_timestamp_seconds``
     - 
     - Unix time of the last disk scan.
   * - ``moodle_moodledata_scan_duration_seconds``
     - 
     - Duration of the last disk scan.
   * - ``moodle_folders``
     - ``size_band``, ``download_enabled``
     - Folders (``mod_folder``) by size band, in MiB (``0-25MB``, ``25-50MB``, ``50-100MB``, ``>100MB`` by default), and by the "Download folder" button (``0`` or ``1``).
   * - ``moodle_folder_max_bytes``
     - 
     - Size of the largest folder.
   * - ``moodle_folders_large_download_enabled``
     - 
     - Folders above the download limit (50 MiB by default) with the "Download folder" button enabled.
   * - ``moodle_folder_downloads_total``
     - ``size_band``, ``download_enabled``
     - Counter of folder downloads (ZIP of every file), by size band and by the state of the button at download time. ``download_enabled="0"``: the ZIP was requested through the URL of a folder whose button is hidden.
   * - ``moodle_folders_scan_timestamp_seconds``
     - 
     - Unix time of the last count of the folders.
   * - ``moodle_scrape_collector_success``
     - ``collector``
     - 1 when the collector (or the hook) succeeded.
   * - ``moodle_scrape_collector_duration_seconds``
     - ``collector``
     - Time spent by the collector.

Cache hit ratio
---------------

Moodle keeps cache hit and miss statistics only inside the current request (``$CFG->perfdebug``), so a hit ratio per store cannot be exported across requests. The exporter reports the readiness of each store instead.

Folders
-------

The "Download folder" button makes ``mod/folder/download_folder.php`` build the ZIP in PHP: with large folders, it holds a web server process for minutes. The ``compute_folder_sizes`` task counts the folders every hour (one aggregate query on ``files``, never during a scrape). A folder equal to a band limit belongs to that band; the limits are configurable (:doc:`configuration`).

The names and labels are the same as the external ``moodle_ops_exporter``, so that the same dashboards and alerts work with both.

The ZIP is also built when someone opens the download URL directly, even with the button disabled: the core only checks ``mod/folder:view``. ``moodle_folder_downloads_total`` shows it (``download_enabled="0"``); it is counted by an observer of ``\mod_folder\event\all_files_downloaded``, without reading the log. Block the URL of large folders in the web server if needed.

The external ``moodle_ops_exporter`` publishes ``moodle_folder_downloads``, the count in a window read from the log; ``increase(moodle_folder_downloads_total[1h])`` gives the same value for one hour.
