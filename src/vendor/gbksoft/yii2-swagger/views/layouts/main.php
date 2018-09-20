<?php
use yii\web\View;
use yii\helpers\Html;
use yii\helpers\Url;
use gbksoft\modules\swagger\SwaggerAsset;

/* @var $this \yii\web\View */
/* @var $content string */

SwaggerAsset::register($this);

$this->registerJs("window.API_HISTORY_URL = '" . Url::toRoute('history') . "';", View::POS_HEAD);
$this->registerJs("window.API_JSON_URL = '" . Url::toRoute('json') . "';", View::POS_HEAD);

$js = <<<JS
$(function () {
    var url = window.location.search.match(/url=([^&]+)/);
    if (url && url.length > 1) {
      url = decodeURIComponent(url[1]);
    } else {
      url = window.API_JSON_URL;
    }

    // Pre load translate...
    if(window.SwaggerTranslator) {
      window.SwaggerTranslator.translate();
    }
    window.swaggerUi = new SwaggerUi({
      url: url,
      dom_id: "swagger-ui-container",
      supportedSubmitMethods: ['get', 'post', 'put', 'delete', 'patch'],
      onComplete: function(swaggerApi, swaggerUi){
        if(typeof initOAuth == "function") {
          initOAuth({
            clientId: "your-client-id",
            clientSecret: "your-client-secret-if-required",
            realm: "your-realms",
            appName: "your-app-name",
            scopeSeparator: ",",
            additionalQueryStringParams: {}
          });
        }

        if(window.SwaggerTranslator) {
          window.SwaggerTranslator.translate();
        }

        $('pre code').each(function(i, e) {
          hljs.highlightBlock(e)
        });

        addApiKeyAuthorization();


      },
      onFailure: function(data) {
        log("Unable to Load SwaggerUI");
      },
      docExpansion: "none",
      jsonEditor: false,
      apisSorter: "alpha",
      defaultModelRendering: 'schema',
      showRequestHeaders: true,
      /*,validatorUrl: "http://localhost:8002"*/
    });

    function addApiKeyAuthorization(){
      var key = encodeURIComponent( $('#input_apiKey')[0].value );
      if(key && key.trim() != "") {
          var apiKeyAuth = new SwaggerClient.ApiKeyAuthorization( "Authorization", "Bearer " + key, "header" );
          window.swaggerUi.api.clientAuthorizations.add( "bearer", apiKeyAuth );
          log( "Set bearer token: " + key );
      }
    }

    $('#input_apiKey').change(addApiKeyAuthorization);

    window.swaggerUi.load();

    function log() {
      if ('console' in window) {
        console.log.apply(console, arguments);
      }
    }
});
JS;
$this->registerJs($js, View::POS_END);
?>
<?php $this->beginPage() ?>
<!DOCTYPE html>
<html lang="<?= Yii::$app->language ?>">
<head>
  <meta charset="<?= Yii::$app->charset ?>">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <?= Html::csrfMetaTags() ?>
  <title><?= Html::encode($this->title) ?></title>
  <?php $this->head() ?>
</head>
<body class="swagger-section">
<?php $this->beginBody() ?>
    <?= $content ?>
<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>
