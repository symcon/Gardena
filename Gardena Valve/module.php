<?php

declare(strict_types=1);
include_once __DIR__ . '/../Gardena Device/module.php';
class GardenaValve extends GardenaDevice
{
    protected $metadata = [
        'activity' => [
            'displayName'  => 'Activity',
            'variableType' => VARIABLETYPE_STRING,
            'profile'      => 'Gardena.Valve.Activity',
        ],
        'state' => [
            'displayName'  => 'State',
            'variableType' => VARIABLETYPE_STRING,
            'profile'      => 'Gardena.State'
        ],
        'lastErrorCode' => [
            'displayName'  => 'Last Error',
            'variableType' => VARIABLETYPE_STRING,
            'profile'      => 'Gardena.Error'
        ],
        'rfLinkState' => [
            'displayName'  => 'Link State',
            'variableType' => VARIABLETYPE_STRING,
            'profile'      => 'Gardena.ReachableStatus'
        ]
    ];
    protected $exclude = ['name', 'serial', 'modelType'];
    protected $type = 'VALVE';
}