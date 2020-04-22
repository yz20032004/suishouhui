## Crontabs
#### 10 * * * * cd /mnt/apps/keyou_crm && /usr/local/php/bin/php refresh_app_access_token.php >> /tmp/refresh_app_access_token.log 2>&1
#### 50 * * * * cd /mnt/apps/keyou_crm && /usr/local/php/bin/php update_access_token.php >> /tmp/keyouxinxi_updateAccessToken.log 2>&1
#### */3 * * * * cd /mnt/apps/keyou_crm && /usr/local/php/bin/php crontab_micro_apply.php >> /tmp/crontab_micro_apply.log 2>&1
#### */3 * * * * cd /mnt/apps/keyou_crm && /usr/local/php/bin/php crontab_micro_updateState.php >> /tmp/crontab_micro_updateState.log 2>&1
#### 1 7 * * * cd /mnt/apps/keyou_crm && /usr/local/php/bin/php crontab_update_member_coupons.php >> /tmp/crontab_update_member_coupons.log 2>&1
#### 5 0 * * * cd /mnt/apps/keyou_crm && /usr/local/php/bin/php crontab_tuitui_day_revenue.php >> /tmp/crontab_tuitui_day_revenue.log 2>&1
#### */2 * * * * cd /mnt/apps/keyou_crm && /usr/local/php/bin/php crontab_update_together.php >> /tmp/crontab_update_together.log 2>&1
#### * * * * * cd /mnt/apps/keyou_crm && /usr/local/php/bin/php marketing.php >> /tmp/crontab_marketing.log 2>&1
#### * * * * * cd /mnt/apps/keyou_crm && /usr/local/php/bin/php crontab_warning.php >> /tmp/crontab_warning.log 2>&1
#### 0 8 * * * cd /mnt/apps/keyou_crm && /usr/local/php/bin/php crontab_daily_report.php >> /tmp/crontab_daily_report.log 2>&1
