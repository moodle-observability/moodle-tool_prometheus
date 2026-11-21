<img src="docs/logo/logo.svg" alt="moodle-tool_prometheus logo" width="96" align="right" />

# moodle-tool_prometheus

[![Documentation](https://github.com/moodle-observability/moodle-tool_prometheus/actions/workflows/docs.yml/badge.svg)](https://github.com/moodle-observability/moodle-tool_prometheus/actions/workflows/docs.yml)

OpenMetrics / Prometheus exporter for Moodle (`tool_prometheus`). Exposes Moodle platform metrics in the OpenMetrics text format, so Prometheus can scrape them and Grafana can chart them.

**Status:** 0.1.0 (alpha). Download it from the [releases](https://github.com/moodle-observability/moodle-tool_prometheus/releases).

Documentation (English and Brazilian Portuguese): https://moodle-observability.github.io/moodle-tool_prometheus/

Requirements: Moodle 5.3 or later.

Quick start:

1. Install the plugin in `public/admin/tool/prometheus` and upgrade Moodle.
2. Set a token in *Site administration > Plugins > Admin tools > Prometheus exporter* (or force it in `config.php`).
3. Scrape `https://<site>/admin/tool/prometheus/metrics.php` with `Authorization: Bearer <token>`.

[`examples/`](examples) has a Prometheus scrape configuration, alert rules and a Grafana dashboard. Other plugins can add
metrics through the `\tool_prometheus\hook\collect_metrics` hook.

Part of [Moodle Observability](https://github.com/moodle-observability). Contributions: see the [contributing guide](https://github.com/moodle-observability/.github/blob/main/CONTRIBUTING.md).

License: [GPL-3.0](LICENSE).
