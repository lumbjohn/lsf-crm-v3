<?php

namespace App;

class Entrepot
{
    public static function findOne($conds)
    {
        return DbQuery::query('iso_entrepots', '*', $conds, true);
    }

    public static function getAll()
    {
        return DbQuery::query('iso_entrepots', '*', '', false, 'entrepot_name');
    }

    public static function getNearFromLatLng($geolat, $geolng)
    {
        return DbQuery::query('iso_entrepots e', 'e.geolat, e.geolng, e.id_entrepot, pDistance(e.geolat, e.geolng, ' . (float)$geolat . ', ' . (float)$geolng . ') as distance', '', true, 'distance');
    }


    public static function create($flds)
    {
        return DbQuery::insert('iso_entrepots', $flds);
    }

    public static function update($flds, $wh)
    {
        return DbQuery::update('iso_entrepots', $flds, $wh);
    }

    public static function delete($conds)
    {
        return DbQuery::delete('iso_entrepots', $conds);
    }
}
