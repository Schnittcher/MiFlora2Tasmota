<?php

declare(strict_types=1);

require_once __DIR__ . '/../libs/MQTTHelper.php';
require_once __DIR__ . '/../libs/PlantbookHTTPHelper.php';

class Configurator extends IPSModule
{
    use TasmotaMQTTHelper;
    use PlantbookHTTPHelper;

    public function Create()
    {
        //Never delete this line!
        parent::Create();
        $this->ConnectParent('{C6D2AEB3-6E1F-4B2E-8E69-3A1A00246850}');
        $this->RegisterPropertyString('Topic', '');
        $this->RegisterPropertyString('Filter', '');
        $this->RegisterPropertyString('FullTopic', '%prefix%/%topic%');
        $this->SetBuffer('Devices', '{}');

        //Plantbook oAuth Token
        $this->RegisterPropertyString('ClientID', '');
        $this->RegisterPropertyString('ClientSecret', '');
        $this->RegisterAttributeString('Token', '');
        $this->RegisterAttributeInteger('TokenExpires', 0);
        $this->RegisterAttributeString('TokenType', '');
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

        $ReceiveDataFilter = $this->ReadPropertyString('Topic');

        //Expert Settings
        if ($this->ReadPropertyString('Filter') != '') {
            $ReceiveDataFilter = $this->ReadPropertyString('Filter');
        }
        $this->SetReceiveDataFilter('.*' . $ReceiveDataFilter . '.*');
    }

