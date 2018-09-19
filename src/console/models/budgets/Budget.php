<?php
namespace console\models\budgets;

use console\models\Curl;
use Yii;
use yii\web\HttpException;

/**
 * Class Budget
 * @package console\models\budgets
 */
class Budget
{
    CONST TABLE_NAME = "budgets_updates";

    /**
     * Search of the budget in our database
     * table budgets_updates
     * @param $ocid
     * @return array
     */
    public static function findByOCID($ocid) {
        return DB::fetch("SELECT * FROM " . self::TABLE_NAME . " WHERE ocid = ?", [$ocid]);
    }

    /**
     * Method for adding to our database a record of a modified budget
     * @param $ocid
     * @param $budgetJson
     */
    public static function add($ocid, $budgetJson) {
        $params = [
            'ocid' => $ocid,
            'response' => $budgetJson,
            'updated_at' => time(),
        ];
        DB::execute("INSERT INTO " . self::TABLE_NAME . " (ocid, response, updated_at) 
            VALUES (:ocid, :response, :updated_at)", $params);
    }

    /**
     * Method for updating in our database a record of a modified tender
     * @param $id
     * @param $budgetJson
     */
    public static function update($id, $budgetJson)
    {
        $params = [
            'response' => $budgetJson,
            'updated_at' => time(),
            'id' => $id
        ];
        DB::execute("UPDATE " . self::TABLE_NAME . " SET response = :response, updated_at = :updated_at WHERE id = :id", $params);
    }

    /**
     * Adding and updating the array of tenders to our database
     * @param array $budgetsIdsArr
     * @throws HttpException
     */
    public static function updateItems(array $budgetsIdsArr) {
        foreach ($budgetsIdsArr as &$item) {
            $ocid = $item['ocid'];
            $budgetsRow = self::findByOCID($ocid);

            $budgetJson = self::getBudgetFromCdb($ocid);

            if (!empty($budgetJson)) {
                if (empty($budgetsRow)) {
                    self::add($item['ocid'], $budgetJson);
                    Yii::info("Added new budget. ID:{$ocid}", 'sync-info');
                } else {
                    self::update($budgetsRow['id'], $budgetJson);
                    Yii::info("Updated budget. ID:{$ocid}", 'sync-info');
                }
            }

            unset($item);
        }
    }

    /**
     * Request to the CDB for budget
     * @param $ocid
     * @return mixed
     * @throws HttpException
     */
    private static function getBudgetFromCdb($ocid) {
        $url = Yii::$app->params['budgets_url'] . "/" . $ocid;
        $result = Curl::sendRequest($url, "GET");

        if ($result['code'] != 200) {
            throw new HttpException($result['code'], "Query error to CDB");
        }

        $tenderJson = $result['body'];

        return $tenderJson;
    }
}