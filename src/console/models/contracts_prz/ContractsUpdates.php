<?php
namespace console\models\contracts_prz;

use Yii;
use yii\web\HttpException;
use console\models\elastic\ElasticComponent;
use console\models\tenders_prz\Tender;
use PDOException;

/**
 * Class ContractsUpdates
 * @package console\models\Contracts
 */
class ContractsUpdates
{
    const TABLE_CONTRACTS_UPDATES = 'contracts_prz_updates';
    const TABLE_CONTRACTS = 'contracts';
    const DEFAULT_COUNT = 30;
    const TABLE_CDU = 'cdu';
    const CDU_ALIAS = 'mtender1';

    /**
     * Export records from contracts_updates to Contracts
     */
    public static function run()
    {
        $delay = (int) Yii::$app->params['sleep_error_interval'];

        while(true) {
            try {
                self::handle();
            } catch(PDOException $exception) {
                DB::dropInstance();
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
     * Export contracts_updates one iteration
     * @param int $count
     * @throws \yii\web\HttpException
     */
    public static function handle($count = self::DEFAULT_COUNT)
    {
        $processed = 0;
        $delay = (int) Yii::$app->params['sleep_delay_interval'];
        $elastic_indexing = (bool) Yii::$app->params['elastic_indexing'];
        $elastic = new ElasticComponent(
            Yii::$app->params['elastic_url'],
            Yii::$app->params['elastic_contracts_index'],
            Yii::$app->params['elastic_contracts_type']
        );

        if ($elastic_indexing) {
            $result = $elastic->checkMapping();
            if ($result['code'] != 200) {
                throw new HttpException(400, "Elastic mapping error. Http-code: " . $result['code']);
            }
        }

        $items = DB::fetchAll('SELECT * FROM ' . self::TABLE_CONTRACTS_UPDATES . ' ORDER BY updated_at LIMIT ?', [$count]);
        $cdu = DB::fetch('SELECT * FROM ' . self::TABLE_CDU . ' WHERE alias=?', [self::CDU_ALIAS]);
        $cdu_id = $cdu['id'] ?? null;

        if (empty($items)) {
            Yii::info("Nothing to update.", 'sync-info');
            Yii::info("Memory usage: " . memory_get_usage(), 'sync-info');
            Yii::info("...........Sleep...............", 'sync-info');
            gc_collect_cycles();
            sleep($delay);
        } else {
            foreach($items as $item) {
                try {
                    DB::beginTransaction();

                    self::handleDb($item, $cdu_id);

                    if ($elastic_indexing) {
                        $contractdata = json_decode($item['response'], 1);
                        // get procurementMethodType from tender
                        $tender = Tender::getTenderFromCdb($contractdata['data']['tender_id']);
                        $tenderData = json_decode($tender, 1);
                        $contractdata['data']['procurementMethodType'] = $tenderData['data']['procurementMethodType'] ?? '';
                        $elastic->indexContractPrz($contractdata['data'], self::CDU_ALIAS);
                    }

                    DB::execute('DELETE FROM ' . self::TABLE_CONTRACTS_UPDATES . ' WHERE "contract_id" = ?', [$item['contract_id']]);

                    $processed++;

                    DB::commit();
                } catch (\Exception $exception) {
                    DB::rollback();
                    throw new $exception($exception->getMessage());
                }
            }

            Yii::info("Processed {$processed} contracts.", 'sync-info');
        }
    }

    private static function handleDb($item, $cdu_id) {

        $count = DB::rowCount('SELECT * FROM ' . self::TABLE_CONTRACTS . ' WHERE contract_id = ?', [$item['contract_id']]);

        if ($count == 0) {
            DB::execute('INSERT INTO ' . self::TABLE_CONTRACTS . ' ("contract_id", "response", "cdu_id") VALUES (?, ?, ?)', [$item['contract_id'], $item['response'], $cdu_id]);
        }

        if ($count == 1) {
            DB::execute('UPDATE ' . self::TABLE_CONTRACTS . ' SET "response" = ? WHERE "contract_id" = ?', [$item['response'], $item['contract_id']]);
        }
    }
}