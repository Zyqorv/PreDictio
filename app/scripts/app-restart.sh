#!/bin/bash
pkill -f "php -S 0.0.0.0:8000 router.php"
cd /home/nz84/PreDictio/app/public
nohup php -S 0.0.0.0:8000 router.php > /tmp/php-server.log 2>&1 &