<?php

declare(strict_types=1);

require_once __DIR__ . '/../libs/MQTTHelper.php';

    class MiFlora extends IPSModule
    {
        use TasmotaMQTTHelper;

        public function Create()
        {
            //Never delete this line!
            parent::Create();
            $this->ConnectParent('{C6D2AEB3-6E1F-4B2E-8E69-3A1A00246850}');
            $this->RegisterPropertyString('Topic', '');
            $this->RegisterPropertyString('FullTopic', '%prefix%/%topic%');
            $this->RegisterPropertyString('Devicename', '');

            $this->RegisterVariableFloat('Temperature', $this->Translate('Temperature'), '~Temperature');
            $this->RegisterVariableInteger('Illuminance', $this->Translate('Illuminance'), '~Illumination');
            $this->RegisterVariableInteger('Moisture', $this->Translate('Moisture'), '~Intensity.100');

            if (!IPS_VariableProfileExists('M2T.Fertility')) {
                IPS_CreateVariableProfile('M2T.Fertility', 1);
            }
            IPS_SetVariableProfileDigits('M2T.Fertility', 0);
            IPS_SetVariableProfileIcon('M2T.Fertility', 'Flower');
            IPS_SetVariableProfileText('M2T.Fertility', '', ' us/cm');

            $this->RegisterVariableInteger('Fertility', $this->Translate('Fertility'), 'M2T.Fertility');
            $this->RegisterVariableInteger('RSSI', $this->Translate('RSSI'), '');
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

        public function ReceiveData($JSONString)
        {
            $this->SendDebug('JSON', $JSONString, 0);
            $data = json_decode($JSONString, true);
            $Payload = json_decode($data['Payload'], true);

            foreach ($Payload as $key => $Device) {
                if ($key == $this->ReadPropertyString('Devicename')) {
                    $this->SetValue('Temperature', $Device['Temperature']);
                    $this->SetValue('Illuminance', $Device['Illuminance']);
                    $this->SetValue('Moisture', $Device['Moisture']);
                    $this->SetValue('Fertility', $Device['Fertility']);
                    $this->SetValue('RSSI', $Device['RSSI']);
                }
            }
        }
    }