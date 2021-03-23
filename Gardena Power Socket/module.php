<?php

declare(strict_types=1);
include_once __DIR__ . '/../Gardena Device/module.php';
class GardenaPowerSocket extends GardenaDevice
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
            'profile'      => 'Gardena.Socket.Activity',
        ],
        'lastErrorCode' => [
            'displayName'  => 'Last Error',
            'variableType' => VARIABLETYPE_STRING,
            'profile'      => 'Gardena.Socket.Error'
        ],
        'duration' => [
            'displayName'  => 'Duration',
            'variableType' => VARIABLETYPE_INTEGER,
            'profile'      => 'Gardena.Seconds'
        ]
    ];
    protected $exclude = ['name', 'serial', 'modelType'];
    protected $type = 'POWER_SOCKET';

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

        //POWER_SOCKET
        if (!IPS_VariableProfileExists('Gardena.Socket.Activity')) {
            IPS_CreateVariableProfile('Gardena.Socket.Activity', VARIABLETYPE_STRING);
            IPS_SetVariableProfileAssociation('Gardena.Socket.Activity', 'OFF', $this->Translate('off'), '', -1);
            IPS_SetVariableProfileAssociation('Gardena.Socket.Activity', 'FOREVER_ON', $this->Translate('physical on'), '', -1);
            IPS_SetVariableProfileAssociation('Gardena.Socket.Activity', 'TIME_LIMITED_ON', $this->Translate('manual on'), '', -1);
            IPS_SetVariableProfileAssociation('Gardena.Socket.Activity', 'SCHEDULED_ON', $this->Translate('scheduled on'), '', -1);
        }

        if (!IPS_VariableProfileExists('Gardena.Socket.Error')) {
            IPS_CreateVariableProfile('Gardena.Socket.Error', VARIABLETYPE_STRING);
            IPS_SetVariableProfileAssociation('Gardena.Socket.Error', 'TIMER_CANCELLED', $this->Translate('action cancelled'), '', 0x00ff00);
            IPS_SetVariableProfileAssociation('Gardena.Socket.Error', 'UNKNOWN', $this->Translate('unknown'), '', -1);
        }

        if (!IPS_VariableProfileExists('Gardena.Seconds')) {
            IPS_CreateVariableProfile('Gardena.Seconds', VARIABLETYPE_INTEGER);
            IPS_SetVariableProfileText('Gardena.Seconds', '', $this->Translate(' Seconds'));
        }
    }
}