<?php
return [
    'discovery_url' => '{{eureka_client_serviceUrl_defaultZone}}',
    'budgets_url' => '{{environments_BUDGETS_URL}}',
    'tenders_url' => '{{environments_TENDERS_URL}}',
    'tenders_prz_url' => '{{environments_TENDERS_PRZ_URL}}',
    'plans_prz_url' => '{{environments_PLANS_PRZ_URL}}',

    'elastic_indexing' => '{{environments_ELASTIC_INDEXING}}',
    'elastic_index' => '{{environments_ELASTIC_BUDGETS_INDEX}}',
    'elastic_type' => '{{environments_ELASTIC_BUDGETS_TYPE}}',
    'elastic_url' => '{{environments_ELASTIC_URL}}',

    'sleep_delay_interval' => '{{environments_SLEEP_DELAY_INTERVAL}}',
    'sleep_error_interval' => '{{environments_SLEEP_ERROR_INTERVAL}}',
];
