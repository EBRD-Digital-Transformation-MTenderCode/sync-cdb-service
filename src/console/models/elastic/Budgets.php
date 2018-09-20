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
}