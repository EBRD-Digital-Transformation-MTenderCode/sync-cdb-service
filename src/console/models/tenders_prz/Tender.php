<?php
namespace console\models\tenders_prz;

use console\models\Curl;
use Yii;
use yii\web\HttpException;

/**
 * Class Tender
 * @package console\models\tenders_prz
 */
class Tender
{
    CONST TABLE_NAME = "tenders_prz_updates";

    /**
     * Search of the tender in our database
     * table tenders_updates
     * @param $tender_id
     * @return array
     */
    public static function findById($tender_id) {
        return DB::fetch("SELECT * FROM " . self::TABLE_NAME . " WHERE tender_id = ?", [$tender_id]);
    }

    /**
     * Method for adding to our database a record of a modified tender
     * @param $tender_id
     * @param $tenderJson
     */
    public static function add($tender_id, $tenderJson) {
        $params = [
            'tender_id' => $tender_id,
            'response' => $tenderJson,
            'updated_at' => time(),
        ];
        DB::execute("INSERT INTO " . self::TABLE_NAME . " (tender_id, response, updated_at) VALUES (:tender_id, :response, :updated_at)", $params);
    }

    /**
     * Method for updating in our database a record of a modified tender
     * @param $id
     * @param $tenderJson
     */
    public static function update($id, $tenderJson)
    {
        $params = [
            'response' => $tenderJson,
            'updated_at' => time(),
            'id' => $id
        ];
        DB::execute("UPDATE " . self::TABLE_NAME . " SET response = :response, updated_at = :updated_at WHERE id = :id", $params);
    }

    /**
     * Adding and updating the array of tenders to our database
     * @param array $tendersIdsArr
     * @throws HttpException
     */
    public static function updateItems(array $tendersIdsArr) {
        foreach ($tendersIdsArr as &$item) {
            $tender_id = $item['tender_id'];
            $tendersRow = self::findById($tender_id);
            $tenderJson = self::getTenderFromCdb($tender_id);

            if (!empty($tenderJson)) {
                if (empty($tendersRow)) {
                    self::add($item['tender_id'], $tenderJson);
                    Yii::info("Added new tender. ID:{$tender_id}", 'sync-info');
                } else {
                    self::update($tender_id, $tenderJson);
                    Yii::info("Updated tender. ID:{$tender_id}", 'sync-info');
                }
            }

            unset($item);
        }
    }

    /**
     * Request to the CDB for tender
     * @param $tender_id
     * @return mixed
     * @throws HttpException
     */
    private static function getTenderFromCdb($tender_id) {
        $url = Yii::$app->params['tenders_url'] . "/" . $tender_id;
        $result = Curl::sendRequest($url, "GET");

        if ($result['code'] != 200) {
            throw new HttpException($result['code'], "Query error to CDB");
        }

        $tenderJson = $result['body'];

        return $tenderJson;
    }
}