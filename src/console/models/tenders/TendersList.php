<?php
namespace console\models\tenders;

use Yii;

/**
 * Class TendersList
 * class is used to work with the list of updated tenders
 *
 * @package console\models\tenders
 */
class TendersList
{
    CONST TABLE_NAME = "tenders_changed_list";

    ///////  list-getter

    /**
     * Get tenders list url with offset
     * @param string $offset
     * @return string $url
     */
    public static function getOffsetUrl($offset) {
        return Yii::$app->params['tenders_url'] . "?offset=" . $offset;
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
            DB::execute("INSERT INTO " . self::TABLE_NAME . " (tender_id, date_modified) VALUES ".implode(',', $values) . "  ON CONFLICT (tender_id) DO NOTHING", $params);
        }
    }

    ///////  updates-getter

    /**
     * Getting a list of tenders
     * @param int $limit
     * @return array
     */
    public static function getTenders(int $limit = 25) {
        return DB::fetchAll("SELECT * FROM " . self::TABLE_NAME . " LIMIT ?", [$limit]);
    }

    /**
     * Delete record
     * @param $tender_id
     */
    public static function deleteRecord($tender_id) {
        DB::execute("DELETE FROM " . self::TABLE_NAME . " WHERE tender_id = ?", [$tender_id]);
    }

    /**
     * Delete records
     * @param array $arrIds
     */
    public static function deleteRecords(array $arrIds) {
        foreach ($arrIds as &$item) {
            self::deleteRecord($item['tender_id']);
            unset($item);
        }
    }

}