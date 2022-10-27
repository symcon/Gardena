<?php

declare(strict_types=1);
include_once __DIR__ . '/../libs/GardenaDeviceModule.php';
class GardenaMower extends GardenaDevice
{
    const ERROR_CODES = [
        'TILT_SENSOR_PROBLEM'          => 'tilt sensor problem',
        'MOWER_TILTED'                 => 'mower tilted',
        'WHEEL_MOTOR_OVERLOADED_RIGHT' => 'wheel motor overloaded, right',
        'WHEEL_MOTOR_OVERLOADED_LEFT'  => 'wheel motor overloaded, left',
        'CHARGING_CURRENT_TOO_HIGH'    => 'charging current too high',
        'ELECTRONIC_PROBLEM'           => 'electronic problem',
        'CUTTING_MOTOR_PROBLEM'        => 'cutting motor problem',
        'LIMITED_CUTTING_HEIGHT_RANGE' => 'limited cutting height range',
        'CUTTING_HEIGHT_PROBLEM_DRIVE' => 'cutting height problem, drive',
        'CUTTING_HEIGHT_PROBLEM_CURR'  => 'cutting height problem, current',
        'CUTTING_HEIGHT_PROBLEM_DIR'   => 'cutting height problem, direction',
        'CUTTING_HEIGHT_BLOCKED'       => 'cutting height blocked',
        'CUTTING_HEIGHT_PROBLEM'       => 'cutting height problem',
        'BATTERY_PROBLEM'              => 'battery problem',
        'TOO_MANY_BATTERIES'           => 'battery problem',
        'ALARM_MOWER_SWITCHED_OFF'     => 'alarm! mower switched off',
        'ALARM_MOWER_STOPPED'          => 'alarm! mower stopped',
        'ALARM_MOWER_LIFTED'           => 'alarm! mower lifted',
        'ALARM_MOWER_TILTED'           => 'alarm! mower tilted',
        'ALARM_MOWER_IN_MOTION'        => 'alarm! mower in motion',
        'ALARM_OUTSIDE_GEOFENCE'       => 'alarm! outside geofence',
        'SLIPPED'                      => 'mower has slipped',
        'INVALID_BATTERY_COMBINATION'  => 'invalid combination of battery types',
        'UNINITIALISED'                => 'radio module sent uninitialised value',
        'WAIT_UPDATING'                => 'mower waiting, updating firmware',
        'WAIT_POWER_UP'                => 'mower powering up',
        'OFF_DISABLED'                 => 'mower disabled on main switch',
        'OFF_HATCH_OPEN'               => 'mower in waiting state, hatch open',
        'OFF_HATCH_CLOSED'             => 'mower in waiting state, hatch closed',
        'PARKED_DAILY_LIMIT_REACHED'   => 'completed cutting, daily limit reached'
    ];
    protected $metadata = [
        'state' => [
            'displayName'  => 'State',
            'variableType' => VARIABLETYPE_STRING,
            'profile'      => 'Gardena.State',
            'position'     => 40
        ],
        'activity' => [
            'displayName'  => 'Activity',
            'variableType' => VARIABLETYPE_STRING,
            'profile'      => 'Gardena.Mower.Activity',
            'position'     => 0
        ],
        'lastErrorCode' => [
            'displayName'  => 'Last Error',
            'variableType' => VARIABLETYPE_STRING,
            'profile'      => 'Gardena.Mower.Error',
            'position'     => 30
        ],
        'operatingHours' => [
            'displayName'  => 'Operating Hours',
            'variableType' => VARIABLETYPE_INTEGER,
            'profile'      => 'Gardena.Hours',
            'position'     => 10
        ]
    ];
    protected $exclude = ['name', 'serial', 'modelType'];
    protected $type = 'MOWER';
    protected $control = 'MOWER_CONTROL';
    protected $commands = ['START_SECONDS_TO_OVERRIDE', 'START_DONT_OVERRIDE', 'PARK_UNTIL_NEXT_TASK', 'PARK_UNTIL_FURTHER_NOTICE'];

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

