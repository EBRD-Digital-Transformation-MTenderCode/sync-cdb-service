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
     * @param array $item
     * @return array $decodedItem
     */
    public static function decode($item)
    {
        $responseArray = json_decode($item['response'], 1);
        $item['records'] = $responseArray['records'];
        $item['releasePackage'] = json_decode($item['release_package'], 1);
        $item['type'] = '';
        $item['stageId'] = '';

        foreach ($responseArray['actualReleases'] as $actualRelease) {
            $type = explode(self::DIVIDER, substr($actualRelease['ocid'], strlen($item['tender_id'])))[1] ?? null;
            $item['item_id'] = $item['tender_id'];
            if (in_array($type, self::MARKS)) {
                $item['type'] = $type;
                $item['stageId'] = $actualRelease['ocid'];
                break;
            }
        }

        $ocids = [];
        foreach ($responseArray['records'] as $record) {
            $type = explode(self::DIVIDER, substr($record['ocid'], strlen($item['tender_id'])))[1] ?? null;

            if (in_array($type, self::MARKS)) {
                $ocids[$type] = $record['ocid'];
            }
        }

        //set EV record as STAGE for CONTRACTS
        if (isset($ocids[self::MARK_CONTRACT]) && isset($ocids[self::MARK_TENDER])) {
            $item['stageId'] = $ocids[self::MARK_TENDER];
        }

        return $item;
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