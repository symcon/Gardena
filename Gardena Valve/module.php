<?php

declare(strict_types=1);
include_once __DIR__ . '/../Gardena Device/module.php';
class GardenaValve extends GardenaDevice
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
            'profile'      => 'Gardena.Valve.Activity',
            'position'     => 0
        ],
        'lastErrorCode' => [
            'displayName'  => 'Last Error',
            'variableType' => VARIABLETYPE_STRING,
            'profile'      => 'Gardena.Valve.Error',
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
    protected $type = 'VALVE';
    protected $control = 'VALVE_CONTROL';
    public function Create()
    {
        parent::Create();

        //Universal for all devices
        if (!IPS_VariableProfileExists('Gardena.State')) {
            IPS_CreateVariableProfile('Gardena.State', VARIABLETYPE_STRING);
            IPS_SetVariableProfileIcon('Gardena.State', 'Information');
            IPS_SetVariableProfileAssociation('Gardena.State', 'OK', $this->Translate('ok'), '', 0x00ff00);
            IPS_SetVariableProfileAssociation('Gardena.State', 'WARNING', $this->Translate('warning'), '', 0xffff00);
            IPS_SetVariableProfileAssociation('Gardena.State', 'UNAVAILABLE', $this->Translate('unavailable'), '', 0xff0000);
            IPS_SetVariableProfileAssociation('Gardena.State', 'ERROR', $this->Translate('error'), '', 0xff0000);
        }

        //VALVE
        if (!IPS_VariableProfileExists('Gardena.Valve.Activity')) {
            IPS_CreateVariableProfile('Gardena.Valve.Activity', VARIABLETYPE_STRING);
            IPS_SetVariableProfileIcon('Gardena.Valve.Activity', 'Power');
            IPS_SetVariableProfileAssociation('Gardena.Valve.Activity', 'CLOSED', $this->Translate('closed'), '', -1);
            IPS_SetVariableProfileAssociation('Gardena.Valve.Activity', 'MANUAL_WATERING', $this->Translate('manual watering'), '', -1);
            IPS_SetVariableProfileAssociation('Gardena.Valve.Activity', 'SCHEDULED_WATERING', $this->Translate('scheduled watering'), '', -1);
        }

        if (!IPS_VariableProfileExists('Gardena.Valve.Error')) {
            IPS_CreateVariableProfile('Gardena.Valve.Error', VARIABLETYPE_STRING);
            IPS_SetVariableProfileIcon('Gardena.Valve.Error', 'Warning');
            IPS_SetVariableProfileAssociation('Gardena.Valve.Error', 'NO_MESSAGE', $this->Translate('no message'), '', 0x00ff00);
            IPS_SetVariableProfileAssociation('Gardena.Valve.Error', 'CONCURRENT_LIMIT_REACHED ', $this->Translate('limit of 2 open valves reached'), '', -1);
            IPS_SetVariableProfileAssociation('Gardena.Valve.Error', 'NOT_CONNECTED ', $this->Translate('not connected'), '', -1);
            IPS_SetVariableProfileAssociation('Gardena.Valve.Error', 'VALVE_CURRENT_MAX_EXCEEDED', $this->Translate('max current exceeded'), '', -1);
            IPS_SetVariableProfileAssociation('Gardena.Valve.Error', 'TOTAL_CURRENT_MAX_EXCEEDED', $this->Translate('max current exceeded'), '', -1);
            IPS_SetVariableProfileAssociation('Gardena.Valve.Error', 'WATERING_CANCELED ', $this->Translate('watering canceled'), '', -1);
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
            IPS_SetVariableProfileIcon('Gardena.Seconds', 'Power');
            IPS_SetVariableProfileAssociation('Gardena.Seconds', 0, '%d', '', -1);
            IPS_SetVariableProfileAssociation('Gardena.Seconds', 1, $this->Translate('%d seconds'), '', -1);
        }

        if (!IPS_VariableProfileExists('Gardena.Valve.Commands')) {
            IPS_CreateVariableProfile('Gardena.Valve.Commands', VARIABLETYPE_STRING);
            IPS_SetVariableProfileIcon('Gardena.Valve.Commands', 'Exceute');
            IPS_SetVariableProfileAssociation('Gardena.Valve.Commands', 'START_SECONDS_TO_OVERRIDE', $this->Translate('open'), '', -1);
            IPS_SetVariableProfileAssociation('Gardena.Valve.Commands', 'STOP_UNTIL_NEXT_TASK', $this->Translate('close'), '', -1);
        }

        if (!IPS_VariableProfileExists('Gardena.Command.Minutes')) {
            IPS_CreateVariableProfile('Gardena.Command.Minutes', VARIABLETYPE_INTEGER);
            IPS_SetVariableProfileIcon('Gardena.Command.Minutes', 'Clock');
            IPS_SetVariableProfileText('Gardena.Command.Minutes', '', $this->Translate(' Minutes'));
            IPS_SetVariableProfileValues('Gardena.Command.Minutes', 1, 360, 1);
        }

        if (!IPS_VariableProfileExists('Gardena.Valve.Commands.Schedule')) {
            IPS_CreateVariableProfile('Gardena.Valve.Commands.Schedule', VARIABLETYPE_STRING);
            IPS_SetVariableProfileIcon('Gardena.Valve.Commands.Schedule', 'Calendar');
            IPS_SetVariableProfileAssociation('Gardena.Valve.Commands.Schedule', 'PAUSE', $this->Translate('activate'), '', -1);
            IPS_SetVariableProfileAssociation('Gardena.Valve.Commands.Schedule', 'UNPAUSE', $this->Translate('deactivate'), '', -1);
        }

        //Open close commands
        $this->RegisterVariableString('ValveControl', $this->Translate('Action'), 'Gardena.Valve.Commands', 50);
        $this->SetValue('ValveControl', 'STOP_UNTIL_NEXT_TASK');
        $this->EnableAction('ValveControl');
        $this->RegisterVariableInteger('ValveDuration', $this->Translate('Open Duration'), 'Gardena.Command.Minutes', 40);
        $this->SetValue('ValveDuration', 5);
        $this->EnableAction('ValveDuration');

        //Schedule commands
        $this->RegisterVariableString('ScheduleControl', $this->Translate('Schedule'), 'Gardena.Valve.Commands.Schedule', 70);
        $this->SetValue('ScheduleControl', 'UNPAUSE');
        $this->EnableAction('ScheduleControl');
    }

    public function RequestAction($Ident, $Value)
    {
        switch ($Ident) {
            case 'ValveControl':
                switch ($Value) {
                    case 'START_SECONDS_TO_OVERRIDE':
                        $this->OpenValve($this->GetValue('ValveDuration'));
                        break;

                    case 'STOP_UNTIL_NEXT_TASK':
                        $this->CloseValve();
                        break;

                    default:
                        throw new Exception(sprintf('Invalid Command: %s'), $Value);
                }
                break;

            case 'ScheduleControl':
                switch ($Value) {
                    case 'PAUSE':
                        $this->DeactivateSchedule();
                        break;

                    case 'UNPAUSE':
                        $this->ActivateSchedule();
                        break;

                    default:
                        throw new Exception(sprintf('Invalid Command: %s'), $Value);

                }
                break;

            default:
                break;

        }

        $this->SetValue($Ident, $Value);
    }

    public function OpenValve(int $Minutes)
    {
        $this->ControlService($this->ReadPropertyString('ID'), 'START_SECONDS_TO_OVERRIDE', 60 * $Minutes);
    }

    public function CloseValve()
    {
        $this->ControlService($this->ReadPropertyString('ID'), 'STOP_UNTIL_NEXT_TASK');
    }

    public function ActivateSchedule()
    {
        $this->ControlService($this->ReadPropertyString('ID'), 'UNPAUSE');
    }

    public function DeactivateSchedule()
    {
        $this->ControlService($this->ReadPropertyString('ID'), 'PAUSE');
    }

    protected function processData($data)
    {
        parent::processData($data);
        //Only process data meant for this intance
        if ($data['type'] != $this->type) {
            return;
        }
        //Only process data meant matching our id
        if ($data['id'] != $this->ReadPropertyString('ID')) {
            return;
        }

        $attributes = $data['attributes'];
        if (!isset($attributes['duration']) && @IPS_GetObjectIDByIdent('duration', $this->InstanceID)) {
            $this->SetTimerInterval('UpdateDuration', 0);
            $this->SetValue('duration', 0);
        }
    }
}