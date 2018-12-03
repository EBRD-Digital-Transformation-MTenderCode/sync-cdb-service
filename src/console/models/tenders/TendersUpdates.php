<?php
namespace console\models\tenders;

use Yii;
use yii\web\HttpException;
use console\models\elastic\ElasticComponent;
use console\models\plans\DB as PlansDB;
use console\models\contracts\DB as ContractsDB;
use PDOException;

/**
 * Class TendersUpdates
 * @package console\models\tenders
 */
class TendersUpdates
{
    const TABLE_TENDERS_UPDATES = 'tenders_updates';
    const TABLE_TENDERS = 'tenders';
    const TABLE_PLANS = 'plans';
    const TABLE_CONTRACTS = 'contracts';
    const TABLE_CDU = 'cdu';
    const DEFAULT_COUNT = 30;
    const CDU_ALIAS = 'mtender2';

    /**
     * Export records from tenders_updates to tenders
     */
    public static function run()
    {
        $delay = (int) Yii::$app->params['sleep_error_interval'];

        while(true) {
            try {
                self::handle();
            } catch(PDOException $exception) {
                DB::dropInstance();
                PlansDB::dropInstance();
                ContractsDB::dropInstance();
                Yii::error('DB ERROR: ' . str_replace("\n", " ", $exception->getMessage()), 'sync-info');
                Yii::info("Memory usage: " . memory_get_usage(), 'sync-info');
                Yii::info("...........Sleep...............", 'sync-info');
                gc_collect_cycles();
                sleep($delay);
            } catch (HttpException $exception) {
                Yii::error('CURL ERROR[' . $exception->getCode() . ']. ' . $exception->getMessage(), 'sync-info');
                Yii::info("Memory usage: " . memory_get_usage(), 'sync-info');
                Yii::info("...........Sleep...............", 'sync-info');
                gc_collect_cycles();
                sleep($delay);
            }
        }
    }

    /**
     * Export tenders_updates one iteration
     * @param int $count
     * @throws HttpException
     */
    public static function handle($count = self::DEFAULT_COUNT)
    {
        $processedTenders = 0;
        $processedPlans = 0;
        $processedContracts = 0;
        $delay = (int) Yii::$app->params['sleep_delay_interval'];
        $elastic_indexing = (bool) Yii::$app->params['elastic_indexing'];
        $elasticTenders = new ElasticComponent(
            Yii::$app->params['elastic_url'],
            Yii::$app->params['elastic_tenders_index'],
            Yii::$app->params['elastic_tenders_type']
        );
        $elasticPlans = new ElasticComponent(
            Yii::$app->params['elastic_url'],
            Yii::$app->params['elastic_plans_index'],
            Yii::$app->params['elastic_plans_type']
        );

        $elasticContracts = new ElasticComponent(
            Yii::$app->params['elastic_url'],
            Yii::$app->params['elastic_contracts_index'],
            Yii::$app->params['elastic_contracts_type']
        );

        if ($elastic_indexing) {
            $result = $elasticTenders->checkMapping();
            if ($result['code'] != 200) {
                throw new HttpException(400, "Elastic mapping tenders error. Http-code: " . $result['code']);
            }

            $result = $elasticPlans->checkMapping();
            if ($result['code'] != 200) {
                throw new HttpException(400, "Elastic mapping plans error. Http-code: " . $result['code']);
            }

            $result = $elasticContracts->checkMapping();
            if ($result['code'] != 200) {
                throw new HttpException(400, "Elastic mapping contracts error. Http-code: " . $result['code']);
            }

        }

        $items = DB::fetchAll('SELECT * FROM ' . self::TABLE_TENDERS_UPDATES . ' ORDER BY updated_at LIMIT ?', [$count]);
        $cdu = DB::fetch('SELECT * FROM ' . self::TABLE_CDU . ' WHERE alias=?', [self::CDU_ALIAS]);
        $cdu_id = $cdu['id'] ?? null;

        if (empty($items)) {
            Yii::info("Nothing to update.", 'sync-info');
            Yii::info("Memory usage: " . memory_get_usage(), 'sync-info');
            Yii::info("...........Sleep...............", 'sync-info');
            DB::dropInstance();
            PlansDB::dropInstance();
            ContractsDB::dropInstance();
            gc_collect_cycles();
            sleep($delay);
        } else {
            foreach($items as $item) {
                try {
                    DB::beginTransaction();

                    $decodedItem = Tender::decode($item);

                    switch ($decodedItem['type']) {
                        case Tender::MARK_PLAN:
                            self::handlePlan($decodedItem, $cdu_id);

                            if ($elastic_indexing) {
                                $elasticPlans->indexPlan($decodedItem, self::CDU_ALIAS);
                            }
                            $processedPlans++;
                            break;

                        case Tender::MARK_TENDER:
                            //self::dropPlan($decodedItem);
                            self::handleTender($decodedItem, $cdu_id);

                            if ($elastic_indexing) {
                                //$elasticPlans->deleteItem($decodedItem);
                                $elasticTenders->indexTender($decodedItem, self::CDU_ALIAS);
                            }
                            $processedTenders++;
                            break;

                        case Tender::MARK_CONTRACT:
                            //self::dropPlan($decodedItem);
                            //self::dropTender($decodedItem);
                            self::handleContract($decodedItem, $cdu_id);

                            if ($elastic_indexing) {
                                //$elasticPlans->deleteItem($decodedItem);
                                //$elasticTenders->deleteItem($decodedItem);
                                $elasticContracts->indexContract($decodedItem, self::CDU_ALIAS);
                            }
                            $processedContracts++;
                            break;


                        default:
                            break;
                    }

                    DB::execute('DELETE FROM ' . self::TABLE_TENDERS_UPDATES . ' WHERE "tender_id" = ?', [$item['tender_id']]);


                    DB::commit();
                } catch (\Exception $exception) {
                    DB::rollback();
                    throw new $exception($exception->getMessage());
                }
            }

            Yii::info("Processed {$processedTenders} tenders, {$processedPlans} plans, {$processedContracts} contracts", 'sync-info');
        }
    }

