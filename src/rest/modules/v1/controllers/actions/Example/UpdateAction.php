<?php

namespace rest\modules\v1\controllers\actions\Example;

use Yii;
use rest\components\api\actions\Action;

class UpdateAction extends Action
{
    public function run($id)
    {
        $result = 42;
        return $result;
    }

}