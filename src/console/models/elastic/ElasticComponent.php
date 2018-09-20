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
        $arr = [
            'settings' =>
                ['analysis' =>
                    [
                        'filter' =>
                            ['ngram_filter' =>
                                [
                                    'type' => 'ngram',
                                    'min_gram' => 3,
                                    'max_gram' => 20
                                ]
                            ]
                        ,
                        'analyzer' =>
                            ['ngram_analyzer' =>
                                [
                                    'tokenizer' => 'standard',
                                    'filter' => ['lowercase', 'ngram_filter']
                                ]
                            ]
                    ]
                ]
        ];
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
        $mapArr = [
            'dynamic' => 'strict',
            '_all' => ['enabled' => false],
            'properties' => [
                'id' => ['type' => 'keyword'],
                'tenderId' => ['type' => 'keyword'],
                'title' => ['type' => 'text'],
                'description' => ['type' => 'text'],
                'cdu-v' => ['type' => 'keyword'],
                'titlesOrDescriptionsStrict' => ['type' => 'text'],
                'titlesOrDescriptions' => ['type' => 'text', 'analyzer' => 'ngram_analyzer'],
                'buyerRegion' => ['type' => 'keyword'],
                'procedureType' => ['type' => 'keyword'],
                'procedureStatus' => ['type' => 'keyword'],
                'budget' => ['type' => 'scaled_float', 'scaling_factor' => 100],
                'classification' => ['type' => 'keyword'],
                'publicationDate' => ['type' => 'date'],
                'enquiryPeriodFrom' => ['type' => 'date'],
                'enquiryPeriodTo' => ['type' => 'date'],
                'tenderPeriodFrom' => ['type' => 'date'],
                'tenderPeriodTo' => ['type' => 'date'],
                'auctionPeriodFrom' => ['type' => 'date'],
                'auctionPeriodTo' => ['type' => 'date'],
                'awardPeriodFrom' => ['type' => 'date'],
                'awardPeriodTo' => ['type' => 'date'],
                'buyerName' => ['type' => 'text'],
                'buyerCode' => ['type' => 'keyword'],
            ],
        ];
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
                'ocid' => ['type' => 'keyword'],
                'title' => ['type' => 'text'],
                'description' => ['type' => 'text'],
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
        $mapArr = [
            'dynamic' => 'strict',
            '_all' => ['enabled' => false],
            'properties' => [
                'id' => ['type' => 'keyword'],
                'cdu-v' => ['type' => 'keyword'],
                'titlesOrDescriptionsStrict' => ['type' => 'text'],
                'titlesOrDescriptions' => ['type' => 'text', 'analyzer' => 'ngram_analyzer'],
            ],
        ];
        $jsonMap = json_encode($mapArr);


        return $this->createMapping($jsonMap);
    }

    /**
     * Index tender
     * @param $tender
     */
    public function indexTender($tender, $cduV) {
        $response = $tender['response'];
        $jsonArr = json_decode($response, 1);
        $records = $jsonArr['records'];
        foreach ($records as $record) {
            if ($record['ocid'] == $tender['tender_id']) {
                $tender_id = $record['ocid'];
                $title = ($record['compiledRelease']['tender']['title']) ?? "";
                $description = ($record['compiledRelease']['tender']['description']) ?? "";
                $docArr = [
                    'tenderId' => $tender_id,
                    'title' => $title,
                    'description' => $description,
                    'cdu-v' => $cduV,
                ];
                $this->indexDoc($docArr, $docArr['tenderId']);
                break;
            }
        }
    }

    /**
     * Index prozorro tender
     * @param $tender
     */
    public function indexTenderPrz($tender, $cduV) {
        $response = $tender['response'];
        $data = json_decode($response, 1);
        $titlesOrDescriptions = [];
        $classification = [];
        $buyerName = [];
        $id = '';
        $title = '';
        $description = '';
        $buyerRegion = '';
        $procedureType = '';
        $procedureStatus = '';
        $budget = '';
        $publicationDate = null;
        $enquiryPeriodFrom = null;
        $enquiryPeriodTo = null;
        $tenderPeriodFrom = null;
        $tenderPeriodTo = null;
        $auctionPeriodFrom = null;
        $auctionPeriodTo = null;
        $awardPeriodFrom = null;
        $awardPeriodTo = null;
        $buyerCode = '';
        $tenderId = $data['data']['id'];

        if (isset($data['data']['title']) && $data['data']['title']) {
            $title = $data['data']['title'];
            $titlesOrDescriptions[] = $title;
        }

        if (isset($data['data']['description']) && $data['data']['description']) {
            $description = $data['data']['description'];
            $titlesOrDescriptions[] = $description;
        }

        if (isset($data['data']['procuringEntity']['address']['region']) && $data['data']['procuringEntity']['address']['region']) {
            $buyerRegion = $data['data']['procuringEntity']['address']['region'];
        }

        if (isset($data['data']['tenderID']) && $data['data']['tenderID']) {
            $id = $data['data']['tenderID'];
        }

        if (isset($data['data']['procurementMethodType']) && $data['data']['procurementMethodType']) {
            $procedureType = $data['data']['procurementMethodType'];
        }

        if (isset($data['data']['status']) && $data['data']['status']) {
            $procedureStatus = $data['data']['status'];
        }

        if (isset($data['data']['value']['amount']) && $data['data']['value']['amount']) {
            $budget = $data['data']['value']['amount'];
        }

        if (isset($data['data']['enquiryPeriod']['startDate']) && $data['data']['enquiryPeriod']['startDate']) {
            $publicationDate = $data['data']['enquiryPeriod']['startDate'];
        }

        if (isset($data['data']['enquiryPeriod']['startDate']) && $data['data']['enquiryPeriod']['startDate']) {
            $enquiryPeriodFrom = $data['data']['enquiryPeriod']['startDate'];
        }

        if (isset($data['data']['enquiryPeriod']['endDate']) && $data['data']['enquiryPeriod']['endDate']) {
            $enquiryPeriodTo = $data['data']['enquiryPeriod']['endDate'];
        }

        if (isset($data['data']['tenderPeriod']['startDate']) && $data['data']['tenderPeriod']['startDate']) {
            $tenderPeriodFrom = $data['data']['tenderPeriod']['startDate'];
        }

        if (isset($data['data']['tenderPeriod']['endDate']) && $data['data']['tenderPeriod']['endDate']) {
            $tenderPeriodTo = $data['data']['tenderPeriod']['endDate'];
        }

        if (isset($data['data']['auctionPeriod']['startDate']) && $data['data']['auctionPeriod']['startDate']) {
            $auctionPeriodFrom = $data['data']['auctionPeriod']['startDate'];
        }

        if (isset($data['data']['auctionPeriod']['endDate']) && $data['data']['auctionPeriod']['endDate']) {
            $auctionPeriodTo = $data['data']['auctionPeriod']['endDate'];
        }

        if (isset($data['data']['awardPeriod']['startDate']) && $data['data']['awardPeriod']['startDate']) {
            $awardPeriodFrom = $data['data']['awardPeriod']['startDate'];
        }

        if (isset($data['data']['awardPeriod']['endDate']) && $data['data']['awardPeriod']['endDate']) {
            $awardPeriodTo = $data['data']['awardPeriod']['endDate'];
        }

        if (isset($data['data']['procuringEntity']['identifier']['id']) && $data['data']['procuringEntity']['identifier']['id']) {
            $buyerCode = $data['data']['procuringEntity']['identifier']['id'];
        }

        if (isset($data['data']['procuringEntity']['name']) && $data['data']['procuringEntity']['name']) {
            $buyerName[] = $data['data']['procuringEntity']['name'];
        }

        if (isset($data['data']['procuringEntity']['identifier']['legalName']) && $data['data']['procuringEntity']['identifier']['legalName']) {
            $buyesName[] = $data['data']['procuringEntity']['identifier']['legalName'];
        }

        if (isset($data['data']['lots']) && is_array($data['data']['lots'])) {
            foreach ($data['data']['lots'] as $lot) {
                if (isset($lot['title']) && $lot['title']) {
                    $titlesOrDescriptions[] = $lot['title'];
                }

                if (isset($lot['description']) && $lot['description']) {
                    $titlesOrDescriptions[] = $lot['description'];
                }
            }
        }

        if (isset($data['data']['items']) && is_array($data['data']['items'])) {
            foreach ($data['data']['items'] as $item) {
                if (isset($item['description']) && $item['description']) {
                    $titlesOrDescriptions[] = $item['description'];
                }

                if (isset($item['classification']['id']) && $item['classification']['id']) {
                    $classification[] = $item['classification']['id'];
                }
            }
        }

        $docArr = [
            'id'                   => $id,
            'tenderId'             => $tenderId,
            'title'                => $title,
            'description'          => $description,
            'cdu-v'                => $cduV,
            'titlesOrDescriptions' => $titlesOrDescriptions,
            'titlesOrDescriptionsStrict' => $titlesOrDescriptions,
            'buyerRegion'          => $buyerRegion,
            'procedureType'        => $procedureType,
            'procedureStatus'      => $procedureStatus,
            'budget'               => $budget,
            'classification'       => $classification,
            'publicationDate'      => $publicationDate,
            'enquiryPeriodFrom'    => $enquiryPeriodFrom,
            'enquiryPeriodTo'      => $enquiryPeriodTo,
            'tenderPeriodFrom'     => $tenderPeriodFrom,
            'tenderPeriodTo'       => $tenderPeriodTo,
            'auctionPeriodFrom'    => $auctionPeriodFrom,
            'auctionPeriodTo'      => $auctionPeriodTo,
            'awardPeriodFrom'      => $awardPeriodFrom,
            'awardPeriodTo'        => $awardPeriodTo,
            'buyerName'            => $buyerName,
            'buyerCode'            => $buyerCode,
        ];
        $this->indexDoc($docArr, $docArr['tenderId']);
    }

    /**
     * Index budget
     * @param $budget
     */
    public function indexBudget($budget) {
        $response = $budget['response'];
        $jsonArr = json_decode($response, 1);
        $records = $jsonArr['records'];
        foreach ($records as $record) {
            if ($record['ocid'] == $budget['ocid']) {
                $ocid = $record['ocid'];
                $title = ($record['compiledRelease']['tender']['title']) ?? "";
                $description = ($record['compiledRelease']['tender']['description']) ?? "";
                $docArr = ['ocid' => $ocid, 'title' => $title, 'description' => $description];
                $this->indexDoc($docArr, $docArr['ocid']);
                break;
            }
        }
    }

    /**
     * Index prozorro plan
     * @param $plan
     * @param $cduV
     */
    public function indexPlanPrz($plan, $cduV) {
        $response = $plan['response'];
        $data = json_decode($response, 1);
        $id = $data['data']['id'];
        $titlesOrDescriptions = [];

        if (!empty($data['data']['classification']['title'])) {
            $titlesOrDescriptions[] = $data['data']['classification']['title'];
        }
        if (!empty($data['data']['classification']['description'])) {
            $titlesOrDescriptions[] = $data['data']['classification']['description'];
        }
        if (isset($data['data']['lots']) && is_array($data['data']['lots'])) {
            foreach ($data['data']['lots'] as $item) {
                if (!empty(($item['title']))) {
                    $titlesOrDescriptions[] = $item['title'];
                }
                if (!empty($item['description'])) {
                    $titlesOrDescriptions[] = $item['description'];
                }
            }
        }
        if (isset($data['data']['items']) && is_array($data['data']['items'])) {
            foreach ($data['data']['items'] as $item) {
                if (!empty($item['description'])) {
                    $titlesOrDescriptions[] = $item['description'];
                }
            }
        }

        $docArr = [
            'id'                         => $id,
            'cdu-v'                      => $cduV,
            'titlesOrDescriptions'       => $titlesOrDescriptions,
            'titlesOrDescriptionsStrict' => $titlesOrDescriptions,
        ];
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
     */
    private function indexDoc($docArr, $id)
    {
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