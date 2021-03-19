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

    public function Create()
    {
        parent::Create();

        //Info about values from https://mips2648.github.io/jeedom-plugins-docs/gardena/en_US/
        if (!IPS_VariableProfileExists('Gardena.Battery')) {
            IPS_CreateVariableProfile('Gardena.Battery', VARIABLETYPE_STRING);
            IPS_SetVariableProfileAssociation('Gardena.Battery', 'OK', $this->Translate('ok'), '', 0x00ff00);
            IPS_SetVariableProfileAssociation('Gardena.Battery', 'LOW', $this->Translate('low'), '', -1);
            IPS_SetVariableProfileAssociation('Gardena.Battery', 'REPLACE_NOW', $this->Translate('replace now'), '', -1);
            IPS_SetVariableProfileAssociation('Gardena.Battery', 'OUT_OF_OPERATION', $this->Translate('out of operation'), '', -1);
            IPS_SetVariableProfileAssociation('Gardena.Battery', 'CHARGING', $this->Translate('charging'), '', -1);
            IPS_SetVariableProfileAssociation('Gardena.Battery', 'NO_BATTERY', $this->Translate('no battery'), '', -1);
            IPS_SetVariableProfileAssociation('Gardena.Battery', 'UNKNOWN', $this->Translate('unknown'), '', -1);
        }

        if (!IPS_VariableProfileExists('Gardena.ReachableStatus')) {
            IPS_CreateVariableProfile('Gardena.ReachableStatus', VARIABLETYPE_STRING);
            IPS_SetVariableProfileAssociation('Gardena.ReachableStatus', 'ONLINE', $this->Translate('online'), '', 0x00ff00);
            IPS_SetVariableProfileAssociation('Gardena.ReachableStatus', 'OFFLINE', $this->Translate('offline'), '', 0xff0000);
            IPS_SetVariableProfileAssociation('Gardena.ReachableStatus', 'UNKNOWN', $this->Translate('unknown'), '', 0xff0000);
        }
    }
}