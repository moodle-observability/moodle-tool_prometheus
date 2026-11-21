Metrics
=======

All metrics are gauges. Session counts are cached for one minute; disk usage comes from the hourly scheduled task.

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
   * - ``moodle_scrape_collector_success``
     - ``collector``
     - 1 when the collector (or the hook) succeeded.
   * - ``moodle_scrape_collector_duration_seconds``
     - ``collector``
     - Time spent by the collector.

Cache hit ratio
---------------

Moodle keeps cache hit and miss statistics only inside the current request (``$CFG->perfdebug``), so a hit ratio per store cannot be exported across requests. The exporter reports the readiness of each store instead.
