<?php
namespace console\models\budgets;

use Yii;
use PDOException;
use yii\web\HttpException;

/**
 * Class BudgetsUpdates
 * @package console\models\budgets
 */
class BudgetsUpdates
{
    const TABLE_BUDGETS_UPDATES = 'budgets_updates';
    const TABLE_BUDGETS = 'budgets';
    const DEFAULT_COUNT = 30;

    /**
     * Export records from budgets_updates to budgets
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
     * Export budgets_updates one iteration
     * @param int $count
     * @throws HttpException
     */
    public static function handle($count = self::DEFAULT_COUNT)
    {
        $processed = 0;
        $delay = (int) Yii::$app->params['sleep_delay_interval'];
        $elastic_indexing = (bool) Yii::$app->params['elastic_indexing'];
        $elastic = new Elastic();

        if ($elastic_indexing) {
            $result = $elastic->checkMapping();
            if ($result['code'] != 200) {
                Yii::error("Elastic mapping error. Http-code: " . $result['code'], 'sync-info');
                exit(0);
            }
        }

        $items = DB::fetchAll('SELECT * FROM ' . self::TABLE_BUDGETS_UPDATES . ' ORDER BY updated_at LIMIT ?', [$count]);

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

                    self::handleDb($item);

                    if ($elastic_indexing) {
                        $elastic->indexBudget($item);
                    }

                    DB::execute('DELETE FROM ' . self::TABLE_BUDGETS_UPDATES . ' WHERE "ocid" = ?', [$item['ocid']]);

                    $processed++;

                    DB::commit();
                } catch (\Exception $exception) {
                    DB::rollback();
                }
            }

            Yii::info("Processed {$processed} budgets.", 'sync-info');
            Yii::info("Memory usage: " . memory_get_usage(), 'sync-info');
            sleep(1);
        }
    }

    private static function handleDb($item) {
        $count = DB::rowCount('SELECT * FROM ' . self::TABLE_BUDGETS . ' WHERE ocid = ?', [$item['ocid']]);

        if ($count == 0) {
            DB::execute('INSERT INTO ' . self::TABLE_BUDGETS . ' ("ocid", "response") VALUES (?, ?)', [$item['ocid'], $item['response']]);
        }

        if ($count == 1) {
            DB::execute('UPDATE ' . self::TABLE_BUDGETS . ' SET "response" = ? WHERE "ocid" = ?', [$item['response'], $item['ocid']]);
        }
    }

}