# sync-cdb-service
Сервис синхронизации данных с ЦБД prozorro, ocds

# миграции
./yii migrate --migrationPath=@app/migrations/budgets --db=db_budgets

./yii migrate --migrationPath=@app/migrations/tenders --db=db_tenders
./yii migrate --migrationPath=@app/migrations/tenders_prz --db=db_tenders

./yii migrate --migrationPath=@app/migrations/plans --db=db_plans
./yii migrate --migrationPath=@app/migrations/plans_prz --db=db_plans

./yii migrate --migrationPath=@app/migrations/contracts --db=db_contracts
./yii migrate --migrationPath=@app/migrations/contracts_prz --db=db_contracts

./yii migrate --migrationPath=@app/migrations/complaints --db=db_complaints

#mapping
./yii mapping-elastic/all
./yii mapping-elastic/contracts
./yii mapping-elastic/tenders
./yii mapping-elastic/plans
./yii mapping-elastic/complaints

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

./yii contracts-prz/get-changed-list
./yii contracts-prz/get-updates
./yii contracts-prz/updates

./yii complaints/complaints
./yii complaints/decisions

./yii cpv/import

# команды для Elastic
**Опция "--hard" очищает БД, переиндексация происходит за счет синхронизилок**
./yii reindex-elastic/all
./yii reindex-elastic/budgets
./yii reindex-elastic/tenders
./yii reindex-elastic/plans
./yii reindex-elastic/contracts
./yii reindex-elastic/complaints

./yii reindex-elastic/add-budget %id%
./yii reindex-elastic/add-tender %id%
./yii reindex-elastic/add-plan-prz %id%
./yii reindex-elastic/add-tender-prz %id%
./yii reindex-elastic/add-contract-prz %id%