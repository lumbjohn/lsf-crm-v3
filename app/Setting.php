<?php

namespace App;

class Setting
{
    public static $settings = array(
        'DISTANCE_RDV' => 50,
        'DISTANCE_PROX' => 50,
        'DURATION_RDV' => '01:00',
        'MORNING_START' => '08:00',
        'MORNING_END' => '12:00',
        'AFTERNOON_START' => '14:00',
        'AFTERNOON_END' => '18:00',
        'TX_TVA' => 5.5,
        'STATUS_MISS_CONFIRM' => ''
    );

    public static $days = array(
        'Dimanche',
        'Lundi',
        'Mardi',
        'Mercredi',
        'Jeudi',
        'Vendredi',
        'Samedi'
    );

    public static $type_preca = array(
        0 => 'Classique',
        1 => 'Précarité',
        2 => 'Grande précarité'
    );

    public static $typechauf = array(/*'Aucun',*/
        'Electricité', 'Fioul', 'Gaz / GPL', 'Bois', 'Autre');

    public static function getGlobalSettings()
    {
        $savesettings = ArrayLoader::loadFlatArray(DbQuery::query('iso_appsettings', '*'), 'value', 'name');
        //print_r(Setting::$settings);
        //print_r($savesettings);
        //die;
        return array_merge(Setting::$settings, $savesettings ? $savesettings : array());
    }

    public static function updateGlobalSettings($name, $value)
    {
        global $db;
        $sql = "INSERT INTO iso_appsettings (name, value)
				VALUES ('" . $name . "', '" . mysqli_real_escape_string($db, $value) . "')
				ON DUPLICATE KEY UPDATE
				name = '" . $name . "', value = '" . mysqli_real_escape_string($db, $value) . "'";
        //echo($sql);
        return DbQuery::querySQL($sql);
    }

    public static function getRDVRange()
    {
        return DbQuery::query('iso_rdv_range', '*');
    }

    public static function getAllStatus()
    {
        return DbQuery::query('iso_statuscont', '*');
    }

    public static function getAllStatusConf()
    {
        return DbQuery::query('iso_statuscontconf', '*');
    }

    public static function getStatus($conds = '')
    {
        return DbQuery::query('iso_statuscont', '*', $conds, true);
    }

    public static function getStatusConf($conds = '')
    {
        return DbQuery::query('iso_statuscontconf', '*', $conds, true);
    }

    public static function getAllMandators($conds = '')
    {
        return DbQuery::query('iso_mandators', '*', $conds);
    }

    public static function getMandator($conds = '')
    {
        return DbQuery::query('iso_mandators', '*', $conds, true);
    }

    public static function getAllContributors($conds = '')
    {
        return DbQuery::query('iso_contributors', '*', $conds);
    }

    public static function getAllMaterials($conds = '')
    {
        return DbQuery::query('iso_materials', '*', $conds);
    }

    public static function getMaterial($conds = '')
    {
        return DbQuery::query('iso_materials', '*', $conds, true);
    }


    public static function getParrains($conds = '')
    {
        return DbQuery::query('iso_parrains', '*', $conds);
    }

    public static function getCampains($conds = '')
    {
        return DbQuery::query('iso_campains', '*', $conds);
    }

    public static function getDepartments($conds = '')
    {
        return DbQuery::query('iso_departments', '*', $conds);
    }

    public static function getDepartment($conds = '')
    {
        return DbQuery::query('iso_departments', '*', $conds, true);
    }

