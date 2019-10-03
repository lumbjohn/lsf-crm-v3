<?php

namespace App;

class Team
{
    public static function findOne($wh)
    {
        return DbQuery::query('iso_teams', '*', $wh, true);
    }

    public static function getAll()
    {
        return DbQuery::query('iso_teams', '*');
    }

    public static function GiveNameFromTeams($teams)
    {
        $res = DbQuery::querySimple('iso_teams', "GROUP_CONCAT(name_team SEPARATOR ', ') as name_teams", "id_team IN (" . $teams . ")", true);
        return $res->name_teams;
    }
}
