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
    const DIVIDER = '-';
    const MARK_TENDER = 'EV';
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
                'cdb' => ['type' => 'keyword'],
                'id' => ['type' => 'keyword'],
                'tenderId' => ['type' => 'keyword'],
                'title' => ['type' => 'text'],
                'description' => ['type' => 'text'],
                'titlesOrDescriptions' => ['type' => 'text'],
                'buyerRegion' => ['type' => 'keyword'],
                'deliveriesRegions' => ['type' => 'keyword'],
                'procedureType' => ['type' => 'keyword'],
                'procedureStatus' => ['type' => 'keyword'],
                'amount' => ['type' => 'scaled_float', 'scaling_factor' => 100],
                'currency' => ['type' => 'keyword'],
                'classifications' => ['type' => 'keyword'],
                'publishedDate' => ['type' => 'date'],
                'periodDeliveryFrom' => ['type' => 'date'],
                'periodDeliveryTo' => ['type' => 'date'],
                'periodEnquiryFrom' => ['type' => 'date'],
                'periodEnquiryTo' => ['type' => 'date'],
                'periodOfferFrom' => ['type' => 'date'],
                'periodOfferTo' => ['type' => 'date'],
                'periodAuctionFrom' => ['type' => 'date'],
                'periodAuctionTo' => ['type' => 'date'],
                'periodAwardFrom' => ['type' => 'date'],
                'periodAwardTo' => ['type' => 'date'],
                'buyerName' => ['type' => 'text'],
                'buyersNames' => ['type' => 'text'],
                'buyerIdentifier' => ['type' => 'keyword'],
                'buyerType' => ['type' => 'keyword'],
                'buyerMainGeneralActivity' => ['type' => 'keyword'],
                'buyerMainSectoralActivity' => ['type' => 'keyword'],
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
                'cdb' => ['type' => 'keyword'],
                'titlesOrDescriptionsStrict' => ['type' => 'text'],
                'titlesOrDescriptions' => ['type' => 'text'],
            ],
        ];
        $jsonMap = json_encode($mapArr);

        return $this->createMapping($jsonMap);
    }

    /**
     * Index tender
     * @param $tender
     */
    public function indexTender($tender, $cdb) {
        $response = $tender['response'];
        $jsonArr = json_decode($response, 1);
        $records = $jsonArr['records'];
        $actualReleases = $jsonArr['actualReleases'];
        $id = $tender['tender_id'];
        $stageId = false;
        $publishedDate = null;
        $periodEnquiryFrom = null;
        $periodEnquiryTo = null;
        $periodOfferFrom = null;
        $periodOfferTo = null;
        $periodAwardFrom = null;
        $periodAwardTo = null;
        $buyerRegion = '';
        $procedureType = '';
        $procedureStatus = '';
        $amount = '';
        $currency = '';
        $buyerName = '';
        $buyerIdentifier = '';
        $buyerType = '';
        $buyerMainGeneralActivity = '';
        $buyerMainSectoralActivity = '';
        $titlesOrDescriptions = [];
        $deliveriesRegions = [];
        $classifications = [];
        $periodDeliveryFrom = [];
        $periodDeliveryTo = [];
        $buyersNames = [];

        //find stage release id
        foreach ($actualReleases as $actualRelease) {
            if (strpos($actualRelease['ocid'], $tender['tender_id'] . self::DIVIDER . self::MARK_TENDER) !== false) {
                $stageId = $actualRelease['ocid'];
                break;
            }
        }

        $ms = [];
        $stage = [];

        //get stage and ms item
        foreach ($records as $record) {
            if ($record['ocid'] == $id) {
                $ms = $record;
            }

            if ($record['ocid'] == $stageId) {
                $stage = $record;
            }
        }

        //create data array and index doc
        if (!empty($ms) && !empty($stage)) {
            $tender_id = $ms['ocid'];

            if (isset($ms['compiledRelease']['tender']['title']) && $ms['compiledRelease']['tender']['title']) {
                $title = $ms['compiledRelease']['tender']['title'];
                $titlesOrDescriptions[] = $title;
            }

            if (isset($ms['compiledRelease']['tender']['description']) && $ms['compiledRelease']['tender']['description']) {
                $description = $ms['compiledRelease']['tender']['description'];
                $titlesOrDescriptions[] = $description;
            }

            if (isset($ms['compiledRelease']['tender']['procurementMethodDetails']) && $ms['compiledRelease']['tender']['procurementMethodDetails']) {
                $procedureType = $ms['compiledRelease']['tender']['procurementMethodDetails'];
            }

            if (isset($ms['compiledRelease']['tender']['statusDetails']) && $ms['compiledRelease']['tender']['statusDetails']) {
                $procedureStatus = $ms['compiledRelease']['tender']['statusDetails'];
            }

            if (isset($ms['compiledRelease']['tender']['value']['amount']) && $ms['compiledRelease']['tender']['value']['amount']) {
                $amount = $ms['compiledRelease']['tender']['value']['amount'];
            }

            if (isset($ms['compiledRelease']['tender']['value']['currency']) && $ms['compiledRelease']['tender']['value']['currency']) {
                $currency = $ms['compiledRelease']['tender']['value']['currency'];
            }

            if (isset($ms['compiledRelease']['parties']) && is_array($ms['compiledRelease']['parties'])) {
                foreach ($ms['compiledRelease']['parties'] as $part) {
                    if (in_array(self::ROLE_BUYER, $part['roles'])) {
                        if (isset($part['address']['addressDetails']['region']['description']) && $part['address']['addressDetails']['region']['description']) {
                            $buyerRegion = $part['address']['addressDetails']['region']['description'];
                        }

                        if (isset($part['name']) && $part['name']) {
                            $buyerName = $part['name'];
                            $buyersNames[] = $part['name'];
                        }

                        if (isset($part['identifier']['legalName']) && $part['identifier']['legalName']) {
                            $buyersNames[] = $part['identifier']['legalName'];
                        }

                        if (isset($part['identifier']['id']) && $part['identifier']['id']) {
                            $buyerIdentifier = $part['identifier']['id'];
                        }

                        if (isset($part['details']['typeOfBuyer']) && $part['details']['typeOfBuyer']) {
                            $buyerType = $part['details']['typeOfBuyer'];
                        }

                        if (isset($part['details']['mainGeneralActivity']) && $part['details']['mainGeneralActivity']) {
                            $buyerMainGeneralActivity = $part['details']['mainGeneralActivity'];
                        }

                        if (isset($part['details']['mainSectoralActivity']) && $part['details']['mainSectoralActivity']) {
                            $buyerMainSectoralActivity = $part['details']['mainSectoralActivity'];
                        }
                    }
                }
            }

            if (isset($stage['compiledRelease']['tender']['enquiryPeriod']['startDate']) && isset($stage['compiledRelease']['tender']['enquiryPeriod']['startDate'])){
                $periodEnquiryFrom = $stage['compiledRelease']['tender']['enquiryPeriod']['startDate'];
            }

            if (isset($stage['compiledRelease']['tender']['enquiryPeriod']['endDate']) && isset($stage['compiledRelease']['tender']['enquiryPeriod']['endDate'])){
                $periodEnquiryTo = $stage['compiledRelease']['tender']['enquiryPeriod']['endDate'];
            }

            if (isset($stage['compiledRelease']['tender']['tenderPeriod']['startDate']) && isset($stage['compiledRelease']['tender']['tenderPeriod']['startDate'])){
                $periodOfferFrom = $stage['compiledRelease']['tender']['tenderPeriod']['startDate'];
            }

            if (isset($stage['compiledRelease']['tender']['tenderPeriod']['endDate']) && isset($stage['compiledRelease']['tender']['tenderPeriod']['endDate'])){
                $periodOfferTo = $stage['compiledRelease']['tender']['tenderPeriod']['endDate'];
            }

            if (isset($stage['compiledRelease']['tender']['awardPeriod']['startDate']) && isset($stage['compiledRelease']['tender']['awardPeriod']['startDate'])){
                $periodAwardFrom = $stage['compiledRelease']['tender']['awardPeriod']['startDate'];
            }

            if (isset($stage['compiledRelease']['tender']['awardPeriod']['endDate']) && isset($stage['compiledRelease']['tender']['awardPeriod']['endDate'])){
                $periodAwardTo = $stage['compiledRelease']['tender']['awardPeriod']['endDate'];
            }

            if (isset($stage['compiledRelease']['tender']['lots']) && is_array($stage['compiledRelease']['tender']['lots'])) {
                foreach ($stage['compiledRelease']['tender']['lots'] as $lot) {
                    if (isset($lot['title']) && $lot['title']) {
                        $titlesOrDescriptions[] = $lot['title'];
                    }

                    if (isset($lot['description']) && $lot['description']) {
                        $titlesOrDescriptions[] = $lot['description'];
                    }

                    if (isset($lot['placeOfPerformance']['address']['addressDetails']['region']['description']) && $lot['placeOfPerformance']['address']['addressDetails']['region']['description']) {
                        $deliveriesRegions[] = $lot['placeOfPerformance']['address']['addressDetails']['region']['description'];
                    }

                    if (isset($lot['contractPeriod']['startDate']) && $lot['contractPeriod']['startDate']) {
                        $periodDeliveryFrom[] = $lot['contractPeriod']['startDate'];
                    }

                    if (isset($lot['contractPeriod']['endDate']) && $lot['contractPeriod']['endDate']) {
                        $periodDeliveryTo[] = $lot['contractPeriod']['endDate'];
                    }
                }
            }

            if (isset($stage['compiledRelease']['tender']['items']) && is_array($stage['compiledRelease']['tender']['items'])) {
                foreach ($stage['compiledRelease']['tender']['items'] as $item) {
                    if (isset($item['description']) && $item['description']) {
                        $titlesOrDescriptions[] = $item['description'];
                    }

                    if (isset($item['classification']['id']) && $item['classification']['id']) {
                        $classifications[] = $item['classification']['id'];
                    }
                }
            }

            if (isset($jsonArr['publishedDate']) && $jsonArr['publishedDate']) {
                $publishedDate = $jsonArr['publishedDate'];
            }

            $docArr = [
                'cdb'                       => $cdb,
                'id'                        => $id,
                'tenderId'                  => $id,
                'title'                     => $title,
                'description'               => $description,
                'titlesOrDescriptions'      => $titlesOrDescriptions,
                'buyerRegion'               => $buyerRegion,
                'deliveriesRegions'         => $deliveriesRegions,
                'procedureType'             => $procedureType,
                'procedureStatus'           => $procedureStatus,
                'amount'                    => $amount,
                'currency'                  => $currency,
                'classifications'           => $classifications,
                'publishedDate'             => $publishedDate,
                'periodDeliveryFrom'        => $periodDeliveryFrom,
                'periodDeliveryTo'          => $periodDeliveryTo,
                'periodEnquiryFrom'         => $periodEnquiryFrom,
                'periodEnquiryTo'           => $periodEnquiryTo,
                'periodOfferFrom'           => $periodOfferFrom,
                'periodOfferTo'             => $periodOfferTo,
                'periodAwardFrom'           => $periodAwardFrom,
                'periodAwardTo'             => $periodAwardTo,
                'buyerName'                 => $buyerName,
                'buyersNames'               => $buyersNames,
                'buyerIdentifier'           => $buyerIdentifier,
                'buyerType'                 => $buyerType,
                'buyerMainGeneralActivity'  => $buyerMainGeneralActivity,
                'buyerMainSectoralActivity' => $buyerMainSectoralActivity,
            ];
            $this->indexDoc($docArr, $docArr['id']);
            die();
        }
    }

    /**
     * Index prozorro tender
     * @param $tender
     */
    public function indexTenderPrz($tender, $cdb) {
        $response = $tender['response'];
        $data = json_decode($response, 1);
        $titlesOrDescriptions = [];
        $classifications = [];
        $periodDeliveryFrom = [];
        $periodDeliveryTo = [];
        $buyersNames = [];
        $id = '';
        $title = '';
        $description = '';
        $buyerName = '';
        $buyerRegion = '';
        $procedureType = '';
        $procedureStatus = '';
        $amount = '';
        $currency = '';
        $publishedDate = null;
        $periodEnquiryFrom = null;
        $periodEnquiryTo = null;
        $periodOfferFrom = null;
        $periodOfferTo = null;
        $periodAuctionFrom = null;
        $periodAuctionTo = null;
        $periodAwardFrom = null;
        $periodAwardTo = null;
        $buyerIdentifier = '';
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
            $amount = $data['data']['value']['amount'];
        }

        if (isset($data['data']['value']['currency']) && $data['data']['value']['currency']) {
            $currency = $data['data']['value']['currency'];
        }

        if (isset($data['data']['enquiryPeriod']['startDate']) && $data['data']['enquiryPeriod']['startDate']) {
            $publishedDate = $data['data']['enquiryPeriod']['startDate'];
        }

        if (isset($data['data']['enquiryPeriod']['startDate']) && $data['data']['enquiryPeriod']['startDate']) {
            $periodEnquiryFrom = $data['data']['enquiryPeriod']['startDate'];
        }

        if (isset($data['data']['enquiryPeriod']['endDate']) && $data['data']['enquiryPeriod']['endDate']) {
            $periodEnquiryTo = $data['data']['enquiryPeriod']['endDate'];
        }

        if (isset($data['data']['tenderPeriod']['startDate']) && $data['data']['tenderPeriod']['startDate']) {
            $periodOfferFrom = $data['data']['tenderPeriod']['startDate'];
        }

        if (isset($data['data']['tenderPeriod']['endDate']) && $data['data']['tenderPeriod']['endDate']) {
            $periodOfferTo = $data['data']['tenderPeriod']['endDate'];
        }

        if (isset($data['data']['auctionPeriod']['startDate']) && $data['data']['auctionPeriod']['startDate']) {
            $periodAuctionFrom = $data['data']['auctionPeriod']['startDate'];
        }

        if (isset($data['data']['auctionPeriod']['endDate']) && $data['data']['auctionPeriod']['endDate']) {
            $periodAuctionTo = $data['data']['auctionPeriod']['endDate'];
        }

        if (isset($data['data']['awardPeriod']['startDate']) && $data['data']['awardPeriod']['startDate']) {
            $periodAwardFrom = $data['data']['awardPeriod']['startDate'];
        }

        if (isset($data['data']['awardPeriod']['endDate']) && $data['data']['awardPeriod']['endDate']) {
            $periodAwardTo = $data['data']['awardPeriod']['endDate'];
        }

        if (isset($data['data']['procuringEntity']['identifier']['id']) && $data['data']['procuringEntity']['identifier']['id']) {
            $buyerIdentifier = $data['data']['procuringEntity']['identifier']['id'];
        }

        if (isset($data['data']['procuringEntity']['name']) && $data['data']['procuringEntity']['name']) {
            $buyerName = $data['data']['procuringEntity']['name'];
            $buyersNames[] = $data['data']['procuringEntity']['name'];
        }

        if (isset($data['data']['procuringEntity']['identifier']['legalName']) && $data['data']['procuringEntity']['identifier']['legalName']) {
            $buyersNames[] = $data['data']['procuringEntity']['identifier']['legalName'];
        }

        if (isset($data['data']['lots']) && is_array($data['data']['lots'])) {
            foreach ($data['data']['lots'] as $lot) {
                if (isset($lot['title']) && $lot['title']) {
                    $titlesOrDescriptions[] = $lot['title'];
                }

                if (isset($lot['description']) && $lot['description']) {
                    $titlesOrDescriptions[] = $lot['description'];
                }

                if (isset($lot['deliveryDate']['startDate']) && $lot['deliveryDate']['startDate']) {
                    $periodDeliveryFrom[] = $lot['deliveryDate']['startDate'];
                }

                if (isset($lot['deliveryDate']['endDate']) && $lot['deliveryDate']['endDate']) {
                    $periodDeliveryTo[] = $lot['deliveryDate']['endDate'];
                }
            }
        }

        if (isset($data['data']['items']) && is_array($data['data']['items'])) {
            foreach ($data['data']['items'] as $item) {
                if (isset($item['description']) && $item['description']) {
                    $titlesOrDescriptions[] = $item['description'];
                }

                if (isset($item['classification']['id']) && $item['classification']['id']) {
                    $classifications[] = $item['classification']['id'];
                }
            }
        }

        $docArr = [
            'cdb'                  => $cdb,
            'id'                   => $id,
            'tenderId'             => $tenderId,
            'title'                => $title,
            'description'          => $description,
            'titlesOrDescriptions' => $titlesOrDescriptions,
            'buyerRegion'          => $buyerRegion,
            'procedureType'        => $procedureType,
            'procedureStatus'      => $procedureStatus,
            'amount'               => $amount,
            'currency'             => $currency,
            'classifications'      => $classifications,
            'publishedDate'        => $publishedDate,
            'periodDeliveryFrom'   => $periodDeliveryFrom,
            'periodDeliveryTo'     => $periodDeliveryTo,
            'periodEnquiryFrom'    => $periodEnquiryFrom,
            'periodEnquiryTo'      => $periodEnquiryTo,
            'periodOfferFrom'      => $periodOfferFrom,
            'periodOfferTo'        => $periodOfferTo,
            'periodAuctionFrom'    => $periodAuctionFrom,
            'periodAuctionTo'      => $periodAuctionTo,
            'periodAwardFrom'      => $periodAwardFrom,
            'periodAwardTo'        => $periodAwardTo,
            'buyerName'            => $buyerName,
            'buyersNames'          => $buyersNames,
            'buyerIdentifier'      => $buyerIdentifier,
        ];
        $this->indexDoc($docArr, $docArr['id']);
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
     * @param $cdb
     */
    public function indexPlanPrz($plan, $cdb) {
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
            'cdb'                        => $cdb,
            'id'                         => $id,
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