Instalação
==========

.. note::

   Ainda não há versão publicada. Estes passos instalam a versão de desenvolvimento.

Com Git
-------

Na pasta raiz do Moodle (``public`` no Moodle 5.1 ou posterior):

.. code-block:: bash

   git clone https://github.com/moodle-observability/moodle-tool_prometheus.git admin/tool/prometheus

Depois abra *Administração do site > Notificações* para concluir a instalação, ou rode ``php admin/cli/upgrade.php``.

Desinstalação
-------------

*Administração do site > Plugins > Visão geral dos plugins*, localize ``tool_prometheus`` e clique em *Desinstalar*.
