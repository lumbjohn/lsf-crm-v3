<?php

namespace App;

class Tool
{
    public static function DropDown($id, $arr, $val, $txt, $selval, $deftxt = '')
    {
        $html = '<select aria-describedby="' . $id . '" id="' . $id . '"  name="' . $id . '" class="form-control">';
        if ($deftxt != '')
            $html .= '<option value="0">' . $deftxt . '</option>';
        foreach ($arr as $line) {
            $sel = $line[$val] == $selval ? 'selected="selected"' : '';
            $html .= '<option value="' . $line[$val] . '" ' . $sel . '>' . $line[$txt] . '</option>';
        }
        $html .= '</select>';

        return $html;
    }

    public static function DropDownArray($id, $arr, $sfx = '')
    {
        $html = '<select aria-describedby="' . $id . '" id="' . $id . '"  name="' . $id . '" class="form-control">';
        foreach ($arr as $key => $val)
            $html .= '<option value="' . $key . '">' . $val . $sfx . '</option>';
        $html .= '</select>';

        return $html;
    }

    public static function dmYtoYmd($dt)
    {
        $dts = explode('/', $dt);
        if (count($dts) <> 3)
            return '';
        else
            return $dts[2] . '-' . $dts[1] . '-' . $dts[0];
    }

    public static function addTimeStr($strtime)
    {
        $times = explode(':', $strtime);
        if (count($times) < 2)
            return '';
        else {
            $hr = (int)$times[0] > 0 ? '+ ' . $times[0] . ' hour ' : '';
            $mn = (int)$times[1] > 0 ? '+ ' . $times[1] . ' minutes ' : '';
            $sc = isset($times[2]) && (int)$times[2] > 0 ? '+ ' . $times[2] . ' seconds ' : '';

            $str = $hr . $mn . $sc;
            if ($str == '')
                $str = '+ 0 seconds ';
            return $str;
        }
    }

    public static function isImage($file)
    {
        return strpos($file, '.jpg') > 0 || strpos($file, '.gif') > 0 || strpos($file, '.jpeg') > 0 || strpos($file, '.png') > 0 || strpos($file, '.bmp') > 0;
    }

    public static function fmtMtDown($number)
    {
        return intval(($number * 100)) / 100;
    }

    public static function displayMt($number, $forcedec = true)
    {
        $whole = floor($number);
        $fraction = $number - $whole;
        if ($fraction == 0 && !$forcedec)
            return number_format($number, 0, ',', ' ');
        else
            return number_format($number, 2, ',', ' ');
    }

    public static function fulldatestr($dt)
    {
        $str = date('l d F Y', strtotime($dt));

        return str_replace(array(
            'Monday',
            'Tuesday',
            'Wednesday',
            'Thursday',
            'Friday',
            'Saturday',
            'Sunday',
            'January',
            'Febuary',
            'March',
            'April',
            'May',
            'June',
            'July',
            'August',
            'September',
            'October',
            'November',
            'December'), array(
            'Lundi',
            'Mardi',
            'Mercredi',
            'Jeudi',
            'Vendredi',
            'Samedi',
            'Dimanche',
            'Janvier',
            'Fevrier',
            'Mars',
            'Avril',
            'Mai',
            'Juin',
            'Juillet',
            'Aout',
            'Septembre',
            'Octobre',
            'Novembre',
            'Decembre'
        ), $str);
    }

    public static function distancestr($dis)
    {
        if ($dis > 1000)
            return round($dis / 1000) . ' km';
        else
            return round($dis) . ' m';
    }

    public static function delaystr($delay)
    {
        $h = (int)($delay / 3600);
        $m = (int)(($delay % 3600) / 60);
        return $h > 0 ? $h . 'h' . $m : $m . 'm';
    }


    public static function arrayToStr($arr, $html = true)
    {
        if (count($arr) == 0 || !is_array($arr))
            return '';
        $str = '';
        $sep = $html ? '<br>' : "\n";
        foreach ($arr as $k => $v)
            $str .= ($str != '' ? $sep : '') . $k . ' : ' . $v;
        return $str;
    }

