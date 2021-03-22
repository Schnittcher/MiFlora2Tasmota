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
        $this->RegisterPropertyString('Filter', '');
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
                'name'                           => $key,
                'mac'                            => $mac,
                'MQTTTopic'                      => $MQTTTopic,
                'Temperature'                    => $Temperature,
                'Illuminance'                    => $Illuminance,
                'Moisture'                       => $Moisture,
                'Fertility'                      => $Fertility,
                'Firmware'                       => $Firmware,
                'Battery'                        => $Battery,
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
                        'ExpertFilter'      => $ValueExpertFilter
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
                        if (fnmatch('Flora-*', $data->Topic)) {
                            $Devices[$key]['MQTTTopic'] = $FloraESPTopic;
                            $Devices[$key]['mac'] = $Value['mac'];
                            $Devices[$key]['Temperature'] = $Value['Temperature'];
                            $Devices[$key]['Illuminance'] = $Value['Illuminance'];
                            $Devices[$key]['Moisture'] = $Value['Moisture'];
                            $Devices[$key]['Fertility'] = $Value['Fertility'];
                            if (array_key_exists('Firmware', $Value)) {
                                $Devices[$key]['Firmware'] = $Value['Firmware'];
                            } else {
                                $Devices[$key]['Firmware'] = '';
                            }
                            if (array_key_exists('Battery', $Value)) {
                                $Devices[$key]['Battery'] = $Value['Battery'];
                            } else {
                                $Devices[$key]['Battery'] = '';
                            }
                            $Devices[$key]['RSSI'] = $Value['RSSI'];
                        }
                    }
                    $this->SetBuffer('Devices', json_encode($Devices));
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