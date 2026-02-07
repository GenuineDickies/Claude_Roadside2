#!/bin/bash
# Create symlink and update password
ln -sf /var/www/html/claude_admin2 /var/www/html/public/claude_admin2
echo "Symlink created"
php /var/www/html/claude_admin2/update_pass.php
echo "Done"
