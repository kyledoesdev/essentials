<?php

return [
    'timezone' => [
        'local_envs' => explode(',', (string) env('LOCAL_ENVS', 'local,development,dev')),
    ],
];
