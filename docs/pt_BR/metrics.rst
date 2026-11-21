Métricas
========

Todas as métricas são gauges. As contagens de sessões ficam em cache por um minuto; o uso de disco vem da tarefa agendada de hora em hora.

.. list-table::
   :header-rows: 1

   * - Métrica
     - Labels
     - Descrição
   * - ``moodle_info``
     - ``release``, ``version``, ``branch``
     - Versão do Moodle (sempre 1).
   * - ``moodle_maintenance_mode``
     - 
     - 1 quando o site está em modo de manutenção.
   * - ``moodle_cron_last_start_timestamp_seconds``
     - 
     - Horário Unix do último início do cron (0 se desconhecido).
   * - ``moodle_users_active``
     - ``window`` (seconds/segundos)
     - Usuários cujo último acesso está dentro da janela.
   * - ``moodle_users``
     - 
     - Usuários não excluídos (suspensos incluídos).
   * - ``moodle_sessions_open``
     - 
     - Sessões abertas no handler de sessões.
   * - ``moodle_sessions_authenticated``
     - 
     - Sessões abertas de usuários logados (visitantes excluídos).
   * - ``moodle_adhoc_tasks_queued``
     - 
     - Adhoc tasks aguardando execução.
   * - ``moodle_adhoc_tasks_due``
     - 
     - Adhoc tasks na fila cujo horário de execução já passou.
   * - ``moodle_adhoc_tasks_running``
     - 
     - Adhoc tasks marcadas como em execução.
   * - ``moodle_adhoc_tasks_failing``
     - 
     - Adhoc tasks com fail delay.
   * - ``moodle_adhoc_task_oldest_age_seconds``
     - 
     - Idade da adhoc task mais antiga na fila.
   * - ``moodle_scheduled_tasks_failing``
     - 
     - Tarefas agendadas habilitadas com fail delay.
   * - ``moodle_quiz_attempts_in_progress``
     - 
     - Tentativas de questionário em andamento (pré-visualizações excluídas).
   * - ``moodle_cache_store_ready``
     - ``store``, ``plugin``
     - 1 quando a instância de cache store está pronta.
   * - ``moodle_moodledata_bytes``
     - ``dir``
     - Tamanho de cada pasta de primeiro nível do moodledata (``_files``: arquivos soltos).
   * - ``moodle_moodledata_scan_timestamp_seconds``
     - 
     - Horário Unix da última varredura.
   * - ``moodle_moodledata_scan_duration_seconds``
     - 
     - Duração da última varredura.
   * - ``moodle_scrape_collector_success``
     - ``collector``
     - 1 quando o coletor (ou o hook) teve sucesso.
   * - ``moodle_scrape_collector_duration_seconds``
     - ``collector``
     - Tempo gasto pelo coletor.

Taxa de acerto do cache
-----------------------

O Moodle guarda estatísticas de acerto e falha do cache só dentro da requisição atual (``$CFG->perfdebug``), então não é possível exportar uma taxa de acerto por store entre requisições. O exportador informa a prontidão de cada store.
