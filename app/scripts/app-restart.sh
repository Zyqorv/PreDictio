#!/bin/bash
pkill -f "php -S localhost:8000"
cd /home/nz84/PreDictio/app/public
nohup php -S localhost:8000 > /tmp/php-server.log 2>&1 &