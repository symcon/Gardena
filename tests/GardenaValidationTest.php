<?php

declare(strict_types=1);

include_once __DIR__ . '/stubs/Validator.php';

class GardenaValidationTest extends TestCaseSymconValidation
{
    public function testValidateGardena(): void
    {
        $this->validateLibrary(__DIR__ . '/..');
    }

    public function testValidateGardenaCloud(): void
    {
        $this->validateModule(__DIR__ . '/../Gardena Cloud');
    }

    public function testValidateGardenaCommon(): void
    {
        $this->validateModule(__DIR__ . '/../Gardena Common');
    }

    public function testValidateGardenaConfigurator(): void
    {
        $this->validateModule(__DIR__ . '/../Gardena Configurator');
    }

    public function testValidateGardenaSensor(): void
    {
        $this->validateModule(__DIR__ . '/../Gardena Sensor');
    }

    public function testValidateGardenaValve(): void
    {
        $this->validateModule(__DIR__ . '/../Gardena Valve');
    }

    public function testValidateGardenaValveSet(): void
    {
        $this->validateModule(__DIR__ . '/../Gardena Valve Set');
    }

    public function testValidateGardenaMower(): void
    {
        $this->validateModule(__DIR__ . '/../Gardena Mower');
    }
}