# Changelog

## 0.6.0 - 2026-11-26

- Large files: `moodle_files_large{component, size_band}` (distinct contents, `mod_page`, `mod_resource` and
  `mod_folder` by default, bands 50-100MB and >100MB) and `moodle_files_large_scan_timestamp_seconds`, computed
  hourly by the new `compute_large_files` task.
- Example alerts aligned with moodle_ops_exporter (shared contract, documented in Usage):
  `MoodleLargeFolderDownloadedByUrl` renamed to `MoodleLargeFolderDirectDownload` (summed by `site`, `for: 15m`);
  `MoodleAdhocClassSlow` gets `for: 15m`.

## 0.5.0 - 2026-11-26

- Forum discussions by number of posts: `moodle_forum_discussions{posts_band}`, `moodle_forum_discussion_max_posts`,
  `moodle_forum_discussions_large` and `moodle_forum_discussions_scan_timestamp_seconds`, computed hourly by the new
  `compute_forum_sizes` task. Band limits and the large discussion limit are settings. Names and labels are the
  same as moodle_ops_exporter.
- Example alert `MoodleForumDiscussionTooLarge`.

## 0.4.0 - 2026-11-24

- Adhoc tasks by class: `moodle_adhoc_class_runs`, `moodle_adhoc_class_failures` and
  `moodle_adhoc_class_duration_seconds{stat="sum"|"max"}` from the task log (window configurable, 1 hour by default,
  top 10 classes by total duration, same as moodle_ops_exporter), and `moodle_adhoc_class_queued` and
  `moodle_adhoc_class_oldest_age_seconds` from the queue (top 10, rest in `other`). Kept for one minute.
- Example alerts `MoodleAdhocClassFailing` and `MoodleAdhocClassSlow`.

## 0.3.0 - 2026-11-24

- `moodle_folder_downloads_total{size_band, download_enabled}`: counter of folder downloads (ZIP of every file),
  by size band and by the state of the "Download folder" button, from an observer of
  `\mod_folder\event\all_files_downloaded` (no log query). `download_enabled="0"` shows ZIPs requested through
  the URL of folders whose button is hidden.
- New table `tool_prometheus_counter` for counters kept across cache purges.
- Example alert `MoodleLargeFolderDownloadedByUrl`.

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
