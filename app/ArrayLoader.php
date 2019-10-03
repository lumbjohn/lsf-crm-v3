<?php

namespace App;

class ArrayLoader
{
    public static function loadAssoc($res, $key = '')
    {
        if ($res && $res->num_rows > 0) {
            $ar = array();
            foreach ($res as $data)
                if (!empty($key))
                    $ar[$data[$key]] = $data;
                else
                    $ar[] = $data;
            return $ar;
        }
        return false;
    }

    public static function loadFlatArray($res, $fld, $key = '')
    {
        if ($res && $res->num_rows > 0) {
            $ar = array();
            while ($data = mysqli_fetch_assoc($res))
                if ($key == '')
                    $ar[] = $data[$fld];
                else
                    $ar[$data[$key]] = $data[$fld];
            return $ar;
        }

        return false;
    }
}
