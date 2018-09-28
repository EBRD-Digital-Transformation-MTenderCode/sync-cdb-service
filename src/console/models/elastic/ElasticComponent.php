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
                'entityId' => ['type' => 'keyword'],
                'title' => ['type' => 'text'],
                'description' => ['type' => 'text'],
                'titlesOrDescriptions' => ['type' => 'text', 'analyzer' => 'ngram_analyzer'],
                'titlesOrDescriptionsStrict' => ['type' => 'text'],
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
                'id' => ['type' => 'keyword'],
                'title' => ['type' => 'text'],
                'description' => ['type' => 'text'],
                'titlesOrDescriptions' => ['type' => 'text'],
                'titlesOrDescriptionsStrict' => ['type' => 'text'],
                'buyerRegion' => ['type' => 'keyword'],
                'budgetStatuses' => ['type' => 'keyword'],
                'amount' => ['type' => 'scaled_float', 'scaling_factor' => 100],
                'currency' => ['type' => 'keyword'],
                'classifications' => ['type' => 'keyword'],
                'periodPlanningFrom' => ['type' => 'date'],
                'periodPlanningTo' => ['type' => 'date'],
                'buyerName' => ['type' => 'text'],
                'buyersNames' => ['type' => 'text'],
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
        $mapArr = [
            'dynamic' => 'strict',
            '_all' => ['enabled' => false],
            'properties' => [
                'id' => ['type' => 'keyword'],
                'entityId' => ['type' => 'keyword'],
                'procedureType' => ['type' => 'keyword'],
                'amount' => ['type' => 'scaled_float', 'scaling_factor' => 100],
                'classifications' => ['type' => 'keyword'],
                'periodEnquiryFrom' => ['type' => 'date'],
                'periodEnquiryTo' => ['type' => 'date'],
                'cdb' => ['type' => 'keyword'],
                'titlesOrDescriptionsStrict' => ['type' => 'text'],
                'titlesOrDescriptions' => ['type' => 'text', 'analyzer' => 'ngram_analyzer'],
                'periodTenderFrom' => ['type' => 'date'],
                'periodDeliveryFrom' => ['type' => 'date'],
                'periodDeliveryTo' => ['type' => 'date'],
                'buyersNames' => ['type' => 'text', 'analyzer' => 'ngram_analyzer'],
                'buyerIdentifier' => ['type' => 'keyword'],
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
        $id = $tender['tender_id'];
        $stageId = $tender['stageId'];
        $title = '';
        $description = '';
        $buyerRegion = '';
        $buyerName = '';
        $buyerIdentifier = '';
        $buyerType = '';
        $buyerMainGeneralActivity = '';
        $buyerMainSectoralActivity = '';
        $amount = 0;
        $titlesOrDescriptions = [];
        $deliveriesRegions = [];
        $classifications = [];
        $periodDeliveryFrom = [];
        $periodDeliveryTo = [];
        $buyersNames = [];

        $ms = [];
        $stage = [];

        //get stage and ms item
        foreach ($tender['records'] as $record) {
            if ($record['ocid'] == $id) {
                $ms = $record;
            }

            if ($record['ocid'] == $stageId) {
                $stage = $record;
            }
        }

        //create data array and index doc
        if (!empty($ms) && !empty($stage)) {
            if (!empty($ms['compiledRelease']['tender']['title'])) {
                $title = $ms['compiledRelease']['tender']['title'];
                $titlesOrDescriptions[$title] = $title;
            }

            if (!empty($ms['compiledRelease']['tender']['description'])) {
                $description = $ms['compiledRelease']['tender']['description'];
                $titlesOrDescriptions[$description] = $description;
            }

            $procedureType = $ms['compiledRelease']['tender']['procurementMethodDetails'] ?? '';
            $procedureStatus = $ms['compiledRelease']['tender']['statusDetails'] ?? '';
            $currency = $ms['compiledRelease']['tender']['value']['currency'] ?? '';
            $publishedDate = $tender['releasePackage']['publishedDate'] ?? null;
            $periodEnquiryFrom = $stage['compiledRelease']['tender']['enquiryPeriod']['startDate'] ?? null;
            $periodEnquiryTo = $stage['compiledRelease']['tender']['enquiryPeriod']['endDate'] ?? null;
            $periodOfferFrom = $stage['compiledRelease']['tender']['tenderPeriod']['startDate'] ?? null;
            $periodOfferTo = $stage['compiledRelease']['tender']['tenderPeriod']['endDate'] ?? null;
            $periodAwardFrom = $stage['compiledRelease']['tender']['awardPeriod']['startDate'] ?? null;
            $periodAwardTo = $stage['compiledRelease']['tender']['awardPeriod']['endDate'] ?? null;

            if (isset($ms['compiledRelease']['tender']['value']['amount'])) {
                $amount = $ms['compiledRelease']['tender']['value']['amount'];
            }

            if (isset($ms['compiledRelease']['parties']) && is_array($ms['compiledRelease']['parties'])) {
                foreach ($ms['compiledRelease']['parties'] as $part) {
                    if (in_array(self::ROLE_BUYER, $part['roles'])) {
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

            if (isset($stage['compiledRelease']['tender']['lots']) && is_array($stage['compiledRelease']['tender']['lots'])) {
                foreach ($stage['compiledRelease']['tender']['lots'] as $lot) {
                    if (!empty($lot['title'])) {
                        $titlesOrDescriptions[$lot['title']] = $lot['title'];
                    }

                    if (!empty($lot['description'])) {
                        $titlesOrDescriptions[$lot['description']] = $lot['description'];
                    }

                    if (!empty($lot['placeOfPerformance']['address']['addressDetails']['region']['description'])) {
                        $deliveriesRegions[$lot['placeOfPerformance']['address']['addressDetails']['region']['description']] = $lot['placeOfPerformance']['address']['addressDetails']['region']['description'];
                    }

                    if (!empty($lot['contractPeriod']['startDate'])) {
                        $periodDeliveryFrom[$lot['contractPeriod']['startDate']] = $lot['contractPeriod']['startDate'];
                    }

                    if (!empty($lot['contractPeriod']['endDate'])) {
                        $periodDeliveryTo[$lot['contractPeriod']['endDate']] = $lot['contractPeriod']['endDate'];
                    }
                }
            }

            if (isset($stage['compiledRelease']['tender']['items']) && is_array($stage['compiledRelease']['tender']['items'])) {
                foreach ($stage['compiledRelease']['tender']['items'] as $item) {
                    if (!empty($item['description'])) {
                        $titlesOrDescriptions[$item['description']] = $item['description'];
                    }

                    if (!empty($item['classification']['id'])) {
                        $classifications[$item['classification']['id']] = $item['classification']['id'];
                    }
                }
            }

            $docArr = [
                'cdb'                       => $cdb,
                'id'                        => $id,
                'entityId'                  => $id,
                'title'                     => $title,
                'description'               => $description,
                'titlesOrDescriptions'      => array_values($titlesOrDescriptions),
                'titlesOrDescriptionsStrict'=> array_values($titlesOrDescriptions),
                'buyerRegion'               => $buyerRegion,
                'deliveriesRegions'         => array_values($deliveriesRegions),
                'procedureType'             => $procedureType,
                'procedureStatus'           => $procedureStatus,
                'amount'                    => $amount,
                'currency'                  => $currency,
                'classifications'           => array_values($classifications),
                'publishedDate'             => $publishedDate,
                'periodDeliveryFrom'        => array_values($periodDeliveryFrom),
                'periodDeliveryTo'          => array_values($periodDeliveryTo),
                'periodEnquiryFrom'         => $periodEnquiryFrom,
                'periodEnquiryTo'           => $periodEnquiryTo,
                'periodOfferFrom'           => $periodOfferFrom,
                'periodOfferTo'             => $periodOfferTo,
                'periodAwardFrom'           => $periodAwardFrom,
                'periodAwardTo'             => $periodAwardTo,
                'buyerName'                 => $buyerName,
                'buyersNames'               => array_values($buyersNames),
                'buyerIdentifier'           => $buyerIdentifier,
                'buyerType'                 => $buyerType,
                'buyerMainGeneralActivity'  => $buyerMainGeneralActivity,
                'buyerMainSectoralActivity' => $buyerMainSectoralActivity,
            ];
            $this->indexDoc($docArr, $docArr['id']);
        }
    }

    /**
     * Index prozorro tender
     * @param $tender
     */
    public function indexTenderPrz($tender, $cdb) {
        $response = $tender['response'];
        $data = json_decode($response, 1);
        $id = $data['data']['id'];
        $title = '';
        $description = '';
        $buyerName = '';
        $amount = 0;
        $titlesOrDescriptions = [];
        $classifications = [];
        $periodDeliveryFrom = [];
        $periodDeliveryTo = [];
        $buyersNames = [];

        if (!empty($data['data']['title'])) {
            $title = $data['data']['title'];
            $titlesOrDescriptions[$title] = $title;
        }

        if (!empty($data['data']['description'])) {
            $description = $data['data']['description'];
            $titlesOrDescriptions[$description] = $description;
        }

        $entityId = $data['data']['tenderID'] ?? '';
        $procedureType = $data['data']['procurementMethodType'] ?? '';
        $procedureStatus = $data['data']['status'] ?? '';
        $buyerRegion = $data['data']['procuringEntity']['address']['region'] ?? '';

        if (isset($data['data']['value']['amount'])) {
            $amount = $data['data']['value']['amount'];
        }

        $currency = $data['data']['value']['currency'] ?? '';

        $publishedDate = $data['data']['enquiryPeriod']['startDate'] ?? null;
        $periodEnquiryFrom = $data['data']['enquiryPeriod']['startDate'] ?? null;
        $periodEnquiryTo = $data['data']['enquiryPeriod']['endDate'] ?? null;
        $periodOfferFrom = $data['data']['tenderPeriod']['startDate'] ?? null;
        $periodOfferTo = $data['data']['tenderPeriod']['endDate'] ?? null;
        $periodAuctionFrom = $data['data']['auctionPeriod']['startDate'] ?? null;
        $periodAuctionTo = $data['data']['auctionPeriod']['endDate'] ?? null;
        $periodAwardFrom = $data['data']['awardPeriod']['startDate'] ?? null;
        $periodAwardTo = $data['data']['awardPeriod']['endDate'] ?? null;

        $buyerIdentifier = $data['data']['procuringEntity']['identifier']['id'] ?? '';

        if (!empty($data['data']['procuringEntity']['name'])) {
            $buyerName = $data['data']['procuringEntity']['name'];
            $buyersNames[$data['data']['procuringEntity']['name']] = $data['data']['procuringEntity']['name'];
        }

        if (!empty($data['data']['procuringEntity']['identifier']['legalName'])) {
            $buyersNames[$data['data']['procuringEntity']['identifier']['legalName']] = $data['data']['procuringEntity']['identifier']['legalName'];
        }

        if (isset($data['data']['lots']) && is_array($data['data']['lots'])) {
            foreach ($data['data']['lots'] as $lot) {
                if (!empty($lot['title'])) {
                    $titlesOrDescriptions[$lot['title']] = $lot['title'];
                }

                if (!empty($lot['description'])) {
                    $titlesOrDescriptions[$lot['description']] = $lot['description'];
                }

                if (!empty($lot['deliveryDate']['startDate'])) {
                    $periodDeliveryFrom[$lot['deliveryDate']['startDate']] = $lot['deliveryDate']['startDate'];
                }

                if (!empty($lot['deliveryDate']['endDate'])) {
                    $periodDeliveryTo[$lot['deliveryDate']['endDate']] = $lot['deliveryDate']['endDate'];
                }
            }
        }

        if (isset($data['data']['items']) && is_array($data['data']['items'])) {
            foreach ($data['data']['items'] as $item) {
                if (!empty($item['description'])) {
                    $titlesOrDescriptions[$item['description']] = $item['description'];
                }

                if (!empty($item['classification']['id'])) {
                    $classifications[$item['classification']['id']] = $item['classification']['id'];
                }
            }
        }

        $docArr = [
            'cdb'                        => $cdb,
            'id'                         => $id,
            'entityId'                   => $entityId,
            'title'                      => $title,
            'description'                => $description,
            'titlesOrDescriptions'       => array_values($titlesOrDescriptions),
            'titlesOrDescriptionsStrict' => array_values($titlesOrDescriptions),
            'buyerRegion'                => $buyerRegion,
            'procedureType'              => $procedureType,
            'procedureStatus'            => $procedureStatus,
            'amount'                     => $amount,
            'currency'                   => $currency,
            'classifications'            => array_values($classifications),
            'publishedDate'              => $publishedDate,
            'periodDeliveryFrom'         => array_values($periodDeliveryFrom),
            'periodDeliveryTo'           => array_values($periodDeliveryTo),
            'periodEnquiryFrom'          => $periodEnquiryFrom,
            'periodEnquiryTo'            => $periodEnquiryTo,
            'periodOfferFrom'            => $periodOfferFrom,
            'periodOfferTo'              => $periodOfferTo,
            'periodAuctionFrom'          => $periodAuctionFrom,
            'periodAuctionTo'            => $periodAuctionTo,
            'periodAwardFrom'            => $periodAwardFrom,
            'periodAwardTo'              => $periodAwardTo,
            'buyerName'                  => $buyerName,
            'buyersNames'                => array_values($buyersNames),
            'buyerIdentifier'            => $buyerIdentifier,
        ];
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
        $periodPlanningFrom = null;
        $periodPlanningTo = null;
        $titlesOrDescriptions = [];
        $budgetStatuses = [];
        $buyersNames = [];
        $classifications = [];

        foreach ($records as $record) {
            //EI
            if ($record['ocid'] == $budget['ocid']) {
                $id = $record['ocid'];
                $data = $record['compiledRelease'];

                $classifications[] = $data['tender']['classification']['id'] ?? '';

                if (!empty($data['tender']['title'])) {
                    $title = $data['tender']['title'];
                    $titlesOrDescriptions[$title] = $title;
                }

                if (!empty($data['tender']['description'])) {
                    $title = $data['tender']['description'];
                    $titlesOrDescriptions[$title] = $title;
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

            //STAGE
            if ($record['ocid'] == $budget['stageId']) {
                $data = $record['compiledRelease'];

                $amount = $data['planning']['budget']['amount']['amount'] ?? 0;
                $currency = $data['planning']['budget']['amount']['currency'] ?? '';
                $periodPlanningFrom = $data['planning']['budget']['period']['startDate'] ?? null;
                $periodPlanningTo = $data['planning']['budget']['period']['endDate'] ?? null;

                if (isset($data['tag']) && is_array($data['tag'])) {
                    foreach ($data['tag'] as $tag) {
                        $budgetStatuses[$tag] = $tag;
                    }
                }
            }
        }

        $docArr = [
            'id'                         => $id,
            'title'                      => $title,
            'description'                => $description,
            'titlesOrDescriptions'       => array_values($titlesOrDescriptions),
            'titlesOrDescriptionsStrict' => array_values($titlesOrDescriptions),
            'buyerRegion'                => $buyerRegion,
            'budgetStatuses'             => array_values($budgetStatuses),
            'amount'                     => $amount,
            'currency'                   => $currency,
            'classifications'            => array_values($classifications),
            'periodPlanningFrom'         => $periodPlanningFrom,
            'periodPlanningTo'           => $periodPlanningTo,
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
     * Index prozorro plan
     * @param $plan
     * @param $cdb
     */
    public function indexPlanPrz($plan, $cdb) {
        $response = $plan['response'];

        $data = json_decode($response, 1);
        $data = $data['data'];
        $id = $data['id'];
        $periodDeliveryFrom = [];
        $periodDeliveryTo = [];

//        if (stripos($response, "Delivery")) {
//            //echo "<pre>" . print_r($plan['id'],1) . "</pre>";
//            echo print_r($plan['id'],1) . "\n";
//        }

        $entityId = $data['planID'];
        $procedureType = $data['tender']['procurementMethodType'] ?? '';
        $amount = $data['budget']['amount'] ?? 0;
        $classifications = $data['classification']['id'] ?? '';
        $titlesOrDescriptions = [];
        $buyersNames = [];

        if (!empty($data['classification']['title'])) {
            $titlesOrDescriptions[
                $data['classification']['title']
            ] = $data['classification']['title'];
        }
        if (!empty($data['classification']['description'])) {
            $titlesOrDescriptions[
                $data['classification']['description']
            ] = $data['classification']['description'];
        }
        if (isset($data['lots']) && is_array($data['lots'])) {
            foreach ($data['lots'] as $item) {
                if (!empty(($item['title']))) {
                    $titlesOrDescriptions[$item['title']] = $item['title'];
                }
                if (!empty($item['description'])) {
                    $titlesOrDescriptions[$item['description']] = $item['description'];
                }
            }
        }
        if (isset($data['items']) && is_array($data['items'])) {
            foreach ($data['items'] as $item) {
                if (!empty($item['description'])) {
                    $titlesOrDescriptions[$item['description']] = $item['description'];
                }

                if (!empty($item['deliveryDate']['startDate'])) {
                    $periodDeliveryFrom[$item['deliveryDate']['startDate']] = $item['deliveryDate']['startDate'];
                }

                if (!empty($item['deliveryDate']['endDate'])) {
                    $periodDeliveryTo[$item['deliveryDate']['endDate']] = $item['deliveryDate']['endDate'];
                }

            }
        }

        $periodTenderStartDate = $data['tender']['tenderPeriod']['startDate'] ?? null;

        if (!empty($data['procuringEntity']['name'])) {
            $buyersNames[$data['procuringEntity']['name']] = $data['procuringEntity']['name'];
        }

        if (!empty($data['procuringEntity']['identifier']['legalName'])) {
            $buyersNames[$data['procuringEntity']['identifier']['legalName']] = $data['procuringEntity']['identifier']['legalName'];
        }

        $buyerIdentifier = $data['procuringEntity']['identifier']['id'] ?? '';

        $docArr = [
            'cdb'                        => $cdb,
            'id'                         => $id,
            'entityId'                   => $entityId,
            'procedureType'              => $procedureType,
            'amount'                     => $amount,
            'titlesOrDescriptions'       => array_values($titlesOrDescriptions),
            'titlesOrDescriptionsStrict' => array_values($titlesOrDescriptions),
            'classifications'            => $classifications,
            'periodTenderFrom'           => $periodTenderStartDate,
            'periodDeliveryFrom'         => array_values($periodDeliveryFrom),
            'periodDeliveryTo'           => array_values($periodDeliveryTo),
            'buyersNames'                => array_values($buyersNames),
            'buyerIdentifier'            => $buyerIdentifier,
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