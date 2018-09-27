<?php
namespace console\models\plans;

use Yii;
use yii\web\HttpException;
use console\models\Curl;

/**
 * Class Plan
 * @package console\models
 */
class Plan
{
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
     * Returns plan with json decoded props and type
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
            $type = explode(self::DIVIDER, substr($actualRelease['ocid'], strlen($item['plan_id'])))[1];
            $item['item_id'] = $item['plan_id'];
            if (in_array($type, self::MARKS)) {
                $item['type'] = $type;
                $item['stageId'] = $actualRelease['ocid'];
                break;
            }

        }

        return $item;
    }

}