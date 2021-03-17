<?php

declare(strict_types=1);
    class GardenaConfigurator extends IPSModule
    {
        //TODO: add different types
        const INSTANCE_TYPES = [
            'DEFAULT'            => '{A927BE2B-EFCC-0AB7-533B-54F2981AFC9E}',
            'COMMON'             => '{FD9A4547-26FE-A75F-B636-839CAB25EACA}',
            'SENSOR'             => '{22726FE1-1B92-292B-F544-C293D23DF937}',
            'VALVE'              => '{68FFB073-8C93-C74C-B3CB-02778AEDE152}',
            'VALVE_SET'          => '{F90A415A-0C56-66E6-F37B-4C2E6DB36742}'
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

        public function ReceiveData($JSONString)
        {
            $data = json_decode($JSONString);
            $snapshot = $data->Buffer;
            $this->SendDebug('Receive Snapshot', $snapshot, 0);
            if ($snapshot) {
            }
        }

        public function GetConfigurationForm()
        {
            $snapshot = json_decode($this->requestSnapshot(), true);
            $mainDevices = $snapshot['data']['relationships']['devices']['data'];
            $allDevices = $snapshot['included'];
            $values = [];
            $values[] = [
                'Name'         => $snapshot['data']['attributes']['name'],
                'SerialNumber' => '',
                'ModelType'    => '',
                'LinkState'    => '',
                'id'           => $snapshot['data']['id'] . $snapshot['data']['type'],
                'ID'           => $snapshot['data']['id'],
                'expanded'     => true
            ];
            $devices = [];
            $services = [];
            foreach ($mainDevices as $mainDevice) {
                $id = $mainDevice['id'];
                foreach ($allDevices as $device) {
                    if ($device['id'] == $id && $device['type'] == 'COMMON') {
                        $devices[] = $this->buildDeviceValues($device, $mainDevice, $snapshot);
                    }
                    if ($device['id'] == $id && $device['type'] == 'DEVICE') {
                        foreach ($device['relationships']['services']['data'] as $service) {
                            if ($service['type'] != 'COMMON') {
                                $moduleGUID = $this->getGuidForType($service['type']);
                                $services[] = [
                                    'id'           => $service['id'] . $service['type'],
                                    'ID'           => $service['id'],
                                    'Name'         => $this->getServiceName($service['id'], $allDevices),
                                    'SerialNumber' => '',
                                    'ModelType'    => '',
                                    'LinkState'    => '',
                                    'parent'       => $id . $mainDevice['type'],
                                    'instanceID'   => $this->getInstanceIDForGuid($service['id'], $moduleGUID),
                                    'create'       => [
                                        'moduleID'      => $moduleGUID,
                                        'configuration' => [
                                            'ID' => $service['id']
                                        ],
                                        'name' => $this->getServiceName($service['id'], $allDevices)

                                    ]
                                ];
                            }
                        }
                    }
                }
            }

            $values = array_merge(array_merge($values, $devices), $services);
            $form = [
                'elements' => [],
                'actions'  => [
                    [
                        'type'    => 'Configurator',
                        'caption' => 'Configurator',
                        'delete'  => true,
                        'columns' => [
                            [
                                'caption' => 'ID',
                                'name'    => 'ID',
                                'width'   => '300px',
                                'visible' => false
                            ],
                            [
                                'caption' => 'Name',
                                'name'    => 'Name',
                                'width'   => 'auto'
                            ],
                            [
                                'caption' => 'Serial Number',
                                'name'    => 'SerialNumber',
                                'width'   => '200px'
                            ],
                            [
                                'caption' => 'RF Link State',
                                'name'    => 'LinkState',
                                'width'   => '150px'
                            ],
                            [
                                'caption' => 'Model Type',
                                'name'    => 'ModelType',
                                'width'   => '300px'
                            ],
                        ],
                        'values' => $values

                    ]
                ]
            ];
            return json_encode($form);
        }

        private function requestSnapshot()
        {
            $snapshot = $this->SendDataToParent(json_encode([
                'DataID'      => '{793F0A25-9FFE-DC27-25D6-8A574EE74C39}',
                'RequestData' => 'snapshot'
            ]));
            $this->SendDebug('DeveloperSnapshot', $snapshot, 0);
            return $snapshot;
        }

        private function getGuidForType($type)
        {
            if (isset(self::INSTANCE_TYPES[$type])) {
                return self::INSTANCE_TYPES[$type];
            } else {
                return self::INSTANCE_TYPES['DEFAULT'];
            }
        }

        private function getServiceName($id, $devices)
        {
            foreach ($devices as $device) {
                if ($device['id'] == $id && isset($device['attributes']['name']['value'])) {
                    return $device['attributes']['name']['value'];
                }
            }
            return 'unknown';
        }

        private function getInstanceIDForGuid($id, $guid)
        {
            $instanceIDs = IPS_GetInstanceListByModuleID($guid);
            foreach ($instanceIDs as $instanceID) {
                if (IPS_GetProperty($instanceID, 'ID') == $id) {
                    $this->SendDebug('InstanceID', strval($instanceID), 0);
                    return $instanceID;
                }
            }
            return 0;
        }

        private function buildDeviceValues($device, $mainDevice, $snapshot)
        {
            $attributes = $device['attributes'];
            $moduleGUID = $this->getGuidForType('COMMON');
            return [
                'id'           => $mainDevice['id'] . $mainDevice['type'],
                'ID'           => $mainDevice['id'],
                'Name'         => $attributes['name']['value'],
                'SerialNumber' => $attributes['serial']['value'],
                'ModelType'    => $attributes['modelType']['value'],
                'LinkState'    => $attributes['rfLinkState']['value'],
                'parent'       => $snapshot['data']['id'] . $snapshot['data']['type'],
                'instanceID'   => $this->getInstanceIDForGuid($device['id'], $moduleGUID),
                'create'       => [
                    'moduleID'      => $moduleGUID,
                    'configuration' => [
                        'ID' => $device['id']
                    ],
                    'name' => $attributes['name']['value']
                ]
            ];
        }
    }
