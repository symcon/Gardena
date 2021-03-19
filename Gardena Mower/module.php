<?php

declare(strict_types=1);
include_once __DIR__ . '/../Gardena Device/module.php';
class GardenaMower extends GardenaDevice
{
    protected $metadata = [
        'activity' => [
            'displayName'  => 'Activity',
            'variableType' => VARIABLETYPE_STRING,
            'profile'      => 'Gardena.Mower.Activity',
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
        'operatingHours' => [
            'displayName'  => 'Operating Hours',
            'variableType' => VARIABLETYPE_INTEGER,
            'profile'      => 'Gardena.Hours'
        ]
    ];
    protected $exclude = ['name', 'serial', 'modelType'];
    protected $type = 'MOWER';

    public function Create()
    {
        parent::Create();

        //Info about values from https://mips2648.github.io/jeedom-plugins-docs/gardena/en_US/
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

        if (!IPS_VariableProfileExists('Gardena.Mower.Activity')) {
            IPS_CreateVariableProfile('Gardena.Mower.Activity', VARIABLETYPE_STRING);
            IPS_SetVariableProfileAssociation('Gardena.Mower.Activity', 'NONE', $this->Translate('none'), '', -1);
            IPS_SetVariableProfileAssociation('Gardena.Mower.Activity', 'PAUSED', $this->Translate('paused'), '', -1);
            IPS_SetVariableProfileAssociation('Gardena.Mower.Activity', 'OK_CUTTING', $this->Translate('cutting'), '', -1);
            IPS_SetVariableProfileAssociation('Gardena.Mower.Activity', 'OK_CUTTING_TIMER_OVERRIDDEN', $this->Translate('manual cutting'), '', -1);
            IPS_SetVariableProfileAssociation('Gardena.Mower.Activity', 'OK_SEARCHING', $this->Translate('searching charging station'), '', -1);
            IPS_SetVariableProfileAssociation('Gardena.Mower.Activity', 'OK_LEAVING', $this->Translate('leaving'), '', -1);
            IPS_SetVariableProfileAssociation('Gardena.Mower.Activity', 'OK_CHARGING', $this->Translate('charging'), '', -1);
            IPS_SetVariableProfileAssociation('Gardena.Mower.Activity', 'PARKED_TIMER', $this->Translate('parked on schedule'), '', -1);
            IPS_SetVariableProfileAssociation('Gardena.Mower.Activity', 'PARKED_PARK_SELECTED', $this->Translate('parked'), '', -1);
            IPS_SetVariableProfileAssociation('Gardena.Mower.Activity', 'PARKED_AUTOTIMER', $this->Translate('parked through SensorControl'), '', -1);
        }

        if (!IPS_VariableProfileExists('Gardena.Hours')) {
            IPS_CreateVariableProfile('Gardena.Hours', VARIABLETYPE_INTEGER);
            IPS_SetVariableProfileText('Gardena.Hours', '', $this->Translate(' Hours'));
        }
    }
}