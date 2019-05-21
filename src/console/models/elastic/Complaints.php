<?php
namespace console\models\elastic;

use Yii;
use yii\db\Exception;
use PDOException;

/**
 * Class Complaints
 * @package console\models\elastic
 */
class Complaints
{
    /**
     * @return mixed|\yii\db\Connection
     */
    public static function getDb()
    {
        return Yii::$app->db_complaints;
    }

    /**
     * reindexing complaints to elastic
     *
     * @throws \yii\web\ForbiddenHttpException
     * @throws \yii\web\HttpException
     */
    public function reindexItemsToElastic()
    {
        Yii::info("Indexing complaints", 'console-msg');
        $limit = 25;
        $offset = 0;
        $url = Yii::$app->params['elastic_url'];
        $index = Yii::$app->params['elastic_complaints_index'];
        $type = Yii::$app->params['elastic_complaints_type'];
        $elastic = new ElasticComponent($url, $index, $type);
        while (true) {
            try {
                // block the update of selected records in the database
                $db = self::getDb();
                $transaction = $db->beginTransaction();
                $items = $db->createCommand("SELECT * FROM complaints FOR UPDATE LIMIT {$limit} OFFSET {$offset}")->queryAll();
                $countItems = count($items);

                if (!$countItems) {
                    break;
                }

                $offset += $limit;

                foreach ($items as $item) {
                    $elastic->indexComplaint(json_decode($item['response'], true));
                }

                $transaction->commit();
            } catch(PDOException $exception) {
                Yii::error("PDOException. " . $exception->getMessage(), 'console-msg');
                exit(0);
            } catch(Exception $exception) {
                Yii::error("DB exception. " . $exception->getMessage(), 'console-msg');
                exit(0);
            }
            Yii::info("Updated {$countItems} complaints", 'console-msg');
            // delay 0.3 sec
            usleep(300000);
        }
    }

    /**
     * clear items from DB
     * @throws Exception
     */
    public function truncate()
    {
        self::getDb()->createCommand('TRUNCATE complaints')->execute();
        self::getDb()->createCommand("UPDATE last_update_time SET updated_at = NULL, offset_time = NULL WHERE id = 'complaints'")->execute();
    }
}