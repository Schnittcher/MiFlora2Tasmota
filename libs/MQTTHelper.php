<?php

declare(strict_types=1);
trait TasmotaMQTTHelper
{
    protected function sendMQTT($Topic, $Payload)
    {
        $retain = $this->ReadPropertyBoolean('MessageRetain');
        if ($retain) {
            $retain = true;
        } else {
            $retain = false;
        }

        $FullTopic = explode('/', $this->ReadPropertyString('FullTopic'));
        $PrefixIndex = array_search('%prefix%', $FullTopic);
        $TopicIndex = array_search('%topic%', $FullTopic);

        $SetCommandArr = $FullTopic;
        $index = count($SetCommandArr);

        $SetCommandArr[$PrefixIndex] = 'cmnd';
        $SetCommandArr[$TopicIndex] = $this->ReadPropertyString('Topic');
        $SetCommandArr[$index] = $Topic;

        $Topic = implode('/', $SetCommandArr);

        $resultServer = true;

        $Data['DataID'] = '{043EA491-0325-4ADD-8FC2-A30C8EEB4D3F}';
        $Data['PacketType'] = 3;
        $Data['QualityOfService'] = 0;
        $Data['Retain'] = false;
        $Data['Topic'] = $Topic;
        $Data['Payload'] = $Payload;
        $DataJSON = json_encode($Data, JSON_UNESCAPED_SLASHES);
        $this->SendDebug(__FUNCTION__ . 'MQTT Data', $DataJSON, 0);
        $result = @$this->SendDataToParent($DataJSON);

        if ($result === false) {
            $last_error = error_get_last();
            echo $last_error['message'];
        }
    }

    protected function FilterFullTopicReceiveData()
    {
        $FullTopic = explode('/', $this->ReadPropertyString('FullTopic'));
        $PrefixIndex = array_search('%prefix%', $FullTopic);
        $TopicIndex = array_search('%topic%', $FullTopic);

        $SetCommandArr = $FullTopic;
        $SetCommandArr[$PrefixIndex] = '.*.';
        //unset($SetCommandArr[$PrefixIndex]);
        $SetCommandArr[$TopicIndex] = $this->ReadPropertyString('Topic');
        $topic = implode('\/', $SetCommandArr);

        return $topic;
    }
}