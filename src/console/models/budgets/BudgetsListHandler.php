<?php
namespace console\models\budgets;

use console\models\Curl;
use Yii;
use PDOException;
use yii\web\HttpException;

/**
 * Class BudgetsListHandler
 * @package console\models\budgets;
 */
class BudgetsListHandler
{
    /**
     * Update budgets
     */
    public static function run()
    {
        $delay = (int) Yii::$app->params['sleep_error_interval'];
        $url = '';

        while(true) {
            try {
                $url = self::handle($url);
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
     * Update budgets one iteration
     * @param string $url
     * @return string
     * @throws HttpException
     */
    public static function handle($url = '') {
        $delay = (int) Yii::$app->params['sleep_delay_interval'];

        if (empty($url)) {
            $url = BudgetsList::getOffsetUrl(UpdateTime::getOffset());
        }

        // send a request to the CDB to get a list of updated tenders
        $result = Curl::sendRequest($url, "GET");
        if ($result['code'] != 200) {
            throw new HttpException($result['code'], "Query error to CDB");
        }

        $result = json_decode($result['body'], true);

        $data = ($result['data']) ?? null;
        if (!empty($data)) {

            // update the list in our database
            BudgetsList::update($data);

            $countTenders = count($data);

            $offset = $result['offset'];

            Yii::info("Found {$countTenders} budgets. Offset: {$offset}", 'sync-info');
            $url = BudgetsList::getOffsetUrl($offset);
            // update synchronization time
            UpdateTime::updateOffset($offset);
        } else {

            Yii::info("Nothing to update.", 'sync-info');
            //Yii::info("Memory usage: " . memory_get_usage(), 'sync-info');
            Yii::info("...........Sleep...............", 'sync-info');

            gc_collect_cycles();
            sleep($delay);

            // getting the time of the last synchronization
            $url = BudgetsList::getOffsetUrl(UpdateTime::getOffset());
        }

        unset($offset, $data, $result, $delay);

        return $url;
    }
}