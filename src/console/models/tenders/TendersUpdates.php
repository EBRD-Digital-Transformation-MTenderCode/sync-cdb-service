<?php
namespace console\models\tenders;

use Yii;
use yii\web\HttpException;
use console\models\elastic\ElasticComponent;
use PDOException;

/**
 * Class TendersUpdates
 * @package console\models\tenders
 */
class TendersUpdates
{
    const TABLE_TENDERS_UPDATES = 'tenders_updates';
    const TABLE_TENDERS = 'tenders';
    const TABLE_CDU = 'cdu';
    const DEFAULT_COUNT = 30;
    const CDU_ALIAS = 'mtender2';

    /**
     * Export records from tenders_updates to tenders
     * @throws \ustudio\service_mandatory\components\elastic\ForbiddenHttpException
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
     * Export tenders_updates one iteration
     * @param int $count
     * @throws HttpException
     * @throws \ustudio\service_mandatory\components\elastic\ForbiddenHttpException
     */
    public static function handle($count = self::DEFAULT_COUNT)
    {
        $processed = 0;
        $delay = (int) Yii::$app->params['sleep_delay_interval'];
        $elastic_indexing = (bool) Yii::$app->params['elastic_indexing'];
        $elastic = new ElasticComponent(
            Yii::$app->params['elastic_url'],
            Yii::$app->params['elastic_tenders_index'],
            Yii::$app->params['elastic_tenders_type']
        );

        if ($elastic_indexing) {
            $result = $elastic->checkMapping();
            if ($result['code'] != 200) {
                throw new HttpException(400, "Elastic mapping error. Http-code: " . $result['code']);
            }
        }

        $items = DB::fetchAll('SELECT * FROM ' . self::TABLE_TENDERS_UPDATES . ' ORDER BY updated_at LIMIT ?', [$count]);
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

                    $decodedItem = Tender::decode($item);

                    switch ($decodedItem['type']) {
                        case Tender::MARK_TENDER:
                            self::handleDb($decodedItem, $cdu_id);

                            if ($elastic_indexing) {
                                $elastic->indexTender($decodedItem, self::CDU_ALIAS);
                            }

                            break;
                        case Tender::MARK_PLAN:
                        case Tender::MARK_CONTRACT:
                        default:
                            break;
                    }

                    DB::execute('DELETE FROM ' . self::TABLE_TENDERS_UPDATES . ' WHERE "tender_id" = ?', [$item['tender_id']]);

                    $processed++;

                    DB::commit();
                } catch (\Exception $exception) {
                    DB::rollback();
                    throw new $exception($exception->getMessage());
                }
            }

            Yii::info("Processed {$processed} tenders.", 'sync-info');
        }
    }

    /**
     * Update tender data in db
     * @param $item
     * @param $cdu_id
     */
    private static function handleDb($item, $cdu_id) {
        $count = DB::rowCount('SELECT * FROM ' . self::TABLE_TENDERS . ' WHERE tender_id = ?', [$item['tender_id']]);

        if ($count == 0) {
            DB::execute('INSERT INTO ' . self::TABLE_TENDERS . ' ("tender_id", "response", "release_package", "cdu_id") VALUES (?, ?, ?, ?)', [$item['tender_id'], $item['response'], $item['release_package'], $cdu_id]);
        }

        if ($count == 1) {
            DB::execute('UPDATE ' . self::TABLE_TENDERS . ' SET "response" = ?, "release_package" = ? WHERE "tender_id" = ?', [$item['response'], $item['release_package'], $item['tender_id']]);
        }
    }
}