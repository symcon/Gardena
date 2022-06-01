<?php

declare(strict_types=1);
include_once __DIR__ . '/../libs/GardenaDeviceModule.php';
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
        ]
    ];
    protected $exclude = ['name', 'serial', 'modelType'];
    protected $type = 'SENSOR';
}