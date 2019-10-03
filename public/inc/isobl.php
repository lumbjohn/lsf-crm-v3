<?php

require __DIR__ . '/../../vendor/autoload.php';
$dotenv = Dotenv\Dotenv::create(__DIR__ . '/../..');
try {
    $dotenv->load();
} catch (DotEnv\Exception\InvalidPathException $e) {}
$dotenv->required(['APP_URL', 'DB_HOST', 'DB_USERNAME', 'DB_DATABASE'])->notEmpty();

ini_set('display_errors', 0);
error_reporting(E_ALL);

include 'isodb.php'; // data interface php file
include 'ImageResize.php';
//include __DIR__.'/../lib/isofuncs.php';

require __DIR__ . '/../lib/vendor/autoload.php';
use \Ovh\Api;


class Tool
{
	public static function DropDown($id, $arr, $val, $txt, $selval, $deftxt = '')
	{
		$html = '<select aria-describedby="'.$id.'" id="'.$id.'"  name="'.$id.'" class="form-control">';
		if ($deftxt != '')
			$html .= '<option value="0">'.$deftxt.'</option>';
		foreach($arr as $line) {
			$sel = $line[$val] == $selval ? 'selected="selected"' : '';
			$html .= '<option value="'.$line[$val].'" '.$sel.'>'.$line[$txt].'</option>';
		}
		$html .= '</select>';

		return $html;
	}

	public static function DropDownArray($id, $arr, $sfx='')
	{
		$html = '<select aria-describedby="'.$id.'" id="'.$id.'"  name="'.$id.'" class="form-control">';
		foreach($arr as $key => $val)
			$html .= '<option value="'.$key.'">'.$val.$sfx.'</option>';
		$html .= '</select>';

		return $html;
	}

	public static function dmYtoYmd($dt)
	{
		$dts = explode('/', $dt);
		if (count($dts) <> 3)
			return '';
		else
			return $dts[2].'-'.$dts[1].'-'.$dts[0];
	}

	public static function addTimeStr($strtime)
	{
		$times = explode(':', $strtime);
		if (count($times) < 2)
			return '';
		else {
			$hr = (int)$times[0] > 0 ? '+ '.$times[0].' hour ' : '';
			$mn = (int)$times[1] > 0 ? '+ '.$times[1].' minutes ' : '';
			$sc = isset($times[2]) && (int)$times[2] > 0 ? '+ '.$times[2].' seconds ' : '';

			$str = $hr.$mn.$sc;
			if ($str == '')
				$str = '+ 0 seconds ';
			return $str;
		}
	}

	public static function isImage($file)
	{
		return strpos($file, '.jpg') > 0 || strpos($file, '.gif') > 0 || strpos($file, '.jpeg') > 0 || strpos($file, '.png') > 0 || strpos($file, '.bmp') > 0;
	}

	public static function fmtMtDown($number)
	{
		return intval(($number*100))/100;
	}

	public static function displayMt($number, $forcedec = true)
	{
		$whole = floor($number);
		$fraction = $number - $whole;
		if ($fraction == 0 && !$forcedec)
			return number_format($number, 0, ',', ' ');
		else
			return number_format($number, 2, ',', ' ');
	}

	public static function fulldatestr($dt)
	{
		$str = date('l d F Y', strtotime($dt));

		return str_replace(array(
			'Monday',
			'Tuesday',
			'Wednesday',
			'Thursday',
			'Friday',
			'Saturday',
			'Sunday',
			'January',
			'Febuary',
			'March',
			'April',
			'May',
			'June',
			'July',
			'August',
			'September',
			'October',
			'November',
			'December'), array(
			'Lundi',
			'Mardi',
			'Mercredi',
			'Jeudi',
			'Vendredi',
			'Samedi',
			'Dimanche',
			'Janvier',
			'Fevrier',
			'Mars',
			'Avril',
			'Mai',
			'Juin',
			'Juillet',
			'Aout',
			'Septembre',
			'Octobre',
			'Novembre',
			'Decembre'
		), $str);
	}

	public static function distancestr($dis)
	{
		if ($dis > 1000)
			return round($dis/1000).' km';
		else
			return round($dis).' m';
	}

	public static function delaystr($delay)
	{
		$h = (int)($delay/3600);
		$m = (int)(($delay % 3600)/60);
		return $h > 0 ? $h.'h'.$m : $m.'m';
	}


	public static function arrayToStr($arr, $html = true) {
		if (count($arr) == 0 || !is_array($arr))
			return '';
		$str = '';
		$sep = $html ? '<br>' : "\n";
		foreach($arr as $k => $v)
			$str .= ($str != '' ? $sep : '') . $k.' : '.$v;
		return $str;
	}

	public static function getInitials($words)
	{
		$res = '';
		$arr = explode(' ', $words);
		foreach($arr as $a)
			$res .= substr($a, 0, 1);
		return $res;
	}

	public static function getRealIpAddr()
	{
		if (!empty($_SERVER['HTTP_CLIENT_IP']))   //check ip from share internet
		{
		  $ip=$_SERVER['HTTP_CLIENT_IP'];
		}
		elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR']))   //to check ip is pass from proxy
		{
		  $ip=$_SERVER['HTTP_X_FORWARDED_FOR'];
		}
		else
		{
		  $ip=$_SERVER['REMOTE_ADDR'];
		}
		return $ip;
	}

	public static function approxMt($mt)
	{
		if ($mt > 1000000000)
			return number_format($mt / 1000000000, 1).' '.Lang::tr('Milliard');
		else
		if ($mt > 1000000)
			return number_format($mt / 1000000, 1).' '.Lang::tr('Million');
		else
		if ($mt > 100000)
			return number_format($mt / 1000, 1).' '.Lang::tr('K');
		else
			return $mt;
	}


	public static function doResize($upload_dir, $name) {
		$image = new \Eventviva\ImageResize($upload_dir . $name);
		$image->crop(200, 200);
		$image->save($upload_dir . $name);

		/*$image->resizeToBestFit(60, 60);
		$image->save($upload_dir . 'small/'. $name);*/
	}

