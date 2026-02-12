#!/bin/bash
cd /var/www/html/claude_admin2
for f in pages/customers.php pages/technicians.php pages/dashboard.php pages/service-requests.php pages/invoices.php pages/service-intake.php api/service-tickets.php; do
    echo -n "$f: "
    php -l "$f" 2>&1 | tail -1
done
