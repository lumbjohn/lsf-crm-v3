<?php

namespace App;

class CrmAction
{
    public static function create($flds)
    {
        return DbQuery::insert('iso_crmaction', $flds);
    }
}
