<?php
namespace console\models\elastic;

use Yii;
use yii\web\HttpException;
use yii\web\ForbiddenHttpException;
use common\components\Curl;

/**
 * Class ElasticComponent
 * @package console\models\elastic
 */
class ElasticComponent
{
    const ROLE_BUYER = 'buyer';

    private $index;
    private $type;
    private $url;

    /**
     * ElasticComponent constructor.
     * @param string $elastic_url
     * @param string $elastic_index
     * @param string $elastic_type
     * @throws ForbiddenHttpException
     */
    public function __construct($elastic_url, $elastic_index, $elastic_type)
    {
        if (!$elastic_url || !$elastic_index || !$elastic_type) {
            throw new ForbiddenHttpException("Elastic params not set.");
        }

        $this->url = $elastic_url;
        $this->index = $elastic_index;
        $this->type = $elastic_type;
    }

    /**
     * @return array
     * @throws HttpException
     */
    public function setIndexSettings()
    {
        Yii::info("Init index settings", 'console-msg');
        $arr = ElasticHelper::getSettings();
        $json = json_encode($arr);

        $elastic_request_url = $this->url . DIRECTORY_SEPARATOR . $this->index;
        $curl_options = ['HTTPHEADER' => ['Content-Type:application/json']];
        $resutl = Curl::sendRequest($elastic_request_url, "PUT", $json, $curl_options);
        return $resutl;
    }


    /**
     * Check Mapping
     * @return array
     * @throws HttpException
     */
    public function checkMapping()
    {
        return Curl::sendRequest($this->getTypePath() . "_mapping", "GET");
    }

    /**
     * Tenders mapping
     * @return array
     * @throws HttpException
     */
    public function tendersMapping()
    {
        Yii::info("Mapping tenders", 'console-msg');
        $mapArr = ElasticHelper::getTenderMap();
        $jsonMap = json_encode($mapArr);

        return $this->createMapping($jsonMap);
    }

    /**
     * Budgets mapping
     * @return array
     * @throws HttpException
     */
    public function budgetsMapping()
    {
        Yii::info("Mapping budgets", 'console-msg');
        $mapArr = [
            'dynamic' => 'strict',
            '_all' => ['enabled' => false],
            'properties' => [
                'id' => ['type' => 'keyword'],
                'entityId' => ['type' => 'keyword'],
                'title' => ['type' => 'text'],
                'description' => ['type' => 'text'],
                'titlesOrDescriptions' => ['type' => 'text', 'analyzer' => 'ngram_analyzer'],
                'titlesOrDescriptionsStrict' => ['type' => 'text'],
                'buyerRegion' => ['type' => 'keyword'],
                'budgetStatus' => ['type' => 'keyword'],
                'amount' => ['type' => 'scaled_float', 'scaling_factor' => 100],
                'currency' => ['type' => 'keyword'],
                'classifications' => ['type' => 'keyword'],
                'periodPlanningFrom' => ['type' => 'date'],
                'periodPlanningTo' => ['type' => 'date'],
                'modifiedDate' => ['type' => 'date'],
                'buyerName' => ['type' => 'text'],
                'buyersNames' => ['type' => 'text', 'analyzer' => 'ngram_analyzer'],
                'buyerIdentifier' => ['type' => 'keyword'],
                'buyerType' => ['type' => 'keyword'],
                'buyerMainGeneralActivity' => ['type' => 'keyword'],
                'buyerMainSectoralActivity' => ['type' => 'keyword'],
            ]
        ];
        $jsonMap = json_encode($mapArr);

        return $this->createMapping($jsonMap);
    }

    /**
     * Plans mapping
     * @return array
     * @throws HttpException
     */
    public function plansMapping()
    {
        Yii::info("Mapping plans", 'console-msg');
        $mapArr = ElasticHelper::getTenderMap();
        $jsonMap = json_encode($mapArr);

        return $this->createMapping($jsonMap);
    }

    /**
     * Contracts mapping
     * @return array
     * @throws HttpException
     */
    public function contractsMapping()
    {
        Yii::info("Mapping contracts", 'console-msg');
        $mapArr = ElasticHelper::getTenderMap();
        $jsonMap = json_encode($mapArr);

        return $this->createMapping($jsonMap);
    }

