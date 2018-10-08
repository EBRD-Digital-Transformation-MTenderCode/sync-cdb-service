<?php
namespace console\models\plans_prz;

use Yii;
use PDOException;
use yii\web\HttpException;

/**
 * Class PlansHandler
 * @package console\models\plans_prz
 */
class PlansHandler
{
    /**
     * Updating plans
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
     * Updating plans one iteration
     * @throws HttpException
     */
    public static function handle()
    {
        $delay = (int) Yii::$app->params['sleep_delay_interval'];

        Yii::info("Check new plans...", 'sync-info');

        // getting a list of changed plans from our database
        $plansIdsArr = PlansList::getPlans($limit = 25);
        $count = count($plansIdsArr);

        if ($count) {
            Yii::info("Found " . $count . " modified plans. Starting update...", 'sync-info');

            // get information about plans from CBD and save in our database
            Plan::updateItems($plansIdsArr);
            PlansList::deleteRecords($plansIdsArr);

            unset($count, $plansIdsArr);
        } else {
            unset($count, $time, $plansIdsArr);

            Yii::info("Nothing to update.", 'sync-info');
            //Yii::info("Memory usage: " . memory_get_usage(), 'sync-info');
            Yii::info("...........Sleep...............", 'sync-info');

            gc_collect_cycles();
            sleep($delay);
        }
    }
}