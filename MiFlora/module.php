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
            $this->RegisterPropertyBoolean('MAC-Address', false);
            $this->RegisterPropertyBoolean('Firmware', false);
            $this->RegisterPropertyBoolean('ExpertFilter', false);

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
            $this->RegisterVariableInteger('Battery', $this->Translate('Battery'), '~Battery.100');
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

            $this->MaintainVariable('MAC', $this->Translate('MAC-Address'), 3, '', 0, $this->ReadPropertyBoolean('MAC-Address') == true);
            $this->MaintainVariable('Firmware', $this->Translate('Firmware'), 3, '', 0, $this->ReadPropertyBoolean('Firmware') == true);

            $ReceiveDataFilter = $this->ReadPropertyString('Topic');
            if ($this->ReadPropertyBoolean('ExpertFilter')) {
                $ReceiveDataFilter = $this->ReadPropertyString('Devicename');
            }

            $this->SetReceiveDataFilter('.*' . $ReceiveDataFilter . '.*');
        }

        public function ReceiveData($JSONString)
        {
            $this->SendDebug('JSON', $JSONString, 0);
            $data = json_decode($JSONString, true);

            if (array_key_exists('Topic', $data)) {
                if (fnmatch('*SENSOR', $data['Topic'])) {
                    $Payload = json_decode($data['Payload'], true);
                    foreach ($Payload as $key => $Device) {
                        if ($key == $this->ReadPropertyString('Devicename')) {
                            $this->SetValueIfNotNull('Temperature', $Device['Temperature']);
                            $this->SetValueIfNotNull('Illuminance', $Device['Illuminance']);
                            $this->SetValueIfNotNull('Moisture', $Device['Moisture']);
                            $this->SetValueIfNotNull('Fertility', $Device['Fertility']);
                            if (array_key_exists('Battery', $Device)) {
                                $this->SetValueIfNotNull('Battery', $Device['Battery']);
                            }
                            if ($this->ReadPropertyBoolean('MAC-Address')) {
                                $this->SetValueIfNotNull('MAC', $Device['mac']);
                            }
                            if ($this->ReadPropertyBoolean('Firmware') && array_key_exists('Firmware', $Device)) {
                                $this->SetValueIfNotNull('Firmware', $Device['Firmware']);
                            }
                            $this->SetValueIfNotNull('RSSI', $Device['RSSI']);
                        }
                    }
                }
            }
        }

        private function SetValueIfNotNull($Ident, $Value)
        {
            if ($Value != null) {
                $this->SetValue($Ident, $Value);
            }
        }
    }