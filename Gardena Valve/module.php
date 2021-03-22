<?php

declare(strict_types=1);
include_once __DIR__ . '/../Gardena Device/module.php';
class GardenaValve extends GardenaDevice
{
    protected $metadata = [
        'state' => [
            'displayName'  => 'State',
            'variableType' => VARIABLETYPE_STRING,
            'profile'      => 'Gardena.State'
        ],
        'activity' => [
            'displayName'  => 'Activity',
            'variableType' => VARIABLETYPE_STRING,
            'profile'      => 'Gardena.Valve.Activity',
        ],
        'lastErrorCode' => [
            'displayName'  => 'Last Error',
            'variableType' => VARIABLETYPE_STRING,
            'profile'      => 'Gardena.Valve.Error'
        ],
        'duration' => [
            'displayName'  => 'Duration',
            'variableType' => VARIABLETYPE_INTEGER,
            'profile'      => 'Gardena.Seconds'
        ]
    ];
    protected $exclude = ['name', 'serial', 'modelType'];
    protected $type = 'VALVE';
    protected $control = 'VALVE_CONTROL';
    protected $commands = ['START_SECONDS_TO_OVERRIDE', 'STOP_UNTIL_NEXT_TASK', 'PAUSE', 'UNPAUSE'];
    public function Create()
    {
        parent::Create();

        //Universal for all devices
        if (!IPS_VariableProfileExists('Gardena.State')) {
            IPS_CreateVariableProfile('Gardena.State', VARIABLETYPE_STRING);
            IPS_SetVariableProfileAssociation('Gardena.State', 'OK', $this->Translate('ok'), '', 0x00ff00);
            IPS_SetVariableProfileAssociation('Gardena.State', 'WARNING', $this->Translate('warning'), '', 0xffff00);
            IPS_SetVariableProfileAssociation('Gardena.State', 'UNAVAILABLE', $this->Translate('unavailable'), '', 0xff0000);
            IPS_SetVariableProfileAssociation('Gardena.State', 'ERROR', $this->Translate('error'), '', 0xff0000);
        }

        //VALVE
        if (!IPS_VariableProfileExists('Gardena.Valve.Activity')) {
            IPS_CreateVariableProfile('Gardena.Valve.Activity', VARIABLETYPE_STRING);
            IPS_SetVariableProfileAssociation('Gardena.Valve.Activity', 'CLOSED', $this->Translate('closed'), '', -1);
            IPS_SetVariableProfileAssociation('Gardena.Valve.Activity', 'MANUAL_WATERING', $this->Translate('manual watering'), '', -1);
            IPS_SetVariableProfileAssociation('Gardena.Valve.Activity', 'SCHEDULED_WATERING', $this->Translate('scheduled watering'), '', -1);
        }

        if (!IPS_VariableProfileExists('Gardena.Valve.Error')) {
            IPS_CreateVariableProfile('Gardena.Valve.Error', VARIABLETYPE_STRING);
            IPS_SetVariableProfileAssociation('Gardena.Valve.Error', 'NO_MESSAGE', $this->Translate('no message'), '', 0x00ff00);
            IPS_SetVariableProfileAssociation('Gardena.Valve.Error', 'CONCURRENT_LIMIT_REACHED ', $this->Translate('limit of 2 open valves reached'), '', -1);
            IPS_SetVariableProfileAssociation('Gardena.Valve.Error', 'NOT_CONNECTED ', $this->Translate('not connected'), '', -1);
            IPS_SetVariableProfileAssociation('Gardena.Valve.Error', 'VALVE_CURRENT_MAX_EXCEEDED', $this->Translate('max current exceeded'), '', -1);
            IPS_SetVariableProfileAssociation('Gardena.Valve.Error', 'TOTAL_CURRENT_MAX_EXCEEDED', $this->Translate('max current exceeded'), '', -1);
            IPS_SetVariableProfileAssociation('Gardena.Valve.Error', 'WARERING_CANCELED ', $this->Translate('watering canceled'), '', -1);
            IPS_SetVariableProfileAssociation('Gardena.Valve.Error', 'MASTER_VALVE ', $this->Translate('master valve not connected'), '', -1);
            IPS_SetVariableProfileAssociation('Gardena.Valve.Error', 'WATERING_DURATION_TOO_SHORT  ', $this->Translate('watering canceled'), '', -1);
            IPS_SetVariableProfileAssociation('Gardena.Valve.Error', 'VALVE_BROKEN', $this->Translate('valve damaged'), '', -1);
            IPS_SetVariableProfileAssociation('Gardena.Valve.Error', 'FROST_PREVENTS_STARTING', $this->Translate('frost prevents starting'), '', -1);
            IPS_SetVariableProfileAssociation('Gardena.Valve.Error', 'LOW_BATTERY_PREVENTS_STARTING', $this->Translate('low battery prevents starting'), '', -1);
            IPS_SetVariableProfileAssociation('Gardena.Valve.Error', 'VALVE_POWER_SUPPLY_FAILED', $this->Translate('valve power supply failed'), '', -1);
            IPS_SetVariableProfileAssociation('Gardena.Valve.Error', 'UNKNOWN', $this->Translate('unknown'), '', -1);
        }

        if (!IPS_VariableProfileExists('Gardena.Seconds')) {
            IPS_CreateVariableProfile('Gardena.Seconds', VARIABLETYPE_INTEGER);
            IPS_SetVariableProfileText('Gardena.Seconds', '', $this->Translate(' Seconds'));
        }
    }
}