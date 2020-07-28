<?php
namespace console\models\budgets;

use Yii;

/**
 * Class BudgetsList
 * class is used to work with the list of updated budgets
 *
 * @package console\models\budgets;
 */
class BudgetsList
{
    CONST TABLE_NAME = "budgets_changed_list";

    ///////  list-getter

    /**
     * Get budgets list url with offset
     * @param string $offset
     * @return string $url
     */
    public static function getOffsetUrl($offset) {
        return Yii::$app->params['budgets_url'] . "?offset=" . $offset;
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
            DB::execute("INSERT INTO " . self::TABLE_NAME . " (ocid, date_modified) VALUES ".implode(',', $values) . "  ON CONFLICT (ocid) DO NOTHING", $params);
        }
    }

    ///////  updates-getter

    /**
     * Getting a list of budgets
     * @param int $limit
     * @return array
     */
    public static function getBudgets(int $limit = 25) {
        return DB::fetchAll("SELECT * FROM " . self::TABLE_NAME . " LIMIT ?", [$limit]);
    }

    /**
     * Delete record
     * @param $ocid
     */
    public static function deleteRecord($ocid) {
        DB::execute("DELETE FROM " . self::TABLE_NAME . " WHERE ocid = ?", [$ocid]);
    }

    /**
     * Delete records
     * @param array $arrIds
     */
    public static function deleteRecords(array $arrIds) {
        foreach ($arrIds as &$item) {
            self::deleteRecord($item['ocid']);
            unset($item);
        }
    }

}