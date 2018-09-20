# sync-cdb-service
Сервис синхронизации данных с ЦБД prozorro, ocds

# миграции
./yii migrate --migrationPath=@app/migrations/budgets --db=db_budgets

./yii migrate --migrationPath=@app/migrations/tenders --db=db_tenders
./yii migrate --migrationPath=@app/migrations/tenders_prz --db=db_tenders

./yii migrate --migrationPath=@app/migrations/plans_prz --db=db_plans

# actions
./yii budgets/get-changed-list
./yii budgets/get-updates
./yii budgets/updates

./yii tenders/get-changed-list
./yii tenders/get-updates
./yii tenders/updates

./yii tenders-prz/get-changed-list
./yii tenders-prz/get-updates
./yii tenders-prz/updates

./yii plans-prz/get-changed-list
./yii plans-prz/get-updates
./yii plans-prz/updates


# команды для Elastic
./yii reindex-elastic/all
./yii reindex-elastic/budgets
./yii reindex-elastic/tenders
./yii reindex-elastic/plans

./yii mapping-elastic/all
./yii mapping-elastic/budgets
./yii mapping-elastic/tenders
./yii mapping-elastic/plans