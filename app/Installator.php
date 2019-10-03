<?php

namespace App;

class Installator
{
    public static function findOne($conds)
    {
        return DbQuery::query('iso_installators', '*', $conds, true);
    }

    public static function getAll()
    {
        return DbQuery::query('iso_installators', '*', '', false, 'installator_name');
    }

    public static function findAvailable($start, $end, $geolat, $geolng, $available, $id_entrepot, $numplan)
    {
        return DbQuery::querySimple('iso_installators i',
            "i.id_installator, i.installator_name, i.first_name_ins, i.last_name_ins, pDistance(i.geolat, i.geolng, " . (float)$geolat . ", " . (float)$geolng . ") as dis",
            "id_installator " . ($available ? 'NOT' : '') . " IN (
										SELECT r.id_installator
										FROM iso_rdv r
										WHERE r.date_rdv BETWEEN '" . $start . "' AND '" . $end . "'
										" . (!$available ? ' AND r.id_entrepot = ' . $id_entrepot . ' AND r.num_planning = ' . $numplan : '') . "
									)", false, 'installator_name');
    }


    public static function create($flds)
    {
        return DbQuery::insert('iso_installators', $flds);
    }

    public static function update($flds, $wh)
    {
        return DbQuery::update('iso_installators', $flds, $wh);
    }

    public static function delete($conds)
    {
        return DbQuery::delete('iso_installators', $conds);
    }
}
