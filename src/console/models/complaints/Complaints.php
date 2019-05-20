<?php
namespace console\models\complaints;

use Yii;

/**
 * Class Complaints
 * @package console\models\complaints
 */
class Complaints
{
    /**
     * Get offset url
     * @param $offset
     * @return string
     */
    public static function getOffsetUrl($offset)
    {
        return Yii::$app->params['complaints_url'] . ($offset ? '&offset=' . $offset : '');
    }
}