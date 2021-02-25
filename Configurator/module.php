<?php

declare(strict_types=1);

require_once __DIR__ . '/../libs/MQTTHelper.php';

class Configurator extends IPSModule
{
    use TasmotaMQTTHelper;

    public function Create()
    {
        //Never delete this line!
        parent::Create();
        $this->ConnectParent('{C6D2AEB3-6E1F-4B2E-8E69-3A1A00246850}');
        $this->RegisterPropertyString('Topic', '');
        $this->RegisterPropertyString('FullTopic', '%prefix%/%topic%');
        $this->SetBuffer('Devices', '{}');
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
        $this->SendDebug(__FUNCTION__ . ' FullTopic', $this->ReadPropertyString('FullTopic'), 0);
        $topic = $this->FilterFullTopicReceiveData();
        $this->SendDebug(__FUNCTION__ . ' Filter FullTopic', $topic, 0);

        $this->SetReceiveDataFilter('.*' . $topic . '.*');
    }

    public function GetConfigurationForm()
    {
        $Form = json_decode(file_get_contents(__DIR__ . '/form.json'), true);
        $Devices = json_decode($this->GetBuffer('Devices'), true);

        $Values = [];

        foreach ($Devices as $key => $Device) {
            $instanceID = $this->getDeviceInstances($key);

            if (array_key_exists('Temperature', $Device)) {
                $Temperature = $Device['Temperature'];
            } else {
                $Temperature = '';
            }

            if (array_key_exists('Illuminance', $Device)) {
                $Illuminance = $Device['Illuminance'];
            } else {
                $Illuminance = '';
            }

            if (array_key_exists('Moisture', $Device)) {
                $Moisture = $Device['Moisture'];
            } else {
                $Moisture = '';
            }

            if (array_key_exists('Fertility', $Device)) {
                $Fertility = $Device['Fertility'];
            } else {
                $Fertility = '';
            }

            if (array_key_exists('RSSI', $Device)) {
                $RSSI = $Device['RSSI'];
            } else {
                $RSSI = '';
            }

            $AddValue = [
                'name'                           => $key,
                'Temperature'                    => $Temperature,
                'Illuminance'                    => $Illuminance,
                'Moisture'                       => $Moisture,
                'Fertility'                      => $Fertility,
                'RSSI'                           => $RSSI,
                'instanceID'                     => $instanceID
            ];

            $AddValue['create'] =
                [
                    'moduleID'      => '{1ABD9482-C0FC-A2D1-BB98-C5509FB8321C}',
                    'configuration' => [
                        'Topic'             => $this->ReadPropertyString('Topic'),
                        'FullTopic'         => $this->ReadPropertyString('FullTopic'),
                        'Devicename'        => $key,
                    ]
                ];

            $Values[] = $AddValue;
        }
        $Form['actions'][0]['values'] = $Values;
        return json_encode($Form);
    }

    public function ReceiveData($JSONString)
    {
        $this->SendDebug('JSON', $JSONString, 0);
        $data = json_decode($JSONString);

        if (property_exists($data, 'Topic')) {
            if (fnmatch('*/SENSOR', $data->Topic)) {
                if (fnmatch('*Flora*', $data->Payload)) {
                    $Payload = json_decode($data->Payload, true);
                    unset($Payload['Time']); //Time aus dem Array entfernen
                    unset($Payload['TempUnit']); //Time aus dem Array entfernen
                    $this->SetBuffer('Devices', json_encode($Payload));
                    $this->ReloadForm();
                }
            }
        }
    }

    private function getDeviceInstances($Device)
    {
        $InstanceIDs = IPS_GetInstanceListByModuleID('{1ABD9482-C0FC-A2D1-BB98-C5509FB8321C}');
        foreach ($InstanceIDs as $id) {
            if (IPS_GetProperty($id, 'Devicename') == $Device) {
                return $id;
            }
        }
        return 0;
    }
}