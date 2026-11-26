Configuração
============

As configurações ficam em *Administração do site > Plugins > Ferramentas de administração > Prometheus exporter*.

Token
-----

O Prometheus envia o token no header ``Authorization: Bearer <token>``. Use um valor aleatório longo (por exemplo ``openssl rand -hex 32``). O token nunca é aceito na query string, que acaba nos logs de acesso.

* Sem token configurado o endpoint responde ``503`` (não configurado).
* Token ausente ou errado: ``401``.

Para manter o token fora do banco de dados, force-o no ``config.php`` (por exemplo a partir de uma variável de ambiente):

.. code-block:: php

   $CFG->forced_plugin_settings['tool_prometheus']['token'] = getenv('MOODLE_PROMETHEUS_TOKEN');

Endereços IP permitidos
-----------------------

Opcional. Um endereço ou faixa por linha (CIDR, ou endereços parciais como ``10.1.``). Requisições de outros endereços recebem ``403``, mesmo com token válido. Atrás de um proxy reverso, configure o Moodle (``$CFG->reverseproxy`` e ``$CFG->getremoteaddrconf``) para que ele veja o endereço real do cliente.

Janelas de usuários ativos
--------------------------

Janelas separadas por vírgula, em segundos, do gauge ``moodle_users_active`` (um label ``window`` para cada, no máximo dez). Padrão: ``300,3600,86400``.

Tarefa agendada
---------------

*Compute moodledata disk usage for the Prometheus exporter* roda a cada hora (minuto 23). Ela só lê o moodledata e grava os tamanhos na configuração do plugin; o endpoint informa o último resultado.

Host usado pelo Prometheus
--------------------------

O Moodle redireciona requisições cujo host é diferente do ``$CFG->wwwroot``. O Prometheus precisa chegar ao endpoint com o host do ``wwwroot``:

* colete pela URL pública do site; ou
* colete por um endereço interno usando ``proxy_url`` (o alvo mantém o host do ``wwwroot`` e a conexão vai para o endereço interno), como no exemplo em :doc:`usage`.

Pastas
------

* **Faixas de tamanho das pastas** (``folderbands``, padrão ``25,50,100``): Limites, em MiB, das faixas de ``moodle_folders``.
* **Limite de download das pastas** (``folderdownloadlimit``, padrão ``50``): Tamanho, em MiB, acima do qual uma pasta com o botão de download ligado entra em ``moodle_folders_large_download_enabled``.

Adhoc tasks
-----------

* **Janela do log de adhoc tasks** (``adhocclasswindow``, padrão ``3600``): segundos do log de tarefas lidos pelas métricas de adhoc tasks por classe.

Fóruns
------

* **Faixas das discussões** (``forumbands``, padrão ``1000,5000,10000``): limites, em posts, das faixas de ``moodle_forum_discussions``.
* **Limite de discussão grande** (``forumlargelimit``, padrão ``10000``): posts acima dos quais uma discussão entra em ``moodle_forum_discussions_large``.

Arquivos grandes
----------------

* **Faixas de arquivos grandes** (``largefilebands``, padrão ``50,100``): limites, em MiB, das faixas de ``moodle_files_large``; o primeiro é o piso.
* **Componentes de arquivos grandes** (``largefilecomponents``, padrão ``mod_page,mod_resource,mod_folder``): componentes cujos arquivos são contados.