    public static function getInitials($words)
    {
        $res = '';
        $arr = explode(' ', $words);
        foreach ($arr as $a)
            $res .= substr($a, 0, 1);
        return $res;
    }

    public static function getRealIpAddr()
    {
        if (!empty($_SERVER['HTTP_CLIENT_IP']))   //check ip from share internet
        {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR']))   //to check ip is pass from proxy
        {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        return $ip;
    }

    public static function approxMt($mt)
    {
        if ($mt > 1000000000)
            return number_format($mt / 1000000000, 1) . ' ' . Lang::tr('Milliard');
        else
            if ($mt > 1000000)
                return number_format($mt / 1000000, 1) . ' ' . Lang::tr('Million');
            else
                if ($mt > 100000)
                    return number_format($mt / 1000, 1) . ' ' . Lang::tr('K');
                else
                    return $mt;
    }


    public static function doResize($upload_dir, $name)
    {
        $image = new \Eventviva\ImageResize($upload_dir . $name);
        $image->crop(200, 200);
        $image->save($upload_dir . $name);

        /*$image->resizeToBestFit(60, 60);
        $image->save($upload_dir . 'small/'. $name);*/
    }

    public static function uploadFile($upload_dir, $myFile, $withResize = false)
    {

        if ($myFile["error"] !== UPLOAD_ERR_OK) {
            echo "<p>An error occurred.</p>";
            die;
        }

        // ensure a safe filename
        $name = preg_replace("/[^A-Z0-9._-]/i", "_", $myFile["name"]);

        // don't overwrite an existing file
        $i = 0;
        $parts = pathinfo($name);
        while (file_exists($upload_dir . $name)) {
            $i++;
            $name = $parts["filename"] . "-" . $i . "." . $parts["extension"];
        }

        // preserve file from temporary directory
        $success = move_uploaded_file($myFile["tmp_name"],
            $upload_dir . $name);
        if (!$success) {
            echo "Unable to save file.";
            die;
        } else {
            if ($withResize)
                Tool::doResize($upload_dir, $name);
            return $name;
        }

        // set proper permissions on the new file
        //chmod(upload_dir . $name, 0644);
    }


    public static function getCurl($url, $pst = array())
    {
        $strResponse = '';
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        if (count($pst) > 0) {
            curl_setopt($ch, CURLOPT_POST, count($pst));
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($pst));
            //die(http_build_query($pst));
        }

        $strResponse = curl_exec($ch);
        curl_close($ch);

        return $strResponse;
    }

    public static function getDistance($deplat, $deplng, $deslat, $deslng)
    {
        $theta = $deplng - $deslng;
        $dist = sin(deg2rad($deplat)) * sin(deg2rad($deslat)) + cos(deg2rad($deplat)) * cos(deg2rad($deslat)) * cos(deg2rad($theta));
        $dist = acos($dist);
        $dist = rad2deg($dist);
        $miles = $dist * 60 * 1.1515;
        //$unit = strtoupper($unit);


        $km = $miles * 1.609344; //in km
        return $km * 1.28; //impact vol d'oiseau / road map
    }

    public static function getDirection($deplat, $deplng, $deslat, $deslng, $deptime)
    {
        $strdepinf = $deptime > time() ? '&departure_time=' . $deptime : '';
        $url = 'https://maps.googleapis.com/maps/api/directions/json?origin=' . $deplat . ',' . $deplng . '&destination=' . $deslat . ',' . $deslng . $strdepinf . '&key=' . env('GOOGLE_API_KEY');
        $info = Tool::getCurl($url);
        return $info;
    }

    public static function getLatLngFromAddress($address, &$lat, &$lng)
    {
        $url = 'https://maps.googleapis.com/maps/api/geocode/json?address=' . str_replace(' ', '+', $address) . '&key=' . env('GOOGLE_API_KEY');
        $info = Tool::getCurl($url);
        $info = json_decode($info);
        if (count($info->results) > 0) {
            $lat = $info->results[0]->geometry->location->lat;
            $lng = $info->results[0]->geometry->location->lng;
            return true;
        }
        return false;
    }
}
