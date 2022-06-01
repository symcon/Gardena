<?php

declare(strict_types=1);
include_once __DIR__ . '/../libs/GardenaDeviceModule.php';
class GardenaPowerSocket extends GardenaDevice
{
    protected $metadata = [
        'state' => [
            'displayName'  => 'State',
            'variableType' => VARIABLETYPE_STRING,
            'profile'      => 'Gardena.State',
            'position'     => 30
        ],
        'activity' => [
            'displayName'  => 'Activity',
            'variableType' => VARIABLETYPE_STRING,
            'profile'      => 'Gardena.Socket.Activity',
            'position'     => 0

        ],
        'lastErrorCode' => [
            'displayName'  => 'Last Error',
            'variableType' => VARIABLETYPE_STRING,
            'profile'      => 'Gardena.Socket.Error',
            'position'     => 20

        ],
        'duration' => [
            'displayName'  => 'Remaining',
            'variableType' => VARIABLETYPE_INTEGER,
            'profile'      => 'Gardena.Seconds',
            'position'     => 10
        ]
    ];
    protected $exclude = ['name', 'serial', 'modelType'];
    protected $type = 'POWER_SOCKET';
    protected $control = 'POWER_SOCKET_CONTROL';

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
            IPS_SetVariableProfileIcon('Gardena.Seconds', 'Clock');
            IPS_SetVariableProfileAssociation('Gardena.Seconds', 0, '%d', '', -1);
            IPS_SetVariableProfileAssociation('Gardena.Seconds', 1, $this->Translate('%d seconds'), '', -1);
        }

        if (!IPS_VariableProfileExists('Gardena.PowerSocket.Commands')) {
            IPS_CreateVariableProfile('Gardena.PowerSocket.Commands', VARIABLETYPE_STRING);
            IPS_SetVariableProfileIcon('Gardena.PowerSocket.Commands', 'Execute');
            IPS_SetVariableProfileAssociation('Gardena.PowerSocket.Commands', 'START_SECONDS_TO_OVERRIDE', $this->Translate('temporary on'), '', -1);
            IPS_SetVariableProfileAssociation('Gardena.PowerSocket.Commands', 'START_OVERRIDE', $this->Translate('on'), '', -1);
            IPS_SetVariableProfileAssociation('Gardena.PowerSocket.Commands', 'STOP_UNTIL_NEXT_TASK', $this->Translate('off'), '', -1);
        }

        if (!IPS_VariableProfileExists('Gardena.Command.Minutes')) {
            IPS_CreateVariableProfile('Gardena.Command.Minutes', VARIABLETYPE_INTEGER);
            IPS_SetVariableProfileIcon('Gardena.Command.Minutes', 'Clock');
            IPS_SetVariableProfileText('Gardena.Command.Minutes', '', $this->Translate(' Minutes'));
            IPS_SetVariableProfileValues('Gardena.Command.Minutes', 1, 360, 1);
        }

        //We are currently not sure how the pausing works | no info in docs or app
        // if (!IPS_VariableProfileExists('Gardena.PowerSocket.Commands.Schedule')) {
        //     IPS_CreateVariableProfile('Gardena.PowerSocket.Commands.Schedule', VARIABLETYPE_STRING);
        //     IPS_SetVariableProfileIcon('Gardena.PowerSocket.Commands.Schedule', 'Calendar');
        //     IPS_SetVariableProfileAssociation('Gardena.PowerSocket.Commands.Schedule', 'PAUSE', $this->Translate('activate'), '', -1);
        //     IPS_SetVariableProfileAssociation('Gardena.PowerSocket.Commands.Schedule', 'UNPAUSE', $this->Translate('deactivate'), '', -1);
        // }

        //On/off commands
        $this->RegisterVariableString('SocketControl', $this->Translate('Action'), 'Gardena.PowerSocket.Commands', 50);
        $this->SetValue('SocketControl', 'STOP_UNTIL_NEXT_TASK');
        $this->EnableAction('SocketControl');
        $this->RegisterVariableInteger('SocketDuration', $this->Translate('Active Duration'), 'Gardena.Command.Minutes', 40);
        if ($this->GetValue('SocketDuration') == 0) {
            $this->SetValue('SocketDuration', 5);
        }
        $this->EnableAction('SocketDuration');

        //Schedule commands
        // $this->RegisterVariableString('ScheduleControl', $this->Translate('Schedule'), 'Gardena.PowerSocket.Commands.Schedule', 70);
        // $this->SetValue('ScheduleControl', 'UNPAUSE');
        // $this->EnableAction('ScheduleControl');
    }

    public function RequestAction($Ident, $Value)
    {
        switch ($Ident) {
            case 'SocketControl':
                $this->ControlService($this->ReadPropertyString('ID'), $Value, 60 * $this->GetValue('SocketDuration'));
                break;

            // case 'ScheduleControl':
            //     $this->ControlService($this->ReadPropertyString('ID'), $Value);
            //     break;

            default:
                $this->SetValue($Ident, $Value);
                break;

        }
    }

    protected function processData($data)
    {
        parent::processData($data);
        //Only process data meant for this intance
        if (!isset($data['type']) || $data['type'] != $this->type) {
            return;
        }
        //Only process data meant matching our id
        if ($data['id'] != $this->ReadPropertyString('ID')) {
            return;
        }

        $attributes = $data['attributes'];
        if (!isset($attributes['duration']) && @$this->GetIDForIdent('duration')) {
            $this->SetTimerInterval('UpdateDuration', 0);
            $this->SetValue('duration', 0);
        }
    }
}