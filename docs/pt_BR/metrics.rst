Métricas
========

Todas as métricas são gauges, exceto ``moodle_folder_downloads_total`` (counter). As contagens de sessões ficam em cache por um minuto; o uso de disco vem da tarefa agendada de hora em hora.

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
   * - ``moodle_folders``
     - ``size_band``, ``download_enabled``
     - Pastas (``mod_folder``) por faixa de tamanho, em MiB (``0-25MB``, ``25-50MB``, ``50-100MB``, ``>100MB`` por padrão), e pelo botão "Baixar pasta" (``0`` ou ``1``).
   * - ``moodle_folder_max_bytes``
     - 
     - Tamanho da maior pasta.
   * - ``moodle_folders_large_download_enabled``
     - 
     - Pastas acima do limite de download (50 MiB por padrão) com o botão "Baixar pasta" ligado.
   * - ``moodle_folder_downloads_total``
     - ``size_band``, ``download_enabled``
     - Contador de downloads de pasta (ZIP de todos os arquivos), por faixa de tamanho e pelo estado do botão no momento do download. ``download_enabled="0"``: o ZIP foi pedido pela URL de uma pasta com o botão escondido.
   * - ``moodle_folders_scan_timestamp_seconds``
     - 
     - Horário Unix da última contagem das pastas.
   * - ``moodle_scrape_collector_success``
     - ``collector``
     - 1 quando o coletor (ou o hook) teve sucesso.
   * - ``moodle_scrape_collector_duration_seconds``
     - ``collector``
     - Tempo gasto pelo coletor.

Taxa de acerto do cache
-----------------------

O Moodle guarda estatísticas de acerto e falha do cache só dentro da requisição atual (``$CFG->perfdebug``), então não é possível exportar uma taxa de acerto por store entre requisições. O exportador informa a prontidão de cada store.

Pastas
------

O botão "Baixar pasta" faz o ``mod/folder/download_folder.php`` montar o ZIP em PHP: com pastas grandes, ele prende um processo do servidor web por minutos. A tarefa ``compute_folder_sizes`` conta as pastas a cada hora (uma consulta agregada em ``files``, nunca durante o scrape). Uma pasta igual ao limite de uma faixa fica nessa faixa; os limites são configuráveis (:doc:`configuration`).

Os nomes e labels são os mesmos do ``moodle_ops_exporter`` (externo), para que os mesmos painéis e alertas sirvam aos dois.

O ZIP também é montado quando alguém abre a URL de download diretamente, mesmo com o botão desligado: o core só confere ``mod/folder:view``. O ``moodle_folder_downloads_total`` mostra isso (``download_enabled="0"``); ele é contado por um observer de ``\mod_folder\event\all_files_downloaded``, sem ler o log. Se preciso, bloqueie a URL das pastas grandes no servidor web.

O ``moodle_ops_exporter`` (externo) publica ``moodle_folder_downloads``, a contagem numa janela lida do log; ``increase(moodle_folder_downloads_total[1h])`` dá o mesmo valor para uma hora.