    public static function getPrecarityInfo($post_code, $revenu, $nbperson, $typechauf, $surface101, $surface102, $surface103)
    {
        $res = array();
        $dept = substr($post_code, 0, 2);
        $isIDF = in_array($dept, array('75', '77', '78', '91', '92', '93', '94', '95'));
        $soc = DbQuery::querySimple('iso_plafonds_bonus', '*', 'nb_person = ' . (int)$nbperson . ' AND (mt_gp' . ($isIDF ? '_idf' : '') . ' >= ' . (float)$revenu . ' OR mt_p' . ($isIDF ? '_idf' : '') . ' >= ' . (float)$revenu . ')', true);
        $preca = 0;
        if (in_array($dept, array('66', '11', '34', '30', '13', '83', '06', '2A', '2B')))
            $zone = 'H3';
        else
            if (in_array($dept, array('29', '22', '56', '50', '35', '53', '44', '72', '49', '85', '41', '37', '79', '18', '36', '86', '17', '16', '33', '24', '40', '46', '47', '64', '65', '09', '31', '32', '81', '82', '46', '12', '48', '07', '26', '84', '04')))
                $zone = 'H2';
            else
                $zone = 'H1';

        if ($soc) {
            if ($soc->{'mt_gp' . ($isIDF ? '_idf' : '')} >= (float)$revenu)
                $preca = 2;
            else
                if ($soc->{'mt_p' . ($isIDF ? '_idf' : '')} >= (float)$revenu)
                    $preca = 1;
        }
        $res['preca'] = $preca;
        $res['zone'] = $zone;
        $res['cumac101'] = 0;
        $res['cumac102'] = 0;
        $res['cumac103'] = 0;
        if ($surface101 > 0) {
            //prix selon la tranche 'grande precarite';
            $bonus = DbQuery::query('iso_bonus_grid', '*', array('type_preca' => '2', 'type_prestation' => '101', 'zone' => $zone, 'type_chauf' => $typechauf == 'Electricité' ? 0 : 1), true);
            if ($bonus)
                $res['pu101'] = $bonus->mt_bonus;

            $bonus = DbQuery::query('iso_bonus_grid', '*', array('type_preca' => $preca, 'type_prestation' => '101', 'zone' => $zone, 'type_chauf' => $typechauf == 'Electricité' ? 0 : 1), true);
            if ($bonus)
                $res['bonus101'] = $bonus->mt_bonus;

            $cumac = DbQuery::query('iso_cumacs', '*', array('type_preca' => $preca, 'type_prestation' => '101'), true);
            if ($cumac)
                $res['cumac101'] = $surface101 * (float)$cumac->nb_cumac;
        }

        if ($surface102 > 0) {
            //prix selon la tranche 'grande precarite';
            $bonus = DbQuery::query('iso_bonus_grid', '*', array('type_preca' => '2', 'type_prestation' => '102', 'zone' => $zone, 'type_chauf' => $typechauf == 'Electricité' ? 0 : 1), true);
            if ($bonus)
                $res['pu102'] = $bonus->mt_bonus;

            $bonus = DbQuery::query('iso_bonus_grid', '*', array('type_preca' => $preca, 'type_prestation' => '102', 'zone' => $zone, 'type_chauf' => $typechauf == 'Electricité' ? 0 : 1), true);
            if ($bonus)
                $res['bonus102'] = $bonus->mt_bonus;

            $cumac = DbQuery::query('iso_cumacs', '*', array('type_preca' => $preca, 'type_prestation' => '102'), true);
            if ($cumac)
                $res['cumac102'] = $surface101 * (float)$cumac->nb_cumac;
        }

        if ($surface103 > 0) {
            //prix selon la tranche 'grande precarite';
            $bonus = DbQuery::query('iso_bonus_grid', '*', array('type_preca' => '2', 'type_prestation' => '103', 'zone' => $zone, 'type_chauf' => $typechauf == 'Electricité' ? 0 : 1), true);
            if ($bonus)
                $res['pu103'] = $bonus->mt_bonus;

            $bonus = DbQuery::query('iso_bonus_grid', '*', array('type_preca' => $preca, 'type_prestation' => '103', 'zone' => $zone, 'type_chauf' => $typechauf == 'Electricité' ? 0 : 1), true);
            if ($bonus)
                $res['bonus103'] = $bonus->mt_bonus;

            $cumac = DbQuery::query('iso_cumacs', '*', array('type_preca' => $preca, 'type_prestation' => '103'), true);
            if ($cumac)
                $res['cumac103'] = $surface103 * (float)$cumac->nb_cumac;
        }

        //print_r($res);
        //die;
        return $res;
    }
}
