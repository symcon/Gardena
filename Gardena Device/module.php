<?php

declare(strict_types=1);
class GardenaDevice extends IPSModule
{
    protected $metadata = [];
    protected $exclude = [];
    protected $type = '';
    protected $control = '';
    protected $commands = [];

    public function Create()
    {
        //Never delete this line!
        parent::Create();
        $this->ConnectParent('{3BD971CA-8DF6-CF4B-736C-2B4CFD2ED7F3}');

        //Properties
        $this->RegisterPropertyString('ID', '');
        $this->RegisterPropertyInteger('UpdateInterval', 5);
        $this->RegisterPropertyBoolean('Timestamp', false);

        //Timer
        $this->RegisterTimer('UpdateDuration', 0, 'GARDENA_UpdateDuration($_IPS[\'TARGET\']);');
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

    public function ControlService(string $ID, string $Command, int $Seconds = 0)
    {
        $endpoint = 'command/' . $this->ReadPropertyString('ID');
        $payload = [
            'data' => [
                'id'         => 'request-4',
                'type'       => $this->control,
                'attributes' => [
                    'command' => $Command
                ]
            ]
        ];
        if ($Seconds) {
            $payload['data']['attributes']['seconds'] = $Seconds;
        }

        $this->requestCommandFromParent($endpoint, json_encode($payload));
    }

    public function UpdateDuration()
    {
        $value = $this->GetValue('duration');
        $updateInterval = $this->ReadPropertyInteger('UpdateInterval');
        $this->SetTimerInterval('UpdateDuration', $updateInterval * 1000);
        $this->SetValue('duration', $value - $updateInterval);
    }

    protected function processData($data)
    {
        //Only process data meant for this intance
        if (!isset($data['type']) || $data['type'] != $this->type) {
            return;
        }
        //Only process data meant matching our id
        if ($data['id'] != $this->ReadPropertyString('ID')) {
            return;
        }

        $this->SendDebug('Data', json_encode($data), 0);

        foreach ($data['attributes'] as $attribute => $value) {
            if (isset($this->metadata[$attribute])) {
                //If duration is transmitted start the timer
                if ($attribute == 'duration') {
                    $currentInterval = $this->GetTimerInterval('UpdateDuration');
                    if (!$currentInterval) {
                        $this->SetTimerInterval('UpdateDuration', $this->ReadPropertyInteger('UpdateInterval') * 1000);
                    }
                }
                $meta = $this->metadata[$attribute];
                $position = isset($meta['position']) ? $meta['position'] : 0;
                //Real value
                $this->MaintainVariable($attribute, $this->Translate($meta['displayName']), $meta['variableType'], $meta['profile'], $position, true);
                $this->SetValue($attribute, $value['value']);

                //Timestamp of the last transmission
                if (isset($value['timestamp'])) {
                    $this->MaintainVariable($attribute . 'TimeStamp', $this->Translate($meta['displayName']) . ' ' . $this->Translate('Last Transmission'), VARIABLETYPE_INTEGER, '~UnixTimestamp', $position, $this->ReadPropertyBoolean('Timestamp'));
                    //Update timestamp only if the variable should exist
                    if ($this->ReadPropertyBoolean('Timestamp')) {
                        $this->SetValue($attribute . 'TimeStamp', intval(date('U', strtotime($value['timestamp']))));
                    }
                }
                $this->SendDebug('Timestamp', intval(date('U', strtotime($value['timestamp']))), 0);
            } elseif (!in_array($attribute, $this->exclude)) {
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

    private function requestCommandFromParent($endpoint, $payload)
    {
        return json_decode($this->SendDataToParent(json_encode([
            'DataID'      => '{793F0A25-9FFE-DC27-25D6-8A574EE74C39}',
            'Payload'     => $payload,
            'Endpoint'    => $endpoint
        ])), true);
    }
}