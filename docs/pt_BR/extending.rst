Extensão
========

Outros plugins acrescentam métricas pelo hook ``\tool_prometheus\hook\collect_metrics``. Registre um callback em ``db/hooks.php``:

.. code-block:: php

   $callbacks = [
       [
           'hook' => \tool_prometheus\hook\collect_metrics::class,
           'callback' => [\local_example\metrics::class, 'collect'],
       ],
   ];

e acrescente as amostras no callback:

.. code-block:: php

   public static function collect(\tool_prometheus\hook\collect_metrics $hook): void {
       $hook->add_gauge('local_example', 'moodle_local_example_queue', 'Messages waiting.', 12, ['queue' => 'mail']);
       $hook->add_counter('local_example', 'moodle_local_example_sent_total', 'Messages sent.', 345);
   }

Regras
------

* Os nomes começam com ``moodle_<componente>_``; contadores terminam em ``_total``.
* No máximo 100 combinações de labels por métrica; nenhum dado pessoal nos labels.
* Sem efeitos colaterais e sem trabalho caro no callback: ele roda a cada coleta. Use cache ou tarefa agendada para valores caros.
* Métricas inválidas são descartadas e informadas com ``debugging()``. Um callback que lança exceção perde só as próprias métricas, e ``moodle_scrape_collector_success{collector="hook"}`` vira 0.
