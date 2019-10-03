<?php

namespace App;

class Planning
{

    public static function getAll()
    {
        return DbQuery::query('iso_planning', '*');
    }

    public static function getByEntrepot($ident)
    {
        return DbQuery::query('iso_planning', '*', array('id_entrepot' => $ident));
    }

    public static function getByInstallator($idinst)
    {
        return DbQuery::query('iso_planning', '*', array('id_installator' => $idinst));
    }

    public static function getPlanningWithRDV($conds)
    {
        return DbQuery::query('iso_planning p
								INNER JOIN iso_entrepots e ON e.id_entrepot = p.id_entrepot
								LEFT JOIN iso_installators i ON i.id_installator = p.id_installator
								LEFT JOIN iso_rdv r ON r.id_planning = p.id_planning
								LEFT JOIN iso_contacts c ON c.id_contact = r.id_contact',
            'p.id_planning, p.date_planning, p.hour_start, p.hour_end,
							CASE
								WHEN p.geolat <> 0 || p.geolng <> 0 THEN p.geolat
								ELSE e.geolat
							END as geolat,
							CASE
								WHEN p.geolat <> 0 || p.geolng <> 0 THEN p.geolng
								ELSE e.geolng
							END as geolng,
							r.id_rdv, r.id_contact, r.rdv_start, r.rdv_end, r.gmap,
							c.first_name, c.last_name, c.geolat as cgeolat, c.geolng as cgeolng, c.adr1, c.post_code, c.city',
            $conds, false, 'date_planning, hour_start');
    }

    public static function getPlanningCond($conds)
    {
        return DbQuery::querySimple('iso_planning p
								INNER JOIN iso_entrepots e ON e.id_entrepot = p.id_entrepot
								LEFT JOIN iso_installators i ON i.id_installator = p.id_installator
								LEFT JOIN iso_rdv r ON r.id_planning = p.id_planning
								LEFT JOIN iso_contacts c ON c.id_contact = r.id_contact',
            'p.id_planning, p.date_planning, p.hour_start, p.hour_end, p.id_installator,
							r.id_rdv, r.id_contact, r.rdv_start, r.rdv_end, r.gmap,
							c.first_name, c.last_name, c.geolat as cgeolat, c.geolng as cgeolng, c.adr1, c.post_code, c.city',
            $conds, true, 'hour_start');
    }

