<?php
namespace console\models\tenders_prz;

use Yii;

/**
 * Class TendersList
 * class is used to work with the list of updated tenders
 *
 * @package console\models\tenders_prz
 */
class TendersList
{
    CONST TABLE_NAME = "tenders_prz_changed_list";

    /**
     * Get tenders list url with offset
     * @param string $offset
     * @return string $url
     */
    public static function getOffsetUrl($offset) {
        return Yii::$app->params['tenders_prz_url'] . "?offset=" . urlencode($offset);
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
}