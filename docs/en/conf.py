import moodle_docs_theme

project = "moodle-tool_prometheus"
copyright = "2026, Moodle Observability contributors"
author = "Kelson da Costa Medeiros"
release = "0.1.0-dev"

extensions = [
    "sphinx.ext.githubpages",
    "moodle_docs_theme",
]

templates_path = []
exclude_patterns = ["_build", "Thumbs.db", ".DS_Store"]

language = "en"

html_theme = "moodle_docs_theme"
html_theme_path = [moodle_docs_theme.get_html_theme_path()]

html_theme_options = {
    "primary_color": "#ea580c",
    "secondary_color": "#0f172a",
    "project_name": "moodle-tool_prometheus",
    "tagline": "OpenMetrics / Prometheus exporter for Moodle",
    "github_url": "https://github.com/moodle-observability/moodle-tool_prometheus",
    "github_repo": "moodle-observability/moodle-tool_prometheus",
    "github_version": "main",
    "doc_path": "docs/en/",
    "show_edit_on_github": True,
    "enable_dark_mode": True,
    "navigation_links": "Home|index, Installation|installation, Configuration|configuration, Usage|usage",
}

html_static_path = []

# Logo e favicon do plugin (fonte em docs/logo/; logo quadrada, sem cantos arredondados).
html_logo = "../logo/logo.svg"
html_favicon = "../logo/favicon.ico"
