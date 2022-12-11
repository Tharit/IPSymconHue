<?php

declare(strict_types=1);
require_once __DIR__ . '/../libs/ColorHelper.php';

class HUEDevice extends IPSModule
{
    use ColorHelper;

    public function Create()
    {
        //Never delete this line!
        parent::Create();
        $this->ConnectParent('{B8014CFA-5211-481A-8017-0B928FFF93A5}');
        $this->RegisterPropertyString('HUEDeviceID', '');
        $this->RegisterPropertyString('DeviceType', 'lights');
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