	public static function uploadFile($upload_dir, $myFile, $withResize = false) {

		if ($myFile["error"] !== UPLOAD_ERR_OK) {
			echo "<p>An error occurred.</p>";
			die;
		}

		// ensure a safe filename
		$name = preg_replace("/[^A-Z0-9._-]/i", "_", $myFile["name"]);

		// don't overwrite an existing file
		$i = 0;
		$parts = pathinfo($name);
		while (file_exists($upload_dir . $name)) {
			$i++;
			$name = $parts["filename"] . "-" . $i . "." . $parts["extension"];
		}

		// preserve file from temporary directory
		$success = move_uploaded_file($myFile["tmp_name"],
			$upload_dir . $name);
		if (!$success) {
			echo "Unable to save file.";
			die;
		}
		else {
			if ($withResize)
				Tool::doResize($upload_dir, $name);
			return $name;
		}

		// set proper permissions on the new file
		//chmod(upload_dir . $name, 0644);
	}


	public static function getCurl($url, $pst = array())
	{
		$strResponse = '';
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		if (count($pst) > 0) {
			curl_setopt($ch, CURLOPT_POST, count($pst));
			curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($pst));
			//die(http_build_query($pst));
		}

		$strResponse = curl_exec($ch);
		curl_close($ch);

		return $strResponse;
	}

	public static function getDistance($deplat, $deplng, $deslat, $deslng)
	{
		$theta = $deplng - $deslng;
		$dist = sin(deg2rad($deplat)) * sin(deg2rad($deslat)) +  cos(deg2rad($deplat)) * cos(deg2rad($deslat)) * cos(deg2rad($theta));
		$dist = acos($dist);
		$dist = rad2deg($dist);
		$miles = $dist * 60 * 1.1515;
		//$unit = strtoupper($unit);



		$km = $miles * 1.609344; //in km
		return $km * 1.28; //impact vol d'oiseau / road map
	}

	public static function getDirection($deplat, $deplng, $deslat, $deslng, $deptime)
	{
		$strdepinf = $deptime > time() ? '&departure_time='.$deptime : '';
		$url = 'https://maps.googleapis.com/maps/api/directions/json?origin='.$deplat.','.$deplng.'&destination='.$deslat.','.$deslng.$strdepinf.'&key=AIzaSyArKK0Hvu_FtKgyvHkUUjyKOMK2Hmt9zY0';
		$info = Tool::getCurl($url);
		return $info;
	}

	public static function getLatLngFromAddress($address, &$lat, &$lng)
	{
		$url = 'https://maps.googleapis.com/maps/api/geocode/json?address='.str_replace(' ', '+', $address).'&key=AIzaSyArKK0Hvu_FtKgyvHkUUjyKOMK2Hmt9zY0';
		$info = Tool::getCurl($url);
		$info = json_decode($info);
		if (count($info->results) > 0) {
			$lat = $info->results[0]->geometry->location->lat;
			$lng = $info->results[0]->geometry->location->lng;
			return true;
		}
		return false;
	}
}



class Search
{
	public static function get($txt, $currentUser)
	{
		$wh = '';
		if ((int)$txt > 0) {
			$whc = " WHERE (c.tel1 LIKE '".$txt."%' OR c.tel2 LIKE '".$txt."%' OR c.post_code LIKE '".$txt."%') ";
			$whi = " WHERE (tel1 LIKE '".$txt."%' OR tel2 LIKE '".$txt."%' OR post_code LIKE '".$txt."%') ";
		}
		else {
			$whc = " WHERE (c.first_name LIKE'%".$txt."%' OR c.last_name LIKE'%".$txt."%' OR c.email LIKE'%".$txt."%')";
			$whi = " WHERE (installator_name LIKE'%".$txt."%' OR email LIKE'%".$txt."%')";
		}

		$instl = "";
		if (CrmUser::isAdmin($currentUser))
			$instl = " UNION
					SELECT 1 as type, i.id_installator as id, i.installator_name, i.tel1, i.tel2, i.adr1, i.post_code, i.city, i.email, '', ''
					FROM iso_installators i
					".$whi;

		$sqlsup = "";
		if (CrmUser::isTelepro($currentUser))
			$sqlsup = " AND u.id_crmuser = ".$currentUser->id_crmuser;
		else
		if (CrmUser::isManager($currentUser))
			$sqlsup = " AND (u.id_team = ".$currentUser->id_team.($currentUser->teams != '' ? " OR u.id_team IN (".$currentUser->teams.")" : '')." ) ";


		$sql = "SELECT REQ.* FROM (
					SELECT 0 as type, c.id_contact as id, CONCAT(c.first_name, ' ', c.last_name) as name, c.tel1, c.tel2, c.adr1, c.post_code, c.city, c.email, c.dept, u.user_name as conf_name
					FROM iso_contacts c
						LEFT JOIN iso_crmusers u ON u.id_crmuser = c.id_crmuser
					".$whc." ".$sqlsup." ".$instl."
				) REQ
				LIMIT 0, 30";

