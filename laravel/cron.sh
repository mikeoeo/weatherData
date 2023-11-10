#! /bin/bash
apt-get install cron -y

crontab -l > crontab_new

echo "* * * * * cd /app && php artisan schedule:run >> /dev/null 2>&1" >> crontab_new

crontab crontab_new

rm crontab_new

service cron start