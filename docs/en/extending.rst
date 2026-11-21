Extending
=========

Other plugins add metrics through the ``\tool_prometheus\hook\collect_metrics`` hook. Register a callback in ``db/hooks.php``:

.. code-block:: php

   $callbacks = [
       [
           'hook' => \tool_prometheus\hook\collect_metrics::class,
           'callback' => [\local_example\metrics::class, 'collect'],
       ],
   ];

and add samples in the callback:

.. code-block:: php

   public static function collect(\tool_prometheus\hook\collect_metrics $hook): void {
       $hook->add_gauge('local_example', 'moodle_local_example_queue', 'Messages waiting.', 12, ['queue' => 'mail']);
       $hook->add_counter('local_example', 'moodle_local_example_sent_total', 'Messages sent.', 345);
   }

Rules
-----

* Names start with ``moodle_<component>_``; counters end in ``_total``.
* At most 100 label combinations per metric; no personal data in labels.
* No side effects and no expensive work in the callback: it runs on every scrape. Use a cache or a scheduled task for costly values.
* Invalid metrics are dropped and reported with ``debugging()``. A callback that throws only loses its own metrics, and ``moodle_scrape_collector_success{collector="hook"}`` becomes 0.
