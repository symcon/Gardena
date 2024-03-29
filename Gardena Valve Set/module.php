<?php

declare(strict_types=1);
include_once __DIR__ . '/../libs/GardenaDeviceModule.php';
class GardenaValveSet extends GardenaDevice
{
    protected $metadata = [
        'state' => [
            'displayName'  => 'State',
            'variableType' => VARIABLETYPE_STRING,
            'profile'      => 'Gardena.State'
        ],
        'lastErrorCode' => [
            'displayName'  => 'Last Error',
            'variableType' => VARIABLETYPE_STRING,
            'profile'      => 'Gardena.ValveSet.Error'
        ],
    ];
    protected $exclude = ['name', 'serial', 'modelType'];
    protected $type = 'VALVE_SET';
    protected $control = 'VALVE_SET_CONTROL';

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

        //VALVE_SET
        if (!IPS_VariableProfileExists('Gardena.ValveSet.Error')) {
            IPS_CreateVariableProfile('Gardena.ValveSet.Error', VARIABLETYPE_STRING);
            IPS_SetVariableProfileIcon('Gardena.ValveSet.Error', 'Warning');
            IPS_SetVariableProfileAssociation('Gardena.ValveSet.Error', 'NO_MESSAGE', $this->Translate('no message'), '', 0x00ff00);
            IPS_SetVariableProfileAssociation('Gardena.ValveSet.Error', 'VOLTAGE_DROP', $this->Translate('voltage drop detected'), '', -1);
            IPS_SetVariableProfileAssociation('Gardena.ValveSet.Error', 'WRONG_POWER_SUPPLY', $this->Translate('wrong power supply'), '', -1);
            IPS_SetVariableProfileAssociation('Gardena.ValveSet.Error', 'NO_MCU_CONNECTION', $this->Translate('no MCU connected'), '', -1);
            IPS_SetVariableProfileAssociation('Gardena.ValveSet.Error', 'UNKNOWN', $this->Translate('unknown'), '', -1);
        }

        if (!IPS_VariableProfileExists('Gardena.ValveSet.Commands')) {
            IPS_CreateVariableProfile('Gardena.ValveSet.Commands', VARIABLETYPE_STRING);
            IPS_SetVariableProfileIcon('Gardena.ValveSet.Commands', 'Execute');
            IPS_SetVariableProfileAssociation('Gardena.ValveSet.Commands', 'STOP_UNTIL_NEXT_TASK', $this->Translate('close all valves'), '', -1);
        }

        $this->RegisterVariableString('ValveSetControl', $this->Translate('Action'), 'Gardena.ValveSet.Commands', 0);
        $this->EnableAction('ValveSetControl');
    }

    public function RequestAction($Ident, $Value)
    {
        switch ($Ident) {
            case 'ValveSetControl':
                $this->ControlService($this->ReadPropertyString('ID'), 'STOP_UNTIL_NEXT_TASK');
                break;

            default:
                throw new Exception(sprintf('Invalid Ident: %s'), $Ident);
        }
    }
}
