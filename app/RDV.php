<?php

namespace App;

class RDV
{
    public static function findOne($conds)
    {
        //return DbQuery::query('iso_rdv r INNER JOIN iso_planning p ON r.id_planning = p.id_planning', 'r.*, p.*', $conds, true);
        return DbQuery::query('iso_rdv r', 'r.*', $conds, true);
    }

    public static function findExists($id_entrepot, $numplan, $dtrdv, $hrstart, $hrend, $typerdv = 0)
    {
        $wh = " id_entrepot = " . (int)$id_entrepot . "
			AND num_planning = " . (int)$numplan . "
			AND date_rdv = '" . $dtrdv . "'
			AND type_rdv = " . $typerdv . "
			AND ((rdv_start <= '" . $hrstart . "' AND rdv_end > '" . $hrstart . "') OR (rdv_start < '" . $hrend . "' AND rdv_end >= '" . $hrend . "') OR (rdv_start >= '" . $hrstart . "' AND rdv_end <= '" . $hrend . "')) ";

        return DbQuery::querySimple('iso_rdv r
									INNER JOIN iso_contacts c ON c.id_contact = r.id_contact
									LEFT JOIN iso_prestations pr ON pr.id_contact = c.id_contact',
            'r.*, c.geolat, c.geolng, c.first_name, c.last_name, c.adr1, c.post_code, c.city, pr.101_m2, pr.102_m2, pr.103_m2', $wh, true);
    }

