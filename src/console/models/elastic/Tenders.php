<?php
namespace console\models\elastic;

use Yii;
use yii\db\Exception;
use yii\helpers\ArrayHelper;
use console\models\tenders\Tender;
use PDOException;

/**
 * Class Tenders
 * @package console\models\elastic
 */
class Tenders
{
    const TYPE_PROZORRO = 'mtender1';

    /**
     * @return mixed|\yii\db\Connection
     */
    public static function getDb()
    {
        return Yii::$app->db_tenders;
    }

    /**
     * indexing of tenders to elastic
     * @throws \yii\web\HttpException
     */
    public function reindexItemsToElastic()
    {
        Yii::info("Indexing tenders", 'console-msg');
        $limit = 25;
        $offset = 0;
        $url = Yii::$app->params['elastic_url'];
        $index = Yii::$app->params['elastic_tenders_index'];
        $type = Yii::$app->params['elastic_tenders_type'];
        $elastic = new ElasticComponent($url, $index, $type);
        while (true) {
            try {
                // block the update of selected records in the database
                $transaction = Yii::$app->db_tenders->beginTransaction();
                $tenders = Yii::$app->db_tenders->createCommand("SELECT * FROM tenders FOR UPDATE LIMIT {$limit} OFFSET {$offset}")->queryAll();
                $cdu = ArrayHelper::map(Yii::$app->db_tenders->createCommand("SELECT * FROM cdu")->queryAll(), 'id', 'alias');
                $countTenders = count($tenders);
                if (!$countTenders) {
                    break;
                }
                $offset += $limit;
                foreach ($tenders as $tender) {
                    $cduV = $cdu[$tender['cdu_id']] ?? '';
                    if ($cduV != self::TYPE_PROZORRO) {
                        $decodedItem = Tender::decode($tender, '');
                        $elastic->indexTender($decodedItem[0] ?? false, $cduV);
                    } else {
                        $elastic->indexTenderPrz($tender, $cduV);
                    }
                }
                $transaction->commit();
            } catch(PDOException $exception) {
                Yii::error("PDOException. " . $exception->getMessage(), 'console-msg');
                exit(0);
            } catch(Exception $exception) {
                Yii::error("DB exception. " . $exception->getMessage(), 'console-msg');
                exit(0);
            }
            Yii::info("Updated {$countTenders} tenders", 'console-msg');
            // delay 0.3 sec
            usleep(300000);
        }
    }

    /**
     * add one item to index queue
     * @param $id
     * @return int
     * @throws Exception
     */
    public function reindexOne($id)
    {
        $query = "INSERT INTO tenders_changed_list (tender_id) VALUES (:id) ON CONFLICT (tender_id) DO NOTHING";

        return self::getDb()->createCommand($query, [':id' => $id])->execute();
    }

    /**
     * add one prozorro item to index queue
     * @param $id
     * @return int
     * @throws Exception
     */
    public function reindexOnePrz($id)
    {
        $query = "INSERT INTO tenders_prz_changed_list (tender_id) VALUES (:id) ON CONFLICT (tender_id) DO NOTHING";

        return self::getDb()->createCommand($query, [':id' => $id])->execute();
    }

    /**
     * clear items from DB
     * @throws Exception
     */
    public function truncate()
    {
        self::getDb()->createCommand('TRUNCATE tenders')->execute();
        self::getDb()->createCommand('TRUNCATE tenders_changed_list')->execute();
        self::getDb()->createCommand('TRUNCATE tenders_updates')->execute();
        self::getDb()->createCommand('TRUNCATE tenders_prz_changed_list')->execute();
        self::getDb()->createCommand('TRUNCATE tenders_prz_updates')->execute();
        self::getDb()->createCommand('UPDATE last_update_time SET updated_at = NULL, offset_time = NULL')->execute();
    }
}