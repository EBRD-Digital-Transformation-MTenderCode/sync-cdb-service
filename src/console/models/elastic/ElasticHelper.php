<?php

namespace console\models\elastic;


class ElasticHelper
{
    const ROLE_BUYER = 'buyer';

    public static function getSettings() {
        return
        [
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

    }

    /**
     * @return array
     */
    public static function getTenderMap() {
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
                'modifiedDate' => ['type' => 'date'],
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
                'buyersNames' => ['type' => 'text', 'analyzer' => 'ngram_analyzer'],
                'buyerIdentifier' => ['type' => 'keyword'],
                'buyerType' => ['type' => 'keyword'],
                'buyerMainGeneralActivity' => ['type' => 'keyword'],
                'buyerMainSectoralActivity' => ['type' => 'keyword'],
                'tags' => ['type' => 'keyword'],
            ],
        ];

        return $mapArr;
    }

    /**
     * @return array
     */
    public static function getCpvMap() {
        $mapArr = [
            'dynamic' => 'strict',
            '_all' => ['enabled' => false],
            'properties' => [
                'id' => ['type' => 'text'],
                'name' => [
                    'properties' => [
                        'en' => ['type' => 'text'],
                        'ro' => ['type' => 'text'],
                        'ru' => ['type' => 'text'],
                    ],
                ],
                'idOrName' => [
                    'properties' => [
                        'en' => ['type' => 'text', 'analyzer' => 'ngram_analyzer'],
                        'ro' => ['type' => 'text', 'analyzer' => 'ngram_analyzer'],
                        'ru' => ['type' => 'text', 'analyzer' => 'ngram_analyzer'],
                    ],
                ],
                'idOrNameStrict' => [
                    'properties' => [
                        'en' => ['type' => 'text'],
                        'ro' => ['type' => 'text'],
                        'ru' => ['type' => 'text'],
                    ],
                ],
            ],
        ];

        return $mapArr;
    }

    /**
     * OCDS tender
     *
     * @param $tender
     * @param $cdb
     * @return array|null
     */
    public static function prepareTenderToElastic($tender, $cdb) {
        $id = $tender['item_id'];
        $stageId = $tender['stageId'];
        $title = '';
        $description = '';
        $buyerRegion = '';
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
        $tags = [];

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
            $amount = $ms['compiledRelease']['tender']['value']['amount'] ?? 0;
            $currency = $ms['compiledRelease']['tender']['value']['currency'] ?? '';
            $publishedDate = $tender['releasePackage']['publishedDate'] ?? null;
            $modifiedDate = $ms['compiledRelease']['date'] ?? null;
            $periodEnquiryFrom = $stage['compiledRelease']['tender']['enquiryPeriod']['startDate'] ?? null;
            $periodEnquiryTo = $stage['compiledRelease']['tender']['enquiryPeriod']['endDate'] ?? null;
            $periodOfferFrom = $stage['compiledRelease']['tender']['tenderPeriod']['startDate'] ?? null;
            $periodOfferTo = $stage['compiledRelease']['tender']['tenderPeriod']['endDate'] ?? null;
            $periodAwardFrom = $stage['compiledRelease']['tender']['awardPeriod']['startDate'] ?? null;
            $periodAwardTo = $stage['compiledRelease']['tender']['awardPeriod']['endDate'] ?? null;


            if (!empty($ms['compiledRelease']['tender']['classification']['id'])) {
                $classifications[
                $ms['compiledRelease']['tender']['classification']['id']
                ] = $ms['compiledRelease']['tender']['classification']['id'];
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

            if (isset($stage['compiledRelease']['tag']) && is_array($stage['compiledRelease']['tag'])) {
                foreach ($stage['compiledRelease']['tag'] as $tag) {
                    $tags[$tag] = $tag;
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
                'modifiedDate'              => $modifiedDate,
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
                'tags'                      => array_values($tags),
            ];
        }

        return $docArr ?? null;
    }

    /**
     * Prozorro tender
     *
     * @param $tender
     * @param $cdb
     * @return array|null
     */

    public static function prepareTenderPrzToElastic($tender, $cdb) {
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
        $modifiedDate = $data['data']['dateModified'] ?? null;
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

                if (!empty($item['deliveryDate']['startDate'])) {
                    $periodDeliveryFrom[$item['deliveryDate']['startDate']] = $item['deliveryDate']['startDate'];
                }

                if (!empty($item['deliveryDate']['endDate'])) {
                    $periodDeliveryTo[$item['deliveryDate']['endDate']] = $item['deliveryDate']['endDate'];
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
            'modifiedDate'               => $modifiedDate,
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

        return $docArr ?? null;
    }

    public static function preparePlanPrzToElastic($plan, $cdb) {
        $response = $plan['response'];

        $data = json_decode($response, 1);
        $data = $data['data'];
        $id = $data['id'];
        $title = '';
        $description = '';
        $buyerName = '';
        $periodDeliveryFrom = [];
        $periodDeliveryTo = [];

        $entityId = $data['planID'];
        $modifiedDate = $data['dateModified'] ?? null;
        $procedureType = $data['tender']['procurementMethodType'] ?? '';
        $amount = $data['budget']['amount'] ?? 0;
        $classifications[] = $data['classification']['id'] ?? '';
        $titlesOrDescriptions = [];
        $buyersNames = [];

        if (!empty($data['budget']['description'])) {
            $title = $data['budget']['description'];
            $titlesOrDescriptions[$data['budget']['description']] = $data['budget']['description'];
        }

        if (!empty($data['budget']['notes'])) {
            $description = $data['budget']['notes'];
            $titlesOrDescriptions[$data['budget']['notes']] = $data['budget']['notes'];
        }

        if (!empty($data['classification']['title'])) {
            $titlesOrDescriptions[$data['classification']['title']] = $data['classification']['title'];
        }

        if (!empty($data['classification']['description'])) {
            $titlesOrDescriptions[$data['classification']['description']] = $data['classification']['description'];
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
            $buyerName = $data['procuringEntity']['name'];
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
            'title'                      => $title,
            'description'                => $description,
            'titlesOrDescriptions'       => array_values($titlesOrDescriptions),
            'titlesOrDescriptionsStrict' => array_values($titlesOrDescriptions),
            'classifications'            => $classifications,
            'modifiedDate'               => $modifiedDate,
            'periodEnquiryFrom'          => $periodTenderStartDate,
            'periodDeliveryFrom'         => array_values($periodDeliveryFrom),
            'periodDeliveryTo'           => array_values($periodDeliveryTo),
            'buyerName'                  => $buyerName,
            'buyersNames'                => array_values($buyersNames),
            'buyerIdentifier'            => $buyerIdentifier,
        ];

        return $docArr;
    }

    public static function prepareContractPrzToElastic($contract, $cdb) {
        $response = $contract['response'];

        $data = json_decode($response, 1);
        $data = $data['data'];
        $id = $data['id'];
        $periodDeliveryFrom = [];
        $periodDeliveryTo = [];
        $classifications = [];

        $entityId = $data['contractID'];
        $modifiedDate = $data['dateModified'] ?? null;
        $procedureType = $data['documents']['procurementMethodType'] ?? '';
        $amount = $data['value']['amount'] ?? 0;
        $titlesOrDescriptions = [];
        $title = '';
        $description = '';
        $buyerName = '';
        $buyersNames = [];

        if (!empty($data['title'])) {
            $title = $data['title'];
            $titlesOrDescriptions[$data['title']] = $data['title'];
        }

        if (!empty($data['description'])) {
            $description = $data['description'];
            $titlesOrDescriptions[$data['description']] = $data['description'];
        }

        if (!empty($data['classification']['title'])) {
            $titlesOrDescriptions[$data['classification']['title']] = $data['classification']['title'];
        }
        if (!empty($data['classification']['description'])) {
            $titlesOrDescriptions[$data['classification']['description']] = $data['classification']['description'];
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

                $classifications[] = $item['classification']['id'] ?? '';

            }
        }

        $periodContractStartDate = $data['period']['startDate'] ?? null;

        if (!empty($data['procuringEntity']['name'])) {
            $buyerName = $data['procuringEntity']['name'];
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
            'title'                      => $title,
            'description'                => $description,
            'titlesOrDescriptions'       => array_values($titlesOrDescriptions),
            'titlesOrDescriptionsStrict' => array_values($titlesOrDescriptions),
            'classifications'            => $classifications,
            'modifiedDate'               => $modifiedDate,
            'periodEnquiryFrom'          => $periodContractStartDate,
            'periodDeliveryFrom'         => array_values($periodDeliveryFrom),
            'periodDeliveryTo'           => array_values($periodDeliveryTo),
            'buyerName'                  => $buyerName,
            'buyersNames'                => array_values($buyersNames),
            'buyerIdentifier'            => $buyerIdentifier,
        ];

        return $docArr;
    }

    public static function prepareCpvToElastic($data) {
        $id = $data['id'];
        $name = $data['name'];
        $idOrName = $data['idOrName'];

        $docArr = [
            'id'             => $id,
            'name'           => $name,
            'idOrName'       => $idOrName,
            'idOrNameStrict' => $idOrName,
        ];

        return $docArr;
    }
}