    public function GetConfigurationForm()
    {
        $Form = json_decode(file_get_contents(__DIR__ . '/form.json'), true);
        $Devices = json_decode($this->GetBuffer('Devices'), true);

        $Values = [];

        foreach ($Devices as $key => $Device) {
            $instanceID = $this->getDeviceInstances($key);

            $pid_plant = $this->getDeviceInstances($key);

            if (array_key_exists('pid_plant', $Device)) {
                $pid_plant = $Device['pid_plant'];
            } else {
                $pid_plant = '';
            }

            if (array_key_exists('mac', $Device)) {
                $mac = $Device['mac'];
            } else {
                $mac = '';
            }

            if (array_key_exists('MQTTTopic', $Device)) {
                $MQTTTopic = $Device['MQTTTopic'];
            } else {
                $MQTTTopic = '';
            }

            if (array_key_exists('Temperature', $Device)) {
                $Temperature = $Device['Temperature'] . ' ℃';
            } else {
                $Temperature = '';
            }

            if (array_key_exists('Illuminance', $Device)) {
                $Illuminance = $Device['Illuminance'] . ' lx';
            } else {
                $Illuminance = '';
            }

            if (array_key_exists('Moisture', $Device)) {
                $Moisture = $Device['Moisture'] . ' %';
            } else {
                $Moisture = '';
            }

            if (array_key_exists('Fertility', $Device)) {
                $Fertility = $Device['Fertility'] . ' us/cm';
            } else {
                $Fertility = '';
            }

            if (array_key_exists('Firmware', $Device)) {
                $Firmware = $Device['Firmware'];
            } else {
                $Firmware = '';
            }

            if (array_key_exists('Battery', $Device)) {
                $Battery = $Device['Battery'] . ' %';
            } else {
                $Battery = '';
            }

            if (array_key_exists('RSSI', $Device)) {
                $RSSI = $Device['RSSI'];
            } else {
                $RSSI = '';
            }

            $ValueExpertFilter = false;
            if ($this->ReadPropertyString('Filter') != '') {
                $ValueExpertFilter = true;
            }

            $AddValue = [
                'name'                               => $key,
                'pid_plant'                          => $pid_plant,
                'mac'                                => $mac,
                'MQTTTopic'                          => $MQTTTopic,
                'Temperature'                        => $Temperature,
                'Illuminance'                        => $Illuminance,
                'Moisture'                           => $Moisture,
                'Fertility'                          => $Fertility,
                'Firmware'                           => $Firmware,
                'Battery'                            => $Battery,
                'RSSI'                               => $RSSI,
                'instanceID'                         => $instanceID
            ];

            $AddValue['create'] =
                [
                    'moduleID'      => '{1ABD9482-C0FC-A2D1-BB98-C5509FB8321C}',
                    'configuration' => [
                        'Topic'                 => $this->ReadPropertyString('Topic'),
                        'FullTopic'             => $this->ReadPropertyString('FullTopic'),
                        'Devicename'            => $key,
                        'pid_plant'             => $pid_plant,
                        'ClientID'              => $this->ReadPropertyString('ClientID'),
                        'ClientSecret'          => $this->ReadPropertyString('ClientSecret'),
                        'ExpertFilter'          => $ValueExpertFilter
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
                    $FloraESPTopic = $this->getTasmotaTopic($data->Topic);
                    $Payload = json_decode($data->Payload, true);
                    unset($Payload['Time']); //Time aus dem Array entfernen
                    unset($Payload['TempUnit']); //Time aus dem Array entfernen
                    $Devices = json_decode($this->GetBuffer('Devices'), true);
                    foreach ($Payload as $key => $Value) {
                        if (fnmatch('Flora*', $key)) {
                            $Devices[$key]['MQTTTopic'] = $FloraESPTopic;
                            $Devices[$key]['mac'] = (array_key_exists('mac', $Value) == true ? $Value['mac'] : $this->Translate('Unknown'));
                            $Devices[$key]['Temperature'] = (array_key_exists('Temperature', $Value) == true ? $Value['Temperature'] : $this->Translate('Unknown'));
                            $Devices[$key]['Illuminance'] = (array_key_exists('Illuminance', $Value) == true ? $Value['Illuminance'] : $this->Translate('Unknown'));
                            $Devices[$key]['Moisture'] = (array_key_exists('Moisture', $Value) == true ? $Value['Moisture'] : $this->Translate('Unknown'));
                            $Devices[$key]['Fertility'] = (array_key_exists('Fertility', $Value) == true ? $Value['Fertility'] : $this->Translate('Unknown'));
                            $Devices[$key]['Firmware'] = (array_key_exists('Firmware', $Value) == true ? $Value['Firmware'] : $this->Translate('Unknown'));
                            $Devices[$key]['Battery'] = (array_key_exists('Battery', $Value) == true ? $Value['Battery'] : $this->Translate('Unknown'));
                            $Devices[$key]['RSSI'] = (array_key_exists('RSSI', $Value) == true ? $Value['RSSI'] : $this->Translate('Unknown'));
                        }
                    }
                    $this->SetBuffer('Devices', json_encode($Devices));
                }
            }
        }
    }

    public function searchPlant($PlantName)
    {
        $Plants = $this->searchRequest($PlantName);
        IPS_LogMessage('Plants', print_r($Plants, true));

        $Values = [];

        $Value['caption'] = '-';
        $Value['value'] = '-';
        array_push($Values, $Value);

        foreach ($Plants['results'] as $Plant) {
            $Value['caption'] = $Plant['display_pid'];
            $Value['value'] = $Plant['pid'];
            array_push($Values, $Value);
        }
        $this->UpdateFormField('Plant', 'options', json_encode($Values));
    }

    public function addPlantToSensor($Sensor, $PlantName)
    {
        IPS_LogMessage('addPlantToSensor', $Sensor . ' ' . $PlantName);

        $Devices = json_decode($this->GetBuffer('Devices'), true);

        $Values = [];

        $Devices[$Sensor]['pid_plant'] = $PlantName;
        $this->SetBuffer('Devices', json_encode($Devices));

        $this->ReloadForm();
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

    private function getPlantNameFromInstance($Device)
    {
        $InstanceIDs = IPS_GetInstanceListByModuleID('{1ABD9482-C0FC-A2D1-BB98-C5509FB8321C}');
        foreach ($InstanceIDs as $id) {
            if (IPS_GetProperty($id, 'Devicename') == $Device) {
                return IPS_GetProperty($id, 'pid_plant');
            }
        }
        return 0;
    }
}