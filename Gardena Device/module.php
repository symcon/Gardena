<?php

declare(strict_types=1);
class GardenaDevice extends IPSModule
{
    protected $metadata = [];
    protected $exclude = [];
    protected $type = '';

    public function Create()
    {
        //Never delete this line!
        parent::Create();
        $this->ConnectParent('{3BD971CA-8DF6-CF4B-736C-2B4CFD2ED7F3}');

        //Properties
        $this->RegisterPropertyString('ID', '');

        //Profiles
        if (!IPS_VariableProfileExists('Gardena.ReachableStatus')) {
            IPS_CreateVariableProfile('Gardena.ReachableStatus', VARIABLETYPE_STRING);
            IPS_SetVariableProfileAssociation('Gardena.ReachableStatus', 'ONLINE', $this->Translate('Online'), '', 0x00ff00);
            IPS_SetVariableProfileAssociation('Gardena.ReachableStatus', 'OFFLINE', $this->Translate('Offline'), '', 0xff0000);
        }

        if (!IPS_VariableProfileExists('Gardena.Valve.Activity')) {
            IPS_CreateVariableProfile('Gardena.Valve.Activity', VARIABLETYPE_STRING);
            IPS_SetVariableProfileAssociation('Gardena.Valve.Activity', 'OPEN', $this->Translate('Open'), '', -1);
            IPS_SetVariableProfileAssociation('Gardena.Valve.Activity', 'CLOSED', $this->Translate('Closed'), '', -1);
        }

        if (!IPS_VariableProfileExists('Gardena.State')) {
            IPS_CreateVariableProfile('Gardena.State', VARIABLETYPE_STRING);
            IPS_SetVariableProfileAssociation('Gardena.State', 'OK', $this->Translate('Ok'), '', 0x00ff00);
            IPS_SetVariableProfileAssociation('Gardena.State', 'UNAVAILABLE', $this->Translate('Unavailable'), '', 0xff0000);
        }

        if (!IPS_VariableProfileExists('Gardena.Error')) {
            IPS_CreateVariableProfile('Gardena.Error', VARIABLETYPE_STRING);
            IPS_SetVariableProfileAssociation('Gardena.Error', 'NO_MESSAGE', $this->Translate('No Message'), '', 0x00ff00);
        }

        if (!IPS_VariableProfileExists('Gardena.Battery')) {
            IPS_CreateVariableProfile('Gardena.Battery', VARIABLETYPE_STRING);
            IPS_SetVariableProfileAssociation('Gardena.Battery', 'OK', $this->Translate('Ok'), '', 0x00ff00);
            IPS_SetVariableProfileAssociation('Gardena.Battery', 'NO_BATTERY', $this->Translate('No Battery'), '', 0xff0000);
        }
    }

    public function Destroy()
    {
        //Never delete this line!
        parent::Destroy();
    }

    public function ApplyChanges()
    {
        //Never delete this line!
        parent::ApplyChanges();

        if (IPS_GetKernelRunlevel() == KR_READY) {
            if ($this->HasActiveParent()) {
                $snapshot = $this->SendDataToParent(json_encode([
                    'DataID'      => '{793F0A25-9FFE-DC27-25D6-8A574EE74C39}',
                    'RequestData' => 'snapshot'
                ]));
                $this->SendDebug('Snapshot', $snapshot, 0);
                foreach (json_decode($snapshot, true)['included'] as $part) {
                    $this->SendDebug('Snapshot', json_encode($part), 0);
                    $this->processSnapshot(json_encode($part));
                }
            }
        }
    }

    public function ReceiveData($JSONString)
    {
        $data = json_decode($JSONString);
        $snapshot = $data->Buffer;
        $this->SendDebug('Receive Snapshot', $snapshot, 0);
        if ($snapshot != '[]') {
            $this->processSnapshot($snapshot);
        }
    }

    private function processSnapshot($snapshot)
    {
        $data = json_decode($snapshot, true);
        $this->SendDebug('META', json_encode($this->metadata), 0);
        //Only process data meant for this intance
        if ($this->type && $data['type'] != $this->type) {
            return;
        }
        if ($data['id'] == $this->ReadPropertyString('ID')) {
            if (isset($data['attributes'])) {
                $attributes = $data['attributes'];
                $this->SendDebug('Attributes', json_encode($attributes), 0);
                foreach ($attributes as $attribute => $value) {
                    if (isset($this->metadata[$attribute])) {
                        $meta = $this->metadata[$attribute];
                        $this->MaintainVariable($attribute, $this->Translate($meta['displayName']), $meta['variableType'], $meta['profile'], 0, true);
                        $this->SetValue($attribute, $value['value']);
                    } elseif (!in_array($attribute, $this->exclude)) {
                        $variablType = VARIABLETYPE_STRING;
                        $valueType = gettype($value['value']);
                        switch ($valueType) {
                            case 'double':
                            case 'integer':
                                $variablType = VARIABLETYPE_FLOAT;
                                break;

                        }
                        $this->MaintainVariable($attribute, $attribute, $variablType, '', 0, true);
                        $this->SetValue($attribute, $value['value']);
                    }
                }
            }
        } else {
            if (isset($data['relationships']['devices']['data'])) {
                if ($data['relationships']['devices']['data']['id'] == $this->ReadPropertyString['ID']) {
                    $this->SendDebug('NewAttribute', json_encode($data['attributes']), 0);
                }
            }
        }
    }
}