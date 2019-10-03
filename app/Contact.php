<?php

namespace App;

class Contact
{
    public static $importableFields = array(
        'first_name' => "Prénom",
        'last_name' => "Nom",
        'raison_sociale' => "Raison sociale",
        'adr1' => "Adresse",
        'adr2' => "Compl. adresse",
        'post_code' => "Code postal",
        'city' => "Ville",
        'dept' => "Département",
        'tel1' => "Tél 1",
        'tel2' => "Tél 2",
        'email' => "Email",
        'id_crmuser' => "Telepro (email)",
        'id_crmuser_conf' => "Confirmateur (email)",
        'id_statuscont' => "Statut",
        'source' => "Source",
        'campain' => "Campagne",
        'date_create' => "Date de création",
        'note' => "Commentaire",
        'date_rdv_pros' => 'Date RDV provisoire',
        'heure_rdv_pros' => 'Heure RDV provisoire',
        'code_dossier' => 'Code dossier',
        'num_lot' => 'Numéro de lot',
        'creneau_start' => 'Créneau début',
        'creneau_end' => 'Créneau fin',
//		'q_avis_impot' => "Avis impot",
//		'q_avis_impot_bis' => "Avis impot 2",
//		'q_source' => "Source questionnaire",
//		'q_nb_personne_foyer' => "Nb. personne foyer",
//		'q_rfr' => "RFR",
//		'q_comble_m2' => "Comble m²",
        'q_taille_trappe_cm' => "Taille de la trappe en cm",
        'q_plancher_sol' => "Existe il un plancher au sol",
        'q_type_plancher' => "Type de plancher",
        'q_laine_plancher' => "Laine sur le plancher de vos combles",
        'q_type_laine' => "Type de laine",
//		'q_pas_laine_plancher' => "Si pas laine sur plancher",
        'q_poutre_visible' => "Poutres sont-elles visibles",
        'q_acces_passage' => "Acces ou passage",
//		'q_cave_soussol' => "cave sous sol",
//		'q_polystyrene' => "deja du polystyrene",
        'q_espace_chauffe' => "Espace chauffe",
        'q_chaudiere' => "Y a t il une chaudiere",
        'q_tuyau_plafond_cave' => "Y a t il de la tuyauterie au plafond de la cave",
        'q_espace_voute' => "Espace voute",
//		'q_poser_poly_10cm' => "Poser un polystyrene de 10cm d epaisseur",
//		'q_si_chaudiere' => "Si chaudiere",
        'q_voyez_plafond_parking' => "Quand vous regardez votre plafond de parking vous voyez",
//		'q_type_chauffage' => "Type de chauffage",
        'q_mur_mitoyen_encombre' => "Votre mur mitoyen est il encombre",
        'q_si_mur_encombre' => "Si mur encombre",
        'q_taille_mur_mitoyen_m2' => "aille du mur mitoyen en m2",
        'q_type_habit' => "Type d'habitation"
    );

    public static function findOne($conds)
    {
        return DbQuery::query('iso_contacts c
								LEFT JOIN iso_crmusers u ON u.id_crmuser = c.id_crmuser
								LEFT JOIN iso_prestations pr ON pr.id_contact = c.id_contact
								LEFT JOIN iso_statuscontconf sc ON sc.id_statuscontconf = c.id_statuscontconf
								LEFT JOIN iso_entrepots e ON e.id_entrepot = c.id_entrepot_near
								LEFT JOIN iso_contacts c2 ON c2.id_contact = c.id_contact_parrain',
            'c.*, u.id_team, pr.101_m2, pr.102_m2, pr.103_m2, sc.cancel_update_team, e.entrepot_name, e.geolat as entlat, e.geolng as entlng, c2.first_name as first_name_parrain, c2.last_name as last_name_parrain', $conds, true);
    }

    public static function getAll()
    {
        return DbQuery::query('iso_contacts', '*');
    }

    public static function getAllForMap($conds)
    {
        return DbQuery::query('iso_contacts', 'id_contact, first_name, last_name, geolat, geolng, adr1, post_code, city', $conds);
    }

    public static function findDoublonTel($tel1, $tel2)
    {
        return DbQuery::querySimple('iso_contacts', '*', "tel1 LIKE '%" . $tel1 . "%'
														OR tel2 LIKE '%" . $tel1 . "%' "
            . ($tel2 != '' ? "
														OR tel1 LIKE '%" . $tel2 . "%'
														OR tel2 LIKE '%" . $tel2 . "%'" : '') . "
														OR '" . $tel1 . "' LIKE CONCAT('%', tel1, '%')
														OR ('" . $tel1 . "' LIKE CONCAT('%', tel2, '%') AND tel2 <> '') "
            . ($tel2 != '' ? "
														OR '" . $tel2 . "' LIKE CONCAT('%', tel1, '%')
														OR ('" . $tel2 . "' LIKE CONCAT('%', tel2, '%') AND tel2 <> '') " : ''), true);
    }

    public static function getAround($idc, $geolat, $geolng, $lmt)
    {
        $sql = "SELECT
					c.*, pDistance(geolat, geolng, " . (float)$geolat . ", " . (float)$geolng . ") as dis,
					s.name_statuscont, sc.name_statuscontconf,
					CONCAT(r.date_rdv, ' ', r.rdv_start) as date_rdv,
					COUNT(cm.id_comment) as nb_com,
					GROUP_CONCAT(CONCAT('<strong>', DATE_FORMAT(cm.date_comment, '%d/%m/%y %H:%I'), '</strong> - <span>', cm.text_comment, '</span>') ORDER BY cm.id_comment DESC SEPARATOR '<hr>') as comments
				FROM iso_contacts c
					INNER JOIN iso_statuscont s ON s.id_statuscont = c.id_statuscont
					LEFT JOIN iso_statuscontconf sc ON sc.id_statuscontconf = c.id_statuscontconf
					LEFT JOIN iso_rdv r ON r.id_contact = c.id_contact
					LEFT JOIN iso_comments cm ON cm.id_contact = c.id_contact
				WHERE pDistance(c.geolat, c.geolng, " . (float)$geolat . ", " . (float)$geolng . ") BETWEEN 1 AND " . (int)$lmt . " * 1000
				  AND c.id_contact <> " . (int)$idc . "
				  AND sc.cancel_rdv = 0
				  AND (r.status_rdv <> 2 OR r.status_rdv IS NULL)
				GROUP BY c.id_contact";

        return DbQuery::querySQL($sql);
    }

    public static function addAudit($flds)
    {
        return DbQuery::insert('iso_audits', $flds);
    }

    public static function create($flds)
    {
        return DbQuery::insert('iso_contacts', $flds);
    }

    public static function update($flds, $wh)
    {
        return DbQuery::update('iso_contacts', $flds, $wh);
    }

    public static function delete($conds)
    {
        return DbQuery::delete('iso_contacts', $conds);
    }
}
