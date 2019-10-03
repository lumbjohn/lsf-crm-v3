<?php

namespace App;

class CrmUserTeam
{
    public static function findOne($wh)
    {
        return DbQuery::query('iso_crmusers_teams', '*', $wh, true);
    }
}
