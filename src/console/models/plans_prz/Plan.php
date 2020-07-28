<?php
namespace console\models\plans_prz;

use console\models\Curl;
use Yii;
use yii\web\HttpException;

/**
 * Class Plan
 * @package console\models\plans_prz
 */
class Plan
{
    CONST TABLE_NAME = "plans_prz_updates";

    /**
     * Search of the plan in our database
     * table plans_updates
     * @param $plan_id
     * @return array
     */
    public static function findById($plan_id) {
        return DB::fetch("SELECT * FROM " . self::TABLE_NAME . " WHERE plan_id = ?", [$plan_id]);
    }

    /**
     * Method for adding to our database a record of a modified plan
     * @param $plan_id
     * @param $planJson
     */
    public static function add($plan_id, $planJson) {
        $params = [
            'plan_id' => $plan_id,
            'response' => $planJson,
            'updated_at' => time(),
        ];
        DB::execute("INSERT INTO " . self::TABLE_NAME . " (plan_id, response, updated_at) VALUES (:plan_id, :response, :updated_at)", $params);
    }

    /**
     * Method for updating in our database a record of a modified plan
     * @param $id
     * @param $planJson
     */
    public static function update($id, $planJson)
    {
        $params = [
            'response' => $planJson,
            'updated_at' => time(),
            'id' => $id
        ];
        DB::execute("UPDATE " . self::TABLE_NAME . " SET response = :response, updated_at = :updated_at WHERE id = :id", $params);
    }

    /**
     * Adding and updating the array of plans to our database
     * @param array $plansIdsArr
     * @throws HttpException
     */
    public static function updateItems(array $plansIdsArr) {
        foreach ($plansIdsArr as &$item) {
            $plan_id = $item['plan_id'];
            $plansRow = self::findById($plan_id);
            $planJson = self::getPlanFromCdb($plan_id);

            if (!empty($planJson)) {
                if (empty($plansRow)) {
                    self::add($item['plan_id'], $planJson);
                    Yii::info("Added new plan. ID:{$plan_id}", 'sync-info');
                } else {
                    self::update($plan_id, $planJson);
                    Yii::info("Updated plan. ID:{$plan_id}", 'sync-info');
                }
            }

            unset($item);
        }
    }

    /**
     * Request to the CDB for plan
     * @param $plan_id
     * @return mixed
     * @throws HttpException
     */
    private static function getPlanFromCdb($plan_id) {
        $url = Yii::$app->params['plans_prz_url'] . "/" . $plan_id;

        $result = Curl::sendRequest($url, "GET");

        if ($result['code'] != 200) {
            throw new HttpException($result['code'], "Query error to CDB. Url: " . $url);
        }

        $planJson = $result['body'];

        return $planJson;
    }
}