    /**
     * Update tender data in db
     * @param $item
     * @param $cdu_id
     */
    private static function handleTender($item, $cdu_id) {
        $count = DB::rowCount('SELECT * FROM ' . self::TABLE_TENDERS . ' WHERE tender_id = ?', [$item['tender_id']]);

        if ($count == 0) {
            DB::execute('INSERT INTO ' . self::TABLE_TENDERS . ' ("tender_id", "response", "release_package", "cdu_id") VALUES (?, ?, ?, ?)', [$item['tender_id'], $item['response'], $item['release_package'], $cdu_id]);
        }

        if ($count == 1) {
            DB::execute('UPDATE ' . self::TABLE_TENDERS . ' SET "response" = ?, "release_package" = ? WHERE "tender_id" = ?', [$item['response'], $item['release_package'], $item['tender_id']]);
        }
    }

    /**
     * Delete tender by id
     * @param $item
     */
    private static function dropTender($item)
    {
        DB::execute('DELETE FROM ' . self::TABLE_TENDERS . ' WHERE tender_id = ?', [$item['tender_id']]);
    }

    /**
     * Update plan data in db
     * @param $item
     * @param $cdu_id
     */
    private static function handlePlan($item, $cdu_id) {
        $count = PlansDB::rowCount('SELECT * FROM ' . self::TABLE_PLANS . ' WHERE plan_id = ?', [$item['tender_id']]);

        if ($count == 0) {
            PlansDB::execute('INSERT INTO ' . self::TABLE_PLANS . ' ("plan_id", "response", "release_package", "cdu_id") VALUES (?, ?, ?, ?)', [$item['tender_id'], $item['response'], $item['release_package'], $cdu_id]);
        }

        if ($count == 1) {
            PlansDB::execute('UPDATE ' . self::TABLE_PLANS . ' SET "response" = ?, "release_package" = ? WHERE "plan_id" = ?', [$item['response'], $item['release_package'], $item['tender_id']]);
        }
    }

    /**
     * Delete plan by id
     * @param $item
     */
    private static function dropPlan($item)
    {
        PlansDB::execute('DELETE FROM ' . self::TABLE_PLANS . ' WHERE plan_id = ?', [$item['tender_id']]);
    }

    /**
     * Update contract data in db
     * @param $item
     * @param $cdu_id
     */
    private static function handleContract($item, $cdu_id) {
        $count = ContractsDB::rowCount('SELECT * FROM ' . self::TABLE_CONTRACTS . ' WHERE contract_id = ?', [$item['tender_id']]);

        if ($count == 0) {
            ContractsDB::execute('INSERT INTO ' . self::TABLE_CONTRACTS . ' ("contract_id", "response", "release_package", "cdu_id") VALUES (?, ?, ?, ?)', [$item['tender_id'], $item['response'], $item['release_package'], $cdu_id]);
        }

        if ($count == 1) {
            ContractsDB::execute('UPDATE ' . self::TABLE_CONTRACTS . ' SET "response" = ?, "release_package" = ? WHERE "contract_id" = ?', [$item['response'], $item['release_package'], $item['tender_id']]);
        }
    }
}