<?php

declare(strict_types=1);

class HUEDevice extends IPSModule
{
    public function Create()
    {
        //Never delete this line!
        parent::Create();
        $this->ConnectParent('{1D8FE7D7-A87A-4D95-A2C7-33B1F026E392}');
        $this->RegisterPropertyString('HUEDeviceID', '');
        $this->RegisterPropertyString('DeviceType', 'lights');

        $this->RegisterVariableBoolean("Connected", "Connected");
    }

    public function ApplyChanges()
    {
        //Never delete this line!
        parent::ApplyChanges();
        if ($this->ReadPropertyString('DeviceType') == '') {
            return;
        }
    }

    public function ReceiveData($JSONString)
    {
        $Data = json_decode($JSONString);
        $Buffer = json_decode($Data->Buffer);

        $DeviceState = new stdClass();
        $DeviceConfig = new stdClass();

        if (property_exists($Buffer, 'Lights')) {
            if (property_exists($Buffer->Lights, $this->ReadPropertyString('HUEDeviceID'))) {
                if (property_exists($Buffer->Lights->{$this->ReadPropertyString('HUEDeviceID')}, 'state')) {
                    $DeviceState = $Buffer->Lights->{$this->ReadPropertyString('HUEDeviceID')}->state;
                }
                if (property_exists($Buffer->Lights->{$this->ReadPropertyString('HUEDeviceID')}, 'config')) {
                    $DeviceConfig = $Buffer->Lights->{$this->ReadPropertyString('HUEDeviceID')}->config;
                }
            } else {
                $this->LogMessage('Device ID: ' . $this->ReadPropertyString('HUEDeviceID') . ' invalid', 10204);
                return;
            }
        }

        if (property_exists($DeviceConfig, 'reachable')) {
            $this->SetValue('Connected', $DeviceConfig->reachable);
        }
    }

    public function Request(array $Value)
    {
        return $this->sendData('state', $Value);
    }

    public function RequestAction($Ident, $Value)
    {
        switch ($Ident) {
            default:
                $this->SendDebug(__FUNCTION__, 'Invalid Ident', 0);
                break;
        }
    }

    private function sendData(string $command, $params = '')
    {
        $DeviceType = $this->ReadPropertyString('DeviceType');
        //Wenn DeviceType "plugs" ist Typ auf "lights" setzen, damit der Endpoint der API stimmt.
        if ($this->ReadPropertyString('DeviceType') == 'plugs') {
            $DeviceType = 'lights';
        }

        $Data['DataID'] = '{B8014CFA-5211-481A-8017-0B928FFF93A5}';
        $Buffer['Command'] = $command;
        $Buffer['DeviceID'] = $this->ReadPropertyString('HUEDeviceID');
        $Buffer['Endpoint'] = $DeviceType;
        $Buffer['Params'] = $params;
        $Data['Buffer'] = $Buffer;
        $Data = json_encode($Data);

        if (!$this->HasActiveParent()) {
            return [];
        }

        $this->SendDebug(__FUNCTION__, $Data, 0);
        $result = $this->SendDataToParent($Data);
        $this->SendDebug(__FUNCTION__, $result, 0);

        if (!$result) {
            return [];
        }
        $Data = json_decode($result, true);
        return $Data;
    }
}