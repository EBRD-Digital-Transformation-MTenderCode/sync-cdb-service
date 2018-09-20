<?php
namespace console\models\plans_prz;

use ustudio\service_mandatory\components\elastic\ElasticComponent;
use Yii;
use PDOException;
use yii\web\HttpException;

/**
 * Class PlansUpdates
 * @package console\models\Plans
 */
class PlansUpdates
{
    const TABLE_PLANS_UPDATES = 'plans_prz_updates';
    const TABLE_PLANS = 'plans';
    const DEFAULT_COUNT = 30;
    const TABLE_CDU = 'cdu';
    const CDU_ALIAS = 'mtender1';

    /**
     * Export records from plans_updates to plans
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
     * Export plans_updates one iteration
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
            Yii::$app->params['elastic_plans_index'],
            Yii::$app->params['elastic_plans_type']
        );

        if ($elastic_indexing) {
            $result = $elastic->checkMapping();
            if ($result['code'] != 200) {
                throw new HttpException(400, "Elastic mapping error. Http-code: " . $result['code']);
            }
        }

        $items = DB::fetchAll('SELECT * FROM ' . self::TABLE_PLANS_UPDATES . ' ORDER BY updated_at LIMIT ?', [$count]);
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
                        $elastic->indexPlanPrz($item, self::CDU_ALIAS);
                    }

                    DB::execute('DELETE FROM ' . self::TABLE_PLANS_UPDATES . ' WHERE "plan_id" = ?', [$item['plan_id']]);

                    $processed++;

                    DB::commit();
                } catch (\Exception $exception) {
                    DB::rollback();
                    throw new $exception($exception->getMessage());
                }
            }

            Yii::info("Processed {$processed} plans.", 'sync-info');
        }
    }

    private static function handleDb($item, $cdu_id) {

        $count = DB::rowCount('SELECT * FROM ' . self::TABLE_PLANS . ' WHERE plan_id = ?', [$item['plan_id']]);

        if ($count == 0) {
            DB::execute('INSERT INTO ' . self::TABLE_PLANS . ' ("plan_id", "response", "cdu_id") VALUES (?, ?, ?)', [$item['plan_id'], $item['response'], $cdu_id]);
        }

        if ($count == 1) {
            DB::execute('UPDATE ' . self::TABLE_PLANS . ' SET "response" = ? WHERE "plan_id" = ?', [$item['response'], $item['plan_id']]);
        }
    }
}