    /**
     * Cpv mapping
     * @return array
     * @throws HttpException
     */
    public function cpvMapping()
    {
        Yii::info("Mapping cpv", 'console-msg');
        $mapArr = ElasticHelper::getCpvMap();
        $jsonMap = json_encode($mapArr);

        return $this->createMapping($jsonMap);
    }

    /**
     * Index tender
     * @param $tender
     */
    public function indexTender($tender, $cdb) {

        $docArr = ElasticHelper::prepareTenderToElastic($tender, $cdb);
        unset($docArr['tags']);
        $this->indexDoc($docArr, $docArr['id']);

    }

    /**
     * Index prozorro tender
     * @param $tender
     */
    public function indexTenderPrz($tender, $cdb) {
        $docArr = ElasticHelper::prepareTenderPrzToElastic($tender, $cdb);
        $this->indexDoc($docArr, $docArr['id']);
    }

    /**
     * Index budget
     * @param $budget
     */
    public function indexBudget($budget) {
        $jsonArr = json_decode($budget['response'], 1);
        $records = $jsonArr['records'];
        $id = '';
        $title = '';
        $description = '';
        $buyerRegion = '';
        $buyerName = '';
        $buyerIdentifier = '';
        $buyerType = '';
        $buyerMainGeneralActivity = '';
        $buyerMainSectoralActivity = '';
        $currency = '';
        $amount = 0;
        $modifiedDate = null;
        $periodPlanningFrom = null;
        $periodPlanningTo = null;
        $titlesOrDescriptions = [];
        $buyersNames = [];
        $classifications = [];

        foreach ($records as $record) {
            //EI
            if ($record['ocid'] == $budget['ocid']) {
                $id = $record['ocid'];
                $data = $record['compiledRelease'];

                $amount = $data['planning']['budget']['amount']['amount'] ?? 0;
                $currency = $data['planning']['budget']['amount']['currency'] ?? '';
                $budgetStatus = $data['tender']['status'] ?? '';

                $modifiedDate = $data['date'] ?? null;
                $periodPlanningFrom = $data['planning']['budget']['period']['startDate'] ?? null;
                $periodPlanningTo = $data['planning']['budget']['period']['endDate'] ?? null;

                $classifications[] = $data['tender']['classification']['id'] ?? '';

                $titlesOrDescriptions[$id] = $id;

                if (!empty($data['tender']['title'])) {
                    $title = $data['tender']['title'];
                    $titlesOrDescriptions[$title] = $title;
                }

                if (!empty($data['tender']['description'])) {
                    $description = $data['tender']['description'];
                    $titlesOrDescriptions[$description] = $description;
                }

                if (isset($data['parties']) && is_array($data['parties'])) {
                    $part = $data['parties'][0];
                    $buyerRegion = $part['address']['addressDetails']['region']['description'] ?? '';

                    if (!empty($part['name'])) {
                        $buyerName = $part['name'];
                        $buyersNames[$part['name']] = $part['name'];
                    }

                    if (!empty($part['identifier']['legalName'])) {
                        $buyersNames[$part['identifier']['legalName']] = $part['identifier']['legalName'];
                    }

                    $buyerIdentifier = $part['identifier']['id'] ?? '';
                    $buyerType = $part['details']['typeOfBuyer'] ?? '';
                    $buyerMainGeneralActivity = $part['details']['mainGeneralActivity'] ?? '';
                    $buyerMainSectoralActivity = $part['details']['mainSectoralActivity'] ?? '';
                }
            }
        }

        $docArr = [
            'id'                         => $id,
            'entityId'                   => $id,
            'title'                      => $title,
            'description'                => $description,
            'titlesOrDescriptions'       => array_values($titlesOrDescriptions),
            'titlesOrDescriptionsStrict' => array_values($titlesOrDescriptions),
            'buyerRegion'                => $buyerRegion,
            'budgetStatus'               => $budgetStatus,
            'amount'                     => $amount,
            'currency'                   => $currency,
            'classifications'            => array_values($classifications),
            'periodPlanningFrom'         => $periodPlanningFrom,
            'periodPlanningTo'           => $periodPlanningTo,
            'modifiedDate'               => $modifiedDate,
            'buyerName'                  => $buyerName,
            'buyersNames'                => array_values($buyersNames),
            'buyerIdentifier'            => $buyerIdentifier,
            'buyerType'                  => $buyerType,
            'buyerMainGeneralActivity'   => $buyerMainGeneralActivity,
            'buyerMainSectoralActivity'  => $buyerMainSectoralActivity
        ];
        $this->indexDoc($docArr, $docArr['id']);
    }

