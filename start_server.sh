#!/bin/bash
pkill -f 'php -S' 2>/dev/null
sleep 1
cd /var/www/html/claude_admin2
nohup php -S 0.0.0.0:8080 -t . > /tmp/php_server.log 2>&1 &
sleep 2
curl -s -o /dev/null -w '%{http_code}' http://localhost:8080/
echo ""
echo "Server PID: $!"
