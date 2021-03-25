<?php

declare(strict_types=1);

class GardenaConfigurator extends IPSModule
{
    const INSTANCE_TYPES = [
        'DEFAULT'            => '{A927BE2B-EFCC-0AB7-533B-54F2981AFC9E}',
        'COMMON'             => '{FD9A4547-26FE-A75F-B636-839CAB25EACA}',
        'SENSOR'             => '{22726FE1-1B92-292B-F544-C293D23DF937}',
        'VALVE'              => '{68FFB073-8C93-C74C-B3CB-02778AEDE152}',
        'VALVE_SET'          => '{F90A415A-0C56-66E6-F37B-4C2E6DB36742}',
        'MOWER'              => '{5E7255D9-6A2E-F10B-9430-360F00A66963}',
        'POWER_SOCKET'       => '{82A86222-2D04-585C-D411-B390627FBBF9}'
    ];

    const STATES = [
        'OK'          => 'ok',
        'UNAVAILABLE' => 'unavailable',
        'ERROR'       => 'error',
        'WARNING'     => 'warning'
    ];

    const SERVICE_NAMES = [
        'COMMON'       => 'common',
        'VALVE'        => 'valve',
        'VALVE_SET'    => 'main valve',
        'SENSOR'       => 'sensor',
        'POWER_SOCKET' => 'power socket',
        'MOWER'        => 'mower'
    ];

    public function Create()
    {
        //Never delete this line!
        parent::Create();
        $this->ConnectParent('{3BD971CA-8DF6-CF4B-736C-2B4CFD2ED7F3}');
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
    }

    public function GetConfigurationForm()
    {
        $form = json_decode(file_get_contents(__DIR__ . '/form.json'), true);
        //Return if parent is not confiured
        if (!$this->HasActiveParent()) {
            return json_encode($form);
        }
        $location = json_decode($this->requestLocation(), true);
        $mainDevices = $location['data']['relationships']['devices']['data'];
        $allDevices = $location['included'];
        $locations = [[
            'Name'         => $this->Translate($location['data']['attributes']['name']),
            'SerialNumber' => '',
            'Type'         => '',
            'State'        => '',
            'id'           => $location['data']['id'] . $location['data']['type'],
            'ID'           => $location['data']['id'],
            'expanded'     => true
        ]];
        $devices = [];
        $services = [];
        foreach ($mainDevices as $mainDevice) {
            $id = $mainDevice['id'];
            foreach ($allDevices as $device) {
                if ($device['id'] == $id) {
                    switch ($device['type']) {
                            case 'COMMON':
                                //Add device to 1 level
                                $devices[] = $this->buildDeviceValues($device, $mainDevice, $location);
                                break;

                            case 'DEVICE':
                                foreach ($device['relationships']['services']['data'] as $service) {
                                    $moduleGUID = $this->getGuidForType($service['type']);
                                    switch ($service['type']) {
                                        case 'COMMON':
                                            $deviceName = $this->Translate('Device Information');
                                            break;

                                        case 'VALVE_SET':
                                            $deviceName = $this->Translate('Main Valve');
                                            break;

                                        case 'VALVE':
                                            $deviceName = $this->getCommonDeviceName($service['id'], $allDevices);
                                            //Translate default valve names
                                            preg_match_all('/(Valve) ([1-6])/m', $deviceName, $matches);
                                            if ($matches[0]) {
                                                $deviceName = sprintf('%s %d', $this->Translate($matches[1][0]), $matches[2][0]);
                                                $this->SendDebug('Name', $deviceName, 0);
                                            }
                                            break;
                                        default:
                                            $deviceName = $this->getCommonDeviceName($service['id'], $allDevices);
                                            break;
                                    }
                                    $services[] = [
                                        'id'           => $service['id'] . $service['type'],
                                        'ID'           => $service['id'],
                                        'Name'         => $deviceName,
                                        'SerialNumber' => '',
                                        'Type'         => $this->getServiceDisplayName($service['type']),
                                        'State'        => $this->getServiceState($service['id'], $allDevices),
                                        'parent'       => $id . $mainDevice['type'],
                                        'instanceID'   => $this->getInstanceIDForGuid($service['id'], $moduleGUID),
                                        'create'       => [
                                            'moduleID'      => $moduleGUID,
                                            'configuration' => [
                                                'ID' => $service['id']
                                            ],
                                            'name'     => $deviceName,
                                            'location' => [
                                                $this->Translate($location['data']['attributes']['name']),
                                                $this->getCommonDeviceName($device['id'], $allDevices)
                                            ]
                                        ]
                                    ];
                                }
                                break;

                        }
                }
            }
        }

        //Make shure parents are declated before the children
        $form['actions'][0]['values'] = array_merge($locations, $devices, $services);
        return json_encode($form);
    }

    private function requestDataFromParent($endpoint)
    {
        return $this->SendDataToParent(json_encode([
            'DataID'      => '{793F0A25-9FFE-DC27-25D6-8A574EE74C39}',
            'Endpoint'    => $endpoint
        ]));
    }

    private function requestLocation()
    {
        $locationID = json_decode($this->requestDataFromParent('locations'), true)['data'][0]['id'];
        $location = $this->requestDataFromParent("locations/$locationID");
        $this->SendDebug('DeveloperLocation', $location, 0);
        return $location;
    }

    private function getGuidForType($type)
    {
        if (isset(self::INSTANCE_TYPES[$type])) {
            return self::INSTANCE_TYPES[$type];
        } else {
            return self::INSTANCE_TYPES['DEFAULT'];
        }
    }

    private function getServiceState($id, $devices)
    {
        foreach ($devices as $device) {
            if ($device['id'] == $id && isset($device['attributes']['state']['value'])) {
                return $this->Translate(self::STATES[$device['attributes']['state']['value']]);
            }
        }
        return '';
    }

    private function getCommonDeviceName($id, $devices)
    {
        foreach ($devices as $device) {
            if ($devices['id'] = 'COMMON' && $device['id'] == $id && isset($device['attributes']['name']['value'])) {
                return $device['attributes']['name']['value'];
            }
        }
        //If we get no name with a suffix try without
        $seperator = strpos($id, ':');
        //If a suffix is present remove it
        if ($seperator) {
            $id = substr($id, 0, $seperator);
            return $this->getCommonDeviceName($id, $devices);
        }
        return $id;
    }

    private function getServiceDisplayName($type)
    {
        if (self::SERVICE_NAMES[$type]) {
            return $this->Translate(self::SERVICE_NAMES[$type]);
        }
        return $this->Translate('unknown');
    }

    private function getInstanceIDForGuid($id, $guid)
    {
        $instanceIDs = IPS_GetInstanceListByModuleID($guid);
        foreach ($instanceIDs as $instanceID) {
            if (IPS_GetProperty($instanceID, 'ID') == $id) {
                return $instanceID;
            }
        }
        return 0;
    }

    private function buildDeviceValues($device, $mainDevice, $location)
    {
        $attributes = $device['attributes'];
        $moduleGUID = $this->getGuidForType('COMMON');
        return [
            'id'           => $mainDevice['id'] . $mainDevice['type'],
            'ID'           => $mainDevice['id'],
            'Name'         => $attributes['name']['value'],
            'SerialNumber' => $attributes['serial']['value'],
            'Type'         => $attributes['modelType']['value'],
            'State'        => $attributes['rfLinkState']['value'],
            'parent'       => $location['data']['id'] . $location['data']['type']
        ];
    }
}
