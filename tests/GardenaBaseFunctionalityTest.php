<?php

declare(strict_types=1);

include_once __DIR__ . '/stubs/GlobalStubs.php';
include_once __DIR__ . '/stubs/ConstantStubs.php';
include_once __DIR__ . '/stubs/KernelStubs.php';
include_once __DIR__ . '/stubs/ModuleStubs.php';
include_once __DIR__ . '/stubs/MessageStubs.php';

use PHPUnit\Framework\TestCase;

class SzenenSteuerungIdMigrationTest extends TestCase
{
    private $webSocket = '{8062CF2B-600E-41D6-AD4B-1BA66C32D6ED}';
    private $gardenaCloud = '{3BD971CA-8DF6-CF4B-736C-2B4CFD2ED7F3}';
    private $gardenaConfigurator = '{7D13C8A3-47EE-A6D8-152D-A98BBE34FCAA}';
    private $wsClient = '{D68FD31F-0E90-7019-F16C-1949BD3079EF}';
    private $gardenaSensor = '{22726FE1-1B92-292B-F544-C293D23DF937}';

    public function setUp(): void
    {
        //Reset
        IPS\Kernel::reset();
        //Register our library we need for testing
        IPS\ModuleLoader::loadLibrary(__DIR__ . '/../library.json');
        IPS\ModuleLoader::loadLibrary(__DIR__ . '/stubs/IOStubs/library.json');
        parent::setUp();
    }

    public function testSmartSensor()
    {
        // $websocketID = IPS_CreateInstance($this->webSocket);
        // $wsClientID = IPS_CreateInstance($this->wsClient);
        $gardenaConfiguratorID = IPS_CreateInstance($this->gardenaConfigurator);
        $testJSON = file_get_contents(__DIR__ . '/../test.json');
        GARDENA_SetSnapshot($gardenaConfiguratorID, json_decode($testJSON, true));
        // GARDENA_SetSnapshot($gardenaConfiguratorID,
        //     [
        //         'data' => [
        //             'id'            => 'abcd',
        //             'type'          => 'LOCATION',
        //             'relationships' => [
        //                 'devices' => [
        //                     'data' => [
        //                         [
        //                             'id'   => 'sensor01',
        //                             'type' => 'DEVICE'
        //                         ]
        //                     ]
        //                 ]
        //                         ],
        //                         'attributes' => [
        //                             'name' => 'My Garden'
        //                         ]
        //         ],
        //         'included' => [
        //             [
        //                 'id'            => 'sensor01',
        //                 'type'          => 'DEVICE',
        //                 'relationships' => [
        //                     'services' => [
        //                         'data' => [
        //                             [
        //                                 'id'   => 'sensor',
        //                                 'type' => 'COMMON'
        //                             ],
        //                             [
        //                                 'id'   => 'sensor',
        //                                 'type' => 'SENSOR'
        //                             ]
        //                         ]
        //                     ]
        //                 ]
        //             ],
        //             [
        //                 'id'         => 'sensor01',
        //                 'type'       => 'COMMON',
        //                 'attributes' => [
        //                     'name' => [
        //                         'value' => 'Sensor 1'
        //                     ],
        //                     'serial' => [
        //                         'value' => 'crazy serial'
        //                     ],
        //                     'modelType' => [
        //                         'value' => 'GARDENA smart sensor'
        //                     ],
        //                     'rfLinkState' => [
        //                         'value' => 'OK'
        //                     ]
        //                 ]
        //             ],
        //             [
        //                 'id'         => 'sensor01',
        //                 'type'       => 'SENSOR',
        //                 'attributes' => [
        //                     'soilHumidity' => [
        //                         'value' => 80
        //                     ]
        //                 ]
        //             ]
        //         ]
        //     ]
        //         );
        // $isntances = IPS_GetInstanceList();
        // foreach($isntances as $isntance) {
        //     var_dump(IPS_GetInstance($isntance));
        // }
        // print_r(IPS_GetConfigurationForm($gardenaConfiguratorID));
        // $sensorID = IPS_CreateInstance($this->gardenaSensor);
        $this->assertTrue(true);
    }
}