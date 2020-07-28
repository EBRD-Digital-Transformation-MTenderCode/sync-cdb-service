<?php

namespace rest\modules\v1\controllers\actions\Example;

use rest\components\api\actions\Action;

class ViewAllAction extends Action
{

    public function run()
    {
        $result = [123,123];
        return $result;
    }

}