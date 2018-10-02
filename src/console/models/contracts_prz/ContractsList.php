<?php
namespace console\models\contracts_prz;

use Yii;

/**
 * Class ContractsList
 * class is used to work with the list of updated plans
 *
 * @package console\models\contracts_prz
 */
class ContractsList
{
    CONST TABLE_NAME = "contracts_prz_changed_list";

    ///////  list-getter

    /**
     * Get Contracts list url with offset
     * @param string $offset
     * @return string $url
     */
    public static function getOffsetUrl($offset) {
        return Yii::$app->params['contracts_prz_url'] . "?offset=" . urlencode($offset);
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
            DB::execute("INSERT INTO " . self::TABLE_NAME . " (contract_id, date_modified) VALUES ".implode(',', $values) . "  ON CONFLICT (contract_id) DO NOTHING", $params);
        }
    }

    ///////  updates-getter

    /**
     * Getting a list of contracts
     * @param int $limit
     * @return array
     */
    public static function getContracts(int $limit = 25) {
        return DB::fetchAll("SELECT * FROM " . self::TABLE_NAME . " LIMIT ?", [$limit]);
    }

    /**
     * Delete record
     * @param $contract_id
     */
    public static function deleteRecord($contract_id) {
        DB::execute("DELETE FROM " . self::TABLE_NAME . " WHERE contract_id = ?", [$contract_id]);
    }

    /**
     * Delete records
     * @param array $arrIds
     */
    public static function deleteRecords(array $arrIds) {
        foreach ($arrIds as &$item) {
            self::deleteRecord($item['contract_id']);
            unset($item);
        }
    }

}