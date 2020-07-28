<?php
namespace console\models\elastic;

use Yii;
use yii\db\Exception;
use PDOException;

/**
 * Class Budgets
 * @package console\models\elastic
 */
class Budgets
{
    /**
     * @return mixed|\yii\db\Connection
     */
    public static function getDb()
    {
        return Yii::$app->db_budgets;
    }

    /**
     * indexing of budgets to elastic
     * @return bool
     * @throws \yii\web\HttpException
     */
    public function reindexItemsToElastic()
    {
        Yii::info("Indexing budgets", 'console-msg');
        $limit = 25;
        $offset = 0;
        $url = Yii::$app->params['elastic_url'];
        $index = Yii::$app->params['elastic_budgets_index'];
        $type = Yii::$app->params['elastic_budgets_type'];
        $elastic = new ElasticComponent($url, $index, $type);
        while (true) {
            try {
                // block the update of selected records in the database
                $transaction = Yii::$app->db_budgets->beginTransaction();
                $budgets = Yii::$app->db_budgets->createCommand("SELECT * FROM budgets FOR UPDATE LIMIT {$limit} OFFSET {$offset}")->queryAll();
                $countBudgets = count($budgets);
                if (!$countBudgets) {
                    break;
                }
                $offset += $limit;
                foreach ($budgets as $budget) {
                    $elastic->indexBudget($budget);
                }
                $transaction->commit();
            } catch(PDOException $exception) {
                Yii::error("PDOException. " . $exception->getMessage(), 'console-msg');
                exit(0);
            } catch(Exception $exception) {
                Yii::error("DB exception. " . $exception->getMessage(), 'console-msg');
                exit(0);
            }
            Yii::info("Updated {$countBudgets} budgets", 'console-msg');
            // delay 0.3 sec
            usleep(300000);
        }
        return true;
    }

    /**
     * add one item to index queue
     * @param $id
     * @return int
     * @throws Exception
     */
    public function reindexOne($id)
    {
        $query = "INSERT INTO budgets_changed_list (ocid) VALUES (:id) ON CONFLICT (ocid) DO NOTHING";

        return self::getDb()->createCommand($query, [':id' => $id])->execute();
    }

    /**
     * clear items from DB
     * @throws Exception
     */
    public function truncate()
    {
        self::getDb()->createCommand('TRUNCATE budgets')->execute();
        self::getDb()->createCommand('TRUNCATE budgets_changed_list')->execute();
        self::getDb()->createCommand('TRUNCATE budgets_updates')->execute();
        self::getDb()->createCommand('UPDATE last_update_time SET updated_at = NULL, offset_time = NULL')->execute();
    }
}