    public static function getBy($conds)
    {
        return DbQuery::query('iso_rdv r
						INNER JOIN iso_entrepots e ON e.id_entrepot = r.id_entrepot
						INNER JOIN iso_contacts c ON c.id_contact = r.id_contact
						LEFT JOIN iso_crmusers u ON u.id_crmuser = c.id_crmuser
						LEFT JOIN iso_installators i ON i.id_installator = r.id_installator
						LEFT JOIN iso_prestations pr ON pr.id_contact = c.id_contact',
            'r.*, i.id_installator, i.installator_name, e.id_entrepot, e.entrepot_name, c.first_name, c.last_name, c.adr1, c.post_code,
					c.city, c.tel1, c.geolat, c.geolng, pr.101_m2, pr.102_m2, pr.103_m2', $conds, false, 'rdv_start');
    }

    public static function getBySimple($conds)
    {
        return DbQuery::querySimple('iso_rdv r
						INNER JOIN iso_entrepots e ON e.id_entrepot = r.id_entrepot
						INNER JOIN iso_contacts c ON c.id_contact = r.id_contact
						LEFT JOIN iso_prestations pr ON pr.id_contact = c.id_contact
						LEFT JOIN iso_materials m1 ON m1.id_material = pr.id_material_101
						LEFT JOIN iso_materials m2 ON m2.id_material = pr.id_material_102
						LEFT JOIN iso_materials m3 ON m3.id_material = pr.id_material_103
						LEFT JOIN iso_crmusers u ON u.id_crmuser = c.id_crmuser
						LEFT JOIN iso_installators i ON i.id_installator = r.id_installator',
            'r.*, i.id_installator, i.installator_name, e.id_entrepot, e.entrepot_name, c.first_name, c.last_name, c.adr1, c.post_code, c.source,
					c.city, c.tel1, c.tel2, c.email, c.geolat, c.geolng, c.code_dossier, c.comment, pr.revenu_fiscal, pr.nb_person, pr.type_chauf,
					pr.101_m2, pr.102_m2, pr.103_m2, pr.id_material_101, pr.id_material_102, pr.id_material_103, pr.no_fiscal_1, pr.ref_avis_1,
					m1.name_material as name_material_101, m2.name_material as name_material_102, m3.name_material as name_material_103',
            $conds, false, 'date_rdv, rdv_start');
    }

    public static function getNextPlanning($conds)
    {
        $res = DbQuery::querySimple("iso_rdv r", "MAX(r.num_planning) as MPLAN", $conds, true);
        return $res ? (int)$res->MPLAN + 1 : 1;
    }

    public static function getEntrepotPlanning($conds)
    {
        return DbQuery::querySimple('iso_rdv r
						INNER JOIN iso_entrepots e ON e.id_entrepot = r.id_entrepot
						INNER JOIN iso_contacts c ON c.id_contact = r.id_contact
						LEFT JOIN iso_crmusers u ON u.id_crmuser = c.id_crmuser
						LEFT JOIN iso_installators i ON i.id_installator = r.id_installator',
            'r.id_entrepot, r.num_planning, r.type_rdv, e.entrepot_name, e.geolat, e.geolng', $conds, false, 'e.entrepot_name, r.num_planning, r.type_rdv', 'r.id_entrepot, r.num_planning, r.type_rdv');
    }

    public static function getStockCounts($conds)
    {
        return DbQuery::querySimple('iso_rdv r
						INNER JOIN iso_prestations pr ON pr.id_contact = r.id_contact
						INNER JOIN iso_entrepots e ON e.id_entrepot = r.id_entrepot
						INNER JOIN iso_installators i ON i.id_installator = r.id_installator
						LEFT JOIN iso_materials m1 ON m1.id_material = pr.id_material_101
						LEFT JOIN iso_materials m2 ON m2.id_material = pr.id_material_102
						LEFT JOIN iso_materials m3 ON m3.id_material = pr.id_material_103',
            'e.id_entrepot, e.entrepot_name, e.email, i.id_installator, i.installator_name, r.date_rdv,
					m1.id_material as id101, m1.name_material as mat101, m1.unit as unit101,
					m2.id_material as id102, m2.name_material as mat102, m2.unit as unit102,
					m3.id_material as id103, m3.name_material as mat103, m3.unit as unit103,
					COUNT(r.id_rdv) as nbrdv, SUM(pr.101_m2) as tot101, SUM(pr.102_m2) as tot102, SUM(pr.103_m2) as tot103,
					SUM(CEIL(pr.101_m2 * m1.ratio)) as nunit101, SUM(CEIL(pr.102_m2 * m2.ratio)) as nunit102, SUM(CEIL(pr.103_m2 * m3.ratio)) as nunit103',
            $conds, false,
            'e.id_entrepot, r.date_rdv, i.id_installator',
            'e.id_entrepot, i.id_installator, r.date_rdv, m1.id_material, m3.id_material');
    }

    public static function getPriorRDV($id_entrepot, $num_planning, $daterdv, $hourstart, $typerdv)
    {
        return DbQuery::querySimple('iso_rdv r
										INNER JOIN iso_contacts c ON c.id_contact = r.id_contact', 'r.*, c.geolat, c.geolng, c.first_name, c.last_name, c.adr1, c.post_code, c.city',
            "r.id_entrepot = " . (int)$id_entrepot . " AND r.num_planning = " . (int)$num_planning . " AND r.type_rdv = " . $typerdv . " AND r.date_rdv = '" . $daterdv . "' AND r.rdv_start < '" . $hourstart . "'",
            true, 'rdv_start desc');
    }

    public static function getPriorsRDV($id_entrepot, $num_planning, $daterdv, $hourstart, $typerdv)
    {
        return DbQuery::querySimple('iso_rdv r
										INNER JOIN iso_contacts c ON c.id_contact = r.id_contact
										LEFT JOIN iso_prestations pr ON pr.id_contact = c.id_contact',
            'r.*, c.geolat, c.geolng, c.first_name, c.last_name, c.adr1, c.post_code, c.city, pr.101_m2, pr.102_m2, pr.103_m2',
            "r.id_entrepot = " . (int)$id_entrepot . " AND r.num_planning = " . (int)$num_planning . " AND r.type_rdv = " . $typerdv . " AND r.date_rdv = '" . $daterdv . "' AND r.rdv_start < '" . $hourstart . "'",
            false, 'rdv_start');
    }

    /*
    public static function getPriorRDV($idplan, $hourstart)
    {
        return DbQuery::querySimple('iso_rdv r INNER JOIN iso_contacts c ON c.id_contact = r.id_contact', 'r.*, c.geolat, c.geolng', "r.id_planning = ".(int)$idplan." AND r.rdv_start < '".$hourstart."'", true, 'rdv_start desc');
    }
    */

    public static function getNextRDV($id_entrepot, $num_planning, $daterdv, $hourstart, $typerdv = 0)
    {
        return DbQuery::querySimple('iso_rdv r
										INNER JOIN iso_contacts c ON c.id_contact = r.id_contact', 'r.*, c.geolat, c.geolng, c.first_name, c.last_name, c.adr1, c.post_code, c.city',
            "r.id_entrepot = " . (int)$id_entrepot . " AND r.num_planning = " . (int)$num_planning . " AND r.type_rdv = " . $typerdv . " AND r.date_rdv = '" . $daterdv . "' AND r.rdv_start > '" . $hourstart . "'",
            true, 'rdv_start');
    }

    public static function getNextsRDV($id_entrepot, $num_planning, $daterdv, $hourstart, $typerdv)
    {
        return DbQuery::querySimple('iso_rdv r
										INNER JOIN iso_contacts c ON c.id_contact = r.id_contact
										LEFT JOIN iso_prestations pr ON pr.id_contact = c.id_contact',
            'r.*, c.geolat, c.geolng, c.first_name, c.last_name, c.adr1, c.post_code, c.city, pr.101_m2, pr.102_m2, pr.103_m2',
            "r.id_entrepot = " . (int)$id_entrepot . " AND r.num_planning = " . (int)$num_planning . " AND r.type_rdv = " . $typerdv . " AND r.date_rdv = '" . $daterdv . "' AND r.rdv_start > '" . $hourstart . "'",
            false, 'rdv_start');
    }

    public static function getWithSteps($strconds, &$steps)
    {
        $rdvs = DbQuery::querySimple('iso_rdv r
								INNER JOIN iso_entrepots e ON e.id_entrepot = r.id_entrepot
								INNER JOIN iso_contacts c ON c.id_contact = r.id_contact
								LEFT JOIN iso_prestations p ON p.id_contact = c.id_contact
								LEFT JOIN iso_installators i ON i.id_installator = r.id_installator',
            'r.*, e.entrepot_name, i.id_installator, i.installator_name, c.first_name, c.last_name, c.adr1, c.post_code, c.city, c.geolat, c.geolng, p.101_m2, p.102_m2, p.103_m2',
            $strconds, false, 'r.id_entrepot, r.num_planning, rdv_start');

        if ($rdvs) {
            $i = -1;
            $curgeolat = 0;
            $curgeolng = 0;
            $oldplan = '';
            $frst = false;
            foreach ($rdvs as $rdv) {
                $i++;
                $cliname = $rdv['first_name'] . ' ' . $rdv['last_name'];
                $cliadr = $rdv['adr1'] . ' ' . $rdv['post_code'] . ' ' . $rdv['city'];
                $idc = $rdv['id_contact'];
                $strinfo = $rdv['gmap'];

                if ($strinfo && !empty($strinfo)) {
                    $info = json_decode($strinfo);
                    $dis = $info->routes[0]->legs[0]->distance->text;
                    if (isset($info->routes[0]->legs[0]->duration_in_traffic)) {
                        $del = $info->routes[0]->legs[0]->duration_in_traffic->value > $info->routes[0]->legs[0]->duration->value ? $info->routes[0]->legs[0]->duration_in_traffic->text : $info->routes[0]->legs[0]->duration->text;
                        $delval = $info->routes[0]->legs[0]->duration_in_traffic->value > $info->routes[0]->legs[0]->duration->value ? $info->routes[0]->legs[0]->duration_in_traffic->value : $info->routes[0]->legs[0]->duration->value;
                    } else {
                        $del = $info->routes[0]->legs[0]->duration->text;
                        $delval = $info->routes[0]->legs[0]->duration->value;
                    }
                    $infosup = $i == 0 ? '(depuis l\'adr. de l\'entrepot)' : '';
                }

                if (($curgeolat == 0 && $curgeolng == 0) || ($oldplan != $rdv['id_entrepot'] . '_' . $rdv['num_planning'])) {
                    //$fplan = Planning::getInfoPlanning((int)$rdv['id_planning']);
                    $entrepot = Entrepot::findOne(array('id_entrepot' => (int)$rdv['id_entrepot']));
                    $curgeolat = $entrepot->geolat;
                    $curgeolng = $entrepot->geolng;
                    $frst = true;
                }

                $steps[] = array(
                    'geolatdep' => $curgeolat,
                    'geolngdep' => $curgeolng,
                    'geolatdes' => $rdv['geolat'],
                    'geolngdes' => $rdv['geolng'],
                    'gmap' => $info,
                    'hour_start' => strtotime($rdv['date_rdv'] . ' ' . $rdv['rdv_start']),
                    'hour_end' => strtotime($rdv['date_rdv'] . ' ' . $rdv['rdv_end']),
                    'dis' => $dis,
                    'del' => $del,
                    'delval' => $delval,
                    'infosup' => $infosup,
                    'cliname' => $cliname,
                    'cliadr' => $cliadr,
                    'infom2' => ((int)$rdv['101_m2'] > 0 ? '101 : ' . $rdv['101_m2'] . 'm²<br>' : '') . ((int)$rdv['102_m2'] > 0 ? '102 : ' . $rdv['102_m2'] . 'm²' : '') . ((int)$rdv['103_m2'] > 0 ? '103 : ' . $rdv['103_m2'] . 'm²' : ''),
                    'entname' => $rdv['entrepot_name'],
                    'numplan' => $rdv['num_planning'],
                    'idc' => $idc,
                    'is_first' => $frst
                );
                $frst = false;
                $oldplan = $rdv['id_entrepot'] . '_' . $rdv['num_planning'];
            }
        }

        return $rdvs;
    }

    public static function getByEntrepot($id_entrepot)
    {
        return DbQuery::query('iso_rdv r
								INNER JOIN iso_planning p ON p.id_planning = r.id_planning
								INNER JOIN iso_contacts c ON c.id_contact = r.id_contact',
            'r.*, c.first_name, c.last_name, c.adr1, c.post_code, c.city, c.geolat, c.geolng', array('p.id_entrepot' => $id_entrepot), false, 'rdv_start');
    }

    public static function getByInstallator($id_installator)
    {
        return DbQuery::query('iso_rdv r
								INNER JOIN iso_planning p ON p.id_planning = r.id_planning
								INNER JOIN iso_contacts c ON c.id_contact = r.id_contact',
            'r.*, c.first_name, c.last_name, c.adr1, c.post_code, c.city, c.geolat, c.geolng', array('p.id_installator' => $id_installator), false, 'rdv_start');
    }

    public static function getRDVToRemind()
    {
        return DbQuery::querySimple('iso_rdv r', 'r.*', 'r.sms_sent = 0 AND r.date_rdv BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 24 HOUR) AND r.date_rdv > DATE_ADD(r.date_confirm, INTERVAL 48 HOUR)');
    }

    public static function loadEvents($cuser, $start, $end, $id_entrepot, $id_installator, &$tot_rdv, &$tot_101, &$tot_102, &$tot_103, &$tot_cumac)
    {
        $conds = "r.date_rdv >= '" . $start . "' AND r.date_rdv < '" . $end . "'";
        if (CrmUser::isManager($cuser))
            $conds .= " AND u.id_team = " . $cuser->id_team;
        else
            if (CrmUser::isTelepro($cuser))
                $conds .= " AND c.id_crmuser = " . $cuser->id_crmuser;
            else
                if (CrmUser::isConfirm($cuser))
                    $conds .= " AND u.id_team = " . $cuser->id_team;

        if ($id_entrepot > 0)
            $conds .= " AND r.id_entrepot = " . (int)$id_entrepot;
        if ($id_installator > 0)
            $conds .= " AND r.id_installator = " . (int)$id_installator;

        $sets = (object)Setting::getGlobalSettings();
        $rdvs = RDV::getBySimple($conds);
        $events = array();
        $tot_rdv = 0;
        $tot_101 = 0;
        $tot_102 = 0;
        $tot_103 = 0;
        $tot_cumac = 0;
        if ($rdvs) {
            foreach ($rdvs as $rdv) {

                $tot_rdv++;
                $tot_101 += (int)$rdv['101_m2'] > 0 ? (int)$rdv['101_m2'] : 0;
                $tot_102 += (int)$rdv['102_m2'] > 0 ? (int)$rdv['102_m2'] : 0;
                $tot_103 += (int)$rdv['103_m2'] > 0 ? (int)$rdv['103_m2'] : 0;
                $preca = Setting::getPrecarityInfo($rdv['post_code'], (float)$rdv['revenu_fiscal'], (int)$rdv['nb_person'], $rdv['type_chauf'], (float)$rdv['101_m2'], (float)$rdv['102_m2'], (float)$rdv['103_m2']);
                if (count($preca) > 0)
                    $tot_cumac += $preca['cumac101'] + $preca['cumac102'] + $preca['cumac103'];
                $mat101 = str_replace('1C', '', Tool::getInitials(strtoupper(str_replace('Soufflé', '', $rdv['name_material_101']))));
                $mat102 = str_replace('1C', '', Tool::getInitials(strtoupper(str_replace('Soufflé', '', $rdv['name_material_102']))));
                $mat103 = str_replace('1C', '', Tool::getInitials(strtoupper(str_replace('Soufflé', '', $rdv['name_material_103']))));

                $icoinfo = '';
                if ($rdv['status_rdv'] == '0')
                    $icoinfo = '<i class="fa fa-thumbs-down text-danger icordvstatut" data-status="0" data-toggle="tooltip" title="A confirmer"></i>';
                else
                    if ($rdv['status_rdv'] == '1')
                        $icoinfo = '<i class="fa fa-thumbs-up text-success icordvstatut" data-status="1" data-toggle="tooltip" title="OK Confirmé"></i>';
                    else
                        if ($rdv['status_rdv'] == '2')
                            $icoinfo = '<i class="fa fa-check-circle text-info icordvstatut" data-status="2" data-toggle="tooltip" title="Rendez vous attribué"></i>';
                $dtcrerdv = '<small>Créé le ' . date('d/m/Y', strtotime($rdv['date_create'])) . '</small>';
                $cocherdv = !CrmUser::isTelepro($cuser) && !CrmUser::isManager($cuser) ? '<div style="float:right;margin-top: -3px;position:relative"><input type="checkbox" class="chksimu" style="position: absolute;right: 3px;z-index: 99;" /></div>' : '';
                $lnkent = $id_entrepot == 0 ? '<span class="calins">[<a href="entrepot.php?id_entrepot=' . $rdv['id_entrepot'] . '">' . $rdv['entrepot_name'] . ' #' . $rdv['num_planning'] . '</a>]</span>' : '<span class="calins">[PLANNING #' . $rdv['num_planning'] . ']</span>';
                $lnkis = $id_installator == 0 && $rdv['id_installator'] > 0 ? '<br><span class="label label-info"><a href="installator.php?id_installator=' . $rdv['id_installator'] . '">' . $rdv['installator_name'] . '</a></span>' : '';
                $lnkcli = '<br><a href="contact.php?id_contact=' . $rdv['id_contact'] . '" data-toggle="tooltip" title="' . $rdv['first_name'] . ' ' . $rdv['last_name'] . ' - ' . $rdv['adr1'] . ' ' . $rdv['post_code'] . ' ' . $rdv['city'] . ' - Tél: ' . $rdv['tel1'] . '">RDV ' . ($rdv['type_rdv'] == '1' ? '<span class="label label-info">SAV</span>' : '') . ' avec ' . $rdv['first_name'] . ' ' . $rdv['last_name'] . '</a>';

                if ($rdv['type_rdv'] == '1') {
                    $lnkcli .= '<br><a href="#" class="btsavdone" data-done="' . $rdv['sav_done'] . '"><i class="fa fa-user-' . ($rdv['sav_done'] == '1' ? 'plus text-success' : 'times text-danger') . '"></i> ' . ($rdv['sav_done'] == '1' ? '<span class="text-success">SAV Effectué</span>' : '<span class="text-danger">SAV Non effectué</span>') . '</a>';
                    $rdvbase = RDV::findOne(array('id_contact' => $rdv['id_contact'], 'type_rdv' => '0'));
                    $lnkcli .= '<br><span class="label label-default">RDV installation le ' . date('d/m/Y', strtotime($rdvbase->date_rdv)) . '</span>';
                }

                $infcreneau = '<br><span class="label label-default">Créneau : ' . date('H', strtotime($rdv['date_rdv'] . ' ' . $rdv['creneau_start'])) . 'h - ' . date('H', strtotime($rdv['date_rdv'] . ' ' . $rdv['creneau_end'])) . 'h</span>';
                $lnktour = '<br><a href="tour.php?id_planning=' . $rdv['id_planning'] . '&view=1&id_rdv=' . $rdv['id_rdv'] . '">Voir la tournée</a>';
                $lnkmtr = ((int)$rdv['101_m2'] > 0 ? '<br>101 : ' . $rdv['101_m2'] . 'm² <span class="blkwinf">' . $mat101 . '</span>' : '')
                    . ((int)$rdv['102_m2'] > 0 ? '<br>102 : ' . $rdv['102_m2'] . 'm² <span class="blkwinf">' . $mat102 . '</span>' : '')
                    . ((int)$rdv['103_m2'] > 0 ? '<br>103 : ' . $rdv['103_m2'] . 'm² <span class="blkwinf">' . $mat103 . '</span>' : '');

                $classdis = round($rdv['distance']) > $sets->DISTANCE_RDV_MAX ? 'danger' : 'success';
                $infsup = round($rdv['distance']) > $sets->DISTANCE_RDV_MAX ? '<i class="fa fa-warning" data-toggle="tooltip" title="Attention : La distance dépasse la limite de ' . $sets->DISTANCE_RDV_MAX . 'km"></i>' : '';
                $infdis = '<br><span class="label label-' . $classdis . '"><i class="fa fa-road"></i> ' . round($rdv['distance']) . 'km ' . $infsup . '</span>&nbsp;
						<span class="label label-' . $classdis . '"><i class="fa fa-clock-o"></i> ' . Tool::delaystr($rdv['delay']) . '</span>';

                $clsout = strtotime($rdv['date_rdv'] . ' ' . $rdv['rdv_start']) > strtotime($rdv['date_rdv'] . ' ' . $rdv['creneau_end']) || strtotime($rdv['date_rdv'] . ' ' . $rdv['rdv_start']) < strtotime($rdv['date_rdv'] . ' ' . $rdv['creneau_start']) ? 'clsout' : '';
                $issav = $rdv['type_rdv'] == '1' ? 'issav' : '';
                $strevents = '<span class="elmrdv ' . $clsout . ' ' . $issav . '" data-id="' . $rdv['id_rdv'] . '" data-dt="' . $rdv['date_rdv'] . '">'
                    . '<span class="elmok">'
                    . $icoinfo . ' ' . $dtcrerdv . $cocherdv . $lnkent . $lnkis . $lnkcli . $infcreneau . $lnktour . $lnkmtr . $infdis
                    . '</span>'
                    . '<span class="simudisdel display-none">
									<span class="label label-info"><i class="fa fa-road"></i> <span class="simudis"></span></span>
									<span class="label label-info"><i class="fa fa-clock-o"></i> <span class="simudel"></span></span>
								</span>'
                    . '</span>';
                $events[] = array(
                    'title' => $strevents,
                    'start' => date('Y-m-d\TH:i', strtotime($rdv['date_rdv'] . ' ' . $rdv['rdv_start'])),
                    'end' => date('Y-m-d\TH:i', strtotime($rdv['date_rdv'] . ' ' . $rdv['rdv_end'])),
                    'allDay' => false,
                    'id' => $rdv['id_rdv'],
                    //constraint:\'canDrop\',
                    'resourceId' => $rdv['id_entrepot'] . '_' . $rdv['num_planning'] . '_' . $rdv['type_rdv']
                );
            }
        }
        $tot_cumac = $tot_cumac / 1000000;
        return $events;
    }

    public static function loadResources($cuser, $start, $end, $id_entrepot = 0, $id_installator = 0)
    {
        $conds = "r.date_rdv >= '" . $start . "' AND r.date_rdv < '" . $end . "'";
        if (CrmUser::isManager($cuser))
            $conds .= " AND u.id_team = " . $cuser->id_team;
        else
            if (CrmUser::isTelepro($cuser))
                $conds .= " AND c.id_crmuser = " . $cuser->id_crmuser;

        if ($id_entrepot > 0)
            $conds .= " AND r.id_entrepot = " . (int)$id_entrepot;

        if ($id_installator > 0)
            $conds .= " AND r.id_installator = " . (int)$id_installator;


        $resources = RDV::getEntrepotPlanning($conds);
        $ress = array();
        if ($resources) {
            $colors = array('#adc23a', '#3ac24d', '#3aadc2', '#9a3ac2', '#c23a84', '#c23a43', '#c28a3a', '#ec7a7a', '#81c8e6');
            $i = -1;
            foreach ($resources as $resource) {
                $i++;
                if ($i > count($colors) - 1)
                    $i = 0;
                $restitle = '<span>' . $resource['entrepot_name'] . '</span><br>'
                    . '<small class="label label-warning" style="font-size:10px">PLANNING #' . $resource['num_planning'] . '</small>'
                    . ($resource['type_rdv'] == '0' ? ' <small class="label label-success" style="font-size:10px">POSE</small>' : ' <small class="label label-info" style="font-size:10px">POST VISITE</small>')
                    . ($resource['type_rdv'] == '0' && !CrmUser::isTelepro($cuser) && !CrmUser::isManager($cuser) ? '<br><a href="#" class="btchent btn btn-xs btn-default" data-ent="' . $resource['id_entrepot'] . '" data-plan="' . $resource['num_planning'] . '"><i class="fa fa-refresh"></i> Changer entrepôt</a>' : '')
                    . '<div class="simuentdisdel display-none" data-id="' . $resource['id_entrepot'] . '_' . $resource['num_planning'] . '_' . $resource['type_rdv'] . '">'
                    . '<span class="label label-info"><i class="fa fa-road"></i> <span class="simuentdis"></span></span>'
                    . '<span class="label label-info"><i class="fa fa-clock-o"></i> <span class="simuentdel"></span></span>'
                    . '</div>';
                $ress[] = array(
                    'id' => $resource['id_entrepot'] . '_' . $resource['num_planning'] . '_' . $resource['type_rdv'],
                    'title' => $restitle,
                    'eventColor' => $colors[$i],
                    'eventTextColor' => 'white'
                );
            }
        }

        return $ress;
    }

    public static function create($flds)
    {
        return DbQuery::insert('iso_rdv', $flds);
    }

    public static function update($flds, $wh)
    {
        return DbQuery::update('iso_rdv', $flds, $wh);
    }

    public static function delete($conds)
    {
        return DbQuery::delete('iso_rdv', $conds);
    }

}
