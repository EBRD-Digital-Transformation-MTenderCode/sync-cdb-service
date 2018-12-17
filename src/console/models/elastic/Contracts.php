<?php
namespace console\models\elastic;

use Yii;
use yii\db\Exception;
use yii\helpers\ArrayHelper;
use console\models\tenders\Tender;
use PDOException;

/**
 * Class Contracts
 * @package console\models\elastic
 */
class Contracts
{
    const TYPE_PROZORRO = 'mtender1';

    /**
     * @return mixed|\yii\db\Connection
     */
    public static function getDb()
    {
        return Yii::$app->db_contracts;
    }

    /**
     * reindexing of contracts to elastic
     *
     * @throws \yii\web\ForbiddenHttpException
     * @throws \yii\web\HttpException
     */
    public function reindexItemsToElastic()
    {
        Yii::info("Indexing contracts", 'console-msg');
        $limit = 25;
        $offset = 0;
        $url = Yii::$app->params['elastic_url'];
        $index = Yii::$app->params['elastic_contracts_index'];
        $type = Yii::$app->params['elastic_contracts_type'];
        $elastic = new ElasticComponent($url, $index, $type);
        while (true) {
            try {
                // block the update of selected records in the database
                $db = self::getDb();
                $transaction = $db->beginTransaction();
                $items = $db->createCommand("SELECT * FROM contracts FOR UPDATE LIMIT {$limit} OFFSET {$offset}")->queryAll();
                $cdu = ArrayHelper::map($db->createCommand("SELECT * FROM cdu")->queryAll(), 'id', 'alias');

                $countItems = count($items);
                if (!$countItems) {
                    break;
                }
                $offset += $limit;
                foreach ($items as $item) {
                    $cduV = $cdu[$item['cdu_id']] ?? '';
                    if ($cduV != self::TYPE_PROZORRO) {
                        $decodedItems = Tender::decode($item, Tender::MARK_CONTRACT);
                        foreach ($decodedItems as $decodedItem) {
                            $elastic->indexContract($decodedItem, $cduV);
                        }
                    } else {
                        $elastic->indexContractPrz($item, $cduV);
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
            Yii::info("Updated {$countItems} contracts", 'console-msg');
            // delay 0.3 sec
            usleep(300000);
        }
    }
}