    public static function getDispo($id_contact, $lat, $lng, $perimeter, $dtfrom = '', $hours = '', $by_distance = true)
    {
        $strwh = '';
        if ($hours != '') {
            if (count($hours) > 0)
                foreach ($hours as $hr) {
                    $hrs = explode('-', $hr);
                    if (count($hrs) == 2)
                        $strwh .= ($strwh != '' ? ' OR ' : '') . " (p.hour_start = '" . $hrs[0] . "' AND p.hour_end = '" . $hrs[1] . "')";
                }
        }
        $sql = "SELECT QRY.* FROM (
					SELECT p.*, e.entrepot_name, i.installator_name, rdvprec.id_rdv,
						(SELECT COUNT(*)
							FROM iso_rdv rdvcount INNER JOIN iso_planning plancount ON rdvcount.id_planning = plancount.id_planning
						WHERE plancount.date_planning = p.date_planning
						  AND plancount.id_entrepot = p.id_entrepot) as nbrdv,
						CASE
							WHEN rdvprec.id_rdv IS NOT NULL THEN pDistance(c.geolat, c.geolng, " . (float)$lat . ", " . (float)$lng . ")
							WHEN p.geolat = 0 AND p.geolng = 0 THEN pDistance(e.geolat, e.geolng, " . (float)$lat . ", " . (float)$lng . ")
							ELSE pDistance(p.geolat, p.geolng, " . (float)$lat . ", " . (float)$lng . ")
						END as dis
					FROM iso_planning p
						INNER JOIN iso_entrepots e ON e.id_entrepot = p.id_entrepot
						LEFT JOIN iso_installators i ON i.id_installator = p.id_installator
						LEFT JOIN iso_rdv r ON r.id_planning = p.id_planning
						LEFT JOIN iso_planning planprec ON planprec.date_planning = p.date_planning AND planprec.id_entrepot = p.id_entrepot AND planprec.hour_end = p.hour_start
						LEFT JOIN iso_rdv rdvprec ON rdvprec.id_planning = planprec.id_planning
						LEFT JOIN iso_contacts c ON c.id_contact = rdvprec.id_contact
					WHERE r.id_rdv IS NULL /* AND i.id_installator > 1 */
					  AND p.id_planning NOT IN (SELECT rdvexist.id_planning FROM iso_rdv rdvexist WHERE rdvexist.id_contact = " . (int)$id_contact . ")
					  " . ($dtfrom != '' ? "AND p.date_planning >= '" . $dtfrom . "'" : 'AND p.date_planning > DATE(NOW())') . "
					  " . ($strwh != '' ? ' AND (' . $strwh . ')' : '') . "
				) QRY
				WHERE QRY.dis <= " . (int)$perimeter . " * 1000
				ORDER BY " . ($by_distance ? 'QRY.dis, ' : '') . " QRY.date_planning, QRY.hour_start
				LIMIT 0, 100";

//die($sql);
        return DbQuery::querySQL($sql);
    }


    public static function getInfoPlanning($idplan)
    {
        return DbQuery::query('iso_planning p
								INNER JOIN iso_entrepots e ON e.id_entrepot = p.id_entrepot
								LEFT JOIN iso_installators i ON i.id_installator = p.id_installator
								LEFT JOIN iso_rdv r ON r.id_planning = p.id_planning',
            'p.id_planning, p.id_entrepot, p.id_installator, p.date_planning, p.hour_start, p.hour_end,
							CASE
								WHEN p.geolat <> 0 || p.geolng <> 0 THEN p.geolat
								ELSE e.geolat
							END as geolat,
							CASE
								WHEN p.geolat <> 0 || p.geolng <> 0 THEN p.geolng
								ELSE e.geolng
							END as geolng, e.entrepot_name, i.installator_name, r.id_rdv, r.id_contact',
            array('p.id_planning' => $idplan), true);
    }


    /*

    public static function getMaxTour()
    {
        $res = DbQuery::query('iso_planning', 'MAX(tour_num) as maxtournum', '', true);
        return $res ? (int)$res->maxtournum : 0;
    }

    public static function getInfoTour($tournum)
    {
        return DbQuery::query('iso_planning p INNER JOIN iso_installators i ON i.id_installator = p.id_installator', 'p.*, MIN(p.hour_start) as min_hour_start, MAX(p.hour_end) as max_hour_start, i.installator_name', array('tour_num' => (int)$tournum), true);
    }

    public static function getByTour($tournum)
    {
        return DbQuery::query('iso_planning p
                                INNER JOIN iso_installators i ON i.id_installator = p.id_installator
                                LEFT JOIN iso_contacts c ON c.id_contact = p.id_contact ',
                            'p.*, c.first_name, c.last_name, c.adr1, c.post_code, c.city, c.geolat as cligeolat, c.geolng as cligeolng, i.geolat as insgeolat, i.geolng as insgeolng',
                            array('tour_num' => (int)$tournum));
    }



    public static function getByTourPos($tournum, $pos)
    {
        return DbQuery::query('iso_planning p',
                            'p.*',
                            array('tour_num' => (int)$tournum, 'planning_position' => (int)$pos), true);
    }

    public static function getNextsPositions($tournum, $pos)
    {
        return DbQuery::querySimple('iso_planning p
                                INNER JOIN iso_installators i ON i.id_installator = p.id_installator
                                LEFT JOIN iso_contacts c ON c.id_contact = p.id_contact ',
                            'p.*, c.first_name, c.last_name, c.adr1, c.post_code, c.city, c.geolat as cligeolat, c.geolng as cligeolng, i.geolat as insgeolat, i.geolng as insgeolng',
                            'tour_num = '.(int)$tournum.' AND planning_position >= '.(int)$pos.' AND p.id_contact > 0');
    }*/

    public static function create($flds)
    {
        return DbQuery::insert('iso_planning', $flds);
    }

    public static function update($flds, $wh)
    {
        return DbQuery::update('iso_planning', $flds, $wh);
    }
}
