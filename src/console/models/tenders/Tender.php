<?php
namespace console\models\tenders;

use Yii;
use yii\web\HttpException;
use console\models\Curl;

/**
 * Class Tender
 * @package console\models
 */
class Tender
{
    CONST TABLE_NAME = "tenders_updates";
    const DIVIDER = '-';
    const MARK_TENDER = 'EV';
    const MARK_PLAN = 'PN';
    const MARK_CONTRACT = 'AC';
    const MARKS = [
        self::MARK_TENDER,
        self::MARK_PLAN,
        self::MARK_CONTRACT,
    ];

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
     * @param string $tender_id
     * @param string $tenderJson
     * @param string $releasePackageJson
     */
    public static function add($tender_id, $tenderJson, $releasePackageJson) {
        $params = [
            'tender_id'       => $tender_id,
            'response'        => $tenderJson,
            'release_package' => $releasePackageJson,
            'updated_at'      => time(),
        ];
        DB::execute("INSERT INTO " . self::TABLE_NAME . " (tender_id, response, release_package, updated_at) VALUES (:tender_id, :response, :release_package, :updated_at)", $params);
    }

    /**
     * Method for updating in our database a record of a modified tender
     * @param string $id
     * @param string $tenderJson
     * @param string $releasePackageJson
     */
    public static function update($id, $tenderJson, $releasePackageJson)
    {
        $params = [
            'response'        => $tenderJson,
            'release_package' => $releasePackageJson,
            'updated_at'      => time(),
            'id'              => $id
        ];
        DB::execute("UPDATE " . self::TABLE_NAME . " SET response = :response, release_package = :release_package, updated_at = :updated_at WHERE id = :id", $params);
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
            $releasePackageJson = self::getReleasePackageFromCdb($tender_id);

            if (!empty($tenderJson)) {
                if (empty($tendersRow)) {
                    self::add($item['tender_id'], $tenderJson, $releasePackageJson);
                    Yii::info("Added new tender. ID:{$tender_id}", 'sync-info');
                } else {
                    self::update($tendersRow['id'], $tenderJson, $releasePackageJson);
                    Yii::info("Updated tender. ID:{$tender_id}", 'sync-info');
                }
            }

            unset($item);
        }
    }

    /**
     * Returns tender with json decoded props and type
     * @param string $type
     * @param array $item
     * @return array $decodedItem
     */
    public static function decode($item, $type = '')
    {
        $result = [];

        $responseArray = json_decode($item['response'], 1);
        $item['records'] = $responseArray['records'];
        $item['releasePackage'] = json_decode($item['release_package'], 1);
        $contractCounter = 0;

        switch ($type) {
            case self::MARK_PLAN:
                $itemId = $item['plan_id'];
                break;
            case self::MARK_CONTRACT:
                $itemId = $item['contract_id'];
                break;
            default:
                $itemId = $item['tender_id'];
                break;
        }

        foreach ($responseArray['records'] as $record) {
            $itemType = explode(self::DIVIDER, substr($record['ocid'], strlen($itemId)))[1] ?? null;

            if (in_array($itemType, self::MARKS)) {
                $item['type'] = $itemType;
                $item['stageId'] = $record['ocid'];
                $item['msId'] = $itemId;

                if ($itemType == self::MARK_CONTRACT) {
                    $item['item_id'] = $itemId . self::DIVIDER . $contractCounter;
                    $contractCounter++;
                } else {
                    $item['item_id'] = $itemId;
                }

                if (empty($type) || $type == $itemType) {
                    $result[] = $item;
                }
            }
        }

        return $result;
    }

    /**
     * Request to the CDB for tender
     * @param $tender_id
     * @return mixed
     * @throws HttpException
     */
    private static function getTenderFromCdb($tender_id) {
        $url = Yii::$app->params['tenders_url'] . DIRECTORY_SEPARATOR . $tender_id;
        $result = Curl::sendRequest($url, "GET");

        if ($result['code'] != 200) {
            throw new HttpException($result['code'], "Query error to CDB");
        }

        return $result['body'];
    }

    /**
     * Request to the CDB for release package
     * @param $tender_id
     * @return mixed
     * @throws HttpException
     */
    private static function getReleasePackageFromCdb($tender_id) {
        $url = Yii::$app->params['tenders_url'] . DIRECTORY_SEPARATOR . $tender_id . DIRECTORY_SEPARATOR . $tender_id;
        $result = Curl::sendRequest($url, "GET");

        if ($result['code'] != 200) {
            throw new HttpException($result['code'], "Query error to CDB");
        }

        return $result['body'];
    }
}