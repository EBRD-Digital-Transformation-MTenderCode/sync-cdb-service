##!/bin/bash
#/usr/sbin/php-fpm
#chmod -R 777 /var/run/php-fpm
#
#cd /var/www/service && php vendor/ustudio/service_mandatory/ConfigServiceController.php
#php /var/www/service/init --env=Production --overwrite=y
#
##/bin/bash
##/usr/sbin/nginx -g 'daemon off;'
#
#cd /var/www/service
cd src
echo "SERVICE: " $SERVICENAME

####### budgets

if [[ $SERVICENAME == "budgets-changed-list-getter" ]]
then
./yii migrate --migrationPath=@app/migrations/budgets --db=db_budgets --interactive=0
./yii budgets/get-changed-list
fi

if [[ $SERVICENAME == "budgets-updates-getter" ]]
then
./yii migrate --migrationPath=@app/migrations/budgets --db=db_budgets --interactive=0
./yii budgets/get-updates
fi

####### tenders

if [[ $SERVICENAME == "tenders-changed-list-getter" ]]
then
./yii migrate --migrationPath=@app/migrations/tenders --db=db_tenders --interactive=0
./yii tenders/get-changed-list
fi

if [[ $SERVICENAME == "tenders-prz-changed-list-getter" ]]
then
./yii migrate --migrationPath=@app/migrations/tenders_prz --db=db_tenders --interactive=0
./yii tenders-prz/get-changed-list
fi

if [[ $SERVICENAME == "tenders-prz-updates-getter" ]]
then
./yii migrate --migrationPath=@app/migrations/tenders_prz --db=db_tenders --interactive=0
./yii tenders-prz/get-updates
fi

####### plans

if [[ $SERVICENAME == "plans-prz-changed-list-getter" ]]
then
./yii migrate --migrationPath=@app/migrations/plans_prz --db=db_plans --interactive=0
./yii plans-prz/get-changed-list
fi

if [[ $SERVICENAME == "plans-prz-updates-getter" ]]
then
./yii migrate --migrationPath=@app/migrations/plans_prz --db=db_plans --interactive=0
./yii plans-prz/get-updates
fi