    /**
     * Index ocds plan
     * @param $tender
     * @param $cdb
     */
    public function indexPlan($tender, $cdb) {

        $docArr = ElasticHelper::prepareTenderToElastic($tender, $cdb);
        $this->indexDoc($docArr, $docArr['id']);

    }

    /**
     * Index prozorro plan
     * @param $plan
     * @param $cdb
     */
    public function indexPlanPrz($plan, $cdb) {
        $docArr = ElasticHelper::preparePlanPrzToElastic($plan, $cdb);
        $this->indexDoc($docArr, $docArr['id']);
    }

    /**
     * Index ocds contract
     * @param $tender
     * @param $cdb
     * @return bool
     */
    public function indexContract($tender, $cdb) {

        $docArr = ElasticHelper::prepareTenderToElastic($tender, $cdb);
        return $this->indexDoc($docArr, $docArr['id']);

    }

    /**
     * Index prozorro contract
     * @param $contract
     * @param $cdb
     */
    public function indexContractPrz($contract, $cdb) {
        $docArr = ElasticHelper::prepareContractPrzToElastic($contract, $cdb);
        $this->indexDoc($docArr, $docArr['id']);
    }

    /**
     * Index cpv
     * @param $cpv
     */
    public function indexCpv($cpv) {
        $docArr = ElasticHelper::prepareCpvToElastic($cpv);
        $this->indexDoc($docArr, $docArr['id']);
    }

    /**
     * Drop index
     * @return array
     * @throws HttpException
     */
    public function dropIndex() {
        Yii::info("Deleting index: " . $this->index, 'console-msg');

        return Curl::sendRequest(
            $this->url . DIRECTORY_SEPARATOR . $this->index,
            "DELETE",
            "",
            ['HTTPHEADER' => ['Content-Type:application/json']]
        );
    }

    /**
     * Delete item by _id
     * @param $item
     * @return array
     * @throws HttpException
     */
    public function deleteItem($item)
    {
        return Curl::sendRequest(
            $this->getTypePath() . $item['tender_id'],
            "DELETE",
            "",
            ['HTTPHEADER' => ['Content-Type:application/json']]
        );
    }

    /**
     * Create mapping
     * @param $jsonMap
     * @return array
     * @throws HttpException
     */
    private function createMapping($jsonMap)
    {
        $elastic_request_url = $this->url . DIRECTORY_SEPARATOR . $this->index;
        $curl_options = ['HTTPHEADER' => ['Content-Type:application/json']];

        // try to create index
        Curl::sendRequest($elastic_request_url, "PUT", "", $curl_options);

        // mapping
        $elastic_request_url = $this->url . DIRECTORY_SEPARATOR
            . $this->index . DIRECTORY_SEPARATOR
            . "_mapping" . DIRECTORY_SEPARATOR
            . $this->type;

        return Curl::sendRequest($elastic_request_url, "PUT", $jsonMap, $curl_options);
    }

    /**
     * Index doc
     * @param $docArr
     * @param $id
     * @return bool
     */
    private function indexDoc($docArr, $id)
    {
        if (!$docArr) {
            return false;
        }
        $delay = (int) Yii::$app->params['sleep_error_interval'];
        if (!$id) {
            Yii::error('Elastic error. Undefined id', 'sync-info');
            sleep($delay);
        }

        try {

            $data_string = json_encode($docArr);
            $curl_options = ['HTTPHEADER' => ['Content-Type:application/json']];
            $result = Curl::sendRequest($this->getTypePath() . $id,
                "POST", $data_string, $curl_options);

            if ($result['code'] != 200 && $result['code'] != 201 && $result['code'] != 100) {
                Yii::error("Elastic indexing error. Http-code: " . $result['code'], 'sync-info');
                sleep($delay);
            }

        } catch (HttpException $exception) {
            Yii::error('Elastic error. CURL ERROR[' . $exception->getCode() . ']. ' . $exception->getMessage(), 'sync-info');
            sleep($delay);
        }
    }

    /**
     * Get elastic type path
     * @return string
     */
    private function getTypePath()
    {
        return $this->url . DIRECTORY_SEPARATOR
            . $this->index . DIRECTORY_SEPARATOR
            . $this->type . DIRECTORY_SEPARATOR;
    }
}