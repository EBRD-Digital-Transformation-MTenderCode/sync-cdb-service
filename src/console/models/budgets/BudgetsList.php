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
}