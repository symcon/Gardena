<?php

declare(strict_types=1);
include_once __DIR__ . '/../Gardena Device/module.php';
class GardenaSensor extends GardenaDevice
{
    protected $metadata = [
        'soilHumidity' => [
            'displayName'  => 'Soil Humidity',
            'variableType' => VARIABLETYPE_INTEGER,
            'profile'      => '~Humidity',
        ],
        'soilTemperature' => [
            'displayName'  => 'Soil Temperature',
            'variableType' => VARIABLETYPE_FLOAT,
            'profile'      => '~Temperature'
        ],
        'ambientTemperature' => [
            'displayName'  => 'Ambient Temperature',
            'variableType' => VARIABLETYPE_FLOAT,
            'profile'      => '~Temperature'
        ],
        'lightIntensity' => [
            'displayName'  => 'Light Intensity',
            'variableType' => VARIABLETYPE_FLOAT,
            'profile'      => '~Illumination.F'
        ],
        'batteryLevel' => [
            'displayName'  => 'Battery Level',
            'variableType' => VARIABLETYPE_INTEGER,
            'profile'      => '~Battery.100',
        ],
        'batteryState' => [
            'displayName'  => 'Battery State',
            'variableType' => VARIABLETYPE_STRING,
            'profile'      => 'Gardena.Battery'
        ],
        'rfLinkLevel' => [
            'displayName'  => 'Link Level',
            'variableType' => VARIABLETYPE_INTEGER,
            'profile'      => '~Intensity.100'
        ],
        'rfLinkState' => [
            'displayName'  => 'Link State',
            'variableType' => VARIABLETYPE_STRING,
            'profile'      => 'Gardena.ReachableStatus'
        ]
    ];
    protected $exclude = ['name', 'serial', 'modelType'];
    protected $type = 'SENSOR';
}