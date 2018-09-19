<?php
namespace console\models\plans_prz;

use Yii;

/**
 * Class PlansList
 * class is used to work with the list of updated plans
 *
 * @package console\models\plans_prz
 */
class PlansList
{
    CONST TABLE_NAME = "plans_prz_changed_list";

    ///////  list-getter

    /**
     * Get plans list url with offset
     * @param string $offset
     * @return string $url
     */
    public static function getOffsetUrl($offset) {
        return Yii::$app->params['plans_prz_url'] . "?offset=" . urlencode($offset);
    }

    /**
     * Insert new changed list records
     * @param array $arrIds
     */
    public static function update(array $arrIds) {
        if (count($arrIds) > 0) {
            $args = array_fill(0, count($arrIds[0]), '?');

            $params = [];
            $values = [];
            foreach($arrIds as $row)
            {
                $values[] = "(".implode(',', $args).")";
                foreach($row as $value)
                {
                    $params[] = $value;
                }
            }
            DB::execute("INSERT INTO " . self::TABLE_NAME . " (plan_id, date_modified) VALUES ".implode(',', $values) . "  ON CONFLICT (plan_id) DO NOTHING", $params);
        }
    }

    ///////  updates-getter
    /**
     * Getting a list of plans
     * @param int $limit
     * @return array
     */
    public static function getPlans(int $limit = 25) {
        return DB::fetchAll("SELECT * FROM " . self::TABLE_NAME . " LIMIT ?", [$limit]);
    }

    /**
     * Delete record
     * @param $plan_id
     */
    public static function deleteRecord($plan_id) {
        DB::execute("DELETE FROM " . self::TABLE_NAME . " WHERE plan_id = ?", [$plan_id]);
    }

    /**
     * Delete records
     * @param array $arrIds
     */
    public static function deleteRecords(array $arrIds) {
        foreach ($arrIds as &$item) {
            self::deleteRecord($item['plan_id']);
            unset($item);
        }
    }

}