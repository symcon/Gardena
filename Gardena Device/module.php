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
                $locationID = $this->requestDataFromParent('locations')['data'][0]['id'];
                $location = $this->requestDataFromParent("locations/$locationID");
                foreach ($location['included'] as $data) {
                    $this->processData($data);
                }
            }
        }
    }

    public function ReceiveData($JSONString)
    {
        //Decoding dataflow
        $data = json_decode($JSONString, true);
        //Decoding websocket event
        $this->processData(json_decode($data['Buffer'], true));
    }

    private function processData($data)
    {
        //Only process data meant for this intance
        if ($data['type'] != $this->type) {
            return;
        }
        //Only process data meant matching our id
        if ($data['id'] != $this->ReadPropertyString('ID')) {
            return;
        }

        $this->SendDebug('Data', json_encode($data), 0);

        foreach ($data['attributes'] as $attribute => $value) {
            if (isset($this->metadata[$attribute])) {
                $meta = $this->metadata[$attribute];
                $this->MaintainVariable($attribute, $this->Translate($meta['displayName']), $meta['variableType'], $meta['profile'], 0, true);
                $this->SetValue($attribute, $value['value']);
            } elseif (!in_array($attribute, $this->exclude)) {
                // $variableType = VARIABLETYPE_STRING;
                switch (gettype($value['value'])) {
                            case 'double':
                            case 'integer':
                                $variablType = VARIABLETYPE_FLOAT;
                                break;

                            default:
                                $variableType = VARIABLETYPE_STRING;
                                break;
                        }
                $this->MaintainVariable($attribute, $attribute, $variableType, '', 0, true);
                $this->SetValue($attribute, $value['value']);
            }
        }
    }

    private function requestDataFromParent($endpoint)
    {
        return json_decode($this->SendDataToParent(json_encode([
            'DataID'      => '{793F0A25-9FFE-DC27-25D6-8A574EE74C39}',
            'Endpoint'    => $endpoint
        ])), true);
    }
}