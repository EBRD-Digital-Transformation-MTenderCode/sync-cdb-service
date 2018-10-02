<?php
namespace console\models\contracts_prz;

use console\models\Curl;
use Yii;
use yii\web\HttpException;

/**
 * Class Contract
 * @package console\models\contracts_prz
 */
class Contract
{
    CONST TABLE_NAME = "contracts_prz_updates";

    /**
     * Search of the contract in our database
     * table contracts_updates
     * @param $contract_id
     * @return array
     */
    public static function findById($contract_id) {
        return DB::fetch("SELECT * FROM " . self::TABLE_NAME . " WHERE contract_id = ?", [$contract_id]);
    }

    /**
     * Method for adding to our database a record of a modified contract
     * @param $contract_id
     * @param $contractJson
     */
    public static function add($contract_id, $contractJson) {
        $params = [
            'contract_id' => $contract_id,
            'response' => $contractJson,
            'updated_at' => time(),
        ];
        DB::execute("INSERT INTO " . self::TABLE_NAME . " (contract_id, response, updated_at) VALUES (:contract_id, :response, :updated_at)", $params);
    }

    /**
     * Method for updating in our database a record of a modified contract
     * @param $id
     * @param $contractJson
     */
    public static function update($id, $contractJson)
    {
        $params = [
            'response' => $contractJson,
            'updated_at' => time(),
            'id' => $id
        ];
        DB::execute("UPDATE " . self::TABLE_NAME . " SET response = :response, updated_at = :updated_at WHERE id = :id", $params);
    }

    /**
     * Adding and updating the array of contracts to our database
     * @param array $contractsIdsArr
     * @throws HttpException
     */
    public static function updateItems(array $contractsIdsArr) {
        foreach ($contractsIdsArr as &$item) {
            $contract_id = $item['contract_id'];
            $contractsRow = self::findById($contract_id);
            $contractJson = self::getContractFromCdb($contract_id);

            if (!empty($contractJson)) {
                if (empty($contractsRow)) {
                    self::add($item['contract_id'], $contractJson);
                    Yii::info("Added new contract. ID:{$contract_id}", 'sync-info');
                } else {
                    self::update($contract_id, $contractJson);
                    Yii::info("Updated contract. ID:{$contract_id}", 'sync-info');
                }
            }

            unset($item);
        }
    }

    /**
     * Request to the CDB for contract
     * @param $contract_id
     * @return mixed
     * @throws HttpException
     */
    private static function getContractFromCdb($contract_id) {
        $url = Yii::$app->params['contracts_prz_url'] . "/" . $contract_id;

        $result = Curl::sendRequest($url, "GET");

        if ($result['code'] != 200) {
            throw new HttpException($result['code'], "Query error to CDB. Url: " . $url);
        }

        $contractJson = $result['body'];

        return $contractJson;
    }
}