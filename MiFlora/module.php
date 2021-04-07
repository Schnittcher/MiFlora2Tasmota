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

        // -- property names --
        private const PROP_TEMPERATURE_HINT                     = 'TemperatureHint';
        private const PROP_FERTILIZE_HINT                       = 'FertilizeHint';
        private const PROP_ILLUMINANCE_HINT                     = 'IlluminanceHint';
        private const PROP_HUMIDITY_HINT                        = 'HumidityHint';
        private const PROP_DLI_HINT                             = 'DLIHint';

        // -- variable names --
        private const VAR_TEMPERATURE                           = 'Temperature';
        private const VAR_ILLUMINANCE                           = 'Illuminance';
        private const VAR_TEMPERATURE_HINT                      = 'TemperatureHint';
        private const VAR_FERTILIZE_HINT                        = 'FertilizeHint';
        private const VAR_ILLUMINANCE_HINT                      = 'IlluminanceHint';
        private const VAR_HUMIDITY_HINT                         = 'HumidityHint';
        private const VAR_DLI_HINT                              = 'DLIHint';


        public function Create()
        {
            //Never delete this line!
            parent::Create();
            $this->ConnectParent('{C6D2AEB3-6E1F-4B2E-8E69-3A1A00246850}');

            // register attributes
            $this->RegisterAttributeString('Token', '');
            $this->RegisterAttributeInteger('TokenExpires', 0);
            $this->RegisterAttributeString('TokenType', '');

            // register properties
            $this->RegisterPropertyString('Topic', 'tasmota_miflora');
            $this->RegisterPropertyString('FullTopic', '%prefix%/%topic%');
            $this->RegisterPropertyString('Devicename', '');
            $this->RegisterPropertyString('pid_plant', '');

            $this->RegisterPropertyBoolean(self::PROP_TEMPERATURE_HINT, false);
            $this->RegisterPropertyBoolean(self::PROP_FERTILIZE_HINT, false);
            $this->RegisterPropertyBoolean(self::PROP_ILLUMINANCE_HINT, false);
            $this->RegisterPropertyBoolean(self::PROP_HUMIDITY_HINT, false);
            $this->RegisterPropertyBoolean(self::PROP_DLI_HINT, false);

            $this->RegisterPropertyString('ClientID', '');
            $this->RegisterPropertyString('ClientSecret', '');

            $this->RegisterPropertyBoolean('MAC-Address', false);
            $this->RegisterPropertyBoolean('Firmware', false);
            $this->RegisterPropertyBoolean('ExpertFilter', false);


            // register M2T profiles
            $this->RegisterProfileIntegerEx('M2T.Conductivity', 'Flower', '', ' µS/cm', [], 1000, 1);
            $this->RegisterProfileFloatEx('M2T.LightQuantity', 'Sun', '', ' µmol/m²/s', [], 15000, 0.1, 2);

            $associations = [];
            $associations[] = [0, $this->Translate('OK'), '', 0x00FF00];
            $associations[] = [1, $this->Translate('too cold'), '', 0xFF0000];
            $associations[] = [2, $this->Translate('too warm'), '', 0xFF0000];
            $this->RegisterProfileIntegerEx('M2T.TemperatureHint', '', '', '', $associations);

            $associations = [];
            $associations[] = [0, $this->Translate('OK'), '', 0x00FF00];
            $associations[] = [1, $this->Translate('under-fertilized'), '', 0xFF0000];
            $associations[] = [2, $this->Translate('over-fertilized'), '', 0xFF0000];
            $this->RegisterProfileIntegerEx('M2T.FertilizeHint', '', '', '', $associations);

            $associations = [];
            $associations[] = [0, $this->Translate('OK'), '', 0x00FF00];
            $associations[] = [1, $this->Translate('too dark'), '', 0xFF0000];
            $associations[] = [2, $this->Translate('too bright'), '', 0xFF0000];
            $this->RegisterProfileIntegerEx('M2T.IlluminanceHint', '', '', '', $associations);

            $associations = [];
            $associations[] = [0, $this->Translate('OK'), '', 0x00FF00];
            $associations[] = [1, $this->Translate('too dark'), '', 0xFF0000];
            $associations[] = [2, $this->Translate('too bright'), '', 0xFF0000];
            $this->RegisterProfileIntegerEx('M2T.DLIHint', '', '', '', $associations);

            $associations = [];
            $associations[] = [0, $this->Translate('OK'), '', 0x00FF00];
            $associations[] = [1, $this->Translate('too dry'), '', 0xFF0000];
            $associations[] = [2, $this->Translate('too wet'), '', 0xFF0000];
            $this->RegisterProfileIntegerEx('M2T.HumidityHint', '', '', '', $associations);


            // register variables
            $this->RegisterVariableFloat(self::VAR_TEMPERATURE, $this->Translate('Temperature'), '~Temperature', 1);
            $this->RegisterVariableInteger(self::VAR_ILLUMINANCE, $this->Translate('Illuminance'), '~Illumination', 2);
            $this->RegisterVariableInteger('Moisture', $this->Translate('Moisture'), '~Intensity.100', 3);
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

            if ($this->ReadPropertyBoolean(self::PROP_TEMPERATURE_HINT)){
                $this->RegisterVariableInteger(self::VAR_TEMPERATURE_HINT, $this->Translate('Temperature Hint'), 'M2T.TemperatureHint', 19);
                AC_SetLoggingStatus(IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}')[0], $this->GetIDForIdent(self::VAR_TEMPERATURE), true);
            }
            if ($this->ReadPropertyBoolean(self::PROP_FERTILIZE_HINT)){
                $this->RegisterVariableInteger(self::VAR_FERTILIZE_HINT, $this->Translate('Soil EC Hint'), 'M2T.FertilizeHint', 19);
            }
            if ($this->ReadPropertyBoolean(self::PROP_ILLUMINANCE_HINT)){
                $this->RegisterVariableInteger(self::VAR_ILLUMINANCE_HINT, $this->Translate('Light Lux Hint'), 'M2T.IlluminanceHint', 19);
            }
            if ($this->ReadPropertyBoolean(self::PROP_DLI_HINT)){
                $this->RegisterVariableInteger(self::VAR_DLI_HINT, $this->Translate('Daily Light Integral Hint'), 'M2T.DLIHint', 19);
                AC_SetLoggingStatus(IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}')[0], $this->GetIDForIdent(self::VAR_ILLUMINANCE), true);
            }
            if ($this->ReadPropertyBoolean(self::PROP_HUMIDITY_HINT)){
                $this->RegisterVariableInteger(self::VAR_HUMIDITY_HINT, $this->Translate('Soil moist Hint'), 'M2T.HumidityHint', 19);
            }

            $this->MaintainVariable('MAC', $this->Translate('MAC-Address'), 3, '', 0, $this->ReadPropertyBoolean('MAC-Address') == true);
            $this->MaintainVariable('Firmware', $this->Translate('Firmware'), 3, '', 0, $this->ReadPropertyBoolean('Firmware') == true);

            $ReceiveDataFilter = $this->ReadPropertyString('Topic');
            if ($this->ReadPropertyBoolean('ExpertFilter')) {
                $ReceiveDataFilter = $this->ReadPropertyString('Devicename');
            }

            if ($this->ReadPropertyString('pid_plant') !== '') {
                $PlantData = $this->getDetailRequest($this->ReadPropertyString('pid_plant'));
                if ($PlantData !== []){
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
                        $PlantImageID = IPS_CreateMedia(MEDIATYPE_IMAGE);
                        IPS_SetParent($PlantImageID, $this->InstanceID);
                        IPS_SetIdent($PlantImageID, 'PlantImage');
                        IPS_SetName($PlantImageID, $this->Translate('Image'));
                        IPS_SetPosition($PlantImageID, -5);
                        IPS_SetMediaCached($PlantImageID, true);
                        $filename = 'media' . DIRECTORY_SEPARATOR . 'PlantImage_' . $this->InstanceID . '.png';
                        IPS_SetMediaFile($PlantImageID, $filename, false);
                        $this->SendDebug('Create Media', $filename, 0);
                    }

                    IPS_SetMediaContent($PlantImageID, base64_encode($PlantImage));
                }
            }

            $this->SetReceiveDataFilter('.*' . $ReceiveDataFilter . '.*');

            $this->SetSummary($this->ReadPropertyString('Devicename'));
        }

        public function ReceiveData($JSONString)
        {
            $this->SendDebug('JSON', $JSONString, 0);
            $data = json_decode($JSONString, true);

            if (array_key_exists('Topic', $data) && fnmatch('*SENSOR', $data['Topic'])) {
                $Payload = json_decode($data['Payload'], true);
                foreach ($Payload as $key => $Device) {
                    if ($key === $this->ReadPropertyString('Devicename')) {
                        $this->SetValueIfNotNull(self::VAR_TEMPERATURE, $Device['Temperature']);
                        $this->SetValueIfNotNull(self::VAR_ILLUMINANCE, $Device['Illuminance']);
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

                        $this->SetHints();
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

        private function SetHints(){
            //SoilMoisture
            if ($this->ReadPropertyBoolean(self::PROP_HUMIDITY_HINT)){
                $hint = $this->getSoilMoistureHint();

                if ($hint !== -1) {
                    $this->SetValue(self::VAR_HUMIDITY_HINT, $hint);
                }
            }

            //Fertilize
            if ($this->ReadPropertyBoolean(self::PROP_FERTILIZE_HINT)) {
                $hint = $this->getFertilizeHint();

                if ($hint !== -1) {
                    $this->SetValue(self::VAR_FERTILIZE_HINT, $hint);
                }
            }

            //Illuminance
            if ($this->ReadPropertyBoolean(self::PROP_ILLUMINANCE_HINT)) {
                $hint = $this->getIlluminanceHint();

                if ($hint !== -1) {
                    $this->SetValue(self::VAR_ILLUMINANCE_HINT, $hint);
                }
            }

            //Temperature
            if ($this->ReadPropertyBoolean(self::PROP_TEMPERATURE_HINT)) {
                $hint = $this->getTemperatureHint();

                if ($hint !== -1) {
                    $this->SetValue(self::VAR_TEMPERATURE_HINT, $hint);
                }
            }

            //tägliche Lichtmenge
            if ($this->ReadPropertyBoolean(self::PROP_TEMPERATURE_HINT)) {
                $hint = $this->getDLIHint();

                if ($hint !== -1) {
                    $this->SetValue(self::VAR_DLI_HINT, $hint);
                }
            }

        }

        private function getSoilMoistureHint(): int{
            $min = $this->GetValue('min_soil_moist');
            $max = $this->GetValue('max_soil_moist');

            if ($min === 0 && $max === 0) {
                return -1;
            }

            if ($this->GetValue(self::VAR_TEMPERATURE) < 3) {
                return -1;
            }

            $value = $this->GetValue('Moisture');

            if ($value < $min) {
                return 1;
            }

            if ($value > $max) {
                return 2;
            }

            return 0;
        }

        private function getFertilizeHint(): int{
            $min = $this->GetValue('min_soil_ec');
            $max = $this->GetValue('max_soil_ec');

            if ($min === 0 && $max === 0) {
                return -1;
            }

            $month = (int)date('n'); // in den Wintermonaten wird nicht gedüngt
            if (($month < 2) || ($month > 9)) {
                return 0;
            }

            // bei Feuchtigkeitswerten unter 30% hat die Leitfähigkeit keine Aussagekraft
            if ($this->GetValue('Moisture') < 30) {
                return -1;
            }

            $value = $this->GetValue('Fertility');
            if ($value < $min){
                return 1;
            }

            if ($value > $max){
                return 2;
            }

            return 0;
        }

        private function getIlluminanceHint(): int{
            $min = $this->GetValue('min_light_lux');
            $max = $this->GetValue('max_light_lux');

            if ($min === 0 && $max === 0) {
                return -1;
            }

            // in den Monaten 11 - 03 wird von 10:00 Uhr bis 15:59 die Helligkeit überprüft
            // sonst wird von 9 - 17:59 die Helligkeit überprüft
            $month = (int)date('n');
            $hour = (int)date('G');
            if ($month >= 11 || $month <= 3){
                $hour_from = 10;
                $hour_to = 15;
            } else {
                $hour_from = 9;
                $hour_to = 17;
            }

            if (($hour < $hour_from) || ($hour > $hour_to)) {
                return -1;
            }

            $value = $this->GetValue(self::VAR_ILLUMINANCE);
            if ($value < $min){
                return 1;
            }

            if ($value > $max){
                return 2;
            }

            return 0;
        }

        private function getTemperatureHint(): int{
            $min = $this->GetValue('min_temp');
            $max = $this->GetValue('max_temp');

            if ($min === 0.0 && $max === 0.0) {
                return -1;
            }

            // calc avg of last 24 hours
            $EndTime = time();
            $StartTime = strtotime('- 24 hours', $EndTime);

            $loggedValues = @AC_GetAggregatedValues(IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}')[0],
                                   $this->GetIDForIdent(self::VAR_TEMPERATURE),
                                   0,
                                    $StartTime,
                                    $EndTime,
                                    0);
            if (!$loggedValues || $loggedValues === []){
                $this->SendDebug(__FUNCTION__, sprintf('No aggregated values available for id %s', $this->GetIDForIdent(self::VAR_TEMPERATURE)), 0);
                return -1;
            }

            $Avg_sum = 0;
            foreach ($loggedValues as $item){
                $Avg_sum += $item['Avg'];
            }
            $value =  $Avg_sum/count($loggedValues);

            if ($value < $min){
                return 1;
            }

            if ($value > $max){
                return 2;
            }

            return 0;
        }
        private function getDLIHint(): int{
            $min = $this->GetValue('max_light_mmol');
            $max = $this->GetValue('max_light_mmol');

            if ($min === 0.0 && $max === 0.0) {
                return -1;
            }

            // calc DLI of last 3 days
            $EndTime = strtotime('today');
            $StartTime = strtotime('-1 day', $EndTime);
            $ArchiveHandlerID = IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}')[0];
            $IlluminanceId = $this->GetIDForIdent(self::VAR_ILLUMINANCE);


            $DLIHints = [];

            for ($i = 1; $i <= 3;$i++){
                $arr = @AC_GetLoggedValues($ArchiveHandlerID, $IlluminanceId, $StartTime, $EndTime, 0);
                if (!$arr || $arr === []){
                    $this->SendDebug(__FUNCTION__, sprintf('No logged values available for id %s', $this->GetIDForIdent(self::VAR_ILLUMINANCE)), 0);
                    return -1;
                }
                $sum_mol = 0;

                foreach (array_reverse($arr) as $item){
                    $sum_mol += $item['Value'] * 0.0185 * $item['Duration'] / 1000000;
                }

                if ($sum_mol < $min){
                    $DLIHints[] =  1;
                } elseif ($sum_mol > $max){
                    $DLIHints[] =  2;
                } else {
                    $DLIHints[] = 0;
                }

                $EndTime = strtotime('-1 day', $EndTime);
                $StartTime = strtotime('-1 day', $StartTime);

            }

            // ist die Lichtmenge an allen Tagen unter- bzw. überschritten?
            if (array_unique($DLIHints) === [1]){
                return 1;
            }
            if (array_unique($DLIHints) === [2]){
                return 2;
            }

            return 0;
        }

    }