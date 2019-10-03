<?php

namespace App;

class CrmUser
{
    public static function findOne($wh)
    {
        return DbQuery::query('iso_crmusers u LEFT JOIN iso_teams t ON t.id_team = u.id_team', 'u.*, t.name_team', $wh, true);
    }

    public static function getAll($conds = '')
    {
        return DbQuery::query('iso_crmusers', '*', $conds, false, 'user_name');
    }

    public static function isTelepro($cuser)
    {
        return is_object($cuser) ? $cuser->id_profil == '2' : (is_array($cuser) ? $cuser['id_profil'] == '2' : $cuser == '2');
    }

    public static function isConfirm($cuser)
    {
        return is_object($cuser) ? $cuser->id_profil == '4' : (is_array($cuser) ? $cuser['id_profil'] == '4' : $cuser == '4');
    }

    public static function isConfirmateur($cuser)
    {
        return is_object($cuser) ? $cuser->id_profil == '4' || $cuser->id_profil == '5' : (is_array($cuser) ? $cuser['id_profil'] == '4' || $cuser['id_profil'] == '5' : $cuser == '4' || $cuser == '5');
    }

    public static function isConfPlus($cuser)
    {
        return is_object($cuser) ? $cuser->id_profil == '5' : (is_array($cuser) ? $cuser['id_profil'] == '5' : $cuser == '5');
    }

    public static function isManager($cuser)
    {
        return is_object($cuser) ? $cuser->id_profil == '3' : (is_array($cuser) ? $cuser['id_profil'] == '3' : $cuser == '3');
    }

    public static function isAdmin($cuser)
    {
        return is_object($cuser) ? $cuser->id_profil == '1' : (is_array($cuser) ? $cuser['id_profil'] == '1' : $cuser == '1');
    }

    public static function create($flds)
    {
        return DbQuery::insert('iso_crmusers', $flds);
    }

    public static function delete($flds)
    {
        return DbQuery::delete('iso_crmusers', $flds);
    }

    public static function update($flds, $wh)
    {
        return DbQuery::update('iso_crmusers', $flds, $wh);
    }
}
