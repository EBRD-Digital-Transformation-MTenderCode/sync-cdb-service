<?php
namespace console\models\contracts_prz;

use Yii;
use PDOException;
use yii\web\HttpException;

/**
 * Class ContractsHandler
 * @package console\models\contracts_prz
 */
class ContractsHandler
{
    /**
     * Updating contracts
     */
    public static function run() {
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
                Yii::error('CURL ERROR[' . $exception->getCode() . ']: ' . $exception->getMessage(), 'sync-info');
                Yii::info("Memory usage: " . memory_get_usage(), 'sync-info');
                Yii::info("...........Sleep...............", 'sync-info');
                gc_collect_cycles();
                sleep($delay);
            }
        }
    }

    /**
     * Updating contracts one iteration
     * @throws HttpException
     */
    public static function handle()
    {
        $delay = (int) Yii::$app->params['sleep_delay_interval'];

        Yii::info("Check new contracts...", 'sync-info');

        // getting a list of changed Contracts from our database
        $contractsIdsArr = ContractsList::getContracts($limit = 25);
        $count = count($contractsIdsArr);

        if ($count) {
            Yii::info("Found " . $count . " modified contracts. Starting update...", 'sync-info');

            // get information about plans from CBD and save in our database
            Contract::updateItems($contractsIdsArr);
            ContractsList::deleteRecords($contractsIdsArr);

            unset($count, $contractsIdsArr);
        } else {
            unset($count, $time, $contractsIdsArr);

            Yii::info("Nothing to update.", 'sync-info');
            Yii::info("Memory usage: " . memory_get_usage(), 'sync-info');
            Yii::info("...........Sleep...............", 'sync-info');

            gc_collect_cycles();
            sleep($delay);
        }
    }
}