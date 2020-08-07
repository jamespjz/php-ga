<?php
return [
    'index' => "gc-ga",
    'body'  => [
        'settings' => [
            'number_of_shards' => 1,
            'number_of_replicas' => 0,
        ],
        'mappings' => [
            '_source' => [
                'enabled' => true
            ],
            'gc_ga_event_date_detail' => [
                  'properties' => [
                      '﻿ga_date'=> [
                          'type'=>'date',
                      ],
                      '﻿ga_dimension1'=> [
                          'type'=>'string',
                      ],
                      '﻿ga_hostname'=> [
                          'type'=>'string',
                      ],
                      '﻿ga_pagePath'=> [
                          'type'=>'string',
                      ],
                      '﻿ga_dimension3'=> [
                          'type'=>'string',
                      ],
                      '﻿ga_eventAction'=> [
                          'type'=>'string',
                      ],
                      '﻿ga_eventLabel'=> [
                          'type'=>'string',
                      ],
                      '﻿ga_totalEvents'=> [
                          'type'=>'integer',
                      ],
                      '﻿ga_pageviews'=> [
                          'type'=>'integer',
                      ],
                      '﻿ga_users'=> [
                          'type'=>'integer',
                      ],
                  ]
            ],

        ]
    ]
];