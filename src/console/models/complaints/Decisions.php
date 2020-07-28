<?php
namespace console\models\complaints;

use Yii;

/**
 * Class Decisions
 * @package console\models\complaints
 */
class Decisions
{
    /**
     * Get offset url
     * @param $offset
     * @return string
     */
    public static function getOffsetUrl($offset)
    {
        return Yii::$app->params['decisions_url'] . ($offset ? '&offset=' . $offset : '');
    }
}