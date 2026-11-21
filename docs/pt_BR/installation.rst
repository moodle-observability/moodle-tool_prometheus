Instalação
==========

A partir de uma versão publicada
--------------------------------

1. Baixe ``tool_prometheus-<versão>.zip`` em `releases <https://github.com/moodle-observability/moodle-tool_prometheus/releases>`_.
2. Instale em *Administração do site > Plugins > Instalar plugins*, ou descompacte em ``public/admin/tool/prometheus`` (o Moodle 5.1 ou posterior serve a partir de ``public/``).
3. Abra *Administração do site > Notificações* para concluir a instalação, ou rode ``php admin/cli/upgrade.php``.

Com Git
-------

Na pasta raiz do Moodle:

.. code-block:: bash

   git clone https://github.com/moodle-observability/moodle-tool_prometheus.git public/admin/tool/prometheus
   php admin/cli/upgrade.php

Depois configure o token (:doc:`configuration`).

Desinstalação
-------------

*Administração do site > Plugins > Visão geral dos plugins*, localize ``tool_prometheus`` e clique em *Desinstalar*.
