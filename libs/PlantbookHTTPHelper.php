<?php

declare(strict_types=1);
trait PlantbookHTTPHelper
{
    protected function getToken()
    {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, 'https://open.plantbook.io/api/v1/token/');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        $post = [
            'grant_type'    => 'client_credentials',
            'client_id'     => $this->ReadPropertyString('ClientID'),
            'client_secret' => $this->ReadPropertyString('ClientSecret'),
        ];
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post);

        $resultAPI = curl_exec($ch);
        $this->SendDebug('Token Result', $resultAPI, 0);
        if (curl_errno($ch)) {
            echo 'Error:' . curl_error($ch);
        }
        curl_close($ch);
        $result = json_decode($resultAPI, true);

        $this->WriteAttributeString('Token', $result['access_token']);
        $this->WriteAttributeInteger('TokenExpires', time() + $result['expires_in']);
        $this->WriteAttributeString('TokenType', $result['token_type']);
    }

    protected function searchRequest($plantName)
    {
        if ($this->ReadAttributeInteger('TokenExpires') <= time()) {
            $this->getToken();
        }

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, 'https://open.plantbook.io/api/v1/plant/search?alias=' . urlencode($plantName));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');

        $headers = [];
        $headers[] = 'Authorization: Bearer ' . $this->ReadAttributeString('Token');
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $resultAPI = curl_exec($ch);
        $this->SendDebug('Search Result', $resultAPI, 0);
        if (curl_errno($ch)) {
            echo 'Error:' . curl_error($ch);
        }
        curl_close($ch);
        $result = json_decode($resultAPI, true);
        return $result;
    }

    protected function getDetailRequest($plantPid)
    {
        if ($this->ReadAttributeInteger('TokenExpires') <= time()) {
            $this->getToken();
        }

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, 'https://open.plantbook.io/api/v1/plant/detail/' . $plantPid . '/');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');

        $headers = [];
        $headers[] = 'Authorization: Bearer ' . $this->ReadAttributeString('Token');
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $resultAPI = curl_exec($ch);
        if (curl_errno($ch)) {
            echo 'Error:' . curl_error($ch);
        }
        curl_close($ch);
        $result = json_decode($resultAPI, true);
        return $result;
    }
}