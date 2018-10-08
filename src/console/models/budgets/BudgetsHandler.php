<?php
namespace console\models\budgets;

use Yii;
use PDOException;
use yii\web\HttpException;

/**
 * Class BudgetsHandler
 * @package console\models\budgets
 */
class BudgetsHandler
{
    /**
     * Updating budgets
     */
    public static function run() {
        $delay = (int) Yii::$app->params['sleep_error_interval'];

        while(true) {
            try {
                self::handle();
            } catch(PDOException $exception) {
                DB::dropInstance();
                Yii::error('DB ERROR: ' . str_replace("\n", " ", $exception->getMessage()), 'sync-info');
                //Yii::info("Memory usage: " . memory_get_usage(), 'sync-info');
                Yii::info("...........Sleep...............", 'sync-info');
                gc_collect_cycles();
                sleep($delay);
            } catch (HttpException $exception) {
                Yii::error('CURL ERROR[' . $exception->getCode() . ']: ' . $exception->getMessage(), 'sync-info');
                //Yii::info("Memory usage: " . memory_get_usage(), 'sync-info');
                Yii::info("...........Sleep...............", 'sync-info');
                gc_collect_cycles();
                sleep($delay);
            }
        }
    }

    /**
     * Updating budgets one iteration
     * @throws HttpException
     */
    public static function handle()
    {
        $delay = (int) Yii::$app->params['sleep_delay_interval'];

        Yii::info("Check new budgets...", 'sync-info');

        // getting a list of changed tenders from our database
        $tendersIdsArr = BudgetsList::getBudgets($limit = 25);
        $count = count($tendersIdsArr);

        if ($count) {
            Yii::info("Found " . $count . " modified budgets. Starting update...", 'sync-info');

            // get information about tenders from CBD and save in our database
            Budget::updateItems($tendersIdsArr);
            BudgetsList::deleteRecords($tendersIdsArr);

            unset($count, $tendersIdsArr);
        } else {
            unset($count, $time, $tendersIdsArr);

            Yii::info("Nothing to update.", 'sync-info');
            //Yii::info("Memory usage: " . memory_get_usage(), 'sync-info');
            Yii::info("...........Sleep...............", 'sync-info');

            gc_collect_cycles();
            sleep($delay);
        }
    }
}