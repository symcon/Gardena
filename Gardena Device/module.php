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

    private function requestCommandFromParent($endpoint, $payload)
    {
        return json_decode($this->SendDataToParent(json_encode([
            'DataID'      => '{793F0A25-9FFE-DC27-25D6-8A574EE74C39}',
            'Payload'     => $payload,
            'Endpoint'    => $endpoint
        ])), true);
    }
}