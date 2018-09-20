<?php

namespace rest\modules\v1\controllers;

use Yii;
use rest\components\api\Controller;
use rest\modules\v1\controllers\actions\Example\ViewAction;
use rest\modules\v1\controllers\actions\Example\ViewAllAction;
use rest\modules\v1\controllers\actions\Example\UpdateAction;
use rest\modules\v1\controllers\actions\Example\IndexAction;
use rest\modules\v1\controllers\actions\Example\DeleteAction;

class ExampleController extends Controller
{

    public function actions()
    {
        return [
            'view' => [
                'class' => ViewAction::class,
            ],
            'index' => [
                'class' => ViewAllAction::class,
            ],
            'update' => [
                'class' => UpdateAction::class,
            ],
            'create' => [
                'class' => IndexAction::class,
            ],
            'delete' => [
                'class' => DeleteAction::class,
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    protected function verbs()
    {
        return [
            'view' => ['GET'],
            'index' => ['GET'],
            'update' => ['PATCH'],
            'create' => ['POST'],
            'delete' => ['DELETE']
        ];
    }

}