<?php

declare(strict_types=1);

require_once __DIR__ . '/../libs/MQTTHelper.php';
require_once __DIR__ . '/../libs/PlantbookHTTPHelper.php';
require_once __DIR__ . '/../libs/VariableProfileHelper.php';

    class MiFlora extends IPSModule
    {
        use TasmotaMQTTHelper;
        use PlantbookHTTPHelper;
        use VariableProfileHelper;

        public function Create()
        {
            //Never delete this line!
            parent::Create();
            $this->ConnectParent('{C6D2AEB3-6E1F-4B2E-8E69-3A1A00246850}');
            $this->RegisterPropertyString('Topic', '');
            $this->RegisterPropertyString('FullTopic', '%prefix%/%topic%');
            $this->RegisterPropertyString('Devicename', '');

            $this->RegisterPropertyString('pid_plant', '');

            $this->RegisterPropertyString('ClientID', '');
            $this->RegisterPropertyString('ClientSecret', '');
            $this->RegisterAttributeString('Token', '');
            $this->RegisterAttributeInteger('TokenExpires', 0);
            $this->RegisterAttributeString('TokenType', '');

            $this->RegisterPropertyBoolean('MAC-Address', false);
            $this->RegisterPropertyBoolean('Firmware', false);
            $this->RegisterPropertyBoolean('ExpertFilter', false);

            $this->RegisterProfileIntegerEx('M2T.Conductivity', 'Flower', '', ' us/cm', [], 1000, 1);
            $this->RegisterProfileFloatEx('M2T.LightQuantity', 'Sun', '', ' µmol/m²/s', [], 0, 15000, 0.1, 2);

            $this->RegisterVariableFloat('Temperature', $this->Translate('Temperature'), '~Temperature', 1);
            $this->RegisterVariableInteger('Illuminance', $this->Translate('Illuminance'), '~Illumination', 2);
            $this->RegisterVariableInteger('Moisture', $this->Translate('Moisture'), '~Intensity.100', 3);

            /**
             * if (!IPS_VariableProfileExists('M2T.Fertility')) {
             * IPS_CreateVariableProfile('M2T.Fertility', 1);
             * }
             * IPS_SetVariableProfileDigits('M2T.Fertility', 0);
             * IPS_SetVariableProfileIcon('M2T.Fertility', 'Flower');
             * IPS_SetVariableProfileText('M2T.Fertility', '', ' us/cm');
             */
            $this->RegisterVariableInteger('Fertility', $this->Translate('Fertility'), 'M2T.Conductivity', 4);
            $this->RegisterVariableInteger('Battery', $this->Translate('Battery'), '~Battery.100', 5);
            $this->RegisterVariableInteger('RSSI', $this->Translate('RSSI'), '', 6);

            $this->RegisterVariableFloat('max_light_mmol', $this->Translate('Max Light mmol'), 'M2T.LightQuantity', 7);
            $this->RegisterVariableFloat('min_light_mmol', $this->Translate('Min Light mmol'), 'M2T.LightQuantity', 8);
            $this->RegisterVariableInteger('max_light_lux', $this->Translate('Max Light Lux'), '~Illumination', 9);
            $this->RegisterVariableInteger('min_light_lux', $this->Translate('Min Light Lux'), '~Illumination', 10);
            $this->RegisterVariableFloat('max_temp', $this->Translate('Max Temperature'), '~Temperature', 11);
            $this->RegisterVariableFloat('min_temp', $this->Translate('Min Temperature'), '~Temperature', 12);
            $this->RegisterVariableInteger('max_env_humid', $this->Translate('Max ENV Humid'), '~Intensity.100', 13);
            $this->RegisterVariableInteger('min_env_humid', $this->Translate('Min ENV Humid'), '~Intensity.100', 14);
            $this->RegisterVariableInteger('max_soil_moist', $this->Translate('Max Soil moist'), '~Intensity.100', 15);
            $this->RegisterVariableInteger('min_soil_moist', $this->Translate('Min Soil moist'), '~Intensity.100', 16);
            $this->RegisterVariableInteger('max_soil_ec', $this->Translate('Max Soil EC'), 'M2T.Conductivity', 17);
            $this->RegisterVariableInteger('min_soil_ec', $this->Translate('Min Soil EC'), 'M2T.Conductivity', 18);
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

            if ($this->ReadPropertyString('pid_plant') != '') {
                $PlantData = $this->getDetailRequest($this->ReadPropertyString('pid_plant'));

                $this->SetValue('max_light_mmol', $PlantData['max_light_mmol']);
                $this->SetValue('min_light_mmol', $PlantData['min_light_mmol']);
                $this->SetValue('max_light_lux', $PlantData['max_light_lux']);
                $this->SetValue('min_light_lux', $PlantData['min_light_lux']);
                $this->SetValue('max_temp', $PlantData['max_temp']);
                $this->SetValue('min_temp', $PlantData['min_temp']);
                $this->SetValue('max_env_humid', $PlantData['max_env_humid']);
                $this->SetValue('min_env_humid', $PlantData['min_env_humid']);
                $this->SetValue('max_soil_moist', $PlantData['max_soil_moist']);
                $this->SetValue('min_soil_moist', $PlantData['min_soil_moist']);
                $this->SetValue('max_soil_ec', $PlantData['max_soil_ec']);
                $this->SetValue('min_soil_ec', $PlantData['min_soil_ec']);

                $PlantImage = file_get_contents($PlantData['image_url']);

                $PlantImageID = @IPS_GetObjectIDByIdent('PlantImage', $this->InstanceID);
                if ($PlantImageID === false) {
                    $PlantImageID = IPS_CreateMedia(1);
                    IPS_SetParent($PlantImageID, $this->InstanceID);
                    IPS_SetIdent($PlantImageID, 'PlantImage');
                    IPS_SetName($PlantImageID, $this->Translate('Image'));
                    IPS_SetPosition($PlantImageID, -5);
                    IPS_SetMediaCached($PlantImageID, true);
                    $filename = 'media' . DIRECTORY_SEPARATOR . 'PlantImgae_' . $this->InstanceID . '.png';
                    IPS_SetMediaFile($PlantImageID, $filename, false);
                    $this->SendDebug('Create Media', $filename, 0);
                }

                IPS_SetMediaContent($PlantImageID, base64_encode($PlantImage));
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