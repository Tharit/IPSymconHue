<?php

declare(strict_types=1);

class HUEBridge extends IPSModule
{
    public function Create()
    {
        //Never delete this line!
        parent::Create();
        $this->RegisterPropertyString('Host', '');
        
        $this->RegisterAttributeString('User', '');
    }

    public function ApplyChanges()
    {
        //Never delete this line!
        parent::ApplyChanges();

        if (!$this->BridgePaired()) {
            $this->SetStatus(200);

            $this->LogMessage('Error: Registration incomplete, please pair IP-Symcon with the Philips HUE Bridge.', KL_ERROR);
            return;
        }
    }

    public function ForwardData($JSONString)
    {
        $this->SendDebug(__FUNCTION__, $JSONString, 0);
        $data = json_decode($JSONString);
        switch ($data->Buffer->Command) {
            case 'state':
                $params = (array) $data->Buffer->Params;
                $result = $this->sendRequest($this->ReadAttributeString('User'), $data->Buffer->Endpoint . '/' . $data->Buffer->DeviceID . '/state', $params, 'PUT');
                break;
        }
        $this->SendDebug(__FUNCTION__, json_encode($result), 0);
        return json_encode($result);
    }

    public function registerUser()
    {
        $params['devicetype'] = 'Symcon';
        $result = $this->sendRequest('', '', $params, 'POST');
        if (@isset($result[0]->success->username)) {
            $this->SendDebug('Register User', 'OK: ' . $result[0]->success->username, 0);
            $this->WriteAttributeString('User', $result[0]->success->username);
            $this->SetStatus(102);
        } else {
            $this->SendDebug(__FUNCTION__ . 'Pairing failed', json_encode($result), 0);
            $this->SetStatus(200);
            $this->LogMessage('Error: ' . $result[0]->error->type . ': ' . $result[0]->error->description, KL_ERROR);
        }
    }

    //Functions for Lights

    public function getAllLights()
    {
        return $this->sendRequest($this->ReadAttributeString('User'), 'lights', [], 'GET');
    }

    //Functions for Scenes

    private function sendRequest(string $User, string $endpoint, array $params = [], string $method = 'GET')
    {
        if ($this->ReadPropertyString('Host') == '') {
            return false;
        }

        $this->SendDebug('User', $User, 0);
        $ch = curl_init();
        if ($User != '' && $endpoint != '') {
            $this->SendDebug(__FUNCTION__ . ' URL', $this->ReadPropertyString('Host') . '/api/' . $User . '/' . $endpoint, 0);
            curl_setopt($ch, CURLOPT_URL, $this->ReadPropertyString('Host') . '/api/' . $User . '/' . $endpoint);
        } elseif ($endpoint != '') {
            return [];
        } else {
            $this->SendDebug(__FUNCTION__ . ' URL', $this->ReadPropertyString('Host') . '/api/' . $endpoint, 0);
            curl_setopt($ch, CURLOPT_URL, $this->ReadPropertyString('Host') . '/api/' . $endpoint);
        }

        curl_setopt($ch, CURLOPT_USERAGENT, 'Symcon');
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        if ($method == 'POST' || $method == 'PUT' || $method == 'DELETE') {
            if ($method == 'POST') {
                curl_setopt($ch, CURLOPT_POST, true);
            }
            if (in_array($method, ['PUT', 'DELETE'])) {
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
            }
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
        }

        $apiResult = curl_exec($ch);
        $this->SendDebug(__FUNCTION__ . ' Result', $apiResult, 0);
        $headerInfo = curl_getinfo($ch);
        if ($headerInfo['http_code'] == 200) {
            if ($apiResult != false) {
                $this->SetStatus(102);
                return json_decode($apiResult, false);
            } else {
                $this->LogMessage('Philips HUE sendRequest Error' . curl_error($ch), 10205);
                $this->SetStatus(201);
                return new stdClass();
            }
        } else {
            $this->LogMessage('Philips HUE sendRequest Error - Curl Error:' . curl_error($ch) . 'HTTP Code: ' . $headerInfo['http_code'], 10205);
            $this->SetStatus(202);
            return new stdClass();
        }
        curl_close($ch);
    }

    private function BridgePaired()
    {
        if ($this->ReadAttributeString('User') != '') {
            return true;
        }
        return false;
    }
}