        //MOWER
        if (!IPS_VariableProfileExists('Gardena.Mower.Activity')) {
            IPS_CreateVariableProfile('Gardena.Mower.Activity', VARIABLETYPE_STRING);
            IPS_SetVariableProfileIcon('Gardena.Mower.Activity', 'Power');
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

        if (!IPS_VariableProfileExists('Gardena.Mower.Error')) {
            IPS_CreateVariableProfile('Gardena.Mower.Error', VARIABLETYPE_STRING);
            IPS_SetVariableProfileIcon('Gardena.Mower.Error', 'Warning');
            IPS_SetVariableProfileAssociation('Gardena.Mower.Error', 'NO_MESSAGE', $this->Translate('no message'), '', 0x00ff00);
            IPS_SetVariableProfileAssociation('Gardena.Mower.Error', 'OUTSIDE_WORKING_AREA', $this->Translate('outside working area'), '', -1);
            IPS_SetVariableProfileAssociation('Gardena.Mower.Error', 'NO_LOOP_SIGNAL', $this->Translate('no loop signal'), '', -1);
            IPS_SetVariableProfileAssociation('Gardena.Mower.Error', 'WRONG_LOOP_SIGNAL', $this->Translate('wrong loop signal'), '', -1);
            IPS_SetVariableProfileAssociation('Gardena.Mower.Error', 'LOOP_SENSOR_PROBLEM_FRONT', $this->Translate('loop sensor problem, front'), '', -1);
            IPS_SetVariableProfileAssociation('Gardena.Mower.Error', 'LOOP_SENSOR_PROBLEM_REAR', $this->Translate('loop sensor problem, rear'), '', -1);
            IPS_SetVariableProfileAssociation('Gardena.Mower.Error', 'LOOP_SENSOR_PROBLEM_LEFT', $this->Translate('loop sensor problem, left'), '', -1);
            IPS_SetVariableProfileAssociation('Gardena.Mower.Error', 'LOOP_SENSOR_PROBLEM_RIGHT', $this->Translate('loop sensor problem, right'), '', -1);
            IPS_SetVariableProfileAssociation('Gardena.Mower.Error', 'WRONG_PIN_CODE', $this->Translate('wrong PIN code'), '', -1);
            IPS_SetVariableProfileAssociation('Gardena.Mower.Error', 'TRAPPED', $this->Translate('trapped'), '', -1);
            IPS_SetVariableProfileAssociation('Gardena.Mower.Error', 'UPSIDE_DOWN', $this->Translate('upside down'), '', -1);
            IPS_SetVariableProfileAssociation('Gardena.Mower.Error', 'EMPTY_BATTERY', $this->Translate('empty battery'), '', -1);
            IPS_SetVariableProfileAssociation('Gardena.Mower.Error', 'NO_DRIVE', $this->Translate('no drive'), '', -1);
            IPS_SetVariableProfileAssociation('Gardena.Mower.Error', 'TEMPORARILY_LIFTED', $this->Translate('mower lifted'), '', -1);
            IPS_SetVariableProfileAssociation('Gardena.Mower.Error', 'LIFTED', $this->Translate('mower lifted'), '', -1);
            IPS_SetVariableProfileAssociation('Gardena.Mower.Error', 'STUCK_IN_CHARGING_STATION', $this->Translate('stuck in charging station'), '', -1);
            IPS_SetVariableProfileAssociation('Gardena.Mower.Error', 'CHARGING_STATION_BLOCKED', $this->Translate('charging station blocked'), '', -1);
            IPS_SetVariableProfileAssociation('Gardena.Mower.Error', 'COLLISION_SENSOR_PROBLEM_REAR', $this->Translate('collision sensor problem, rear'), '', -1);
            IPS_SetVariableProfileAssociation('Gardena.Mower.Error', 'COLLISION_SENSOR_PROBLEM_FRONT', $this->Translate('collision sensor problem, front'), '', -1);
            IPS_SetVariableProfileAssociation('Gardena.Mower.Error', 'WHEEL_MOTOR_BLOCKED_RIGHT', $this->Translate('wheel motor blocked, right'), '', -1);
            IPS_SetVariableProfileAssociation('Gardena.Mower.Error', 'WHEEL_MOTOR_BLOCKED_LEFT', $this->Translate('wheel motor blocked, left'), '', -1);
            IPS_SetVariableProfileAssociation('Gardena.Mower.Error', 'WHEEL_DRIVE_BLOCKED_RIGHT', $this->Translate('wheel drive problem, right'), '', -1);
            IPS_SetVariableProfileAssociation('Gardena.Mower.Error', 'WHEEL_DRIVE_BLOCKED_LEFT', $this->Translate('wheel drive problem, right'), '', -1);
            IPS_SetVariableProfileAssociation('Gardena.Mower.Error', 'CUTTING_MOTOR_DRIVE_DEFECT', $this->Translate('cutting system blocked'), '', -1);
            IPS_SetVariableProfileAssociation('Gardena.Mower.Error', 'CUTTING_SYSTEM_BLOCKED', $this->Translate('cutting system blocked'), '', -1);
            IPS_SetVariableProfileAssociation('Gardena.Mower.Error', 'INVALID_SUB_DEVICE_COMBINATION', $this->Translate('invalid sub-device combination'), '', -1);
            IPS_SetVariableProfileAssociation('Gardena.Mower.Error', 'MEMORY_CIRCUIT_PROBLEM', $this->Translate('memory circuit problem'), '', -1);
            IPS_SetVariableProfileAssociation('Gardena.Mower.Error', 'CHARGING_SYSTEM_PROBLEM', $this->Translate('charging system problem'), '', -1);
            IPS_SetVariableProfileAssociation('Gardena.Mower.Error', 'STOP_BUTTON_PROBLEM', $this->Translate('STOP button problem'), '', -1);
            foreach (self::ERROR_CODES as $error => $displayName) {
                IPS_SetVariableProfileAssociation('Gardena.Mower.Error', $error, $this->Translate($displayName), '', -1);
            }
        }

        if (!IPS_VariableProfileExists('Gardena.Hours')) {
            IPS_CreateVariableProfile('Gardena.Hours', VARIABLETYPE_INTEGER);
            IPS_SetVariableProfileIcon('Gardena.Hours', 'Clock');
            IPS_SetVariableProfileText('Gardena.Hours', '', $this->Translate(' Hours'));
        }

        if (!IPS_VariableProfileExists('Gardena.Command.Minutes')) {
            IPS_CreateVariableProfile('Gardena.Command.Minutes', VARIABLETYPE_INTEGER);
            IPS_SetVariableProfileIcon('Gardena.Command.Minutes', 'Clock');
            IPS_SetVariableProfileText('Gardena.Command.Minutes', '', $this->Translate(' Minutes'));
            IPS_SetVariableProfileValues('Gardena.Command.Minutes', 1, 360, 1);
        }

        if (!IPS_VariableProfileExists('Gardena.Mower.Start.Commands')) {
            IPS_CreateVariableProfile('Gardena.Mower.Start.Commands', VARIABLETYPE_STRING);
            IPS_SetVariableProfileIcon('Gardena.Mower.Start.Commands', 'Execute');
            IPS_SetVariableProfileAssociation('Gardena.Mower.Start.Commands', 'START_SECONDS_TO_OVERRIDE', $this->Translate('manual'), '', -1);
            IPS_SetVariableProfileAssociation('Gardena.Mower.Start.Commands', 'START_DONT_OVERRIDE', $this->Translate('follow schedule'), '', -1);
        }

        if (!IPS_VariableProfileExists('Gardena.Mower.Stop.Commands')) {
            IPS_CreateVariableProfile('Gardena.Mower.Stop.Commands', VARIABLETYPE_STRING);
            IPS_SetVariableProfileIcon('Gardena.Mower.Stop.Commands', 'Execute');
            IPS_SetVariableProfileAssociation('Gardena.Mower.Stop.Commands', 'PARK_UNTIL_NEXT_TASK', $this->Translate('until next task'), '', -1);
            IPS_SetVariableProfileAssociation('Gardena.Mower.Stop.Commands', 'PARK_UNTIL_FURTHER_NOTICE', $this->Translate('ignore schedule'), '', -1);
        }

        //Commands
        //Start commands
        $this->RegisterVariableString('MowerStart', $this->Translate('Cutting'), 'Gardena.Mower.Start.Commands', 60);
        $this->SetValue('MowerStart', '');
        $this->EnableAction('MowerStart');
        $this->RegisterVariableInteger('MowerDuration', $this->Translate('Cutting Duration'), 'Gardena.Command.Minutes', 50);
        $this->EnableAction('MowerDuration');

        //Stop commands
        $this->RegisterVariableString('MowerStop', $this->Translate('Parking'), 'Gardena.Mower.Stop.Commands', 70);
        $this->EnableAction('MowerStop');
    }

    public function RequestAction($Ident, $Value)
    {
        switch ($Ident) {
            case 'MowerStart':
                $this->ControlService($this->ReadPropertyString('ID'), $Value, 60 * $this->GetValue('MowerDuration'));
                break;

            case 'MowerStop':
                $this->ControlService($this->ReadPropertyString('ID'), $Value);
                break;

            default:
                $this->SetValue($Ident, $Value);
                break;

        }
    }
}