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
        ]
    ];
    protected $exclude = ['name', 'serial', 'modelType'];
    protected $type = 'VALVE';

    public function Create()
    {
        parent::Create();

        //Info about values from https://mips2648.github.io/jeedom-plugins-docs/gardena/en_US/
        if (!IPS_VariableProfileExists('Gardena.Valve.Activity')) {
            IPS_CreateVariableProfile('Gardena.Valve.Activity', VARIABLETYPE_STRING);
            IPS_SetVariableProfileAssociation('Gardena.Valve.Activity', 'CLOSED', $this->Translate('closed'), '', -1);
            IPS_SetVariableProfileAssociation('Gardena.Valve.Activity', 'MANUALE_WATERING', $this->Translate('manual watering'), '', -1);
            IPS_SetVariableProfileAssociation('Gardena.Valve.Activity', 'SCHEDULED_WATERING', $this->Translate('scheduled watering'), '', -1);
        }

        if (!IPS_VariableProfileExists('Gardena.State')) {
            IPS_CreateVariableProfile('Gardena.State', VARIABLETYPE_STRING);
            IPS_SetVariableProfileAssociation('Gardena.State', 'OK', $this->Translate('ok'), '', 0x00ff00);
            IPS_SetVariableProfileAssociation('Gardena.State', 'WARNING', $this->Translate('warning'), '', 0xffff00);
            IPS_SetVariableProfileAssociation('Gardena.State', 'UNAVAILABLE', $this->Translate('unavailable'), '', 0xff0000);
            IPS_SetVariableProfileAssociation('Gardena.State', 'ERROR', $this->Translate('error'), '', 0xff0000);
        }

        //Might be different for each device
        if (!IPS_VariableProfileExists('Gardena.Error')) {
            IPS_CreateVariableProfile('Gardena.Error', VARIABLETYPE_STRING);
            IPS_SetVariableProfileAssociation('Gardena.Error', 'NO_MESSAGE', $this->Translate('no message'), '', 0x00ff00);
            IPS_SetVariableProfileAssociation('Gardena.Error', 'OFF_DISABLED', $this->Translate('off'), '', -1);
        }
    }
}