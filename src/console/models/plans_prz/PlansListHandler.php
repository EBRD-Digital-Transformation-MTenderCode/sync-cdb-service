<?php
namespace console\models\plans_prz;

use console\models\Curl;
use Yii;
use PDOException;
use yii\web\HttpException;

/**
 * Class PlansListHandler
 * @package console\models\plans_prz
 */
class PlansListHandler
{
    /**
     * Update plans
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
     * Update plans one iteration
     * @param string $url
     * @return string
     * @throws HttpException
     */
    public static function handle($url = '') {
        $delay = (int) Yii::$app->params['sleep_delay_interval'];

        if (empty($url)) {
            $url = PlansList::getOffsetUrl(UpdateTime::getOffset());
        }

        // send a request to the CDB to get a list of updated plans
        $result = Curl::sendRequest($url, "GET");
        if ($result['code'] != 200) {
            throw new HttpException($result['code'], "Query error to CDB");
        }

        $result = json_decode($result['body'], true);

        $data = ($result['data']) ?? null;
        if (!empty($data)) {

            // update the list in our database
            PlansList::update($data);

            $countPlans = count($data);

            $offset = $result['next_page']['offset'];

            Yii::info("Found {$countPlans} plans. Offset: {$offset}", 'sync-info');
            $url = PlansList::getOffsetUrl($offset);
        } else {
            $offset = $result['next_page']['offset'];
            // update synchronization time
            UpdateTime::updateOffset($offset);

            Yii::info("Nothing to update.", 'sync-info');
            //Yii::info("Memory usage: " . memory_get_usage(), 'sync-info');
            Yii::info("...........Sleep...............", 'sync-info');

            gc_collect_cycles();
            sleep($delay);

            // getting the time of the last synchronization
            $url = PlansList::getOffsetUrl(UpdateTime::getOffset());
        }

        unset($offset, $data, $result, $delay);

        return $url;
    }
}