		//echo $sql;
		return DbQuery::querySQL($sql);
	}
}

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
		return DbQuery::querySimple('iso_contacts', '*', "tel1 LIKE '%".$tel1."%'
														OR tel2 LIKE '%".$tel1."%' "
														.($tel2 != '' ? "
														OR tel1 LIKE '%".$tel2."%'
														OR tel2 LIKE '%".$tel2."%'" : '')."
														OR '".$tel1."' LIKE CONCAT('%', tel1, '%')
														OR ('".$tel1."' LIKE CONCAT('%', tel2, '%') AND tel2 <> '') "
														.($tel2 != '' ? "
														OR '".$tel2."' LIKE CONCAT('%', tel1, '%')
														OR ('".$tel2."' LIKE CONCAT('%', tel2, '%') AND tel2 <> '') " : ''), true);
	}

	public static function getAround($idc, $geolat, $geolng, $lmt)
	{
		$sql = "SELECT
					c.*, pDistance(geolat, geolng, ".(float)$geolat.", ".(float)$geolng.") as dis,
					s.name_statuscont, sc.name_statuscontconf,
					CONCAT(r.date_rdv, ' ', r.rdv_start) as date_rdv,
					COUNT(cm.id_comment) as nb_com,
					GROUP_CONCAT(CONCAT('<strong>', DATE_FORMAT(cm.date_comment, '%d/%m/%y %H:%I'), '</strong> - <span>', cm.text_comment, '</span>') ORDER BY cm.id_comment DESC SEPARATOR '<hr>') as comments
				FROM iso_contacts c
					INNER JOIN iso_statuscont s ON s.id_statuscont = c.id_statuscont
					LEFT JOIN iso_statuscontconf sc ON sc.id_statuscontconf = c.id_statuscontconf
					LEFT JOIN iso_rdv r ON r.id_contact = c.id_contact
					LEFT JOIN iso_comments cm ON cm.id_contact = c.id_contact
				WHERE pDistance(c.geolat, c.geolng, ".(float)$geolat.", ".(float)$geolng.") BETWEEN 1 AND ".(int)$lmt." * 1000
				  AND c.id_contact <> ".(int)$idc."
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


class Comment
{
	public static function findOne($conds)
	{
		return DbQuery::query('iso_comments', '*', $conds, true);
	}

	public static function getBy($conds)
	{
		return DbQuery::query('iso_comments c LEFT JOIN iso_crmusers u ON u.id_crmuser = c.id_user_comment', 'c.*, u.user_name', $conds);
	}

	public static function getFirstRecall($dt, $id_crmuser)
	{
		return DbQuery::querySimple('iso_comments cm
								INNER JOIN iso_contacts c ON c.id_contact = cm.id_contact',
							'cm.id_comment, cm.text_comment, cm.date_recall, c.id_contact, c.raison_sociale, c.first_name, c.last_name, c.tel1, c.email, c.post_code, c.city',
							"cm.type_comment = 1 AND cm.date_recall < '".$dt."' AND is_read = 0 AND (c.id_crmuser = ".$id_crmuser." OR c.id_crmuser_conf = ".$id_crmuser.")",
							true);
	}

	public static function getRecall($conds)
	{
		return DbQuery::query('iso_comments cm
								INNER JOIN iso_contacts c ON c.id_contact = cm.id_contact
								LEFT JOIN iso_crmusers u ON u.id_crmuser = c.id_crmuser',
							'cm.id_comment, cm.text_comment, cm.date_recall, c.id_contact, c.first_name, c.last_name',
							$conds);
	}

	public static function create($flds)
	{
		return DbQuery::insert('iso_comments', $flds);
	}

	public static function update($flds, $wh)
	{
		return DbQuery::update('iso_comments', $flds, $wh);
	}

	public static function delete($conds)
	{
		return DbQuery::delete('iso_comments', $conds);
	}
}

class Doc
{
	public static function findOne($conds)
	{
		return DbQuery::query('iso_contacts_docs', '*', $conds, true);
	}

	public static function getBy($conds)
	{
		return DbQuery::query('iso_contacts_docs', '*', $conds);
	}

	public static function create($flds)
	{
		return DbQuery::insert('iso_contacts_docs', $flds);
	}

	public static function delete($conds)
	{
		return DbQuery::delete('iso_contacts_docs', $conds);
	}

}

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
		return DbQuery::query('iso_entrepots e', 'e.geolat, e.geolng, e.id_entrepot, pDistance(e.geolat, e.geolng, '.(float)$geolat.', '.(float)$geolng.') as distance', '', true, 'distance');
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
									"i.id_installator, i.installator_name, i.first_name_ins, i.last_name_ins, pDistance(i.geolat, i.geolng, ".(float)$geolat.", ".(float)$geolng.") as dis",
									"id_installator ".($available ? 'NOT' : '')." IN (
										SELECT r.id_installator
										FROM iso_rdv r
										WHERE r.date_rdv BETWEEN '".$start."' AND '".$end."'
										".(!$available ? ' AND r.id_entrepot = '.$id_entrepot.' AND r.num_planning = '.$numplan : '')."
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
				foreach($hours as $hr) {
					$hrs = explode('-', $hr);
					if (count($hrs) == 2)
						$strwh .= ($strwh != '' ? ' OR ' : '')." (p.hour_start = '".$hrs[0]."' AND p.hour_end = '".$hrs[1]."')";
				}
		}
		$sql = "SELECT QRY.* FROM (
					SELECT p.*, e.entrepot_name, i.installator_name, rdvprec.id_rdv,
						(SELECT COUNT(*)
							FROM iso_rdv rdvcount INNER JOIN iso_planning plancount ON rdvcount.id_planning = plancount.id_planning
						WHERE plancount.date_planning = p.date_planning
						  AND plancount.id_entrepot = p.id_entrepot) as nbrdv,
						CASE
							WHEN rdvprec.id_rdv IS NOT NULL THEN pDistance(c.geolat, c.geolng, ".(float)$lat.", ".(float)$lng.")
							WHEN p.geolat = 0 AND p.geolng = 0 THEN pDistance(e.geolat, e.geolng, ".(float)$lat.", ".(float)$lng.")
							ELSE pDistance(p.geolat, p.geolng, ".(float)$lat.", ".(float)$lng.")
						END as dis
					FROM iso_planning p
						INNER JOIN iso_entrepots e ON e.id_entrepot = p.id_entrepot
						LEFT JOIN iso_installators i ON i.id_installator = p.id_installator
						LEFT JOIN iso_rdv r ON r.id_planning = p.id_planning
						LEFT JOIN iso_planning planprec ON planprec.date_planning = p.date_planning AND planprec.id_entrepot = p.id_entrepot AND planprec.hour_end = p.hour_start
						LEFT JOIN iso_rdv rdvprec ON rdvprec.id_planning = planprec.id_planning
						LEFT JOIN iso_contacts c ON c.id_contact = rdvprec.id_contact
					WHERE r.id_rdv IS NULL /* AND i.id_installator > 1 */
					  AND p.id_planning NOT IN (SELECT rdvexist.id_planning FROM iso_rdv rdvexist WHERE rdvexist.id_contact = ".(int)$id_contact.")
					  ".($dtfrom != '' ? "AND p.date_planning >= '".$dtfrom."'" : 'AND p.date_planning > DATE(NOW())')."
					  ".($strwh != '' ? ' AND ('.$strwh.')' : '')."
				) QRY
				WHERE QRY.dis <= ".(int)$perimeter." * 1000
				ORDER BY ".($by_distance ? 'QRY.dis, ' : '')." QRY.date_planning, QRY.hour_start
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
			AND type_rdv = ".$typerdv."
			AND ((rdv_start <= '".$hrstart."' AND rdv_end > '".$hrstart."') OR (rdv_start < '".$hrend."' AND rdv_end >= '".$hrend."') OR (rdv_start >= '".$hrstart."' AND rdv_end <= '".$hrend."')) ";

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
									"r.id_entrepot = ".(int)$id_entrepot." AND r.num_planning = ".(int)$num_planning." AND r.type_rdv = ".$typerdv." AND r.date_rdv = '".$daterdv."' AND r.rdv_start < '".$hourstart."'",
									true, 'rdv_start desc');
	}

	public static function getPriorsRDV($id_entrepot, $num_planning, $daterdv, $hourstart, $typerdv)
	{
		return DbQuery::querySimple('iso_rdv r
										INNER JOIN iso_contacts c ON c.id_contact = r.id_contact
										LEFT JOIN iso_prestations pr ON pr.id_contact = c.id_contact',
									'r.*, c.geolat, c.geolng, c.first_name, c.last_name, c.adr1, c.post_code, c.city, pr.101_m2, pr.102_m2, pr.103_m2',
									"r.id_entrepot = ".(int)$id_entrepot." AND r.num_planning = ".(int)$num_planning." AND r.type_rdv = ".$typerdv." AND r.date_rdv = '".$daterdv."' AND r.rdv_start < '".$hourstart."'",
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
									"r.id_entrepot = ".(int)$id_entrepot." AND r.num_planning = ".(int)$num_planning." AND r.type_rdv = ".$typerdv." AND r.date_rdv = '".$daterdv."' AND r.rdv_start > '".$hourstart."'",
									true, 'rdv_start');
	}

	public static function getNextsRDV($id_entrepot, $num_planning, $daterdv, $hourstart, $typerdv)
	{
		return DbQuery::querySimple('iso_rdv r
										INNER JOIN iso_contacts c ON c.id_contact = r.id_contact
										LEFT JOIN iso_prestations pr ON pr.id_contact = c.id_contact',
									'r.*, c.geolat, c.geolng, c.first_name, c.last_name, c.adr1, c.post_code, c.city, pr.101_m2, pr.102_m2, pr.103_m2',
									"r.id_entrepot = ".(int)$id_entrepot." AND r.num_planning = ".(int)$num_planning." AND r.type_rdv = ".$typerdv." AND r.date_rdv = '".$daterdv."' AND r.rdv_start > '".$hourstart."'",
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
			$i=-1;
			$curgeolat = 0;
			$curgeolng = 0;
			$oldplan = '';
			$frst = false;
			foreach($rdvs as $rdv) {
				$i++;
				$cliname = $rdv['first_name'].' '.$rdv['last_name'];
				$cliadr = $rdv['adr1'].' '.$rdv['post_code'].' '.$rdv['city'];
				$idc = $rdv['id_contact'];
				$strinfo = $rdv['gmap'];

				if ($strinfo && !empty($strinfo)) {
					$info = json_decode($strinfo);
					$dis = $info->routes[0]->legs[0]->distance->text;
					if (isset($info->routes[0]->legs[0]->duration_in_traffic)) {
						$del = $info->routes[0]->legs[0]->duration_in_traffic->value > $info->routes[0]->legs[0]->duration->value ? $info->routes[0]->legs[0]->duration_in_traffic->text : $info->routes[0]->legs[0]->duration->text;
						$delval = $info->routes[0]->legs[0]->duration_in_traffic->value > $info->routes[0]->legs[0]->duration->value ? $info->routes[0]->legs[0]->duration_in_traffic->value : $info->routes[0]->legs[0]->duration->value;
					}
					else {
						$del = $info->routes[0]->legs[0]->duration->text;
						$delval = $info->routes[0]->legs[0]->duration->value;
					}
					$infosup = $i == 0 ? '(depuis l\'adr. de l\'entrepot)' : '';
				}

				if (($curgeolat == 0 && $curgeolng == 0) || ($oldplan != $rdv['id_entrepot'].'_'.$rdv['num_planning'])) {
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
					'hour_start' => strtotime($rdv['date_rdv'].' '.$rdv['rdv_start']),
					'hour_end' => strtotime($rdv['date_rdv'].' '.$rdv['rdv_end']),
					'dis' => $dis,
					'del' => $del,
					'delval' => $delval,
					'infosup' => $infosup,
					'cliname' => $cliname,
					'cliadr' => $cliadr,
					'infom2' => ((int)$rdv['101_m2'] > 0 ? '101 : '.$rdv['101_m2'].'m²<br>' : '').((int)$rdv['102_m2'] > 0 ? '102 : '.$rdv['102_m2'].'m²' : '').((int)$rdv['103_m2'] > 0 ? '103 : '.$rdv['103_m2'].'m²' : ''),
					'entname' => $rdv['entrepot_name'],
					'numplan' => $rdv['num_planning'],
					'idc' => $idc,
					'is_first' => $frst
				);
				$frst = false;
				$oldplan = $rdv['id_entrepot'].'_'.$rdv['num_planning'];
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
		$conds = "r.date_rdv >= '".$start."' AND r.date_rdv < '".$end."'";
		if (CrmUser::isManager($cuser))
			$conds .= " AND u.id_team = ".$cuser->id_team;
		else
		if (CrmUser::isTelepro($cuser))
			$conds .= " AND c.id_crmuser = ".$cuser->id_crmuser;
		else
		if (CrmUser::isConfirm($cuser))
			$conds .= " AND u.id_team = ".$cuser->id_team;

		if ($id_entrepot > 0)
			$conds .= " AND r.id_entrepot = ".(int)$id_entrepot;
		if ($id_installator > 0)
			$conds .= " AND r.id_installator = ".(int)$id_installator;

		$sets = (object)Setting::getGlobalSettings();
		$rdvs = RDV::getBySimple($conds);
		$events = array();
		$tot_rdv = 0;
		$tot_101 = 0;
		$tot_102 = 0;
		$tot_103 = 0;
		$tot_cumac = 0;
		if ($rdvs) {
			foreach($rdvs as $rdv) {

				$tot_rdv++;
				$tot_101 += (int)$rdv['101_m2'] > 0 ? (int)$rdv['101_m2'] : 0;
				$tot_102 += (int)$rdv['102_m2'] > 0 ? (int)$rdv['102_m2'] : 0;
				$tot_103 += (int)$rdv['103_m2'] > 0 ? (int)$rdv['103_m2'] : 0;
				$preca = Setting::getPrecarityInfo($rdv['post_code'], (float)$rdv['revenu_fiscal'], (int)$rdv['nb_person'], $rdv['type_chauf'], (float)$rdv['101_m2'], (float)$rdv['102_m2'], (float)$rdv['103_m2']);
				if (count($preca) > 0)
					$tot_cumac += $preca['cumac101'] + $preca['cumac102'] + $preca['cumac103'];
				$mat101 = str_replace('1C', '', Tool::getInitials(strtoupper(str_replace('Soufflé', '', $rdv['name_material_101']))));
				$mat102 = str_replace('1C', '', Tool::getInitials(strtoupper(str_replace('Soufflé', '', $rdv['name_material_102']))));
				$mat103 = str_replace('1C', '',Tool::getInitials(strtoupper(str_replace('Soufflé', '', $rdv['name_material_103']))));

				$icoinfo = '';
				if ($rdv['status_rdv'] == '0')
					$icoinfo = '<i class="fa fa-thumbs-down text-danger icordvstatut" data-status="0" data-toggle="tooltip" title="A confirmer"></i>';
				else
				if ($rdv['status_rdv'] == '1')
					$icoinfo = '<i class="fa fa-thumbs-up text-success icordvstatut" data-status="1" data-toggle="tooltip" title="OK Confirmé"></i>';
				else
				if ($rdv['status_rdv'] == '2')
					$icoinfo = '<i class="fa fa-check-circle text-info icordvstatut" data-status="2" data-toggle="tooltip" title="Rendez vous attribué"></i>';
				$dtcrerdv = '<small>Créé le '.date('d/m/Y', strtotime($rdv['date_create'])).'</small>';
				$cocherdv = !CrmUser::isTelepro($cuser) && !CrmUser::isManager($cuser) ? '<div style="float:right;margin-top: -3px;position:relative"><input type="checkbox" class="chksimu" style="position: absolute;right: 3px;z-index: 99;" /></div>' : '';
				$lnkent = $id_entrepot == 0 ? '<span class="calins">[<a href="entrepot.php?id_entrepot='.$rdv['id_entrepot'].'">'.$rdv['entrepot_name'].' #'.$rdv['num_planning'].'</a>]</span>' : '<span class="calins">[PLANNING #'.$rdv['num_planning'].']</span>';
				$lnkis = $id_installator == 0 && $rdv['id_installator'] > 0 ? '<br><span class="label label-info"><a href="installator.php?id_installator='.$rdv['id_installator'].'">'.$rdv['installator_name'].'</a></span>' : '';
				$lnkcli = '<br><a href="contact.php?id_contact='.$rdv['id_contact'].'" data-toggle="tooltip" title="'.$rdv['first_name'].' '.$rdv['last_name'].' - '.$rdv['adr1'].' '.$rdv['post_code'].' '.$rdv['city'].' - Tél: '.$rdv['tel1'].'">RDV '.($rdv['type_rdv'] == '1' ? '<span class="label label-info">SAV</span>' : '').' avec '.$rdv['first_name'].' '.$rdv['last_name'].'</a>';

				if ($rdv['type_rdv'] == '1') {
					$lnkcli .= '<br><a href="#" class="btsavdone" data-done="'.$rdv['sav_done'].'"><i class="fa fa-user-'.($rdv['sav_done'] == '1' ? 'plus text-success' : 'times text-danger').'"></i> '.($rdv['sav_done'] == '1' ? '<span class="text-success">SAV Effectué</span>' : '<span class="text-danger">SAV Non effectué</span>').'</a>';
					$rdvbase = RDV::findOne(array('id_contact' => $rdv['id_contact'], 'type_rdv' => '0'));
					$lnkcli .= '<br><span class="label label-default">RDV installation le '.date('d/m/Y', strtotime($rdvbase->date_rdv)).'</span>';
				}

				$infcreneau = '<br><span class="label label-default">Créneau : '.date('H', strtotime($rdv['date_rdv'].' '.$rdv['creneau_start'])).'h - '.date('H', strtotime($rdv['date_rdv'].' '.$rdv['creneau_end'])).'h</span>';
				$lnktour = '<br><a href="tour.php?id_planning='.$rdv['id_planning'].'&view=1&id_rdv='.$rdv['id_rdv'].'">Voir la tournée</a>';
				$lnkmtr = ((int)$rdv['101_m2'] > 0 ? '<br>101 : '.$rdv['101_m2'].'m² <span class="blkwinf">'.$mat101.'</span>' : '')
						 .((int)$rdv['102_m2'] > 0 ? '<br>102 : '.$rdv['102_m2'].'m² <span class="blkwinf">'.$mat102.'</span>' : '')
						 .((int)$rdv['103_m2'] > 0 ? '<br>103 : '.$rdv['103_m2'].'m² <span class="blkwinf">'.$mat103.'</span>' : '');

				$classdis = round($rdv['distance']) > $sets->DISTANCE_RDV_MAX ? 'danger' : 'success';
				$infsup = round($rdv['distance']) > $sets->DISTANCE_RDV_MAX ? '<i class="fa fa-warning" data-toggle="tooltip" title="Attention : La distance dépasse la limite de '.$sets->DISTANCE_RDV_MAX.'km"></i>' : '';
				$infdis = '<br><span class="label label-'.$classdis.'"><i class="fa fa-road"></i> ' . round($rdv['distance']) . 'km '.$infsup.'</span>&nbsp;
						<span class="label label-'.$classdis.'"><i class="fa fa-clock-o"></i> ' . Tool::delaystr($rdv['delay']) . '</span>';

				$clsout = strtotime($rdv['date_rdv'].' '.$rdv['rdv_start']) > strtotime($rdv['date_rdv'].' '.$rdv['creneau_end']) || strtotime($rdv['date_rdv'].' '.$rdv['rdv_start']) < strtotime($rdv['date_rdv'].' '.$rdv['creneau_start']) ? 'clsout' : '';
				$issav = $rdv['type_rdv'] == '1' ? 'issav' : '';
				$strevents = '<span class="elmrdv '.$clsout.' '.$issav.'" data-id="'.$rdv['id_rdv'].'" data-dt="'.$rdv['date_rdv'].'">'
								.'<span class="elmok">'
									.$icoinfo.' '.$dtcrerdv.$cocherdv.$lnkent.$lnkis.$lnkcli.$infcreneau.$lnktour.$lnkmtr.$infdis
								.'</span>'
								.'<span class="simudisdel display-none">
									<span class="label label-info"><i class="fa fa-road"></i> <span class="simudis"></span></span>
									<span class="label label-info"><i class="fa fa-clock-o"></i> <span class="simudel"></span></span>
								</span>'
							.'</span>';
				$events[] = array(
					'title' => $strevents,
					'start' => date('Y-m-d\TH:i', strtotime($rdv['date_rdv'].' '.$rdv['rdv_start'])),
					'end' => date('Y-m-d\TH:i', strtotime($rdv['date_rdv'].' '.$rdv['rdv_end'])),
					'allDay' => false,
					'id' => $rdv['id_rdv'],
					//constraint:\'canDrop\',
					'resourceId' => $rdv['id_entrepot'].'_'.$rdv['num_planning'].'_'.$rdv['type_rdv']
				);
			}
		}
		$tot_cumac = $tot_cumac / 1000000;
		return $events;
	}

	public static function loadResources($cuser, $start, $end, $id_entrepot = 0, $id_installator = 0)
	{
		$conds = "r.date_rdv >= '".$start."' AND r.date_rdv < '".$end."'";
		if (CrmUser::isManager($cuser))
			$conds .= " AND u.id_team = ".$cuser->id_team;
		else
		if (CrmUser::isTelepro($cuser))
			$conds .= " AND c.id_crmuser = ".$cuser->id_crmuser;

		if ($id_entrepot > 0)
			$conds .= " AND r.id_entrepot = ".(int)$id_entrepot;

		if ($id_installator > 0)
			$conds .= " AND r.id_installator = ".(int)$id_installator;


		$resources = RDV::getEntrepotPlanning($conds);
		$ress = array();
		if ($resources) {
			$colors = array('#adc23a', '#3ac24d', '#3aadc2', '#9a3ac2', '#c23a84', '#c23a43', '#c28a3a', '#ec7a7a', '#81c8e6');
			$i=-1;
			foreach($resources as $resource) {
				$i++;
				if ($i > count($colors)-1)
					$i=0;
				$restitle = '<span>'.$resource['entrepot_name'].'</span><br>'
							.'<small class="label label-warning" style="font-size:10px">PLANNING #' . $resource['num_planning'] . '</small>'
							.($resource['type_rdv'] == '0' ? ' <small class="label label-success" style="font-size:10px">POSE</small>' : ' <small class="label label-info" style="font-size:10px">POST VISITE</small>')
							.($resource['type_rdv'] == '0' && !CrmUser::isTelepro($cuser) && !CrmUser::isManager($cuser) ? '<br><a href="#" class="btchent btn btn-xs btn-default" data-ent="'.$resource['id_entrepot'].'" data-plan="'.$resource['num_planning'].'"><i class="fa fa-refresh"></i> Changer entrepôt</a>' : '')
							.'<div class="simuentdisdel display-none" data-id="'.$resource['id_entrepot'].'_'.$resource['num_planning'].'_'.$resource['type_rdv'].'">'
								.'<span class="label label-info"><i class="fa fa-road"></i> <span class="simuentdis"></span></span>'
								.'<span class="label label-info"><i class="fa fa-clock-o"></i> <span class="simuentdel"></span></span>'
							.'</div>';
				$ress[] = array(
					'id' => $resource['id_entrepot'].'_'.$resource['num_planning'].'_'.$resource['type_rdv'],
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


class Prestation
{
	public static function findOne($conds)
	{
		return DbQuery::query('iso_prestations', '*', $conds, true);
	}

	public static function findDoublonFiscal($prest)
	{
		$wh = "";
		for($i=1;$i<=5;$i++) {
			if ($prest->{'no_fiscal_'.$i} != '') {
				for($j=1;$j<=5;$j++)
					$wh .= ($wh != '' ? ' OR ' : '')." no_fiscal_".$j." = ".$prest->{'no_fiscal_'.$i};
			}
		}
		if ($wh == '')
			return false;

		$wh = '('.$wh.') AND id_contact <> '.$prest->id_contact;
		return DbQuery::querySimple('iso_prestations', '*', $wh, true);
	}

	public static function getNextNoFac()
	{
		$res = DbQuery::query('iso_prestations', 'MAX(nofac) as MFAC', '', true);
		return $res && (int)$res->MFAC > 0 ? (int)$res->MFAC + 1 : 7720;
	}

	public static function create($flds)
	{
		return DbQuery::insert('iso_prestations', $flds);
	}

	public static function update($flds, $wh)
	{
		return DbQuery::update('iso_prestations', $flds, $wh);
	}

	public static function delete($conds)
	{
		return DbQuery::delete('iso_prestations', $conds);
	}

}


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
		$res = DbQuery::querySimple('iso_teams', "GROUP_CONCAT(name_team SEPARATOR ', ') as name_teams", "id_team IN (".$teams.")", true);
		return $res->name_teams;
	}
}

class Profil
{
	public static function getAll()
	{
		return DbQuery::query('iso_profils', '*');
	}
}



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


class CrmUserTeam
{
	public static function findOne($wh)
	{
		return DbQuery::query('iso_crmusers_teams', '*', $wh, true);
	}
}

class CrmAction
{
	public static function create($flds)
	{
		return DbQuery::insert('iso_crmaction', $flds);
	}
}

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

	public static $typechauf = array(/*'Aucun',*/'Electricité', 'Fioul', 'Gaz / GPL', 'Bois', 'Autre');

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
				VALUES ('".$name."', '".mysqli_real_escape_string($db, $value)."')
				ON DUPLICATE KEY UPDATE
				name = '".$name."', value = '".mysqli_real_escape_string($db, $value)."'";
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
		$dept = substr($post_code,  0, 2);
		$isIDF = in_array($dept, array('75', '77', '78', '91', '92', '93', '94', '95'));
		$soc = DbQuery::querySimple('iso_plafonds_bonus', '*', 'nb_person = '.(int)$nbperson.' AND (mt_gp'.($isIDF ? '_idf' : '').' >= '.(float)$revenu.' OR mt_p'.($isIDF ? '_idf' : '').' >= '.(float)$revenu.')', true);
		$preca = 0;
		if (in_array($dept, array('66', '11', '34', '30', '13', '83', '06', '2A', '2B')))
			$zone = 'H3';
		else
		if (in_array($dept, array('29', '22', '56', '50', '35', '53', '44', '72', '49', '85', '41', '37', '79', '18', '36', '86', '17', '16', '33', '24', '40', '46', '47', '64', '65', '09', '31', '32', '81', '82', '46', '12', '48', '07', '26', '84', '04')))
			$zone = 'H2';
		else
			$zone = 'H1';

		if ($soc) {
			if ($soc->{'mt_gp'.($isIDF ? '_idf' : '')} >= (float)$revenu)
				$preca = 2;
			else
			if ($soc->{'mt_p'.($isIDF ? '_idf' : '')} >= (float)$revenu)
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


class Template
{
	public static function displayListRDV($plans, $curusr, $sets)
	{
		$str = '';

		foreach($plans as $plan) {
			$classdis = round($plan['dis']/1000) > $sets->DISTANCE_RDV_MAX ? 'danger' : 'success';
			$infsup = round($plan['dis']/1000) > $sets->DISTANCE_RDV_MAX ? '<i class="fa fa-warning" data-toggle="tooltip" title="Attention : La distance dépasse la limite de '.$sets->DISTANCE_RDV_MAX.'km"></i>' : '';
			$str .= '
					<tr>
						<td>
							<a href="tour.php?id_planning='.$plan['id_planning'].'&id_contact='.$curusr->id_contact.'">
								<div class="pull-left">
									<small class="label label-info"><i class="fa fa-clock-o"></i> '.date('H:i', strtotime($plan['hour_start'])).' - '.date('H:i', strtotime($plan['hour_end'])).'</small>
								</div>
								<div class="pull-right">
									<small class="text-danger">'.$plan['nbrdv'].' RDV</small>
								</div>
								<h4 style="text-align:center;padding:10px 0;">
									<strong>'.Tool::fulldatestr($plan['date_planning']).'</strong>
								</h4>
								<div class="pull-left">
									<small class="text-warning"><i class="gi gi-kiosk"></i> '.$plan['entrepot_name'].'</small>
								</div>
								<div class="pull-right">
									<small class="label label-'.$classdis.'"><i class="fa fa-car"></i> '.Tool::distancestr($plan['dis']).' '.$infsup.'</small>
								</div>
							</a>
						</td>
					</tr>';
		}

		return $str;
	}

	public static function displayListNewRDV($curusr, $sets, $dtrdv = '', $ranges = '')
	{
		$str = '';
		$plans = Planning::getDispo($curusr->id_contact, $curusr->geolat, $curusr->geolng, $sets->DISTANCE_RDV, $dtrdv, $ranges);
		if ($plans) {
			$str .= '
				<div class="col-md-6">
					<div class="block">
						<div class="block-title">
							<h2>Affichage par <strong>Distance</strong></h2>
						</div>
						<div class="row">
							<table class="table table-striped table-vcenter table-condensed table-hover">'.Template::displayListRDV($plans, $curusr, $sets).'</table>
						</div>
					</div>
				</div>';

			$plans = Planning::getDispo($curusr->id_contact, $curusr->geolat, $curusr->geolng, $sets->DISTANCE_RDV, $dtrdv, $ranges, false);
			$str .= '
				<div class="col-md-6">
					<div class="block">
						<div class="block-title">
							<h2>Affichage par <strong>Date</strong></h2>
						</div>
						<div class="row">
							<table class="table table-striped table-vcenter table-condensed table-hover">'.Template::displayListRDV($plans, $curusr, $sets).'</table>
						</div>
					</div>
				</div>';
		}

		return $str;
	}
}


class IsoPDFBuilder
{
	public static function checkDir($rep)
	{
		$dirup = __DIR__ . '/../storage/uploads/';
		$dir = $dirup.$rep;
		if (!is_dir($dir)) {
			mkdir($dir);
			copy($dirup . 'index.php', $dir . '/index.php');
		}

		return $dir;
	}

	public static function BuildContactDoc($rep, $docname, $content, $local = true)
	{
		$dir = IsoPDFBuilder::checkDir($rep);
		$htmldoc = '<style type="text/css" media="screen,print">
						<!--
						* {
							font-family: Arial;
						}
						.latolight {font-family:latolight;}
						-->
					</style>
					<page backtop="17mm" backbottom="14mm" backleft="5mm" backright="5mm">
						'.$content.'

					</page>';

		// require_once('lib/html2pdf/vendor/autoload.php');
		try
		{
			$html2pdf = new HTML2PDF('P','A4','fr');
			$html2pdf->WriteHTML($htmldoc);
			// $html2pdf->addFont('latoregular', '', 'latoregular');
			$filename = $dir.'/'.$docname;
			$html2pdf->Output($filename, 'F');
			return $local ? $filename : 'uploads/'.$rep.'/'.$docname.'?time='.time();

		}
		catch(HTML2PDF_exception $e) {
			return $e;
			exit;
		}
	}
}




require __DIR__ . '/../lib/google-api/vendor/autoload.php';
// USEFUL LINK - https://stackoverflow.com/questions/50656151/adding-an-event-to-google-calendar-using-php
class GoogleAgenda
{
	private static function getClient()
	{
		$client = new Google_Client();
		$client->setApplicationName('Google Calendar API TEST');
		$client->setAuthConfig(__DIR__ . '/../lib/google-api/LSF ISO-999d69173e47.json');
		$client->useApplicationDefaultCredentials();
		$client->setScopes(Google_Service_Calendar::CALENDAR);
		$client->setAccessType('offline');
		return $client;
	}

	public static function ListAgenda()
	{
		$client = GoogleAgenda::getClient();
		$service = new Google_Service_Calendar($client);
		$calendarList = $service->calendarList->listCalendarList();
		//print_r($calendarList);
	}
	public static function ListEvents($calendarId)
	{
		// Get the API client and construct the service object.
		$client = GoogleAgenda::getClient();
		$service = new Google_Service_Calendar($client);
		// Print the next 10 events on the user's calendar.
		//$calendarId = 'primary';
		$optParams = array(
			'maxResults' => 10,
			'orderBy' => 'startTime',
			'singleEvents' => true,
			'timeMin' => date('c'),
		);
		$results = $service->events->listEvents($calendarId, $optParams);
		$events = $results->getItems();

		if (empty($events)) {
			print "No upcoming events found.\n";
		} else {
			print "Upcoming events:\n";
			foreach ($events as $event) {
				$start = $event->start->dateTime;
				if (empty($start)) {
					$start = $event->start->date;
				}
				printf("%s (%s)\n", $event->getSummary(), $start);
			}
		}
	}

	public static function createEvent($calendarId, $idrdv, $summary, $description, $start, $end, $location)
	{
		$client = GoogleAgenda::getClient();
		$service = new Google_Service_Calendar($client);

		$event = new Google_Service_Calendar_Event(array(
			'summary' => $summary,
			'location' => $location,
			'description' => $description,
			'start' => array(
			  //'dateTime' => '2018-06-02T09:00:00-07:00'
			  'dateTime' => date('Y-m-d\TH:i:s', $start),
			  'timeZone'=> 'Europe/Paris'
			),
			'end' => array(
				'dateTime' => date('Y-m-d\TH:i:s', $end),
				'timeZone'=> 'Europe/Paris'
			)
		  ));
		  $event = $service->events->insert($calendarId, $event);
		  return $event->id;
	}

	public static function deleteEvent($calendarId, $idEvent)
	{
		$client = GoogleAgenda::getClient();
		$service = new Google_Service_Calendar($client);
		$service->events->delete($calendarId, $idEvent);
		return true;
	}
}



class SMS
{
	public static function SendMessage($txt, $fullnum)
	{
		$sets = (object)Setting::getGlobalSettings();
		$prms = array(
			'method' => 'http',
			'username' => $sets->CLICKSEND_UNAME,
			'key' => $sets->CLICKSEND_KEY,
			'to' => $fullnum,
			'message' => $txt,
			'senderid' => 'LSF Energie'
		);

		$url = 'https://api-mapper.clicksend.com/http/v2/send.php'; //?'.http_build_query($prms);
		return Tool::getCurl($url, $prms);
	}

	public static function SendMessageOVH($txt, &$num)
	{

		//!!demo - return true;
		$endpoint = 'ovh-eu';
		$applicationKey = "FwtEIhoQcFJJScsm";
		$applicationSecret = "uLXeh0y4okeTEHYZix2o05ZdYfpr9fME";
		$consumer_key = "qQWh8IpNn5keZtHq7L38eIdKvNZLxikC";

		$conn = new Api(    $applicationKey,
							$applicationSecret,
							$endpoint,
							$consumer_key);

		$smsServices = $conn->get('/sms/');
		/*foreach ($smsServices as $smsService) {

			print_r($smsService);
		}*/

		$num = str_replace(' ', '', $num);
		$num = str_replace('-', '', $num);
		$num = str_replace('.', '', $num);

		if (substr($num, 0, 4) == '0033')
			$num = '+33'.substr($num, 4);
		else
		if (substr($num, 0, 1) == '0')
			$num = '+33'.substr($num, 1);

		//!! a retirer num de test wes !
		//$num = "+33665490101";

		$content = (object) array(
			"charset"=> "UTF-8",
			"class"=> "phoneDisplay",
			"coding"=> "7bit",
			"message"=> $txt,
			"noStopClause"=> true,
//			"sender" => "Handigo",
			"priority"=> "high",
			"receivers"=> [ $num ],
			"senderForResponse"=> true,
			"validityPeriod"=> 1440
		);

		$resultPostJob = $conn->post('/sms/'. $smsServices[0] . '/jobs/', $content);

		return isset($resultPostJob['validReceivers']) && isset($resultPostJob['validReceivers'][0]) && $resultPostJob['validReceivers'][0] == $num;


		/*$smsJobs = $conn->get('/sms/'. $smsServices[0] . '/jobs/');
		print_r($smsJobs);*/
	}
}


require __DIR__.'/../lib/PHPMailer/PHPMailerAutoload.php';
class MailEngine
{
	private static function DefaultTemplate()
	{
		return '##MESAGE_BODY##';

		/*
		return '<table width="688" border="0" cellspacing="0" cellpadding="0" align="center" style="font-family:Arial, Helvetica, sans-serif;font-size:14px;" >
			<tr>
				<td width="688" valign="top" style="padding:18px;">

					<table width="649" border="0" cellspacing="0" cellpadding="0">
						<tr>
							<td  valign="top"><a href="" ><img src="https://lsf-energie.fr/" border="0" style="width:100px" ></a></td>
						</tr>
						<tr>
							<td valign="top" style="padding:25px;">
								##MESAGE_BODY##
							</td>
						</tr>
					</table>
				</td>
			</tr>
			</table>';*/
	}

	private static function createMail()
	{
		$mail = new PHPMailer;
		$mail->CharSet = 'UTF-8';

		//$mail->SMTPDebug = 3;                               // Enable verbose debug output

		/*$mail->isSMTP();                                      // Set mailer to use SMTP
		$mail->Host = 'smtp1.example.com;smtp2.example.com';  // Specify main and backup SMTP servers
		$mail->SMTPAuth = true;                               // Enable SMTP authentication
		$mail->Username = 'user@example.com';                 // SMTP username
		$mail->Password = 'secret';                           // SMTP password
		$mail->SMTPSecure = 'tls';                            // Enable TLS encryption, `ssl` also accepted
		$mail->Port = 587;                                    // TCP port to connect to
		*/

		$mail->isSMTP();                                      // Set mailer to use SMTP
		$mail->Host = 'smtp.gmail.com';  // Specify main and backup SMTP servers
		$mail->SMTPAuth = true;                               // Enable SMTP authentication
		$mail->Username = 'system@lsf-iso.fr';                 // SMTP username
		$mail->Password = 'Sys55@26!';                           // SMTP password
		$mail->SMTPSecure = 'ssl';                            // Enable TLS encryption, `ssl` also accepted
		$mail->Port = 465;

		$mail->From = 'system@lsf-iso.fr';
		$mail->FromName = 'LSF ENERGIE';


		return $mail;
	}

	public static function sendMail($type, $vals)
	{
		switch ($type)
		{
			case 'mail-entrepot' :

				$mail = MailEngine::createMail();

				$mail->addAddress($vals->email, $vals->entrepot_name);     // Add a recipient
				$mail->isHTML(true);                                  // Set email format to HTML

				$mail->Subject = $vals->subject;

				$mail->Body    = str_replace('##MESAGE_BODY##', $vals->msg, MailEngine::DefaultTemplate());

				if(!$mail->send()) {
					echo 'Message could not be sent.';
					echo 'Mailer Error: ' . $mail->ErrorInfo;
				} else {
					//echo 'Message has been sent';
				}

				break;
		}
	}

}

function filename ($url) {
    // Extract filename from URL
    $parts = explode('/', $url);
    return end($parts);
}

?>
