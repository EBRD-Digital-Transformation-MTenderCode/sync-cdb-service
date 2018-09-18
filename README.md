# sync-cdb-service
Сервис синхронизации данных с ЦБД prozorro, ocds

./yii migrate --migrationPath=@app/migrations/budgets --db=db_budgets

./yii migrate --migrationPath=@app/migrations/tenders --db=db_tenders
./yii migrate --migrationPath=@app/migrations/tenders_prz --db=db_tenders

./yii migrate --migrationPath=@app/migrations/plans_prz --db=db_plans


./yii budgets/get-changed-list
./yii tenders/get-changed-list
./yii tenders-prz/get-changed-list
./yii plans-prz/get-changed-list