<?php

declare(strict_types=1);
include_once __DIR__ . '/../Gardena Device/module.php';
class GardenaCommon extends GardenaDevice
{
    protected $metadata = [
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
    protected $type = 'COMMON';
}