<?php

namespace App;

class SMS
{
    public static function SendMessage($txt, $fullnum)
    {
        $sets = (object)Setting::getGlobalSettings();
        $prms = array(
            'method' => 'http',
            'username' => $sets->CLICKSEND_UNAME,
            'key' => $sets->CLICKSEND_KEY,
            'to' => $fullnum,
            'message' => $txt,
            'senderid' => 'LSF Energie'
        );

        $url = 'https://api-mapper.clicksend.com/http/v2/send.php'; //?'.http_build_query($prms);
        return Tool::getCurl($url, $prms);
    }

    public static function SendMessageOVH($txt, &$num)
    {

        // FIXME: change credentials

        //!!demo - return true;
        $endpoint = 'ovh-eu';
        $applicationKey = "FwtEIhoQcFJJScsm";
        $applicationSecret = "uLXeh0y4okeTEHYZix2o05ZdYfpr9fME";
        $consumer_key = "qQWh8IpNn5keZtHq7L38eIdKvNZLxikC";

        $conn = new Api($applicationKey,
            $applicationSecret,
            $endpoint,
            $consumer_key);

        $smsServices = $conn->get('/sms/');
        /*foreach ($smsServices as $smsService) {

            print_r($smsService);
        }*/

        $num = str_replace(' ', '', $num);
        $num = str_replace('-', '', $num);
        $num = str_replace('.', '', $num);

        if (substr($num, 0, 4) == '0033')
            $num = '+33' . substr($num, 4);
        else
            if (substr($num, 0, 1) == '0')
                $num = '+33' . substr($num, 1);

        //!! a retirer num de test wes !
        //$num = "+33665490101";

        $content = (object)array(
            "charset" => "UTF-8",
            "class" => "phoneDisplay",
            "coding" => "7bit",
            "message" => $txt,
            "noStopClause" => true,
//			"sender" => "Handigo",
            "priority" => "high",
            "receivers" => [$num],
            "senderForResponse" => true,
            "validityPeriod" => 1440
        );

        $resultPostJob = $conn->post('/sms/' . $smsServices[0] . '/jobs/', $content);

        return isset($resultPostJob['validReceivers']) && isset($resultPostJob['validReceivers'][0]) && $resultPostJob['validReceivers'][0] == $num;


        /*$smsJobs = $conn->get('/sms/'. $smsServices[0] . '/jobs/');
        print_r($smsJobs);*/
    }
}
