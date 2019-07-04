<?php
namespace console\models\complaints;

use Yii;
use PDOException;
use console\models\elastic\ElasticComponent;
use console\models\Curl;
use yii\web\HttpException;


/**
 * Class ComplaintsHandler
 * @package console\models\complaints
 */
class ComplaintsHandler
{
    public static function run() {
        $delay = (int) Yii::$app->params['sleep_error_interval'];
        $offset = '';

        while(true) {
            try {
                $offset = self::handle($offset);
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
     * @param $offset
     * @return string
     * @throws HttpException
     */
    public static function handle($offset)
    {
        $delay = (int) Yii::$app->params['sleep_delay_interval'];
        $adminUser = Yii::$app->params['admin_user'];
        $adminPassword = Yii::$app->params['admin_password'];
        $elastic_indexing = (bool) Yii::$app->params['elastic_indexing'];
        $newOffset = '';

        $elasticProceeding = new ElasticComponent(
            Yii::$app->params['elastic_url'],
            Yii::$app->params['elastic_proceedings_index'],
            Yii::$app->params['elastic_proceedings_type']
        );

        $elasticComplaint = new ElasticComponent(
            Yii::$app->params['elastic_url'],
            Yii::$app->params['elastic_complaints_index'],
            Yii::$app->params['elastic_complaints_type']
        );

        if ($elastic_indexing) {
            $result = $elasticProceeding->checkMapping();

            if ($result['code'] != 200) {
                throw new HttpException(400, "Elastic mapping error. Http-code: " . $result['code']);
            }

            $result = $elasticComplaint->checkMapping();

            if ($result['code'] != 200) {
                throw new HttpException(400, "Elastic mapping error. Http-code: " . $result['code']);
            }
        }

        if (empty($offset)) {
            $offset = UpdateTime::getOffset(UpdateTime::COMPLAINTS);
        }

        $url = Complaints::getOffsetUrl($offset);
        $result = Curl::sendRequest($url, "GET", "", ['USERPWD' => $adminUser . ':' . $adminPassword]);

        if ($result['code'] != 200) {
            throw new HttpException($result['code'], "Query error to CDB");
        }

        $result = json_decode($result['body'], true);
        $data = ($result['response']['docs']) ?? null;
        $filteredCount = 0;
        $updatedCount = 0;

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

                    if ($elastic_indexing) {
                        $preparedItem = $item;
                        unset($preparedItem['_version_'], $preparedItem['timestamp']);
                        $hash = hash('md5', serialize($preparedItem));
                        $response = json_encode($item, JSON_UNESCAPED_UNICODE + JSON_UNESCAPED_SLASHES);

                        if (Complaint::handleDb($id, $tenderId, $hash, $response)) {
                            $item['modificationDate'] = (new \DateTime('now', new \DateTimeZone("UTC")))->format('Y-m-d\TH:i:s.v') . 'Z';
                            $elasticComplaint->indexComplaint($item);
                            $elasticProceeding->indexProceeding(['ocid' => $item['NrProcedurii'], 'date' => $item['modificationDate']]);
                            $updatedCount++;
                            usleep(1000);
                        }

                        $filteredCount++;
                    }
                }

                $newOffset = $item['timestamp'];
            }

            $receivedCount = count($data);
            Yii::info("Received {$receivedCount}, filtered {$filteredCount}, updated {$updatedCount} complaints. Offset: {$newOffset}", 'sync-info');
        }

        if ($offset && $offset == $newOffset) {
            Yii::info("...........Sleep...............", 'sync-info');

            gc_collect_cycles();
            sleep($delay);
        } else {
            // update synchronization time
            UpdateTime::updateOffset(UpdateTime::COMPLAINTS, $newOffset);
        }

        unset($item, $preparedItem, $data, $result, $delay);

        return $newOffset;
    }
}