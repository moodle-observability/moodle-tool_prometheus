# Changelog

## 0.2.0 - 2026-11-24

- Folder metrics (`mod_folder`): `moodle_folders{size_band, download_enabled}`, `moodle_folder_max_bytes`,
  `moodle_folders_large_download_enabled` and `moodle_folders_scan_timestamp_seconds`, computed hourly by the new
  `compute_folder_sizes` task. Band limits and the download limit are settings. Names and labels are the same as
  moodle_ops_exporter.
- Example alert `MoodleLargeFolderDownloadEnabled`.

## 0.1.0 - 2026-11-21

First release (alpha).

- `/admin/tool/prometheus/metrics.php` endpoint authenticated by a bearer token, with an optional IP allow list;
  OpenMetrics or Prometheus text format by content negotiation.
- Metrics: Moodle version, maintenance mode, last cron start, active users per window, registered users, open
  sessions (any session handler), adhoc task queue (queued, due, running, failing, oldest age), failing scheduled
  tasks, quiz attempts in progress, cache store readiness, moodledata disk usage per folder and collector health.
- Scheduled task that computes the moodledata disk usage (read only).
- `collect_metrics` hook for metrics contributed by other plugins.
- Example Prometheus scrape configuration, alert rules and Grafana dashboard.
