<?php
namespace console\models\complaints;

use Yii;
use PDOException;
use console\models\elastic\ElasticComponent;
use console\models\Curl;
use yii\web\HttpException;

/**
 * Class DecisionsHandler
 * @package console\models\complaints
 */
class DecisionsHandler
{
    public static function run() {
        $delay = (int) Yii::$app->params['sleep_error_interval'];
        $url = '';

        while(true) {
            try {
                $url = self::handle($url);
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
     * @param $url
     * @return string
     * @throws HttpException
     */
    public static function handle($url)
    {
        $delay = (int) Yii::$app->params['sleep_delay_interval'];
        $adminUser = Yii::$app->params['admin_user'];
        $adminPassword = Yii::$app->params['admin_password'];
        $elastic_indexing = (bool) Yii::$app->params['elastic_indexing'];

        $elastic = new ElasticComponent(
            Yii::$app->params['elastic_url'],
            Yii::$app->params['elastic_decisions_index'],
            Yii::$app->params['elastic_decisions_type']
        );

        if ($elastic_indexing) {
            $result = $elastic->checkMapping();
            if ($result['code'] != 200) {
                throw new HttpException(400, "Elastic mapping error. Http-code: " . $result['code']);
            }
        }

        if (empty($url)) {
            $url = Decisions::getOffsetUrl(UpdateTime::getOffset(UpdateTime::DECISIONS));
        }

        $result = Curl::sendRequest($url, "GET", "", ['USERPWD' => $adminUser . ':' . $adminPassword]);

        if ($result['code'] != 200) {
            throw new HttpException($result['code'], "Query error to CDB");
        }

        $result = json_decode($result['body'], true);
        $data = ($result['response']['docs']) ?? null;


        if (!empty($data)) {
            foreach ($data as $item) {
                //tenderId filter
                if (isset($item['NrProcedurii'])
                    && ((strlen($item['NrProcedurii']) == 28
                            && substr($item['NrProcedurii'], 0, 4) == 'ocds')
                        || (strlen($item['NrProcedurii']) == 22
                            && substr($item['NrProcedurii'], 0, 2) == 'MD'))) {
                    $id = $item['id'];
                    $tenderId = $item['NrProcedurii'] ?? '';
                    $item['registrationDate'] = substr($item['DataDecizie'], 0, 4) . '-'
                        . substr($item['DataDecizie'], 4, 2) . '-'
                        . substr($item['DataDecizie'], 6, 2) . 'T'
                        . substr($item['DataDecizie'], 8, 2) . ':'
                        . substr($item['DataDecizie'], 10, 2) . ':'
                        . substr($item['DataDecizie'], 12, 2) . 'Z';
                    $response = json_encode($item, JSON_UNESCAPED_UNICODE + JSON_UNESCAPED_SLASHES);
                    Decision::handleDb($id, $tenderId, $response);

                    if ($elastic_indexing) {
                        $elastic->indexDecision($item);
                    }
                }

                $offset = $item['timestamp'];
            }

            $countDecisions = count($data);

            Yii::info("Found {$countDecisions} decisions. Offset: {$offset}", 'sync-info');

            // update synchronization time
            $url = Decisions::getOffsetUrl($offset);

            // update synchronization time
            UpdateTime::updateOffset(UpdateTime::DECISIONS, $offset);

            if ($countDecisions < 30) {
                Yii::info("...........Sleep...............", 'sync-info');

                gc_collect_cycles();
                sleep($delay);
            }
        } else {
            Yii::info("Nothing to update.", 'sync-info');
            Yii::info("Memory usage: " . memory_get_usage(), 'sync-info');
            Yii::info("...........Sleep...............", 'sync-info');

            gc_collect_cycles();
            sleep($delay);

            // getting the time of the last synchronization
            $url = Decisions::getOffsetUrl(UpdateTime::getOffset(UpdateTime::DECISIONS));
        }

        unset($item, $data, $result, $delay);

        return $url;
    }
}