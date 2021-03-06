<?php
ini_set('display_errors', 0);
error_reporting(E_ALL);
include './inc/isobl.php';

function successJSON($content, $die = true)
{
	wrapJSON('SUCCESS', $content, $die);
}
function errorJSON($content, $die = true)
{
	wrapJSON('ERROR', $content, $die);
}
function wrapJSON($code, $content, $die = true)
{
	$content = json_encode((object)$content);
	$content = substr($content, 1, strlen($content) - 1);
	$buffer = '{"code":"' . $code . '",' . $content;

	echo $buffer;
	if ($die) {
		die;
	}
}

function checkFields($arr, $flds)
{
	$ret = true;
	foreach ($flds as $fld) {
		if (!isset($arr[$fld]) || (empty($arr[$fld]) && $arr[$fld] !== '0')) {
			$ret = false;
			break;
		}
	}
	return $ret;
}

function fmtMtDown($number)
{
	return intval(($number * 100)) / 100;
}

if (isset($_POST['action'])) {
	$action = $_POST['action'];

	//actions of connected user only
	if ($action != 'login') {
		session_start();
		if (!isset($_SESSION['crmloggin'])) {
			echo '##';
			die;
		} else
			$currentUser = unserialize($_SESSION['crm_user']);
	}

	$URL = getenv('APP_URL') ?: 'https://lsf-crm-v2.herokuapp.com/';

	switch ($action) {
		case 'login':
			if (!checkFields($_POST, array('email', 'psw')))
				errorJSON(array('message' => 'Champs obligatoires manquants'));

			$user = CrmUser::findOne(array('email' => $_POST['email']));
			if (!$user) {
				CrmAction::create(array('id_crmuser' => '0', 'table_action' => 'CRMUsers', 'id_entity' => '0', 'type_action' => 'LOGIN ECHEC EMAIL', 'date_action' => date('Y-m-d H:i:s'), 'details_action' => 'IP:'.Tool::getRealIpAddr().' - EMAIL:'.$_POST['email']));
				errorJSON(array('message' => 'Utilisateur non enregistré'));
			}

			if ($user->psw == md5($_POST['psw'])) {
				session_start();
				$_SESSION['crmloggin'] = true;
				$_SESSION['crm_user'] = serialize($user);
				CrmUser::update(array('date_last_login' => date('Y-m-d H:i:s'), 'ip_login' => Tool::getRealIpAddr()), array('id_crmuser' => $user->id_crmuser));
				CrmAction::create(array('id_crmuser' => $user->id_crmuser, 'table_action' => 'CRMUsers', 'id_entity' => $user->id_crmuser, 'type_action' => 'LOGIN', 'date_action' => date('Y-m-d H:i:s')));
				successJSON(array('OK' => 'OK'));
			} else {
				CrmAction::create(array('id_crmuser' => '0', 'table_action' => 'CRMUsers', 'id_entity' => '0', 'type_action' => 'LOGIN ECHEC', 'date_action' => date('Y-m-d H:i:s'), 'details_action' => 'IP:'.Tool::getRealIpAddr().' - EMAIL:'.$_POST['email']));
				errorJSON(array('message' => 'Informations incorrectes'));
			}


			break;

		case 'global-search':
			if (!checkFields($_POST, array('txt')))
				errorJSON(array('message' => 'Champs obligatoires manquants'));

			$datas = Search::get($_POST['txt'], $currentUser);
			CrmAction::create(array('id_crmuser' => $currentUser->id_crmuser, 'table_action' => 'Search', 'id_entity' => '0', 'type_action' => 'RECHERCHE', 'date_action' => date('Y-m-d H:i:s')));
			successJSON(array('datas' => ArrayLoader::loadAssoc($datas)));
			break;

		case 'get-crmuser':
			if (!CrmUser::isAdmin($currentUser) && $currentUser->id_crmuser != $_POST['idcrmuser'])
				errorJSON(array('message' => 'Droits insuffisants'));

			if (!checkFields($_POST, array('idcrmuser')))
				errorJSON(array('message' => 'Informations incorrectes'));

			$crmuser = CrmUser::findOne(array('id_crmuser' => $_POST['idcrmuser']));
			if (!$crmuser)
				errorJSON(array('message' => 'Utilisateur inconnu'));

			$crmuser->date_create = date('d/m/Y H:i', strtotime($crmuser->date_create));
			$crmuser->date_upd = strtotime($crmuser->date_upd) > 0 ? date('d/m/Y H:i', strtotime($crmuser->date_upd)) : '-';
			$crmuser->date_last_login = strtotime($crmuser->date_last_login) > 0 ? date('d/m/Y H:i', strtotime($crmuser->date_last_login)) : '-';
			unset($crmuser->psw);
			successJSON(array('crmuser' => $crmuser));
			break;


		case 'update-crmuser':
			parse_str($_POST['data'], $crmuser);

			if (!CrmUser::isAdmin($currentUser) && $currentUser->id_crmuser != $crmuser['id_crmuser'])
				errorJSON(array('message' => 'Droits insuffisants'));

			if (!checkFields($crmuser, array('user_name', 'email')))
				errorJSON(array('message' => 'Informations incorrectes'));

			if ((int)$crmuser['id_crmuser'] > 0) {
				$excrmuser = CrmUser::findOne(array('id_crmuser' => $crmuser['id_crmuser']));
				if (!$excrmuser)
					errorJSON(array('message' => 'Utilisateur invalide'));

				if ($excrmuser->email != $crmuser['email']) {
					$emcrmuser = CrmUser::findOne(array('email' => $crmuser['email']));
					if ($emcrmuser)
						errorJSON(array('message' => 'Email existe déjà chez un autre utilisateur'));
				}

				$arr = array(
					'date_upd' => date('Y-m-d H:i:s'),
					'user_name' => $crmuser['user_name'],
					'email' => $crmuser['email'],
					'tel' => $crmuser['tel']
				);

				if (CrmUser::isAdmin($currentUser)) {
					$arr['id_profil'] = (int)$crmuser['id_profil'] > 0 ? $crmuser['id_profil'] : '2';
					$arr['id_team'] = (int)$crmuser['id_profil'] > 1 ? $crmuser['id_team'] : '0';
					$arr['depts'] = (int)$crmuser['id_profil'] == 4 && isset($_POST['depts']) ? $_POST['depts'] : '';
					$arr['teams'] = (int)$crmuser['id_profil'] == 3 && isset($_POST['teams']) ? $_POST['teams'] : '';
				}

				if (!empty($crmuser['psw']))
					$arr['psw'] = md5($crmuser['psw']);

				if (CrmUser::update($arr, array('id_crmuser' => $crmuser['id_crmuser']))) {
					CrmAction::create(array('id_crmuser' => $currentUser->id_crmuser, 'table_action' => 'CrmUsers', 'id_entity' => $crmuser['id_crmuser'], 'type_action' => 'MODIFICATION', 'date_action' => date('Y-m-d H:i:s'), 'details_action' => Tool::arrayToStr($arr)));
					successJSON(array('OK' => 'OK'));
				} else
					errorJSON(array('message' => 'Erreur à la mise à jours'));
			} else {
				if (empty($crmuser['psw']))
					errorJSON(array('message' => 'Veuillez renseigner le mot de passe'));

				$emcrmuser = CrmUser::findOne(array('email' => $crmuser['email']));
				if ($emcrmuser)
					errorJSON(array('message' => 'Email existe déjà chez un autre utilisateur'));

				$idprofil = isset($crmuser['id_profil']) && (int)$crmuser['id_profil'] > 0 ? $crmuser['id_profil'] : 2;

				$arr = array(
					'date_create' => date('Y-m-d H:i:s'),
					'user_name' => $crmuser['user_name'],
					'email' => $crmuser['email'],
					'tel' => $crmuser['tel'],
					'id_profil' => $idprofil,
					'id_team' => $idprofil == 1 ? 0 : (isset($crmuser['id_team']) && (int)$crmuser['id_team'] > 1 ? (int)$crmuser['id_team'] : 1),
					'psw' => md5($crmuser['psw'])
				);
				if (CrmUser::isAdmin($currentUser)) {
					$arr['depts'] = $idprofil == 4 && isset($_POST['depts']) ? $_POST['depts'] : '';
					$arr['teams'] = $idprofil == 3 && isset($_POST['teams']) ? $_POST['teams'] : '';
				}


				if ($idc = CrmUser::create($arr)) {
					CrmAction::create(array('id_crmuser' => $currentUser->id_crmuser, 'table_action' => 'CrmUsers', 'id_entity' => $idc, 'type_action' => 'CREATION', 'date_action' => date('Y-m-d H:i:s'), 'details_action' => Tool::arrayToStr($arr)));
					successJSON(array('OK' => 'OK'));
				} else
					errorJSON(array('message' => 'Erreur à la mise à jours'));
			}
			break;

		case 'delete-crmuser':
			if ($currentUser->id_profil != 1)
				errorJSON(array('message' => 'Droits insuffisants'));

			if (!checkFields($_POST, array('idcrmuser')))
				errorJSON(array('message' => 'Informations incorrectes'));

			$exct = Contact::findOne(array('id_crmuser' => $_POST['idcrmuser']));
			if ($exct)
				errorJSON(array('message' => 'Suppression impossible ! Il reste des clients / prospects rattachés'));

			if (CrmUser::delete(array('id_crmuser' => $_POST['idcrmuser']))) {
				CrmAction::create(array('id_crmuser' => $currentUser->id_crmuser, 'table_action' => 'CrmUsers', 'id_entity' => $_POST['idcrmuser'], 'type_action' => 'SUPPRESSION', 'date_action' => date('Y-m-d H:i:s')));
				successJSON(array('OK' => 'OK'));
			}
			break;


		case 'update-contact':
			if (!checkFields($_POST, array('id_contact', 'first_name', 'last_name', 'tel1')))
				errorJSON(array('message' => 'Informations incorrectes'));

			if (!checkFields($_POST, array('id_statuscont')))
				errorJSON(array('message' => 'Statut manquant'));

			$numtel = preg_replace('/\D/', '', $_POST['tel1']);
			if (strlen($numtel) != 10)
				errorJSON(array('message' => 'Numéro de telephone invalide. Veuillez saisir le numéro au format 0612345678'));

			/*$sets = (object)Setting::getGlobalSettings();
			if ((int)$_POST['id_statuscont'] != $sets->STATUS_NO_CTR_ADR)
				if (!checkFields($_POST, array('adr1', 'post_code', 'city')))
					errorJSON(array('message' => 'Informations d\'adresse manquantes'));*/

			if ((int)$_POST['id_contact'] > 0) {
				//update mode

				$usr = Contact::findOne(array('c.id_contact' => $_POST['id_contact']));
				if (!$usr)
					errorJSON(array('message' => 'Client / Prospect inexistant'));

				if ($currentUser->id_profil == 2 && $usr->id_crmuser != $currentUser->id_crmuser)
					errorJSON(array('message' => 'Droits insuffisants'));

				$geolat = (float)$_POST['geolat'];
				$geolng = (float)$_POST['geolng'];

				if ($geolat == 0 && $geolng == 0)
					Tool::getLatLngFromAddress(trim($_POST['adr1'] . ',' . $_POST['post_code'] . ',' . $_POST['city']), $geolat, $geolng);

				$arr = array(
					'first_name' => $_POST['first_name'],
					'last_name' => $_POST['last_name'],
					'raison_sociale' => $_POST['raison_sociale'],
					'adr1' => $_POST['adr1'],
					'adr2' => $_POST['adr2'],
					'post_code' => $_POST['post_code'],
					'city' => $_POST['city'],
					'country' => 'FRANCE',
					'tel1' => $_POST['tel1'],
					'tel2' => $_POST['tel2'],
					'email' => $_POST['email'],
					'id_statuscont' => (int)$_POST['id_statuscont'],
					'id_statuscontconf' => (int)$_POST['id_statuscontconf'],
					'code_dossier' => $_POST['code_dossier'],
					'num_lot' => isset($_POST['num_lot']) ? $_POST['num_lot'] : '',
					'source' => $_POST['source'],
					'campain' => $_POST['campain'],
					'id_contact_parrain' => (int)$_POST['id_contact_parrain'],
					'geolat' => $geolat,
					'geolng' => $geolng,
					'date_update' => date('Y-m-d H:i:s')
				);
				if ($_POST['post_code'] != '')
					$arr['dept'] = substr($_POST['post_code'], 0, 2);

				if (isset($_POST['date_rdv_pros']) && $_POST['date_rdv_pros'] != '')
					$arr['date_rdv_pros'] = Tool::dmYtoYmd($_POST['date_rdv_pros']);
				if (isset($_POST['heure_rdv_pros']))
					$arr['heure_rdv_pros'] = $_POST['heure_rdv_pros'].':00';
				if (isset($_POST['creneau_start']))
					$arr['creneau_start'] = date('H:i:s', strtotime('+'.(int)$_POST['creneau_start'].' hour', strtotime(Tool::dmYtoYmd($_POST['date_rdv_pros']))));
				if (isset($_POST['creneau_end']))
					$arr['creneau_end'] = date('H:i:s', strtotime('+'.(int)$_POST['creneau_end'].' hour', strtotime(Tool::dmYtoYmd($_POST['date_rdv_pros']))));


				if ($st = Setting::getStatusConf(array('id_statuscontconf' => $arr['id_statuscontconf'])))
					if ($st->rdv_need_exists == 1) {
						$exrdv = RDV::findOne(array('id_contact' => (int)$_POST['id_contact']));
						if (!$exrdv)
							errorJSON(array('message' => 'Un RDV doit exister pour pouvoir utiliser ce statut'));
					}


				if (!CrmUser::isTelepro($currentUser) && !CrmUser::isConfirmateur($currentUser) && (int)$_POST['id_crmuser'] > 0)
					$arr['id_crmuser'] = (int)$_POST['id_crmuser'];
				if (!CrmUser::isTelepro($currentUser) && isset($_POST['id_crmuser_conf']) && (int)$_POST['id_crmuser_conf'] > 0)
					$arr['id_crmuser_conf'] = (int)$_POST['id_crmuser_conf'];


				if (!CrmUser::isTelepro($currentUser)) {
					if (isset($_POST['date_install']) && $_POST['date_install'] != '')
						$arr['date_install'] = Tool::dmYtoYmd($_POST['date_install']);
					if (isset($_POST['date_valid']) && $_POST['date_valid'] != '')
						$arr['date_valid'] = Tool::dmYtoYmd($_POST['date_valid']);
				}

				// if (CrmUser::isAdmin($currentUser))
				// 	$arr['id_parrain'] = $_POST['id_parrain'];
				/*
				if (strtotime($usr->date_valid) == 0 && ((int)$_POST['id_statuscont'] == 4 || (int)$_POST['id_statuscont'] == 13))
					$arr['date_valid'] = date('Y-m-d H:i:s');
				else
				if (strtotime($usr->date_install) == 0 && (int)$_POST['id_statuscont'] == 10) { //Installe
					$rdv = RDV::findOne(array('id_contact' => $_POST['id_contact']));
					if ($rdv)
						$arr['date_install'] = $rdv->date_rdv;
				}
				*/

				//entrepot le plus proche
				if (((float)$geolat != 0 || (float)$geolng != 0) && ((float)$geolat != $usr->geolat || (float)$geolng != $usr->geolng)) {
					$ent = Entrepot::getNearFromLatLng((float)$geolat, (float)$geolng);
					if ($ent) {
						$arr['id_entrepot_near'] = $ent->id_entrepot;

						$strinfo = Tool::getDirection((float)$geolat, (float)$geolng, $ent->geolat, $ent->geolng, '');
						if ($strinfo && !empty($strinfo)) {
							//calcul google map (google direction)
							$info = json_decode($strinfo);

							$dis = $info->routes[0]->legs[0]->distance->text;
							if (isset($info->routes[0]->legs[0]->duration_in_traffic)) {
								$del = $info->routes[0]->legs[0]->duration_in_traffic->value > $info->routes[0]->legs[0]->duration->value ? $info->routes[0]->legs[0]->duration_in_traffic->text : $info->routes[0]->legs[0]->duration->text;
								$delval = $info->routes[0]->legs[0]->duration_in_traffic->value > $info->routes[0]->legs[0]->duration->value ? $info->routes[0]->legs[0]->duration_in_traffic->value : $info->routes[0]->legs[0]->duration->value;
							} else {
								$del = $info->routes[0]->legs[0]->duration->text;
								$delval = $info->routes[0]->legs[0]->duration->value;
							}

							$arr['distance_ent'] = $dis;
							$arr['delay_ent'] = $del;
						}
						else {
							//calcul vol doiseau
							$arr['distance_ent'] = $ent->distance;
							$totdel = $ent->distance / 95;
							$delh = floor($totdel);
							$delay = $delh . 'h' .round(($totdel - $delh) * 60);
							$arr['delay_ent'] = $delay;
						}
					}
				}


				foreach ($_POST as $k => $v) {
					if (strpos($k, 'q_') !== false)
						$arr[$k] = $v;
				}

				if (Contact::update($arr, array('id_contact' => (int)$_POST['id_contact']))) {

					$arrupdrdv = array();
					if (isset($_POST['creneau_start']))
						$arrupdrdv['creneau_start'] = date('H:i', strtotime('+'.(int)$_POST['creneau_start'].' hour', strtotime(Tool::dmYtoYmd($_POST['date_rdv_pros']))));
					if (isset($_POST['creneau_end']))
						$arrupdrdv['creneau_end'] = date('H:i', strtotime('+'.(int)$_POST['creneau_end'].' hour', strtotime(Tool::dmYtoYmd($_POST['date_rdv_pros']))));
					if (count($arrupdrdv) > 0)
						RDV::update($arrupdrdv, array('id_contact' => (int)$_POST['id_contact']));

					$curidstatus = $arr['id_statuscont'];
					$curidstatusconf = $arr['id_statuscontconf'];
					//add name in id_statuscont for general update log
					if ($st = Setting::getStatus(array('id_statuscont' => $curidstatus)))
						$arr['id_statuscont'] = $st->name_statuscont;

					//add name in id_statuscontconf for general update log
					if ($st = Setting::getStatusConf(array('id_statuscontconf' => $curidstatusconf))) {
						$arr['id_statuscontconf'] = $st->name_statuscontconf;

						//Delete rdv for specific status conf
						if ((int)$st->cancel_rdv == 1) {
							if (RDV::findOne(array('r.id_contact' => (int)$_POST['id_contact']))) {
								RDV::delete(array('id_contact' => (int)$_POST['id_contact']));
								CrmAction::create(array('id_crmuser' => $currentUser->id_crmuser, 'table_action' => 'Contacts', 'id_entity' => $_POST['id_contact'], 'type_action' => 'SUPPRESSION DU RDV SUITE AU CHANGEMENT DE STATUT', 'date_action' => date('Y-m-d H:i:s'), 'details_action' => 'Statut : '.$st->name_statuscontconf));
							}
						}

						//if not new - update status to the "force confirm" one
						if (strpos(strtolower($st->name_statuscontconf), 'nouveau') === false) {
							if ($st = Setting::getStatus(array('force_confirm' => '1'))) {
								Contact::update(array('id_statuscont' => $st->id_statuscont), array('id_contact' => (int)$_POST['id_contact']));
								$arr['id_statuscont'] = $st->name_statuscont;
								$curidstatus = $st->id_statuscont;
							}
						}
					}

					//general update log
					CrmAction::create(array('id_crmuser' => $currentUser->id_crmuser, 'table_action' => 'Contacts', 'id_entity' => $_POST['id_contact'], 'type_action' => 'MODIFICATION', 'date_action' => date('Y-m-d H:i:s'), 'details_action' => Tool::arrayToStr($arr)));

					$audit = '';

					//add log of updated status
					if ($curidstatus != $usr->id_statuscont) {
						$str = 'Ancien statut : ';
						if ($st = Setting::getStatus(array('id_statuscont' => $usr->id_statuscont)))
							$str .= $st->name_statuscont;
						$str .= '<br>Nouveau statut : '.$arr['id_statuscont'];
						CrmAction::create(array('id_crmuser' => $currentUser->id_crmuser, 'table_action' => 'Contacts', 'id_entity' => $_POST['id_contact'], 'type_action' => 'CHANGEMENT STATUT', 'date_action' => date('Y-m-d H:i:s'), 'details_action' => $str));
						$audit .= 'Champ : <b>Statut Télépro</b> | Ancien : <b>'.$st->name_statuscont.'</b> | Nouveau : <b>'.$arr['id_statuscont'].'</b><br>';
					}

					//add log of updated status conf
					if ($curidstatusconf != $usr->id_statuscontconf) {
						$str = 'Ancien statut : ';
						if ($st = Setting::getStatusConf(array('id_statuscontconf' => $usr->id_statuscontconf)))
							$str .= $st->name_statuscontconf;
						$str .= '<br>Nouveau statut : '.$arr['id_statuscontconf'];
						CrmAction::create(array('id_crmuser' => $currentUser->id_crmuser, 'table_action' => 'Contacts', 'id_entity' => $_POST['id_contact'], 'type_action' => 'CHANGEMENT STATUT CONFIRMATEUR', 'date_action' => date('Y-m-d H:i:s'), 'details_action' => $str));
						$audit .= 'Champ : <b>Statut Confirmateur</b> | Ancien : <b>'.$st->name_statuscontconf.'</b> | Nouveau : <b>'.$arr['id_statuscontconf'].'</b><br>';
					}

					//audit details of changes values
					foreach($arr as $k => $v) {
						if ($usr->{$k} != $v) {
							if ($k != 'id_statuscont' && $k != 'id_statuscontconf' && $k != 'id_contact_parrain' && $k != 'date_update')
								$audit .= 'Champ : <b>'.Contact::$importableFields[$k].'</b> | Ancien : <b>'.$usr->{$k}.'</b> | Nouveau : <b>'.$v.'</b><br>';
						}
					}

					if ($audit != '')
						Contact::addAudit(array(
							'id_crmuser' => $currentUser->id_crmuser,
							'id_contact' => $_POST['id_contact'],
							'date_update' => date('Y-m-d H:i:s'),
							'details' => $audit
						));

					successJSON(array('OK' => 'OK'));
				}
			} else {
				//creation mode
				$arr = array(
					'first_name' => $_POST['first_name'],
					'last_name' => $_POST['last_name'],
					'raison_sociale' => $_POST['raison_sociale'],
					'adr1' => $_POST['adr1'],
					'adr2' => $_POST['adr2'],
					'post_code' => $_POST['post_code'],
					'city' => $_POST['city'],
					'country' => 'FRANCE',
					'tel1' => $_POST['tel1'],
					'tel2' => $_POST['tel2'],
					'email' => $_POST['email'],
					'id_statuscont' => (int)$_POST['id_statuscont'],
					'id_statuscontconf' => (int)$_POST['id_statuscontconf'],
					'code_dossier' => $_POST['code_dossier'],
					'num_lot' => $_POST['num_lot'],
					'source' => $_POST['source'],
					'campain' => $_POST['campain'],
					'id_contact_parrain' => (int)$_POST['id_contact_parrain'],
					'geolat' => (float)$_POST['geolat'],
					'geolng' => (float)$_POST['geolng'],
					'codekey' => substr(str_shuffle("0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, 8),
					'date_create' => date('Y-m-d H:i:s')
				);

				if ($_POST['post_code'] != '')
					$arr['dept'] = substr($_POST['post_code'], 0, 2);

				if ($st = Setting::getStatusConf(array('id_statuscontconf' => $arr['id_statuscontconf'])))
					if ($st->rdv_need_exists == 1)
						errorJSON(array('message' => 'Un RDV doit exister pour pouvoir utiliser ce statut'));


				if (isset($_POST['date_rdv_pros']) && $_POST['date_rdv_pros'] != '')
					$arr['date_rdv_pros'] = Tool::dmYtoYmd($_POST['date_rdv_pros']);
				if (isset($_POST['heure_rdv_pros']))
					$arr['heure_rdv_pros'] = $_POST['heure_rdv_pros'];

				if (CrmUser::isTelepro($currentUser) || CrmUser::isConfirmateur($currentUser) || CrmUser::isManager($currentUser)) {
					$arr['id_crmuser'] =  $currentUser->id_crmuser;
					if (CrmUser::isConfirmateur($currentUser))
						$arr['id_crmuser_conf'] =  $currentUser->id_crmuser;
				} else {
					$arr['id_crmuser'] =  (int)$_POST['id_crmuser'];
					$arr['id_crmuser_conf'] =  (int)$_POST['id_crmuser_conf'];
				}


				if (CrmUser::isTelepro($currentUser)) {
					$tm = Team::findOne(array('id_team' => $currentUser->id_team));
					if ($tm)
						$arr['source'] = $tm->name_team;
				}
				// if (CrmUser::isAdmin($currentUser))
				// 	$arr['id_parrain'] = $_POST['id_parrain'];

				//if ((int)$_POST['id_statuscont'] == 4 || (int)$_POST['id_statuscont'] == 13)  //Fiche ok OU devis a faire
					//$arr['date_valid'] = date('Y-m-d H:i:s');

				//entrepot le plus proche
				if ((float)$_POST['geolat'] != 0 || (float)$_POST['geolng'] != 0) {
					$ent = Entrepot::getNearFromLatLng((float)$_POST['geolat'], (float)$_POST['geolng']);
					if ($ent) {
						$arr['id_entrepot_near'] = $ent->id_entrepot;

						$strinfo = Tool::getDirection((float)$_POST['geolat'], (float)$_POST['geolng'], $ent->geolat, $ent->geolng, '');
						if ($strinfo && !empty($strinfo)) {
							//calcul google map (google direction)
							$info = json_decode($strinfo);
							$dis = $info->routes[0]->legs[0]->distance->text;
							if (isset($info->routes[0]->legs[0]->duration_in_traffic)) {
								$del = $info->routes[0]->legs[0]->duration_in_traffic->value > $info->routes[0]->legs[0]->duration->value ? $info->routes[0]->legs[0]->duration_in_traffic->text : $info->routes[0]->legs[0]->duration->text;
								$delval = $info->routes[0]->legs[0]->duration_in_traffic->value > $info->routes[0]->legs[0]->duration->value ? $info->routes[0]->legs[0]->duration_in_traffic->value : $info->routes[0]->legs[0]->duration->value;
							} else {
								$del = $info->routes[0]->legs[0]->duration->text;
								$delval = $info->routes[0]->legs[0]->duration->value;
							}

							$arr['distance_ent'] = $dis;
							$arr['delay_ent'] = $del;
						}
						else {
							//calcul vol doiseau
							$arr['distance_ent'] = $ent->distance;
							$totdel = $ent->distance / 95;
							$delh = floor($totdel);
							$delay = $delh . 'h' .round(($totdel - $delh) * 60);
							$arr['delay_ent'] = $delay;
						}
					}
				}

				foreach ($_POST as $k => $v) {
					if (strpos($k, 'q_') !== false)
						$arr[$k] = $v;
				}

				if ($id_contact = Contact::create($arr)) {
					if ($st = Setting::getStatus(array('id_statuscont' => $arr['id_statuscont'])))
						$arr['id_statuscont'] = $st->name_statuscont;
					if ($st = Setting::getStatusConf(array('id_statuscontconf' => $arr['id_statuscontconf'])))
						$arr['id_statuscontconf'] = $st->name_statuscontconf;

					CrmAction::create(array('id_crmuser' => $currentUser->id_crmuser, 'table_action' => 'Contacts', 'id_entity' => $id_contact, 'type_action' => 'CREATION', 'date_action' => date('Y-m-d H:i:s'), 'details_action' => Tool::arrayToStr($arr)));

					successJSON(array('OK' => 'OK', 'id_contact' => $id_contact));
				} else
					errorJSON(array('message' => 'Erreur à la création du client / prospect ' . print_r($arr)));
			}
			break;


		case 'update-quest':
			if (!checkFields($_POST, array('id_contact')))
				errorJSON(array('message' => 'Informations incorrectes'));

			if ((int)$_POST['id_contact'] > 0) {
				//update mode

				$usr = Contact::findOne(array('c.id_contact' => $_POST['id_contact']));
				if (!$usr)
					errorJSON(array('message' => 'Client / Prospect inexistant'));

				$arr = array(
					'date_update' => date('Y-m-d H:i:s')
				);

				foreach ($_POST as $k => $v) {
					if (strpos($k, 'q_') !== false)
						$arr[$k] = $v;
				}

				if (Contact::update($arr, array('id_contact' => $_POST['id_contact']))) {
					CrmAction::create(array('id_crmuser' => $currentUser->id_crmuser, 'table_action' => 'Contacts', 'id_entity' => $_POST['id_contact'], 'type_action' => 'MODIFICATION QUESTIONNAIRE', 'date_action' => date('Y-m-d H:i:s'), 'details_action' => Tool::arrayToStr($arr)));
					successJSON(array('OK' => 'OK'));
				}
			}

			break;

		case 'update-fisc':
			if (!checkFields($_POST, array('id_contact')))
				errorJSON(array('message' => 'Informations incorrectes'));

			if ((int)$_POST['id_contact'] > 0) {
				//update mode

				$usr = Contact::findOne(array('c.id_contact' => $_POST['id_contact']));
				if (!$usr)
					errorJSON(array('message' => 'Client / Prospect inexistant'));

				$arr = array(
					'date_update' => date('Y-m-d H:i:s'),
					'q_impot_html' => isset($_POST['htmlimp']) ? $_POST['htmlimp'] : ''
				);

				foreach ($_POST as $k => $v) {
					if (strpos($k, 'q_') !== false)
						$arr[$k] = $v;
				}

				if (Contact::update($arr, array('id_contact' => $_POST['id_contact']))) {
					unset($arr['q_impot_html']);
					CrmAction::create(array('id_crmuser' => $currentUser->id_crmuser, 'table_action' => 'Contacts', 'id_entity' => $_POST['id_contact'], 'type_action' => 'MODIFICATION INFOS FISCALES', 'date_action' => date('Y-m-d H:i:s'), 'details_action' => Tool::arrayToStr($arr)));
					successJSON(array('OK' => 'OK'));
				}
			}

			break;

		case 'import-ct':

			if (!checkFields($_FILES, array('file')))
				errorJSON(array('message' => 'Informations incorrectes'));

			$file = $_FILES['file']['tmp_name'];
			if (is_uploaded_file($file)) {

				$cname = pathinfo($_FILES['file']['name'], PATHINFO_FILENAME);
				$ext = pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION);
				$dirup = __DIR__ . '/uploads/';
				$inpfile = $dirup . 'IMPCT.xls';
				unset($_FILES);
				if ($ext != 'xlsx' && $ext != 'xls')
					errorJSON(array('message' => 'Extension incorrecte (Type XLS ou XLSX accepté)'));
				else
				if (move_uploaded_file($file, $inpfile)) {
					//début du traitement

					require_once('lib/excel/PHPExcel.php');
					$objPHPExcel = PHPExcel_IOFactory::load($inpfile);
					foreach ($objPHPExcel->getWorksheetIterator() as $worksheet) {
						$highestRow         = $worksheet->getHighestRow(); // e.g. 10
						$highestColumn      = $worksheet->getHighestColumn(); // e.g 'F'
						$highestColumnIndex = PHPExcel_Cell::columnIndexFromString($highestColumn);

						$select_ctfields = '<select class="select-chosen" class="form-control" name="col_##"><option value="0"> </option>';
						foreach (Contact::$importableFields as $fld => $val)
							$select_ctfields .= '<option value="' . $fld . '">' . $val . '</option>';
						$select_ctfields .= '</select>';
						$strhtml = '';
						$strhd = '';
						for ($col = 0; $col < $highestColumnIndex; ++$col) {
							$hdrow[$col] = trim($worksheet->getCellByColumnAndRow($col, '1')->getValue());
							$strhd .= '<td>' . str_replace('##', $col, $select_ctfields) . '</td>';
							$strhtml .= '<th>' . $hdrow[$col] . '</th>';
						}
						$strhtml = '<tr>' . $strhd . '</tr><tr>' . $strhtml . '</tr>';
						for ($row = 2; $row <= 8; ++$row) {
							$strhtml .= '<tr>';
							for ($col = 0; $col < $highestColumnIndex; ++$col) {
								$strhtml .= '<td>' . trim($worksheet->getCellByColumnAndRow($col, $row)->getValue()) . '</td>';
							}
							$strhtml .= '</tr>';
						}
						$strhtml = '<table class="table table-striped table-vcenter table-condensed table-hover">' . $strhtml . '</table>';
						successJSON(array('OK' => 'OK', 'html' => $strhtml));
					}
				} else
					errorJSON(array('message' => 'Impossible de charger le fichier'));
			} else
				errorJSON(array('message' => 'Fichier non uploadé'));
			break;

		case 'import-cols':
			ini_set('max_execution_time', 0);
			ini_set('memory_limit', '1024M');
			require_once('lib/excel/PHPExcel.php');

			/*ob_start();
			successJSON(array('resp' => 'OK'), false);
			$size = ob_get_length();
			header("Content-Encoding: none");
			header("Content-Length: {$size}");
			header("Connection: close");
			ob_end_flush();
			ob_flush();
			flush();*/

			$dirup = __DIR__ . '/uploads/';
			$inpfile = $dirup . 'IMPCT.xls';
			$objPHPExcel = PHPExcel_IOFactory::load($inpfile);
			$nbimp = 0;
			$logimport = '';
			foreach ($objPHPExcel->getWorksheetIterator() as $worksheet) {
				$highestRow         = $worksheet->getHighestRow(); // e.g. 10
				$highestColumn      = $worksheet->getHighestColumn(); // e.g 'F'
				$highestColumnIndex = PHPExcel_Cell::columnIndexFromString($highestColumn);
				//if ($highestColumnIndex > 10)
				//$highestColumnIndex = 10;

				//$nrColumns = ord($highestColumn) - 64;


				for ($row = 2; $row <= $highestRow; ++$row) {
					$arr = array();
					$note = '';
					for ($col = 0; $col < $highestColumnIndex; ++$col) {
						$val = trim($worksheet->getCellByColumnAndRow($col, $row)->getValue());
						if (isset($_POST['col_' . $col]) && $_POST['col_' . $col] != '0' && $_POST['col_' . $col] != 'note') {
							if ($_POST['col_' . $col] == 'id_statuscont') {
								$st = Setting::getStatus(array('name_statuscont' => $val));
								if ($st)
									$val = $st->id_statuscont;
								else
									$val = 1; //default status new
							} else
							if ($_POST['col_' . $col] == 'id_crmuser' || $_POST['col_' . $col] == 'id_crmuser_conf') {
								$usr = CrmUser::findOne(array('email' => $val));
								if ($usr)
									$val = $usr->id_crmuser;
								else
									$val = 0;
							} else
							if ($_POST['col_' . $col] == 'date_create') {
								$val = Tool::dmYtoYmd($val);
							} else
							if ($_POST['col_' . $col] == 'q_type_chauffage') {
								$val = str_replace(' ', ',', trim(str_replace('|', ' ', $val)));
							}

							$arr[$_POST['col_' . $col]] = $val;
						} else
						if ($_POST['col_' . $col] == 'note')
							$note = $val;
					}

					if (($arr['first_name'] != "" || $arr['last_name'] != "") && $arr['tel1'] != "") {

						$doub = Contact::findDoublonTel($arr['tel1'], isset($arr['tel2']) ? $arr['tel2'] : '');
						if (!$doub) {
							if ($id_contact = Contact::create($arr)) {
								CrmAction::create(array('id_crmuser' => $currentUser->id_crmuser, 'table_action' => 'Contacts', 'id_entity' => $id_contact, 'type_action' => 'CREATION IMPORT', 'date_action' => date('Y-m-d H:i:s'), 'details_action' => Tool::arrayToStr($arr)));
								$nbimp++;
								if ($note != '')
									Comment::create(array('id_contact' => $id_contact, 'date_comment' => $dtcreate, 'text_comment' => $note));
							} else
								$logimport .= 'Ligne ' . $row . ' - Erreur a l\'insertion -> : ' . $arr['first_name'] . ' ' . $arr['last_name'] . '<br>';
						} else
							$logimport .= 'Ligne ' . $row . ' - Tel existe : ' . $arr['tel1'] . (isset($arr['tel2']) ? ' / ' . $arr['tel2'] : '') . '  -  Contact : ' . $doub->first_name . ' ' . $doub->last_name . ' à ' . $doub->post_code . ' ' . $doub->city . '<br>';
					} else {
						$existstr = false;
						foreach ($arr as $ar) {
							if ($ar != '') {
								$existstr = true;
								break;
							}
						}
						if ($existstr)
							$logimport .= 'Ligne ' . $row . ' - Nom, prenom ou tel manquant pour : ' . print_r($arr, true) . '<br>';
					}
				}
			}

			if ($nbimp > 0)
				$logimport .= 'Fin de l\'importation de ' . $nbimp . ' lignes importées correctement <br>';

			unlink($inpfile);

			if ($logimport != '')
				Setting::updateGlobalSettings('last_import_log', $logimport);

			successJSON(array('OK' => 'OK', 'log' => $logimport));
			break;


		case 'delete-contacts':
			if (!checkFields($_POST, array('rws')))
				errorJSON(array('message' => 'Informations incorrectes'));

			if (count($_POST['rws']) == 0)
				errorJSON(array('message' => 'Informations incorrectes'));

			foreach ($_POST['rws'] as $idc) {
				CrmAction::create(array('id_crmuser' => $currentUser->id_crmuser, 'table_action' => 'Contacts', 'id_entity' => $idc, 'type_action' => 'CONTACT SUPPRESSION', 'date_action' => date('Y-m-d H:i:s')));
				Contact::delete(array('id_contact' => $idc));
				RDV::delete(array('id_contact' => $idc));
				Comment::delete(array('id_contact' => $idc));
				Doc::delete(array('id_contact' => $idc));
				Prestation::delete(array('id_contact' => $idc));
			}

			successJSON(array('OK' => 'OK'));
			break;

		case 'assign-contacts':
			if (!checkFields($_POST, array('rws', 'assid_crmuser', 'id_profil')))
				errorJSON(array('message' => 'Informations incorrectes'));


			$cuser = CrmUser::findOne(array('id_crmuser' => (int)$_POST['assid_crmuser']));
			if (!$cuser)
				errorJSON(array('message' => 'Informations incorrectes'));

			foreach ($_POST['rws'] as $idc) {
				CrmAction::create(array('id_crmuser' => $currentUser->id_crmuser, 'table_action' => 'Contacts', 'id_entity' => $idc, 'type_action' => 'CONTACT ASSIGNATION ' . (CrmUser::isTelepro($_POST['id_profil']) ? 'TELEPRO' : 'CONFIRMATEUR'), 'date_action' => date('Y-m-d H:i:s'), 'details_action' => $cuser->user_name));
				$arraupd = array();
				if (CrmUser::isTelepro($_POST['id_profil']))
					$arrupd['id_crmuser'] = (int)$_POST['assid_crmuser'];
				else
					$arrupd['id_crmuser_conf'] = (int)$_POST['assid_crmuser'];

				Contact::update($arrupd, array('id_contact' => $idc));
			}

			successJSON(array('OK' => 'OK'));
			break;


		case 'changest-contacts';
			if (!checkFields($_POST, array('rws', 'stcont', 'id_statuscont')))
				errorJSON(array('message' => 'Informations incorrectes'));

			$st = Setting::getStatus(array('id_statuscont' => (int)$_POST['id_statuscont']));
			if (!$st)
				errorJSON(array('message' => 'Informations incorrectes'));

			if ((int)$_POST['stcont'] > 0) {
				CrmAction::create(array('id_crmuser' => $currentUser->id_crmuser, 'table_action' => 'Contacts', 'id_entity' => (int)$_POST['stcont'], 'type_action' => 'CONTACT CHANGE STATUT', 'date_action' => date('Y-m-d H:i:s'), 'details_action' => $st->name_statuscont));

				$arr = array(
					'id_statuscont' => (int)$_POST['id_statuscont'],
					'date_update' => date('Y-m-d H:i:s')
				);

				Contact::update($arr, array('id_contact' => (int)$_POST['stcont']));
			} else {
				foreach ($_POST['rws'] as $idc) {
					CrmAction::create(array('id_crmuser' => $currentUser->id_crmuser, 'table_action' => 'Contacts', 'id_entity' => $idc, 'type_action' => 'CONTACT CHANGE STATUT', 'date_action' => date('Y-m-d H:i:s'), 'details_action' => $st->name_statuscont));

					$arr = array(
						'id_statuscont' => (int)$_POST['id_statuscont'],
						'date_update' => date('Y-m-d H:i:s')
					);

					Contact::update($arr, array('id_contact' => $idc));
				}
			}

			successJSON(array('OK' => 'OK'));
			break;

		case 'changestconf-contacts';
			if (!checkFields($_POST, array('rws', 'stcontconf', 'id_statuscontconf')))
				errorJSON(array('message' => 'Informations incorrectes'));

			$st = Setting::getStatusConf(array('id_statuscontconf' => (int)$_POST['id_statuscontconf']));
			if (!$st)
				errorJSON(array('message' => 'Informations incorrectes'));

			if ((int)$_POST['stcontconf'] > 0) {
				CrmAction::create(array('id_crmuser' => $currentUser->id_crmuser, 'table_action' => 'Contacts', 'id_entity' => (int)$_POST['stcontconf'], 'type_action' => 'CONTACT CHANGE STATUT', 'date_action' => date('Y-m-d H:i:s'), 'details_action' => $st->name_statuscont));

				$arr = array(
					'id_statuscontconf' => (int)$_POST['id_statuscontconf'],
					'date_update' => date('Y-m-d H:i:s')
				);

				/*if ((int)$_POST['id_statuscont'] == 4 || (int)$_POST['id_statuscont'] == 13)
					$arr['date_valid'] = date('Y-m-d H:i:s');
				else
				if ((int)$_POST['id_statuscont'] == 10) { //Installe
					$rdv = RDV::findOne(array('id_contact' => $_POST['stcontconf']));
					if ($rdv)
						$arr['date_install'] = $rdv->date_rdv;
				}*/

				Contact::update($arr, array('id_contact' => (int)$_POST['stcontconf']));
			}
			//Multi ligne
			/*else {
				foreach ($_POST['rws'] as $idc) {
					CrmAction::create(array('id_crmuser' => $currentUser->id_crmuser, 'table_action' => 'Contacts', 'id_entity' => $idc, 'type_action' => 'CONTACT CHANGE STATUT', 'date_action' => date('Y-m-d H:i:s'), 'details_action' => $st->name_statuscont));

					$arr = array(
						'id_statuscont' => (int)$_POST['id_statuscont'],
						'date_update' => date('Y-m-d H:i:s')
					);

					if ((int)$_POST['id_statuscont'] == 4 || (int)$_POST['id_statuscont'] == 13)
						$arr['date_valid'] = date('Y-m-d H:i:s');
					else
					if ((int)$_POST['id_statuscont'] == 10) { //Installe
						$rdv = RDV::findOne(array('id_contact' => $idc));
						if ($rdv)
							$arr['date_install'] = $rdv->date_rdv;
					}

					Contact::update($arr, array('id_contact' => $idc));
				}
			}*/

			successJSON(array('OK' => 'OK'));
			break;

		case 'add-comment':
			if (!checkFields($_POST, array('id_contact', 'comment')))
				errorJSON(array('message' => 'Informations incorrectes'));

			$inits = $currentUser->user_name; //Tool::getInitials($currentUser->user_name);

			if ($idc = Comment::create(array('id_contact' => $_POST['id_contact'], 'date_comment' => date('Y-m-d H:i:s'), 'text_comment' => $inits.' : '.$_POST['comment']))) {
				CrmAction::create(array('id_crmuser' => $currentUser->id_crmuser, 'table_action' => 'Contacts', 'id_entity' => $_POST['id_contact'], 'type_action' => 'AJOUT COMMENTAIRE', 'date_action' => date('Y-m-d H:i:s'), 'details_action' => $_POST['comment']));
				successJSON(array('OK' => 'OK', 'id_comment' => $idc, 'inits' => $inits));
			}
			break;

		case 'update-comment-chantier':
			if (!checkFields($_POST, array('id_contact', 'comment')))
				errorJSON(array('message' => 'Informations incorrectes'));

			if (Contact::update(array('comment' => $_POST['comment']), array('id_contact' => (int)$_POST['id_contact']))) {
				CrmAction::create(array('id_crmuser' => $currentUser->id_crmuser, 'table_action' => 'Contacts', 'id_entity' => $_POST['id_contact'], 'type_action' => 'MISE A JOUR COMMENTAIRE CHANTIER', 'date_action' => date('Y-m-d H:i:s'), 'details_action' => $_POST['comment']));
				successJSON(array('OK' => 'OK'));
			}
			break;

		case 'delete-comment':
			if (!checkFields($_POST, array('id_comment')))
				errorJSON(array('message' => 'Informations incorrectes'));

			$com = Comment::findOne(array('id_comment' => (int)$_POST['id_comment']));
			if (!$com)
				errorJSON(array('message' => 'Informations incorrectes'));

			if (Comment::delete(array('id_comment' => $_POST['id_comment']))) {
				CrmAction::create(array('id_crmuser' => $currentUser->id_crmuser, 'table_action' => 'Contacts', 'id_entity' => $com->id_contact, 'type_action' => 'SUPPRESSION COMMENTAIRE', 'date_action' => date('Y-m-d H:i:s'), 'details_action' => $com->text_comment));
				successJSON(array('OK' => 'OK'));
			}
			break;

		case 'add-recall':
			if (!checkFields($_POST, array('id_contact', 'date_recall', 'time_recall')))
				errorJSON(array('message' => 'Informations incorrectes'));

			$dtrecall = Tool::dmYtoYmd($_POST['date_recall']) . ' ' . $_POST['time_recall'];
			$txtcomment = 'Client à rappeler le <br>' . date('d/m/Y à H:i', strtotime($dtrecall));

			if ($idc = Comment::create(array('id_contact' => $_POST['id_contact'], 'date_comment' => date('Y-m-d H:i:s'), 'text_comment' => $txtcomment, 'type_comment' => '1', 'date_recall' => $dtrecall, 'id_user_comment' => $currentUser->id_crmuser))) {
				CrmAction::create(array('id_crmuser' => $currentUser->id_crmuser, 'table_action' => 'Contacts', 'id_entity' => $_POST['id_contact'], 'type_action' => 'AJOUT RAPPEL', 'date_action' => date('Y-m-d H:i:s'), 'details_action' => $txtcomment));
				successJSON(array('OK' => 'OK', 'comment' => $txtcomment,  'id_comment' => $idc));
			}
			break;

		case 'check-recall':
			if (CrmUser::isAdmin($currentUser))
				errorJSON(array('message' => ''));
			if (!checkFields($_POST, array('tm')))
				errorJSON(array('message' => 'Informations incorrectes'));

			$when = new DateTime(explode('(', $_POST['tm'])[0]);
			$dt = $when->format('Y-m-d H:i:s');
			$recall = Comment::getFirstRecall($dt, $currentUser->id_crmuser);
			if ($recall)
				successJSON(array('data' => $recall));
			errorJSON(array('message' => 'pas de rappel'));
			break;

		case 'wait-recall':
			if (!checkFields($_POST, array('idcom', 'idcli')))
				errorJSON(array('message' => 'Informations incorrectes'));

			$com = Comment::findOne(array('id_comment' => (int)$_POST['idcom'], 'id_contact' => (int)$_POST['idcli']));
			if (!$com)
				errorJSON(array('message' => 'Rappel inexistant'));

			$when = new DateTime(explode('(', $_POST['tm'])[0]);
			$when->add(new DateInterval('PT' . (int)$_POST['tp'] . 'M'));
			$dt = $when->format('Y-m-d H:i:s');

			Comment::update(array('date_recall' => $dt), array('id_comment' => (int)$_POST['idcom']));
			successJSON(array('OK' => 'OK'));
			break;

		case 'read-recall':
			if (!checkFields($_POST, array('idcom', 'idcli')))
				errorJSON(array('message' => 'Informations incorrectes'));

			$com = Comment::findOne(array('id_comment' => (int)$_POST['idcom'], 'id_contact' => (int)$_POST['idcli']));
			if (!$com)
				errorJSON(array('message' => 'Rappel inexistant'));

			Comment::update(array('is_read' => '1'), array('id_comment' => (int)$_POST['idcom']));
			successJSON(array('OK' => 'OK'));
			break;

		case 'upload-doc':
			if (!checkFields($_FILES, array('file')))
				errorJSON(array('message' => 'Informations incorrectes'));
			if (!checkFields($_POST, array('id_contact')))
				errorJSON(array('message' => 'Informations incorrectes'));

			$dirup = __DIR__ . '/storage/uploads/';
			$dir = $dirup . $_POST['codekey'].$_POST['id_contact'];
			if (!is_dir($dir)) {
				mkdir($dir);
				copy($dirup . 'index.php', $dir . '/index.php');
			}

			if ($fl = Tool::uploadFile($dir . '/', $_FILES['file'])) {
				$filename = $URL . '/storage/uploads/' . $_POST['codekey'] . $_POST['id_contact'] . '/' . $fl;
				if ($iddoc = Doc::create(array('id_contact' => (int)$_POST['id_contact'], 'date_doc' => date('Y-m-d H:i:s'), 'name_doc' => $filename))) {
					CrmAction::create(array('id_crmuser' => $currentUser->id_crmuser, 'table_action' => 'Contacts', 'id_entity' => $_POST['id_contact'], 'type_action' => 'AJOUT DOCUMENT', 'date_action' => date('Y-m-d H:i:s'), 'details_action' => $filename));
					successJSON(array('filename' => $filename, 'fl' => $fl, 'id_doc' => $iddoc, 'isimage' => Tool::isImage($filename) ? '1' : '0'));
				}
			}

			break;

		case 'delete-doc':
			if (!checkFields($_POST, array('iddoc')))
				errorJSON(array('message' => 'Informations incorrectes'));

			$doc = Doc::findOne(array('id_doc' => (int)$_POST['iddoc']));
			if (!$doc)
				errorJSON(array('message' => 'Informations incorrectes'));

			if (Doc::delete(array('id_doc' => (int)$_POST['iddoc']))) {
				CrmAction::create(array('id_crmuser' => $currentUser->id_crmuser, 'table_action' => 'Contacts', 'id_entity' => $doc->id_contact, 'type_action' => 'SUPPRESSION DOCUMENT', 'date_action' => date('Y-m-d H:i:s'), 'details_action' => $doc->name_doc));
				successJSON(array('OK' => 'OK'));
			}

			break;

		case 'sync-fisc':
			if (!checkFields($_POST, array('nofisc1', 'refavis1')))
				errorJSON(array('message' => 'Informations incorrectes'));


			$globbody = '';
			$globrev = 0;
			$globperson = 0;
			$globnbfoyer = 0;

			for ($numavis=1;$numavis<=5;$numavis++) {
				if (checkFields($_POST, array('nofisc'.$numavis, 'refavis'.$numavis))) {
					$body = '';
					$elms = '';
					$nbperson = 0;
					$rev = 0;
					$oldkey = '';

					getInfoImpot($_POST['nofisc'.$numavis], $_POST['refavis'.$numavis], $body, $elms);

					foreach ($elms as $elm) {
						if ($oldkey == 'Situation de famille') {
							if (trim($elm->nodeValue) == 'Marié(e)s')
								$nbperson += 2;
							else
								$nbperson ++;
						}
						else
						if ($oldkey == 'Nombre de personne(s) à charge')
							$nbperson += (int)$elm->nodeValue;
						else
						if ($oldkey == 'Revenu fiscal de référence')
							$rev = (float)str_replace(array(chr( 194 ) . chr( 160 ), '€', ' '), '', trim($elm->nodeValue));

						$oldkey = trim($elm->nodeValue);
					}

					$globbody .= $body;
					$globperson += $nbperson;
					$globrev += $rev;
					$globnbfoyer++;
				}
			}

			successJSON(array('rev' => $globrev, 'nbperson' => $globperson, 'nbfoyer' => $globnbfoyer, 'html' => $globbody));
			/* OLD
			$body = '';
			$elms = '';
			getInfoImpot(trim($_POST['nofisc1']), trim($_POST['refavis1']), $body, $elms);

			$nbperson = 0;
			$rev = 0;
			$lname = '';
			$fname = '';
			$adr = '';
			$isadr = false;
			$cpvil = '';
			$cp = '';
			$vil = '';
			$oldkey = '';
			foreach ($elms as $elm) {
				//echo $elm->nodeValue.'<br>';
				if ($oldkey == 'Situation de famille') {
					if (trim($elm->nodeValue) == 'Marié(e)s')
						$nbperson += 2;
					else
						$nbperson++;
				} else
				if ($oldkey == 'Nombre de personne(s) à charge')
					$nbperson += (int)$elm->nodeValue;
				else
				if ($oldkey == 'Revenu fiscal de référence')
					$rev = (float)str_replace(array(chr(194) . chr(160), '€', ' '), '', trim($elm->nodeValue));
				else
				if ($oldkey == 'Nom')
					$lname = trim($elm->nodeValue);
				else
				if ($oldkey == 'Prénom(s)')
					$fname = trim($elm->nodeValue);
				else
				if (strpos($oldkey, 'Adresse déclarée') !== false) {
					$adr = trim($elm->nodeValue);
					$isadr = true;
				} else
				if ($isadr && trim($elm->nodeValue) != '') {
					$cpvil = trim($elm->nodeValue);
					$cpvil = explode(' ', $cpvil);
					$cp = $cpvil[0];
					$vil = $cpvil[1];
					$isadr = false;
				}

				$oldkey = trim($elm->nodeValue);
			}


			$body2 = '';
			$elms = '';
			if (checkFields($_POST, array('nofisc2', 'refavis2'))) {
				getInfoImpot($_POST['nofisc2'], $_POST['refavis2'], $body2, $elms);

				foreach ($elms as $elm) {
					if ($oldkey == 'Revenu fiscal de référence')
						$rev += (float)str_replace(array(chr(194) . chr(160), '€', ' '), '', trim($elm->nodeValue));

					$oldkey = trim($elm->nodeValue);
				}
			}

			successJSON(array('rev' => $rev, 'nbperson' => $nbperson, 'fname' => $fname, 'lname' => $lname, 'adr' => $adr, 'cp' => $cp, 'vil' => $vil, 'html' => $body . ($body2 != '' ? '<br><br>' . $body2 : '')));
			*/
			break;

		case 'update-dir':
			if (!checkFields($_POST, array('id_contact', 'revenu_fiscal', 'nb_person'))) /*'id_installator', 'id_mandator', 'id_contributor', */
				errorJSON(array('message' => 'Informations incorrectes'));

			if ((!isset($_POST['101_m2']) || (float)$_POST['101_m2'] == 0) && (!isset($_POST['102_m2']) || (float)$_POST['102_m2'] == 0) && (!isset($_POST['103_m2']) || (float)$_POST['103_m2'] == 0))
				errorJSON(array('message' => 'Veuillez renseigner la surface des travaux 101, 102, 103'));

			$contact = Contact::findOne(array('c.id_contact' => (int)$_POST['id_contact']));
			if (!$contact)
				errorJSON(array('message' => 'Client inexistant'));

			//$isidf = Setting::isIDF($contact->post_code);
			$preca = Setting::getPrecarityInfo($contact->post_code, (float)$_POST['revenu_fiscal'], (int)$_POST['nb_person'], $_POST['type_chauf'], (float)$_POST['101_m2'], (float)$_POST['102_m2'], (float)$_POST['103_m2']);
			if (count($preca) == 0)
				errorJSON(array('message' => 'Erreur au calcul des informations de précarité et bonus'));

			$sets = (object)Setting::getGlobalSettings();
			$px101 = isset($preca['pu101']) ? (float)$preca['pu101'] : 0;
			$px102 = isset($preca['pu102']) ? (float)$preca['pu102'] : 0;
			$px103 = isset($preca['pu103']) ? (float)$preca['pu103'] : 0;
			$bonus101 = isset($preca['bonus101']) ? (float)$preca['bonus101'] : 0;
			$bonus102 = isset($preca['bonus102']) ? (float)$preca['bonus102'] : 0;
			$bonus103 = isset($preca['bonus103']) ? (float)$preca['bonus103'] : 0;
			$totht = ($px101 * (float)$_POST['101_m2']) + ($px102 * (float)$_POST['102_m2']) + ($px103 * (float)$_POST['103_m2']);
			$tva = $totht * ($sets->TX_TVA / 100);
			$ttc = $totht + $tva;
			$bonus = ($bonus101 * (float)$_POST['101_m2']) + ($bonus102 * (float)$_POST['102_m2']) + ($bonus103 * (float)$_POST['103_m2']);

			$dtinvoice = isset($_POST['date_invoice']) && $_POST['date_invoice'] != '' ? Tool::dmYtoYmd($_POST['date_invoice']) : '';
			$arr = array(
				'id_contact' => $contact->id_contact,
				'id_mandator' => isset($_POST['id_mandator']) ? (int)$_POST['id_mandator'] : 0,
				'id_contributor' => isset($_POST['id_contributor']) ? (int)$_POST['id_contributor'] : 0,
				'id_installator' => isset($_POST['id_installator']) ? (int)$_POST['id_installator'] : 0,
				'no_fiscal_1' => trim($_POST['no_fiscal_1']),
				'no_fiscal_2' => trim($_POST['no_fiscal_2']),
				'no_fiscal_3' => trim($_POST['no_fiscal_3']),
				'no_fiscal_4' => trim($_POST['no_fiscal_4']),
				'no_fiscal_5' => trim($_POST['no_fiscal_5']),
				'ref_avis_1' => trim($_POST['ref_avis_1']),
				'ref_avis_2' => trim($_POST['ref_avis_2']),
				'ref_avis_3' => trim($_POST['ref_avis_3']),
				'ref_avis_4' => trim($_POST['ref_avis_4']),
				'ref_avis_5' => trim($_POST['ref_avis_5']),
				'type_chauf' => $_POST['type_chauf'],
				'occupation' => $_POST['occupation'],
				'revenu_fiscal' => $_POST['revenu_fiscal'],
				'nb_foyer' => $_POST['nb_foyer'],
				'nb_person' => $_POST['nb_person'],
				'chantier_status' => isset($_POST['chantier_status']) ? 1 : 0,
				'101_m2' => $_POST['101_m2'],
				'id_material_101' => $_POST['id_material_101'],
				'101_comble_perdu' => isset($_POST['101_comble_perdu']) ? 1 : 0,
				'101_rem_toiture' => isset($_POST['101_rem_toiture']) ? 1 : 0,
				'102_m2' => $_POST['102_m2'],
				'id_material_102' => $_POST['id_material_102'],
				'103_m2' => $_POST['103_m2'],
				'id_material_103' => $_POST['id_material_103'],
				'103_colle' => isset($_POST['103_colle']) ? 1 : 0,
				'103_clous' => isset($_POST['103_clous']) ? 1 : 0,
				'103_cheville' => isset($_POST['103_cheville']) ? 1 : 0,
				'101_pu' => $px101,
				'102_pu' => $px102,
				'103_pu' => $px103,
				'101_cumac' => isset($preca['cumac101']) ? (float)$preca['cumac101'] : 0,
				'102_cumac' => isset($preca['cumac102']) ? (float)$preca['cumac102'] : 0,
				'103_cumac' => isset($preca['cumac103']) ? (float)$preca['cumac103'] : 0,
				'101_bonus' => $bonus101,
				'102_bonus' => $bonus102,
				'103_bonus' => $bonus103,
				'type_preca' => (int)$preca['preca'],
				'zone' => $preca['zone'],
				'totht' => $totht,
				'txtva' => $sets->TX_TVA,
				'mttva' => $tva,
				'totttc' => $ttc,
				'mtcontrib' => (int)$preca['preca'] == 2 ? $ttc - 1 : $bonus,
				'impot_html' => trim($_POST['htmlimp'])
			);

			if (isset($_POST['date_devis']) && $_POST['date_devis'] != '')
				$arr['date_devis'] = Tool::dmYtoYmd($_POST['date_devis']);
			if ($dtinvoice != '')
				$arr['date_invoice'] = $dtinvoice;

			if (trim($_POST['htmlimp']) != '') {
				$nm = 'AVIS IMPOSITION.pdf';
				IsoPDFBuilder::BuildContactDoc($contact->codekey . $contact->id_contact, $nm, str_replace('<div id="principal">', '<div id="principal"><div id=date><b>Date d\'impression de l\'avis : ' . date("d-m-Y") . '</b><br/><br/></div>', trim($_POST['htmlimp'])));
				if (!Doc::findOne(array('id_contact' => $contact->id_contact, 'name_doc' => $nm)))
					Doc::create(array('id_contact' => $contact->id_contact, 'date_doc' => date('Y-m-d H:i:s'), 'name_doc' => $nm));
			}

			$dorefresh = false;
			$idp = (int)$_POST['id_prestation'];
			if ($idp == 0) {
				$arr['date_create'] = date('Y-m-d H:i:s');
				$arr['user_create'] = $currentUser->id_crmuser;
				if ($dtinvoice != '')
					$arr['nofac'] = Prestation::getNextNoFac();
				if ($idp = Prestation::create($arr)) {
					unset($arr['impot_html']);
					CrmAction::create(array('id_crmuser' => $currentUser->id_crmuser, 'table_action' => 'Contacts', 'id_entity' => $contact->id_contact, 'type_action' => 'CREATION CHANTIER', 'date_action' => date('Y-m-d H:i:s'), 'details_action' => Tool::arrayToStr($arr)));
				}
			} else {
				if ($dtinvoice != '') {
					$oldprest = Prestation::findOne(array('id_prestation' => $idp));
					if ($oldprest && (strtotime($oldprest->date_invoice) == 0 || $oldprest->nofac == 0))
						$arr['nofac'] = Prestation::getNextNoFac();
				}

				if (Prestation::update($arr, array('id_prestation' => $idp))) {
					unset($arr['impot_html']);
					CrmAction::create(array('id_crmuser' => $currentUser->id_crmuser, 'table_action' => 'Contacts', 'id_entity' => $contact->id_contact, 'type_action' => 'MODIFICATION CHANTIER', 'date_action' => date('Y-m-d H:i:s'), 'details_action' => Tool::arrayToStr($arr)));
					$doubfisc = Prestation::findDoublonFiscal((object)$arr);
					$dorefresh = $doubfisc;
				}
			}

			successJSON(array('id_prestation' => $idp, 'precainfo' => $preca, 'dorefresh' => $dorefresh ? '1' : '0'));
			break;



		case 'update-entrepot':
			if (!checkFields($_POST, array('id_entrepot', 'entrepot_name', 'adr1', 'post_code', 'city', 'tel1', 'email')))
				errorJSON(array('message' => 'Informations incorrectes'));

			if ((int)$_POST['id_entrepot'] > 0) {
				//update mode

				$usr = Entrepot::findOne(array('id_entrepot' => $_POST['id_entrepot']));
				if (!$usr)
					errorJSON(array('message' => 'Entrepot inexistant'));

				$arr = array(
					'entrepot_name' => $_POST['entrepot_name'],
					'adr1' => $_POST['adr1'],
					'adr2' => $_POST['adr2'],
					'post_code' => $_POST['post_code'],
					'city' => $_POST['city'],
					'country' => 'FRANCE',
					'tel1' => $_POST['tel1'],
					'email' => $_POST['email'],
					'comment' => $_POST['comment'],
					'geolat' => (float)$_POST['geolat'],
					'geolng' => (float)$_POST['geolng'],
					'date_update' => date('Y-m-d H:i:s')
				);

				if (Entrepot::update($arr, array('id_entrepot' => $_POST['id_entrepot']))) {
					CrmAction::create(array('id_crmuser' => $currentUser->id_crmuser, 'table_action' => 'Entrepots', 'id_entity' => $_POST['id_entrepot'], 'type_action' => 'MODIFICATION ENTREPOT', 'date_action' => date('Y-m-d H:i:s'), 'details_action' => Tool::arrayToStr($arr)));
					successJSON(array('OK' => 'OK'));
				}
			} else {
				//creation mode
				$arr = array(
					'entrepot_name' => $_POST['entrepot_name'],
					'adr1' => $_POST['adr1'],
					'adr2' => $_POST['adr2'],
					'post_code' => $_POST['post_code'],
					'city' => $_POST['city'],
					'country' => 'FRANCE',
					'tel1' => $_POST['tel1'],
					'email' => $_POST['email'],
					'comment' => $_POST['comment'],
					'geolat' => (float)$_POST['geolat'],
					'geolng' => (float)$_POST['geolng'],
					'date_create' => date('Y-m-d H:i:s')
				);

				if ($id_entrepot = Entrepot::create($arr)) {
					CrmAction::create(array('id_crmuser' => $currentUser->id_crmuser, 'table_action' => 'Entrepots', 'id_entity' => $id_entrepot, 'type_action' => 'CREATION ENTREPOT', 'date_action' => date('Y-m-d H:i:s'), 'details_action' => Tool::arrayToStr($arr)));
					successJSON(array('OK' => 'OK', 'id_entrepot' => $id_entrepot));
				} else
					errorJSON(array('message' => 'Erreur à la création de l\'entrepot'));
			}
			break;

		case 'delete-entrepot':
			if (!checkFields($_POST, array('ident')))
				errorJSON(array('message' => 'Informations incorrectes'));

			$rdv = RDV::findOne(array('id_entrepot' => (int)$_POST['ident']));
			if ($rdv)
				errorJSON(array('message' => 'Suppresion impossible, il existe des rendez vous rattachés à cet entrepot'));

			Entrepot::delete(array('id_entrepot' => (int)$_POST['ident']));

			successJSON(array('OK' => 'OK'));
			break;

		case 'update-installator':
			if (!checkFields($_POST, array('id_installator', 'installator_name', 'adr1', 'post_code', 'city', 'tel1')))
				errorJSON(array('message' => 'Informations incorrectes'));

			if (((int)$_POST['type_msg'] == 1 || (int)$_POST['type_msg'] == 2) && empty($_POST['tel2']))
				errorJSON(array('message' => 'Veuillez saisir le numéro de SMS'));

			if ((int)$_POST['id_installator'] > 0) {
				//update mode

				$usr = Installator::findOne(array('id_installator' => $_POST['id_installator']));
				if (!$usr)
					errorJSON(array('message' => 'Installateur inexistant'));

				$arr = array(
					'installator_name' => $_POST['installator_name'],
					'first_name_ins' => $_POST['first_name_ins'],
					'last_name_ins' => $_POST['last_name_ins'],
					'siret_ins' => $_POST['siret_ins'],
					'adr1' => $_POST['adr1'],
					'adr2' => $_POST['adr2'],
					'post_code' => $_POST['post_code'],
					'city' => $_POST['city'],
					'country' => 'FRANCE',
					'tel1' => $_POST['tel1'],
					'tel2' => $_POST['tel2'],
					'comment' => $_POST['comment'],
					'type_msg' => (int)$_POST['type_msg'],
					'agenda_id' => $_POST['agenda_id'],
					'geolat' => (float)$_POST['geolat'],
					'geolng' => (float)$_POST['geolng'],
					'date_update' => date('Y-m-d H:i:s')
				);

				if (Installator::update($arr, array('id_installator' => $_POST['id_installator']))) {
					CrmAction::create(array('id_crmuser' => $currentUser->id_crmuser, 'table_action' => 'Installators', 'id_entity' => $_POST['id_installator'], 'type_action' => 'MODIFICATION INSTALLATEUR', 'date_action' => date('Y-m-d H:i:s'), 'details_action' => Tool::arrayToStr($arr)));
					successJSON(array('OK' => 'OK'));
				}
			} else {
				//creation mode
				$arr = array(
					'installator_name' => $_POST['installator_name'],
					'first_name_ins' => $_POST['first_name_ins'],
					'last_name_ins' => $_POST['last_name_ins'],
					'siret_ins' => $_POST['siret_ins'],
					'adr1' => $_POST['adr1'],
					'adr2' => $_POST['adr2'],
					'post_code' => $_POST['post_code'],
					'city' => $_POST['city'],
					'country' => 'FRANCE',
					'tel1' => $_POST['tel1'],
					'tel2' => $_POST['tel2'],
					'comment' => $_POST['comment'],
					'type_msg' => (int)$_POST['type_msg'],
					'agenda_id' => $_POST['agenda_id'],
					'geolat' => (float)$_POST['geolat'],
					'geolng' => (float)$_POST['geolng'],
					'date_create' => date('Y-m-d H:i:s')
				);

				if ($id_installator = Installator::create($arr)) {
					CrmAction::create(array('id_crmuser' => $currentUser->id_crmuser, 'table_action' => 'Installators', 'id_entity' => $id_installator, 'type_action' => 'CREATION INSTALLATEUR', 'date_action' => date('Y-m-d H:i:s'), 'details_action' => Tool::arrayToStr($arr)));
					successJSON(array('OK' => 'OK', 'id_installator' => $id_installator));
				} else
					errorJSON(array('message' => 'Erreur à la création de l\'installateur'));
			}
			break;

		case 'delete-installator':
			if (!checkFields($_POST, array('idins')))
				errorJSON(array('message' => 'Informations incorrectes'));

			$rdv = RDV::findOne(array('id_installator' => (int)$_POST['idins']));
			if ($rdv)
				errorJSON(array('message' => 'Suppresion impossible, il existe des rendez vous rattachés à cet installateur'));

			Installator::delete(array('id_installator' => (int)$_POST['idins']));

			successJSON(array('OK' => 'OK'));
			break;

		case 'generate-plan':
			if (!checkFields($_POST, array('ide', 'data')))
				errorJSON(array('message' => 'Informations incorrectes'));


			$data = array();
			parse_str($_POST['data'], $data);
			$dtst = strtotime(Tool::dmYtoYmd($data['startdt']));
			$dtend = strtotime(Tool::dmYtoYmd($data['enddt']));
			if ($dtst == 0 || $dtend == 0 || ($dtst > $dtend))
				errorJSON(array('message' => 'Informations de dates incorrectes'));
			//$geolat = (float)$data['geolatplan'];
			//$geolng = (float)$data['geolngplan'];
			$oneday = 86400;
			$curdt = $dtst;
			$sets = (object)Setting::getGlobalSettings();
			$today = time();
			$strduration = Tool::addTimeStr($sets->DURATION_RDV);
			$strmornst = Tool::addTimeStr($sets->MORNING_START);
			$strmornend = Tool::addTimeStr($sets->MORNING_END);
			$straftst = Tool::addTimeStr($sets->AFTERNOON_START);
			$straftend = Tool::addTimeStr($sets->AFTERNOON_END);
			$rdvrange = Setting::getRDVRange();
			while ($curdt <= $dtend) {
				$curday = date('w', $curdt);
				$j = 0;
				$opn = false;
				foreach ($rdvrange as $range) {
					$j++;
					if (isset($data['day_' . $curday . '_' . $j])) {
						$hrst = strtotime(Tool::addTimeStr($range['hour_start']), $curdt);
						$hrend = strtotime(Tool::addTimeStr($range['hour_end']), $curdt);
						Planning::create(array(
							'id_entrepot' => $_POST['ide'],
							'date_planning' => date('Y-m-d', $curdt),
							'hour_start' => date('H:i:s', $hrst),
							'hour_end' => date('H:i:s', $hrend),
							'geolat' => 0, //$geolat,
							'geolng' => 0 //$geolng
						));
					}
				}


				$curdt += $oneday;
			}

			successJSON(array('OK' => 'OK'));
			break;

		case 'update-tour':
			//print_r($_POST); die;
			if (!checkFields($_POST, array('id_entrepot', 'num_planning', 'date_rdv', 'rdv_start', 'rdv_end', 'duration', 'id_contact')))
				errorJSON(array('message' => 'Informations incorrectes'));

			$crnstart = date('H:i', strtotime('+'.(int)$_POST['crnstart'].' hour', strtotime($_POST['date_rdv'])));
			$crnend = date('H:i', strtotime('+'.(int)$_POST['crnend'].' hour', strtotime($_POST['date_rdv'])));
			IsoCreateRDV($_POST['id_entrepot'], $_POST['num_planning'], $_POST['date_rdv'], $_POST['rdv_start'], $_POST['rdv_end'], $_POST['duration'], $_POST['id_contact'], $crnstart, $crnend, $_POST['status'], '', $_POST['typerdv']);
			successJSON(array('OK' => 'OK'));
			break;

		case 'get-rdv-planning':
			if (!checkFields($_POST, array('id_contact', 'dtfrom', 'duration')))
				errorJSON(array('message' => 'Informations incorrectes'));

			$contact = Contact::findOne(array('c.id_contact' => (int)$_POST['id_contact']));
			if (!$contact)
				errorJSON(array('message' => 'Paramètre invalide'));

			if ((int)$contact->geolat == 0 && (int)$contact->geolng == 0)
				errorJSON(array('message' => 'Latitude / Longitude du client non renseigné ! Veuillez choisir l\'adresse depuis la liste google proposée dans la zone "Adresse" '));

			$nbdaytodis = 1; //6;
			$hrstart = 7;
			$hrmax = 20;
			//$impactdis = 1.28;
			$dtstart = strtotime(Tool::dmYtoYmd($_POST['dtfrom']));
			$dtend = strtotime('+ ' . $nbdaytodis . ' day', $dtstart);
			$duration = (int)$_POST['duration'];
			$hr = isset($_POST['hr']) && (int)$_POST['hr'] > 0 ? (int)$_POST['hr'] : $hrstart;
			$crndeb = (int)$_POST['crndeb'];
			$crnend = (int)$_POST['crnend'];
			$typerdv = (int)$_POST['typerdv'];

			$lstrdvs = array();
			$lstplans = array();
			$sets = (object)Setting::getGlobalSettings();
			$entrepots = Entrepot::getAll();
			foreach ($entrepots as $entrepot) {
				//echo $entrepot['entrepot_name'] . "\r\n";
				$numplan = 1;
				$idxplan = $entrepot['id_entrepot'] . '_' . $numplan;
				$lstplans[$idxplan] = (object)array(
					'id' => $idxplan,
					'title' => $entrepot['entrepot_name'] . '<br><small class="label label-warning" style="font-size:10px">PLANNING #' . $numplan . '</small>',
					'distancemin' => 100000
				);

				$distance_ent = Tool::getDistance($entrepot['geolat'], $entrepot['geolng'], $contact->geolat, $contact->geolng);
				$nbloop = 0;
				for ($day = $dtstart; $day <= $dtend; $day += 86400) {
					$existrdv = false;
					$numplan = 1;
					do {
						$idxplan = $entrepot['id_entrepot'] . '_' . $numplan;
						if (!isset($lstplans[$idxplan])) {
							$lstplans[$idxplan] = (object)array(
								'id' => $idxplan,
								'title' => $entrepot['entrepot_name'] . '<br><small class="label label-warning" style="font-size:10px">PLANNING #' . $numplan . '</small>',
								'distancemin' => 100000
							);
						}

						$dtrdv = date('Y-m-d', $day);
						$rdvs = RDV::getBy(array('r.id_entrepot' => $entrepot['id_entrepot'], 'num_planning' => $numplan, 'date_rdv' => $dtrdv, 'type_rdv' => $typerdv));
						if ($rdvs && $rdvs->num_rows > 0) {
							$existrdv = true;
							$lastisrdv = false;

							$curlat = $entrepot['geolat'];
							$curlng = $entrepot['geolng'];

							$curhr = strtotime('+ ' . $hr . ' hour', $day);
							$hrend = strtotime('+ ' . $hrmax . ' hour', $day);
							$nxhr = strtotime('+ ' . $duration . ' hour', $curhr);

							$existdispo = false;
							while ($curhr < $hrend && $nxhr < $hrend) {
								$nbloop++;
								if ($nbloop > 1000)
									break;
								//$nxhr = $curhr + $duration;
								$nxhr = strtotime('+ ' . $duration . ' hour', $curhr);
								//if ($nxhr > $hrend)
									//break;

								//$starttm = 	strtotime('+ ' . $curhr . ' hour', $day);
								//$endtm =  strtotime('+ ' . $nxhr . ' hour', $day);

								if ($lastisrdv) {
									$distance = Tool::getDistance($curlat, $curlng, $contact->geolat, $contact->geolng);
									$totdel = $distance / 95;
									$nxhr = strtotime('+ ' . $duration . ' hour', $curhr + (3600 * $totdel));
									//die('==> '.$distance.'  -  '.$totdel.'  -  '.date('d/m/Y H:i', $nxhr));
								}

								$startstr = date('H:i', $curhr);
								$endstr = date('H:i', $nxhr);

								$existerdvpd = RDV::findExists($entrepot['id_entrepot'], $numplan, $dtrdv, $startstr, $endstr);
								if (!$existerdvpd) {
									$existdispo = true;
									$writedispo = true;
									$distance = Tool::getDistance($curlat, $curlng, $contact->geolat, $contact->geolng);

									$totdel = $distance / 95;
									$delh = floor($totdel);
									$delay = $delh . 'h' .round(($totdel - $delh) * 60);
									if ($lastisrdv) {
										$curhr = $curhr + (3600 * $totdel);
										$nxhr = strtotime('+ ' . $duration . ' hour', $curhr);
									}

									$nextrdv = RDV::getNextRDV($entrepot['id_entrepot'], $numplan, $dtrdv, $startstr);
									if ($nextrdv) {
										$distancet = Tool::getDistance($nextrdv->geolat, $nextrdv->geolng, $contact->geolat, $contact->geolng);
										$totdel = $distancet / 95;
										$fthr = $nxhr + (3600 * $totdel);
										if ($fthr > strtotime($nextrdv->date_rdv.' '.$nextrdv->rdv_start)) {
											//die($fthr.' > '.strtotime($nextrdv->date_rdv.' '.$nextrdv->rdv_start));
											$writedispo = false;
										}
									}


									if ($nxhr > $hrend)
										break;


									if ($writedispo	) {
										$classdis = round($distance) > $sets->DISTANCE_RDV_MAX ? 'danger' : 'success';
										$infsup = round($distance) > $sets->DISTANCE_RDV_MAX ? '<i class="fa fa-warning" data-toggle="tooltip" title="Attention : La distance dépasse la limite de '.$sets->DISTANCE_RDV_MAX.'km"></i>' : '';
										$link = 'tour.php?id_contact='.$contact->id_contact.'&id_entrepot='.$entrepot['id_entrepot'].'&num_planning='.$numplan.'&date_rdv='.$dtrdv.'&heure_start='.date('H:i', $curhr).'&heure_end='.date('H:i', $nxhr).'&duration='.$duration.'&crndeb='.$crndeb.'&crnend='.$crnend.'&typerdv='.$typerdv;
										$title = '<a href="'.$link.'" class="calrdvdispo" data-toggle="tooltip" title="Distance : ' . round($distance) . 'km / Délai : '.$delay.'">
													Disponible<br>
													<span class="label label-'.$classdis.'"><i class="fa fa-road"></i> ' . round($distance) . 'km '.$infsup.'</span>&nbsp;
													<span class="label label-'.$classdis.'"><i class="fa fa-clock-o"></i> ' . $delay . '</span>
												</a>';

										$lstrdvs[] = (object)array(
											'id_entrepot' => $entrepot['id_entrepot'],
											'num_planning' => $numplan,
											'title' => $title,
											'date_rdv' => $dtrdv,
											//'distance' => $distance_ent,
											'id_contact' => $contact->id_contact,
											'start' => date('Y-m-d\TH:i', $curhr),
											'end' => date('Y-m-d\TH:i', $nxhr),
											'duration' => $duration,
											'allDay' => false,
											'id' => 0,
											'resourceId' => $idxplan
											//'dispo' => true
										);
									}

									$curhr = $nxhr;
									$lastisrdv = false;
								} else {
									$distance = Tool::getDistance($existerdvpd->geolat, $existerdvpd->geolng, $contact->geolat, $contact->geolng);

									//$nxdelay = round($distance / 95, 1);
									$totdel = $existerdvpd->delay / 3600;
									$delh = floor($totdel);
									$delay = $delh . 'h' .round(($totdel - $delh) * 60);

									//$delay = round($existerdvpd->delay / 3600, 1);
									//$delaystr = $delay . 'h';

									$nxtotdel = $distance / 95;
									$nxdelh = floor($nxtotdel);
									$nxdelay = $nxdelh . 'h' .round(($nxtotdel - $nxdelh) * 60);

									$infocli = $existerdvpd->first_name.' '.$existerdvpd->last_name.'<br>'
											  .$existerdvpd->adr1.' '.$existerdvpd->post_code.' '.$existerdvpd->city
											  .((int)$existerdvpd->{'101_m2'} > 0 ? '<br>101 : '.$existerdvpd->{'101_m2'}.'m²' : '')
											  .((int)$existerdvpd->{'102_m2'} > 0 ? '<br>102 : '.$existerdvpd->{'102_m2'}.'m²' : '')
											  .((int)$existerdvpd->{'103_m2'} > 0 ? '<br>103 : '.$existerdvpd->{'103_m2'}.'m²' : '');
									$title = '<span data-toggle="tooltip" title="Client : '.$infocli.'">Rendez vous<br>
											<span class="label label-info"><i class="fa fa-road"></i> ' . round($existerdvpd->distance) . 'km</span>&nbsp;
											<span class="label label-info"><i class="fa fa-clock-o"></i> ' . $delay . '</span><br>'
											.($existdispo ? '<span class="label label-warning"><i class="fa fa-road"></i> ' . round($distance) . 'km <i class="fa fa-question-circle" data-toggle="tooltip" title="Distance du contact"></i></span>&nbsp;
											<span class="label label-warning"> ' . $nxdelay . ' <i class="fa fa-question-circle" data-toggle="tooltip" title="Délai du contact"></i></span>' : '').'</span>';

									$lstrdvs[] = (object)array(
										'id_entrepot' => $entrepot['id_entrepot'],
										'num_planning' => $numplan,
										'title' => $title,
										'date_rdv' => $dtrdv,
										//'distance' => $distance_ent,
										'id_contact' => $contact->id_contact,
										'start' => date('Y-m-d\TH:i', strtotime($existerdvpd->date_rdv . ' ' . $existerdvpd->rdv_start)),
										'end' => date('Y-m-d\TH:i', strtotime($existerdvpd->date_rdv . ' ' . $existerdvpd->rdv_end)),
										'duration' => $duration,
										'allDay' => false,
										'id' => $existerdvpd->id_rdv,
										'typerdv' => $existerdvpd->type_rdv,
										'resourceId' => $idxplan
										//'dispo' => true
									);
									$curlat = $existerdvpd->geolat;
									$curlng = $existerdvpd->geolng;
									$curhr = strtotime($existerdvpd->date_rdv . ' ' . $existerdvpd->rdv_end);
									$lastisrdv = true;
									//$curhr = $curhr + (3600 * $nxtotdel);
								}

								if ($distance < $lstplans[$idxplan]->distancemin && !$existerdvpd)
									$lstplans[$idxplan]->distancemin = $distance;
							}
						} else
							$existrdv = false;

						if ($nbloop > 1000)
							break;

						if (!$existrdv) {
							$curhr = strtotime('+ ' . $hr . ' hour', $day);
							$hrend = strtotime('+ ' . $hrmax . ' hour', $day);
							$i = 0;
							while ($curhr < $hrend) {
								$i++;
								//$nxhr = $curhr + $duration;
								$nxhr = strtotime('+ ' . $duration . ' hour', $curhr);
								if ($nxhr > $hrend)
									break;

								//echo $curhr.' '.$nxhr."\r\n";
								$totdel = $distance_ent / 95;
								$delh = floor($totdel);
								$delay = $delh . 'h' .round(($totdel - $delh) * 60);

								$classdis = round($distance_ent) > $sets->DISTANCE_RDV_MAX ? 'danger' : 'success';
								$infsup = round($distance_ent) > $sets->DISTANCE_RDV_MAX ? '<i class="fa fa-warning" data-toggle="tooltip" title="Attention : La distance dépasse la limite de '.$sets->DISTANCE_RDV_MAX.'km"></i>' : '';

								$link = 'tour.php?id_contact='.$contact->id_contact.'&id_entrepot='.$entrepot['id_entrepot'].'&num_planning='.$numplan.'&date_rdv='.$dtrdv.'&heure_start='.date('H:i', $curhr).'&heure_end='.date('H:i', $nxhr).'&duration='.$duration.'&crndeb='.$crndeb.'&crnend='.$crnend.'&typerdv='.$typerdv;
								$title = '<a href="'.$link.'" class="calrdvdispo"  data-toggle="tooltip" title="Distance : ' . round($distance_ent) . 'km / Délai : '.$delay.'">
											Disponible<br>
											<span class="label label-'.$classdis.'"><i class="fa fa-road"></i> ' . round($distance_ent) . 'km '.$infsup.'</span>&nbsp;
											<span class="label label-'.$classdis.'"><i class="fa fa-clock-o"></i> ' . $delay . '</span>
										</a>';

								$lstrdvs[] = (object)array(
									'id_entrepot' => $entrepot['id_entrepot'],
									'num_planning' => $numplan,
									'title' => $title,
									'date_rdv' => $dtrdv,
									//'distance' => $distance_ent,
									'id_contact' => $contact->id_contact,
									'start' => date('Y-m-d\TH:i', $curhr),
									'end' => date('Y-m-d\TH:i', $nxhr),
									'duration' => $duration,
									'allDay' => false,
									'id' => 0,
									'resourceId' => $idxplan
									//'dispo' => true
								);


								if ($distance_ent < $lstplans[$idxplan]->distancemin)
									$lstplans[$idxplan]->distancemin = $distance_ent;
								$curhr = $nxhr;
							}
						} else
							$numplan++;

						if ($nbloop > 1000)
							break;

					} while ($existrdv);

					if ($nbloop > 1000)
						break;
				}
			}

			if ($nbloop > 1000)
				errorJSON(array('message' => 'Probleme a la recherche de rendez vous disponible !!! #555'));

			usort($lstplans, function ($a, $b) {
				return $a->distancemin > $b->distancemin;
			});

			successJSON(array('OK' => 'OK', 'rdvs' => $lstrdvs, 'plans' => $lstplans));
			break;

		case 'calculate-rdv':
			if (!checkFields($_POST, array('id', 'dts', 'dte')))
				errorJSON(array('message' => 'Informations incorrectes'));

			$rdv = RDV::findOne(array('id_rdv' => (int)$_POST['id']));
			if (!$rdv)
				errorJSON(array('message' => 'Rendez vous inexistant'));

			$contact = Contact::findOne(array('c.id_contact' => $rdv->id_contact));
			if (!$contact)
				errorJSON(array('message' => 'Contact du RDV inexistant !! '));

			//$rdvs = RDV::getBySimple("date_rdv = '".$_POST['dt']."' AND id_rdv <> ".$rdv->id_rdv);
			$rdvs = RDV::getBySimple("date_rdv >= '".$_POST['dts']."' AND date_rdv < '".$_POST['dte']."' AND id_rdv <> ".$rdv->id_rdv);
			if (!$rdvs || $rdvs->num_rows == 0)
				errorJSON(array('message' => 'Aucun rendez vous à comparer'));

			$lst = array();
			foreach($rdvs as $rd) {
				$distance = Tool::getDistance($contact->geolat, $contact->geolng, $rd['geolat'], $rd['geolng']);

				$totdel = $distance / 95;
				$delh = floor($totdel);
				$delay = $delh . 'h' .round(($totdel - $delh) * 60);

				$lst[] = array(
					'id_rdv' => $rd['id_rdv'],
					'distance' => round($distance).'km',
					'delay' => $delay
				);
			}

			$lste = array();
			//$resources = RDV::getEntrepotPlanning("r.date_rdv = '".$_POST['dt']."' AND r.id_rdv <> ".$rdv->id_rdv);
			$resources = RDV::getEntrepotPlanning("r.date_rdv >= '".$_POST['dts']."' AND r.date_rdv < '".$_POST['dte']."' AND r.id_rdv <> ".$rdv->id_rdv);
			foreach($resources as $res) {
				$distance = Tool::getDistance($contact->geolat, $contact->geolng, $res['geolat'], $res['geolng']);

				$totdel = $distance / 95;
				$delh = floor($totdel);
				$delay = $delh . 'h' .round(($totdel - $delh) * 60);

				$lste[] = array(
					'id_entrepot' => $res['id_entrepot'],
					'num_planning' => $res['num_planning'],
					'type_rdv' => $res['type_rdv'],
					'distance' => round($distance).'km',
					'delay' => $delay
				);
			}

			successJSON(array('OK' => 'OK', 'rdvs' => $lst, 'ents' => $lste));
			break;


		case 'get-planning':
			if (!checkFields($_POST, array('start', 'end')))
				errorJSON(array('message' => 'Informations incorrectes'));

			$events = RDV::loadEvents($currentUser, $_POST['start'], $_POST['end'], (int)$_POST['id_entrepot'], (int)$_POST['id_installator'], $tot_rdv, $tot_101, $tot_102, $tot_103, $tot_cumac);
			successJSON(array('OK' => 'OK', 'events' => $events, 'tot_rdv' => $tot_rdv, 'tot_101' => number_format($tot_101, 0, '.', ','), 'tot_102' => number_format($tot_102, 0, '.', ','), 'tot_103' => number_format($tot_103, 0, '.', ','), 'tot_cumac' => number_format($tot_cumac, 1, ',', ' ')));
			break;

		case 'get-resources-planning':
			if (!checkFields($_POST, array('start', 'end')))
				errorJSON(array('message' => 'Informations incorrectes'));

			successJSON(array('OK' => 'OK', 'resources' => RDV::loadResources($currentUser, $_POST['start'], $_POST['end'], (int)$_POST['id_entrepot'], (int)$_POST['id_installator'])));
			break;

		case 'get-rdv':
			if (!checkFields($_POST, array('id_contact', 'dtfrom', 'hrs')))
				errorJSON(array('message' => 'Informations incorrectes'));

			$ct = Contact::findOne(array('c.id_contact' => (int)$_POST['id_contact']));
			if (!$ct)
				errorJSON(array('message' => 'Informations incorrectes'));

			$sets = (object)Setting::getGlobalSettings();
			$res = Template::displayListNewRDV($ct, $sets, Tool::dmYtoYmd($_POST['dtfrom']), $_POST['hrs']);
			successJSON(array('OK' => 'OK', 'html' => $res));
			break;

		case 'confirm-rdv':
			if (!checkFields($_POST, array('idrdv', 'status')))
				errorJSON(array('message' => 'Informations incorrectes'));

			if (CrmUser::isTelepro($cuser) || CrmUser::isManager($cuser))
				errorJSON(array('message' => 'Vous n\'avez pas les droits de faire cette opération'));

			$rdv = RDV::findOne(array('id_rdv' => (int)$_POST['idrdv']));
			if (!$rdv)
				errorJSON(array('message' => 'Informations incorrectes'));

			$newst = 0;
			if ((int)$_POST['status'] == 0)
				$newst = 1;
			else
				$newst = 0;
			$arrupd = array('status_rdv' => $newst);
			if ($newst == 1)
				$arrupd['date_confirm'] = date('Y-m-d H:i');

			if (RDV::update($arrupd, array('id_rdv' => (int)$_POST['idrdv']))) {
				CrmAction::create(array('id_crmuser' => $currentUser->id_crmuser, 'table_action' => 'Contacts', 'id_entity' => $rdv->id_contact, 'type_action' => 'MODIFICATION STATUT RENDEZ-VOUS', 'date_action' => date('Y-m-d H:i:s'), 'details_action' => $newst == 1 ? 'Confirmé' : 'A confirmer'));

				//Envoi SMS au client si date +48H
				$sets = (object)Setting::getGlobalSettings();
				if ($sets->SEND_SMS_CUST == '1' && $newst == 1  && strtotime($rdv->date_rdv) < (time() + 172800)) {
					//1 - get client
					$ct = Contact::findOne(array('id_contact' => $rdv->id_contact));
					if ($ct) {
						//2 - Get Phone number - tel1 or tel2 - start with 6 or 7 - format for sms (remove leading 0, space, char / adding +33 - length 12)
						$phone = preg_replace('/[^0-9]/', '', $ct->tel1);
						if (strlen($phone) != 10 || (substr($phone, 0, 2) != '06' && substr($phone, 0, 2) != '07'))
							$phone = preg_replace('/[^0-9]/', '', $ct->tel2);

						if (strlen($phone) == 10 && (substr($phone, 0, 2) == '06' || substr($phone, 0, 2) == '07')) {
							$fullnum = '+33'.substr($phone, 1);
						}
						if ($fullnum != '') {
							$txt = 'Bonjour '.$ct->first_name.' '.$ct->last_name.',<br><br>
									Nous vous confirmons votre rendez-vous d’installation du '.date('d/m/Y', strtotime($rdv->date_rdv)).' entre '.$rdv->creneau_start.' et '.$rdv->creneau_end.' avec nos équipes pour l\'isolation de votre habitation pour 1€.<br>
									VOTRE NUMÉRO DE DOSSIER EST LE : '.$ct->code_dossier.'<br>
									Merci de bien noter que ce numéro de dossier est strictement confidentiel et que vous ne devez le communiquer à quiconque sous aucun prétexte. Au contraire : exigez de chaque personne qui pourrait vous solliciter qu’il vous le communique; et même à l’équipe qui viendra isoler votre maison !<br>
									N\'hésitez pas à nous appeler au 0 805 240 650 (numéro vert) si vous avez la moindre question.<br><br>
									Merci de votre confiance.<br><br>
									L\'équipe LSF Energie';

							SMS::SendMessage($txt, $fullnum);
							RDV::update(array('sms_sent' => '1'), array('id_rdv' => (int)$_POST['idrdv']));
						}
					}
				}

				successJSON(array('OK' => 'OK'));
			}
			break;

			case 'confirm-sav':
			if (!checkFields($_POST, array('idrdv', 'status')))
				errorJSON(array('message' => 'Informations incorrectes'));

			$rdv = RDV::findOne(array('id_rdv' => (int)$_POST['idrdv']));
			if (!$rdv)
				errorJSON(array('message' => 'Informations incorrectes'));

			$newst = 0;
			if ((int)$_POST['status'] == 0)
				$newst = 1;
			else
				$newst = 0;
			$arrupd = array('sav_done' => $newst);
			if (RDV::update($arrupd, array('id_rdv' => (int)$_POST['idrdv']))) {
				CrmAction::create(array('id_crmuser' => $currentUser->id_crmuser, 'table_action' => 'Contacts', 'id_entity' => $rdv->id_contact, 'type_action' => 'MODIFICATION STATUT SAV', 'date_action' => date('Y-m-d H:i:s'), 'details_action' => $newst == 1 ? 'Effectué' : 'A effectuer'));
				successJSON(array('OK' => 'OK'));
			}
			break;

		case 'delete-rdv':
			if (!checkFields($_POST, array('idrdv')))
				errorJSON(array('message' => 'Informations incorrectes'));

			$rdv = RDV::findOne(array('id_rdv' => (int)$_POST['idrdv']));
			if (!$rdv)
				errorJSON(array('message' => 'Informations incorrectes'));

			IsoRemoveRDV($rdv);

			successJSON(array('OK' => 'OK'));
			break;

		case 'move-rdv':
			if (!checkFields($_POST, array('idrdv', 'hrstart', 'idins')))
				errorJSON(array('message' => 'Informations incorrectes'));

			$rdv = RDV::findOne(array('id_rdv' => (int)$_POST['idrdv']));
			if (!$rdv)
				errorJSON(array('message' => 'Informations incorrectes'));

			if (strtotime($_POST['hrstart']) < time())
				errorJSON(array('message' => 'Vous ne pouvez deplacer un rendez vous dans le passé !'));

			$dts = explode(' ', $_POST['hrstart']);
			if (count($dts) != 2)
				errorJSON(array('message' => 'Informations incorrectes'));

			//$sets = (object)Setting::getGlobalSettings();
			$dt = $dts[0];
			$hr = $dts[1];
			$hrend = date('H:i', strtotime('+ '.$rdv->duration.' hour', strtotime($_POST['hrstart'])));

			$entplan = explode('_', $_POST['idins']);
			$ident =  $entplan[0];
			$numplan =  $entplan[1];
			$typerdv =  $entplan[2];
			if ($typerdv != $rdv->type_rdv)
				errorJSON(array('message' => 'Le type de rendez vous d\'origine est different du type de rendez vous cible'));

			//Check if exist rdv
			/*on ne check plus si un rdv existe = autorise le chevauchement
			$existrdv = RDV::findExists($ident, $numplan, $dt, $hr, $hrend);
			if ($existrdv && $existrdv->id_rdv != (int)$_POST['idrdv'])
				errorJSON(array('message' => 'Il existe déjà un rendez vous à cette période !'));
			*/

			//Find planning (+ rdv) in this range
			/*$plan = Planning::getPlanningCond("p.id_entrepot = " . (int)$_POST['idins'] . " AND p.date_planning = '" . $dt . "' AND ((hour_start <= '" . $hr . "' AND hour_end >= '" . $hr . "') OR (hour_start <= '" . $hrend . "' AND hour_end >= '" . $hrend . "')) ");
			if ($plan && (int)$plan->id_rdv > 0 && $plan->id_rdv != $rdv->id_rdv)
				errorJSON(array('message' => 'Il existe deja un rendez vous sur la tranche choisie !'));
			else
			if (!$plan)
				errorJSON(array('message' => 'Aucun planning installateur à cette date'));
			*/

			//errorJSON(array('message' => print_r($plan, true)));
			//if ($plan->id_rdv != $rdv->id_rdv) {
				IsoRemoveRDV($rdv);
				IsoCreateRDV($ident, $numplan, $dt, $hr, $hrend, $rdv->duration, $rdv->id_contact, $rdv->creneau_start, $rdv->creneau_end, $rdv->status_rdv, $rdv->date_create, $rdv->type_rdv);
			//}
			//remove
			successJSON(array('OK' => 'OK')); //, 'events' => RDV::loadEvents($currentUser)));
			//$plans = Planning::getPlanningWithRDV(array('date_planning' => $rdv->date_planning, 'p.id_installator' => $rdv->id_installator));

			break;

		case 'load-entrepot-planning':
			if (!checkFields($_POST, array('dtstart', 'dtend')))
				errorJSON(array('message' => 'Informations incorrectes'));

			$start = Tool::dmYtoYmd($_POST['dtstart']);
			$end = Tool::dmYtoYmd($_POST['dtend']);
			$conds = "r.date_rdv >= '".$start."' AND r.date_rdv <= '".$end."' AND r.type_rdv = 0 AND ".((int)$_POST['attach'] == 1 ? 'r.id_installator = 0' : 'r.id_installator <> 0');
			$entplans = RDV::getEntrepotPlanning($conds);

			successJSON(array('entplans' => ArrayLoader::loadAssoc($entplans)));
			break;

		case 'load-installator-entrepot':
			if (!checkFields($_POST, array('entplan', 'dtstart', 'dtend')))
				errorJSON(array('message' => 'Informations incorrectes'));

			$start = Tool::dmYtoYmd($_POST['dtstart']);
			$end = Tool::dmYtoYmd($_POST['dtend']);
			$id_entrepot = explode('_', $_POST['entplan'])[0];
			$numplan = explode('_', $_POST['entplan'])[1];
			$entrepot = Entrepot::findOne(array('id_entrepot' => $id_entrepot));
			if (!$entrepot)
				errorJSON(array('message' => 'Entrepot incorrect'));

			$insts = Installator::findAvailable($start, $end, $entrepot->geolat, $entrepot->geolng, (int)$_POST['attach'] == 1, $entrepot->id_entrepot, $numplan);
			successJSON(array('insts' => ArrayLoader::loadAssoc($insts)));
			break;


		case 'attach-planning-installator':
			if (!checkFields($_POST, array('entplan', 'id_installator', 'date_start', 'date_end')))
				errorJSON(array('message' => 'Informations incorrectes'));

			$start = Tool::dmYtoYmd($_POST['date_start']);
			$end = Tool::dmYtoYmd($_POST['date_end']);
			$entplan = explode('_', $_POST['entplan']);
			$ident = $entplan[0];
			$numplan = $entplan[1];
			$modeattach = (int)$_POST['attach'] == 1;

			$inst = Installator::findOne(array('id_installator' => (int)$_POST['id_installator']));
			if (!$inst)
				errorJSON(array('message' => 'Installateur incorrecte'));
			if (($inst->type_msg == 0 || $inst->type_msg == 2) && $inst->agenda_id == '')
				errorJSON(array('message' => 'ID de l\'Agenda Google manquant'));
			if (($inst->type_msg == 1 || $inst->type_msg == 2) && $inst->tel2 == '')
				errorJSON(array('message' => 'Numéro de portable de l\'installateur manquant'));

			$entrepot = Entrepot::findOne(array('id_entrepot' => (int)$ident));
			if (!$entrepot)
				errorJSON(array('message' => 'Entrepot incorrecte'));
			if ($entrepot->email == '')
				errorJSON(array('message' => 'Email de l\'entrepot non renseigné'));

			$conds = "r.id_entrepot = ".(int)$ident." AND r.num_planning = ".(int)$numplan." AND r.type_rdv = 0 AND r.id_installator = ".($modeattach ? '0' : $inst->id_installator)." AND date_rdv BETWEEN '".$start."' AND '".$end."'";
			$rdvs = RDV::getBySimple($conds);
			if (!$rdvs || $rdvs->num_rows == 0)
				errorJSON(array('message' => 'Aucun rendez vous à '.($modeattach ? 'attribuer' : 'détacher').' sur cet entrepot et cette période'));


			$nbmissm2 = 0;
			foreach($rdvs as $rdv)
				if ((float)$rdv['101_m2'] == 0 && (float)$rdv['102_m2'] == 0 && (float)$rdv['103_m2'] == 0)
					$nbmissm2++;
				else
				if ((float)$rdv['id_material_101'] == 0 && (float)$rdv['id_material_102'] == 0 && (float)$rdv['id_material_103'] == 0)
					$nbmissm2++;

			if ($modeattach && $nbmissm2 > 0)
				errorJSON(array('message' => 'Rattachement impossible ! Il existe '.$nbmissm2.' rendez vous pour lesquels il manque le nombre de m2 et/ou le matériel utilisé !'));
			else {
				$totm2 = 0;
				$nbm2 = 0;
				$dtsave = 0;
				$arrent = array();
				$tps = array('101', '102', '103');
				foreach($rdvs as $rdv) {
					$infoinsm2 = '';
					if ($rdv['type_rdv'] == '0') {
						foreach($tps as $tp){
							if ((float)$rdv[$tp.'_m2'] > 0) {
								$mat = Setting::getMaterial(array('id_material' => $rdv['id_material_'.$tp]));
								$infoinsm2 .= $tp.' - '.(float)$rdv[$tp.'_m2'].' m² '.($mat ? '('.$mat->name_material.')' : '').'<br>';
							}
						}
					}


					if ($modeattach) {
						$contact = Contact::findOne(array('c.id_contact' => $rdv['id_contact']));
						$fieldsquests = array(
							'q_type_habit' => 'Type habitation',
							'q_type_chauf' => 'Type de chauffage',
							'q_occupation' => 'Nature occupation',
							'q_plus2an' => 'Maison + de 2 ans',
							'q_taille_trappe_cm' => 'Taille de la trappe en cm',
							'q_plancher_sol' => 'Plancher au sol',
							'q_type_plancher' => 'Type de plancher',
							'q_laine_plancher' => 'Laine sur le plancher de vos combles',
							'q_type_laine' => 'Type de laine',
							'q_poutre_visible' => 'Si non les poutres sont-elles visibles',
							'q_comble_m2' => 'Comble en M2',
							'q_type_laine_amettre' => 'Type de laine à mettre',
							'q_pas_laine_plancher' => 'Si pas laine sur plancher',
							'q_cave_soussol' => 'Cave ou un sous sol ou un vide sanitaire ?',
							'q_acces_passage' => 'l\'acces ou passage est de',
							'q_exist_polystyrene_cave' => 'Y a t-il déjà du polystyrène ?',
							'q_tuyau_plafond_cave' => 'Tuyauterie au plafond de la cave',
							'q_espace_chauffe' => 'Espace chauffé ',
							'q_chaudiere' => 'Chaudiere',
							'q_mlineair_tuyau' => 'Nombre de m linéaire de tuyau ?',
							'q_voyez_plafond_parking' => 'Quand vous regardez votre plafond de parking vous voyez',
							'q_bois_apparente' => 'Bois apparente',
							'q_polystyrene_lineair' => 'Polystyrène linéaire',
							'q_epaisseur_polystyrene_plafond' => 'Polystyrène épaisseur',
							'q_espace_voute' => 'Espace vouté',
							'q_poser_poly_10cm' => 'Poser un polystyrène de 10cm d’épaisseur',
							'q_mur_mitoyen_encombre' => 'Mur mitoyen encombre',
							'q_si_mur_encombre' => 'Si mur encombre',
							'q_taille_mur_mitoyen_m2' => 'Taille du mur mitoyen en m2'
						);
						$fiedlsyesno = array(
							'q_plus2an', 'q_plancher_sol', 'q_laine_plancher', 'q_poutre_visible', 'q_cave_soussol', 'q_exist_polystyrene_cave', 'q_espace_chauffe', 'q_chaudiere', 'q_tuyau_plafond_cave',
							'q_bois_apparente', 'q_polystyrene_lineair', 'q_espace_voute', 'q_mur_mitoyen_encombre'
						);

						$adr = $rdv['adr1'].' '.$rdv['post_code'].' '.$rdv['city'];
						$infobase = 'Adresse : '.$adr.'<br>'
									.'Tél : '.$rdv['tel1'].'<br>'
									.'Tél 2 : '.$rdv['tel2'].'<br>'
									.'Email : '.$rdv['email'].'<br>'
									.'Code secret : '.$rdv['code_dossier'].'<br>'
									.'Source : '.$rdv['source'].'<br>'
									.'Infos fiscales : '.$rdv['no_fiscal_1'].' / '.$rdv['ref_avis_1'].'<br><br>'
									.'<u>Métrages et produits :</u><br>'.$infoinsm2;

						$infobasesms = 'Adresse : '.$adr.'<br>'
									.'Tél : '.$rdv['tel1'].'<br>'
									.'Tél 2 : '.$rdv['tel2'].'<br>'
									.'Email : '.$rdv['email'].'<br>'
									.'Code secret : '.$rdv['code_dossier'].'<br>'
									.'<u>Métrages et produits :</u><br>'.$infoinsm2;

						$infoques = '<u>Infos questionnaire :</u><br><br>';

						foreach($fieldsquests as $key => $val)
							if ($contact->{$key} != '' && $contact->{$key} != '--' && $contact->{$key} != '0')
								$infoques .= $val.' : <b>'.(in_array($key, $fiedlsyesno) ? ($contact->{$key} == '1' ? 'Oui' : 'Non') : $contact->{$key}).'</b><br>';

						$infocom = '<u>Commentaires :</u><br><br>'.$rdv['comment'];

						$infocli = $rdv['first_name'].' '.$rdv['last_name'];
						$infosup = $infobase.'<br><br>'.$infoques.'<br><br>'.$infocom;
						$infosupsms = $infobasesms.'<br><br>'.$infoques.'<br><br>'.$infocom;

						try {
							$idev = 0;
							//Google Agenda new Event
							if ($inst->type_msg == 0 || $inst->type_msg == 2) {
								$idev = GoogleAgenda::createEvent($inst->agenda_id, $rdv['id_rdv'], $infocli, $infosup, strtotime($rdv['date_rdv'].' '.$rdv['rdv_start']), strtotime($rdv['date_rdv'].' '.$rdv['rdv_end']), $adr);
							}

							//Send SMS
							if ($inst->type_msg == 1 || $inst->type_msg == 2) {
								$specifsms = $inst->installator_name.'<br>'
											.'RDV AVEC '.$infocli.'<br>'
											.'A'.date('d/m/Y H:i', strtotime($rdv['date_rdv'].' '.$rdv['rdv_start']));

								$smsmsg = $specifsms.'<br>'.str_replace(array('<u>', '</u>', '<b>', '</b>'), '', $infosupsms);
								if (strlen($smsmsg) >= 600) {
									$sms = SMS::SendMessage($specifsms.'<br>'.str_replace(array('<u>', '</u>', '<b>', '</b>'), '', $infobasesms), $inst->tel2);
									$sms = SMS::SendMessage($infocli.'<br>'.str_replace(array('<u>', '</u>', '<b>', '</b>'), '', $infocom), $inst->tel2);
								}
								else
									$sms = SMS::SendMessage($smsmsg, $inst->tel2);
							}

							RDV::update(array('status_rdv' => '2', 'id_installator' => $inst->id_installator, 'id_event' => $idev), array('id_rdv' => $rdv['id_rdv']));
							Prestation::update(array('id_installator' => $inst->id_installator), array('id_contact' => $rdv['id_contact']));
						} catch (Exception $e) {
							errorJSON(array('message' => 'Erreur au rattachement installateur : '.$e->getMessage()));
						}
					}
					else {
						try {
							if ($inst->type_msg == 0 || $inst->type_msg == 2)
								GoogleAgenda::deleteEvent($inst->agenda_id, $rdv['id_event']);

							RDV::update(array('status_rdv' => '0', 'id_installator' => '0', 'id_event' => ''), array('id_rdv' => $rdv['id_rdv']));
							Prestation::update(array('id_installator' => '0'), array('id_contact' => $rdv['id_contact']));
						} catch (Exception $e) {
							errorJSON(array('message' => 'Erreur au déttachement installateur : '.$inst->agenda_id.' '.$rdv['id_event'].' '.$e->getMessage()));
						}
					}
				}


				successJSON(array('OK' => 'OK'));
			}

			//GoogleAgenda::ListEvents($inst->agenda_id);

			successJSON(array('resp' => $msgent, 'agenda_id' => $inst->agenda_id));
			break;

		case 'mail-entrepot':
			if (!checkFields($_POST, array('date_start', 'date_end')))
				errorJSON(array('message' => 'Informations incorrectes'));

			$start = Tool::dmYtoYmd($_POST['date_start']);
			$end = Tool::dmYtoYmd($_POST['date_end']);

			/*$conds = "r.id_installator = 0 AND date_rdv BETWEEN '".$start."' AND '".$end."'";
			$rdvs = RDV::getBySimple($conds);
			if ($rdvs && $rdvs->num_rows > 0)
				errorJSON(array('message' => 'Il reste des rendez vous non attribués à des installateurs sur la période selectionnée'));
			*/

			$conds = "r.id_installator > 0 AND r.type_rdv = 0 AND date_rdv BETWEEN '".$start."' AND '".$end."'";
			$rdvs = RDV::getStockCounts($conds);
			if (!$rdvs || $rdvs->num_rows == 0)
				errorJSON(array('message' => 'Aucun rendez vous attribués sur cette période'));

			$curent = '';
			$curdt = '';
			$curins = '';
			$curentname = '';
			$curemail = '';
			$mail = '';
			foreach($rdvs as $rdv) {
				if ($curent != $rdv['id_entrepot']) {
					if ($curent != '') {
						$msg = 'FICHE DE PRODUIT A RETIRER DU '.$_POST['date_start'].' AU '.$_POST['date_end'].'<br><br>'.$mail;
						$vals = (object)array(
							'subject' => 'LSF ENERGIE - '.$curentname.' - Fiche de produit à retirer du '.$_POST['date_start'].' AU '.$_POST['date_end'],
							'entrepot_name' => $curentname,
							'email' => $curemail,
							'msg' => $msg
						);
						//print_r($vals);
						MailEngine::sendMail('mail-entrepot', $vals);
						$mail = '';
					}

					$mail .= 'ENTREPOT : '.$rdv['entrepot_name'].'<br>';
				}
				if ($curdt != $rdv['date_rdv']) {
					$mail .= '<br><u>Le '.Tool::fulldatestr($rdv['date_rdv']).':</u><br>';
				}
				if ($curins != $rdv['id_installator']) {
					$mail .= '<br>INSTALLATEUR : '.$rdv['installator_name'].'<br>';
				}

				if ((int)$rdv['id101'] > 0)
					$mail .= '<br>Matériaux : <b>'.$rdv['mat101'].'</b><br>Nombre de m2 : <b>'.$rdv['tot101'].'</b><br>Nombre de '.$rdv['unit101'].' : <b>'.$rdv['nunit101'].'</b><br>';
				if ((int)$rdv['id102'] > 0)
					$mail .= '<br>Matériaux : <b>'.$rdv['mat102'].'</b><br>Nombre de m2 : <b>'.$rdv['tot102'].'</b><br>Nombre de '.$rdv['unit102'].' : <b>'.$rdv['nunit102'].'</b><br>';
				if ((int)$rdv['id103'] > 0)
					$mail .= '<br>Matériaux : <b>'.$rdv['mat103'].'</b><br>Nombre de m2 : <b>'.$rdv['tot103'].'</b><br>Nombre de '.$rdv['unit103'].' : <b>'.$rdv['nunit103'].'</b><br>';

				$curent = $rdv['id_entrepot'];
				$curdt = $rdv['date_rdv'];
				$curins = $rdv['id_installator'];
				$curentname = $rdv['entrepot_name'];
				$curemail = $rdv['email'];
			}
			if ($mail != '') {
				$msg = 'FICHE DE PRODUIT A RETIRER DU '.$_POST['date_start'].' AU '.$_POST['date_end'].'<br><br>'.$mail;
				$vals = (object)array(
					'subject' => 'LSF ENERGIE - '.$curentname.' - Fiche de produit à retirer du '.$_POST['date_start'].' AU '.$_POST['date_end'],
					'entrepot_name' => $curentname,
					'email' => $curemail,
					'msg' => $msg
				);
				//print_r($vals);
				MailEngine::sendMail('mail-entrepot', $vals);
			}


			successJSON(array('OK' => 'OK'));
			break;

		case 'change-rdv-entrepot':
			if (!checkFields($_POST, array('id_entrepot', 'id_entrepot_base', 'num_planning_base', 'dt')))
				errorJSON(array('message' => 'Informations incorrectes'));

			$rdvs = RDV::getBySimple("r.id_entrepot = ".(int)$_POST['id_entrepot_base']." AND r.num_planning = ".(int)$_POST['num_planning_base']." AND r.date_rdv = '".$_POST['dt']."'");
			if (!$rdvs || $rdvs->num_rows == 0)
				errorJSON(array('message' => 'Aucun RDV à changer d\'entrepot!'));

			$nextplan = RDV::getNextPlanning("r.id_entrepot = ".(int)$_POST['id_entrepot']." AND r.date_rdv = '".$_POST['dt']."'");

			RDV::update(
				array('id_entrepot' => (int)$_POST['id_entrepot'], 'num_planning' => $nextplan),
				array('id_entrepot' => (int)$_POST['id_entrepot_base'], 'num_planning' => (int)$_POST['num_planning_base'], 'date_rdv' => $_POST['dt'])
			);

			successJSON(array('OK' => 'OK'));
			break;

		case 'attach-planning-installator':
			if (!checkFields($_POST, array('entplan', 'id_installator', 'date_start', 'date_end')))
				errorJSON(array('message' => 'Informations incorrectes'));

			$start = Tool::dmYtoYmd($_POST['date_start']);
			$end = Tool::dmYtoYmd($_POST['date_end']);
			$entplan = explode('_', $_POST['entplan']);
			$ident = $entplan[0];
			$numplan = $entplan[1];
			$modeattach = (int)$_POST['attach'] == 1;

			$inst = Installator::findOne(array('id_installator' => (int)$_POST['id_installator']));
			if (!$inst)
				errorJSON(array('message' => 'Installateur incorrecte'));
			if (($inst->type_msg == 0 || $inst->type_msg == 2) && $inst->agenda_id == '')
				errorJSON(array('message' => 'ID de l\'Agenda Google manquant'));
			if (($inst->type_msg == 1 || $inst->type_msg == 2) && $inst->tel2 == '')
				errorJSON(array('message' => 'Numéro de portable de l\'installateur manquant'));

			$entrepot = Entrepot::findOne(array('id_entrepot' => (int)$ident));
			if (!$entrepot)
				errorJSON(array('message' => 'Entrepot incorrecte'));
			if ($entrepot->email == '')
				errorJSON(array('message' => 'Email de l\'entrepot non renseigné'));

			$conds = "r.id_entrepot = ".(int)$ident." AND r.num_planning = ".(int)$numplan." AND r.id_installator = ".($modeattach ? '0' : $inst->id_installator)." AND date_rdv BETWEEN '".$start."' AND '".$end."'";
			$rdvs = RDV::getBySimple($conds);
			if (!$rdvs || $rdvs->num_rows == 0)
				errorJSON(array('message' => 'Aucun rendez vous à '.($modeattach ? 'attribuer' : 'détacher').' sur cet entrepot et cette période'));


			$nbmissm2 = 0;
			foreach($rdvs as $rdv)
				if ((float)$rdv['101_m2'] == 0 && (float)$rdv['102_m2'] == 0 && (float)$rdv['103_m2'] == 0)
					$nbmissm2++;
				else
				if ((float)$rdv['id_material_101'] == 0 && (float)$rdv['id_material_102'] == 0 && (float)$rdv['id_material_103'] == 0)
					$nbmissm2++;

			if ($modeattach && $nbmissm2 > 0)
				errorJSON(array('message' => 'Rattachement impossible ! Il existe '.$nbmissm2.' rendez vous pour lesquels il manque le nombre de m2 et/ou le matériel utilisé !'));
			else {
				$totm2 = 0;
				$nbm2 = 0;
				$dtsave = 0;
				$arrent = array();
				$tps = array('101', '102', '103');
				foreach($rdvs as $rdv) {
					$infoinsm2 = '';
					if ($rdv['type_rdv'] == '0') {
						foreach($tps as $tp){
							if ((float)$rdv[$tp.'_m2'] > 0) {
								$mat = Setting::getMaterial(array('id_material' => $rdv['id_material_'.$tp]));
								$infoinsm2 .= $tp.' - '.(float)$rdv[$tp.'_m2'].' m² '.($mat ? '('.$mat->name_material.')' : '').'<br>';
							}
						}
					}


					if ($modeattach) {
						$contact = Contact::findOne(array('c.id_contact' => $rdv['id_contact']));
						$fieldsquests = array(
							'q_type_habit' => 'Type habitation',
							'q_type_chauf' => 'Type de chauffage',
							'q_occupation' => 'Nature occupation',
							'q_plus2an' => 'Maison + de 2 ans',
							'q_taille_trappe_cm' => 'Taille de la trappe en cm',
							'q_plancher_sol' => 'Plancher au sol',
							'q_type_plancher' => 'Type de plancher',
							'q_laine_plancher' => 'Laine sur le plancher de vos combles',
							'q_type_laine' => 'Type de laine',
							'q_poutre_visible' => 'Si non les poutres sont-elles visibles',
							'q_comble_m2' => 'Comble en M2',
							'q_type_laine_amettre' => 'Type de laine à mettre',
							'q_pas_laine_plancher' => 'Si pas laine sur plancher',
							'q_cave_soussol' => 'Cave ou un sous sol ou un vide sanitaire ?',
							'q_acces_passage' => 'l\'acces ou passage est de',
							'q_exist_polystyrene_cave' => 'Y a t-il déjà du polystyrène ?',
							'q_tuyau_plafond_cave' => 'Tuyauterie au plafond de la cave',
							'q_espace_chauffe' => 'Espace chauffé ',
							'q_chaudiere' => 'Chaudiere',
							'q_mlineair_tuyau' => 'Nombre de m linéaire de tuyau ?',
							'q_voyez_plafond_parking' => 'Quand vous regardez votre plafond de parking vous voyez',
							'q_bois_apparente' => 'Bois apparente',
							'q_polystyrene_lineair' => 'Polystyrène linéaire',
							'q_epaisseur_polystyrene_plafond' => 'Polystyrène épaisseur',
							'q_espace_voute' => 'Espace vouté',
							'q_poser_poly_10cm' => 'Poser un polystyrène de 10cm d’épaisseur',
							'q_mur_mitoyen_encombre' => 'Mur mitoyen encombre',
							'q_si_mur_encombre' => 'Si mur encombre',
							'q_taille_mur_mitoyen_m2' => 'Taille du mur mitoyen en m2'
						);
						$fiedlsyesno = array(
							'q_plus2an', 'q_plancher_sol', 'q_laine_plancher', 'q_poutre_visible', 'q_cave_soussol', 'q_exist_polystyrene_cave', 'q_espace_chauffe', 'q_chaudiere', 'q_tuyau_plafond_cave',
							'q_bois_apparente', 'q_polystyrene_lineair', 'q_espace_voute', 'q_mur_mitoyen_encombre'
						);

						$adr = $rdv['adr1'].' '.$rdv['post_code'].' '.$rdv['city'];
						$infobase = 'Adresse : '.$adr.'<br>'
									.'Tél : '.$rdv['tel1'].'<br>'
									.'Tél2 : '.$rdv['tel2'].'<br>'
									.'Email : '.$rdv['email'].'<br>'
									.'Code secret : '.$rdv['code_dossier'].'<br>'
									.'Source : '.$rdv['source'].'<br>'
									.'Infos fiscales : '.$rdv['no_fiscal_1'].' / '.$rdv['ref_avis_1'].'<br><br>'
									.'<u>Métrages et produits :</u><br>'.$infoinsm2;

						$infobasesms = 'Adresse : '.$adr.'<br>'
									.'Tél : '.$rdv['tel1'].'<br>'
									.'Tél2 : '.$rdv['tel2'].'<br>'
									.'Email : '.$rdv['email'].'<br>'
									.'Code secret : '.$rdv['code_dossier'].'<br>'
									.'<u>Métrages et produits :</u><br>'.$infoinsm2;

						$infoques = '<u>Infos questionnaire :</u><br><br>';

						foreach($fieldsquests as $key => $val)
							if ($contact->{$key} != '' && $contact->{$key} != '--' && $contact->{$key} != '0')
								$infoques .= $val.' : <b>'.(in_array($key, $fiedlsyesno) ? ($contact->{$key} == '1' ? 'Oui' : 'Non') : $contact->{$key}).'</b><br>';

						$infocom = '<u>Commentaires :</u><br><br>'.$rdv['comment'];

						$infocli = $rdv['first_name'].' '.$rdv['last_name'];
						$infosup = $infobase.'<br><br>'.$infoques.'<br><br>'.$infocom;
						$infosupsms = $infobasesms.'<br><br>'.$infoques.'<br><br>'.$infocom;

						try {
							$idev = 0;
							//Google Agenda new Event
							if (/*$rdv['type_rdv'] == '0' &&*/ ($inst->type_msg == 0 || $inst->type_msg == 2)) {
								$idev = GoogleAgenda::createEvent($inst->agenda_id, $rdv['id_rdv'], $infocli, $infosup, strtotime($rdv['date_rdv'].' '.$rdv['rdv_start']), strtotime($rdv['date_rdv'].' '.$rdv['rdv_end']), $adr);
							}

							//Send SMS
							if (/*$rdv['type_rdv'] == '0' &&*/ ($inst->type_msg == 1 || $inst->type_msg == 2)) {
								$specifsms = $inst->installator_name.'<br>'
											.'RDV AVEC '.$infocli.'<br>'
											.'A'.date('d/m/Y H:i', strtotime($rdv['date_rdv'].' '.$rdv['rdv_start']));

								$smsmsg = $specifsms.'<br>'.str_replace(array('<u>', '</u>', '<b>', '</b>'), '', $infosupsms);
								if (strlen($smsmsg) >= 600) {
									$sms = SMS::SendMessage($specifsms.'<br>'.str_replace(array('<u>', '</u>', '<b>', '</b>'), '', $infobasesms), $inst->tel2);
									$sms = SMS::SendMessage($infocli.'<br>'.str_replace(array('<u>', '</u>', '<b>', '</b>'), '', $infocom), $inst->tel2);
								}
								else
									$sms = SMS::SendMessage($smsmsg, $inst->tel2);
							}

							RDV::update(array('status_rdv' => '2', 'id_installator' => $inst->id_installator, 'id_event' => $idev), array('id_rdv' => $rdv['id_rdv']));
							Prestation::update(array('id_installator' => $inst->id_installator), array('id_contact' => $rdv['id_contact']));
						} catch (Exception $e) {
							errorJSON(array('message' => 'Erreur au rattachement installateur : '.$e->getMessage()));
						}
					}
					else {
						try {
							if (/*$rdv['type_rdv'] == '0' &&*/ ($inst->type_msg == 0 || $inst->type_msg == 2))
								GoogleAgenda::deleteEvent($inst->agenda_id, $rdv['id_event']);

							RDV::update(array('status_rdv' => '0', 'id_installator' => '0', 'id_event' => ''), array('id_rdv' => $rdv['id_rdv']));
							Prestation::update(array('id_installator' => '0'), array('id_contact' => $rdv['id_contact']));
						} catch (Exception $e) {
							errorJSON(array('message' => 'Erreur au déttachement installateur : '.$inst->agenda_id.' '.$rdv['id_event'].' '.$e->getMessage()));
						}
					}
				}


				successJSON(array('OK' => 'OK'));
			}

			//GoogleAgenda::ListEvents($inst->agenda_id);

			successJSON(array('resp' => $msgent, 'agenda_id' => $inst->agenda_id));
			break;

		case 'get-direction':
			if (!checkFields($_POST, array('deplat', 'deplng', 'deslat', 'deslng', 'tm')))
				errorJSON(array('message' => 'Informations incorrectes'));

			$url = 'https://maps.googleapis.com/maps/api/directions/json?origin=' . $_POST['deplat'] . ',' . $_POST['deplng'] . '&destination=' . $_POST['deslat'] . ',' . $_POST['deslng'] . '&departure_time=' . $_POST['tm'] . '&key=AIzaSyArKK0Hvu_FtKgyvHkUUjyKOMK2Hmt9zY0';
			$info = Tool::getCurl($url);
			successJSON(array('gmap' => $info));
			break;



		case 'get-map-rdvs':
			if (!checkFields($_POST, array('dt', 'id_entrepot')))
				errorJSON(array('message' => 'Informations incorrectes'));

			$steps = array();
			$conds = "r.date_rdv = '" . Tool::dmYtoYmd($_POST['dt']) . "'";
			$conds .= (int)$_POST['id_entrepot'] > 0 ? " AND r.id_entrepot = " . (int)$_POST['id_entrepot'] : '';
			$rdvs = RDV::getWithSteps($conds, $steps);
			successJSON(array('rdvs' => ArrayLoader::loadAssoc($rdvs), 'steps' => $steps));
			break;

		case 'get-dash-park':
			$conds = isset($_POST['idst']) && $_POST['idst'] != '' ? array('id_statuscont' => (int)$_POST['idst']) : '';
			$contacts = Contact::getAllForMap($conds);
			$installators = Installator::getAll();
			$entrepots = Entrepot::getAll();
			successJSON(array('contacts' => ArrayLoader::loadAssoc($contacts), 'installators' => ArrayLoader::loadAssoc($installators), 'entrepots' => ArrayLoader::loadAssoc($entrepots)));
			break;

		case 'update-settings':
			if (!CrmUser::isAdmin($currentUser))
				errorJSON(array('message' => 'Droits insuffisants'));

			$flds = array(
				'DISTANCE_RDV', 'DISTANCE_RDV_MAX', 'DISTANCE_PROX', 'TX_TVA', 'STATUS_MISS_CONFIRM', 'GOOGLE_AGENDA_SENDER'
			);
			if (!checkFields($_POST, $flds))
				errorJSON(array('message' => 'Informations incorrectes'));

			$flds[] = 'CLICKSEND_KEY';
			$flds[] = 'CLICKSEND_UNAME';
			$flds[] = 'SEND_SMS_CUST';

			foreach ($flds as $fld)
				if (!Setting::updateGlobalSettings($fld, $_POST[$fld]))
					errorJSON(array('message' => 'Error on update settings (' . $fld . ' => ' . $_POST[$fld] . ')'));

			CrmAction::create(array('id_crmuser' => $currentUser->id_crmuser, 'table_action' => 'Settings', 'id_entity' => '0', 'type_action' => 'MODIFICATION PARAMETRAGE GENERAL', 'date_action' => date('Y-m-d H:i:s'), 'details_action' => Tool::arrayToStr($_POST)));
			successJSON(array('OK' => 'OK'));
			break;
	}
}


function getInfoImpot($nofis, $refavis, &$body, &$elms)
{
	$url = 'https://cfsmsp.impots.gouv.fr/secavis/';
	$info = Tool::getCurl($url);
	$DOM = new DOMDocument;
	$DOM->loadHTML($info);
	$vstate = $DOM->getElementById('j_id__v_0:javax.faces.ViewState:1')->getAttribute('value');
	if (empty($vstate) || $vstate == null)
		errorJSON(array('message' => 'Erreur avec le site des impots ...'));

	$pst = array(
		'j_id_7:spi' => $nofis,
		'j_id_7:num_facture' => $refavis,
		'j_id_7:j_id_l' => "Valider",
		'j_id_7_SUBMIT' => "1",
		'javax.faces.ViewState' => $vstate
	);
	$url = "https://cfsmsp.impots.gouv.fr/secavis/faces/commun/index.jsf";
	$info = Tool::getCurl($url, $pst);
	$DOM = new DOMDocument;
	$DOM->loadHTML($info);
	$elms = $DOM->getElementsByTagName('td');
	//$body = $DOM->getElementsByTagName('body');
	$body = $DOM->savehtml($DOM->getElementById('principal')); //$body->item(0));

	return;
}




function IsoCreateRDV($id_entrepot, $num_planning, $date_rdv, $rdv_start, $rdv_end, $duration, $id_contact, $crnstart, $crnend, $statusrdv, $dtcreaterdv = '', $typerdv = '0')
{
	global $currentUser;

	$entrepot = Entrepot::findOne(array('id_entrepot' => $id_entrepot));
	if (!$entrepot)
		errorJSON(array('message' => 'Entepot invalide !'));

	$contact = Contact::findOne(array('c.id_contact' => $id_contact));
	if (!$contact)
		errorJSON(array('message' => 'Contact invalide !'));


	//1 - check if exist rdv
	/* on ne check plus si il existe rdv sur periode = autorise le chevauchement
	$existrdv = RDV::findExists($entrepot->id_entrepot, $num_planning, $date_rdv, $rdv_start, $rdv_end);
	if ($existrdv)
		errorJSON(array('message' => 'Il existe déjà un rendez vous à cette période !'));
	*/

	$starttime = strtotime($date_rdv . ' ' . $rdv_start);
	//2 - get prior rdv
	$priorrdv = RDV::getPriorRDV($entrepot->id_entrepot, $num_planning, $date_rdv, $rdv_start, $typerdv);
	if (!$priorrdv) {
		$deplat = $entrepot->geolat;
		$deplng = $entrepot->geolng;
		$deptime = $starttime;
	}
	else {
		$deplat = $priorrdv->geolat;
		$deplng = $priorrdv->geolng;
		$deptime = strtotime($date_rdv . ' ' . $priorrdv->rdv_end);
	}


	$dis = 0;
	$strinfo = Tool::getDirection($deplat, $deplng, $contact->geolat, $contact->geolng, $deptime);
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
	}

	if ($priorrdv) {
		/* VERSION DECALAGE DES RDV
		$deptime = strtotime('+ '.$delval.' second', $deptime);
		if ($deptime < $starttime)
			$deptime = $starttime; */
		$deptime = $starttime;
	}
	$endtime = strtotime('+ '.$duration.' hour', $deptime);

	//if ($dis == 0)
	//	$dis = Tool::getDistance($entrepot->geolat, $entrepot->geolng, $contact->geolat, $contact->geolng);
	RDV::create(array(
		'id_entrepot' => $entrepot->id_entrepot,
		'num_planning' => $num_planning,
		'id_contact' => $contact->id_contact,
		'date_create' => $dtcreaterdv != '' ? $dtcreaterdv : date('Y-m-d H:i:s'),
		'date_rdv' => $date_rdv,
		'rdv_start' => date('Y-m-d H:i', $deptime),
		'rdv_end' => date('Y-m-d H:i', $endtime),
		'creneau_start' => $crnstart,
		'creneau_end' => $crnend,
		'status_rdv' => (int)$statusrdv,
		'duration' => $duration,
		'distance' => $dis,
		'delay' => $delval,
		'gmap' => $strinfo,
		'type_rdv' => (int)$typerdv
	));

	CrmAction::create(array('id_crmuser' => $currentUser->id_crmuser, 'table_action' => 'Contacts', 'id_entity' => $contact->id_contact, 'type_action' => 'CREATION RENDEZ-VOUS', 'date_action' => date('Y-m-d H:i:s')));

	$arrupdct = array('date_rdv_pros' => $date_rdv, 'heure_rdv_pros' => date('Y-m-d H:i', $deptime), 'creneau_start' => $crnstart, 'creneau_end' => $crnend);
	if ($st = Setting::getStatusConf(array('after_rdv' => '1'))) {
		$arrupdct['id_statuscontconf'] = $st->id_statuscontconf;
	}
	Contact::update($arrupdct, array('id_contact' => $contact->id_contact));

	$nextrdv = RDV::getNextsRDV($entrepot->id_entrepot, $num_planning, $date_rdv, date('H:i', $deptime), $typerdv);
	if ($nextrdv && $nextrdv->num_rows > 0) {

		$curlat = $contact->geolat;
		$curlng = $contact->geolng;
		$deptime = $endtime;

		$i=0;
		$decal = 0;
		foreach($nextrdv as $rdv) {
			$i++;
			$rdvst = strtotime($rdv['date_rdv'].' '.$rdv['rdv_start']);
			$rdven = strtotime($rdv['date_rdv'].' '.$rdv['rdv_end']);

			$strinfo = $i == 1 ? Tool::getDirection($curlat, $curlng, $rdv['geolat'], $rdv['geolng'], $deptime) : $rdv['gmap'];
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

				if ($i == 1) {
					$decal = $delval - ($rdvst - $deptime);
					//echo $decal.' = '.$delval.' -  ('.$rdvst.' - '.$deptime.')';
					if ($decal < 0)
						$decal = 0;
				}
			}

			/* VERSION DECALAGE DES RDV
			$arrupd = array(
				'rdv_start' => date('Y-m-d H:i', strtotime('+ '.$decal.' second', $rdvst)),
				'rdv_end' => date('Y-m-d H:i', strtotime('+ '.$decal.' second', $rdven))
			);
			if ($i == 1) {
				$arrupd['distance'] = $dis;
				$arrupd['delay'] = $delval;
				$arrupd['gmap'] = $strinfo;
			}

			RDV::update($arrupd, array('id_rdv' => $rdv['id_rdv'])); */

			if ($i == 1) {
				$arrupd = array(
					'distance' => $dis,
					'delay' => $delval,
					'gmap' => $strinfo,
				);

				RDV::update($arrupd, array('id_rdv' => $rdv['id_rdv']));
				break;
			}

			$curlat = $rdv['curlat'];
			$curlng = $rdv['curlng'];
		}
	}
}
/*
function IsoCreateRDV($id_planning, $id_contact)
{
	global $currentUser;
	$plandispo = Planning::getInfoPlanning((int)$id_planning);
	if (!$plandispo)
		errorJSON(array('message' => 'Planning inexistant !'));

	if ((int)$plandispo->id_rdv > 0)
		errorJSON(array('message' => 'Planning non disponible !'));

	$contact = Contact::findOne(array('id_contact' => $id_contact));
	if (!$contact)
		errorJSON(array('message' => 'Contact invalide !'));


	$plans = Planning::getPlanningWithRDV(array('date_planning' => $plandispo->date_planning, 'p.id_entrepot' => $plandispo->id_entrepot));
	$sets = (object)Setting::getGlobalSettings();
	$nxcalc = false;
	$okrdv = false;
	$endtime = 0;
	$i = 0;
	foreach ($plans as $plan) {
		$i++;

		$deptime = $endtime > 0 ? $endtime : strtotime($plan['date_planning'] . ' ' . $plan['hour_start']);

		if ((int)$plan['id_rdv'] > 0 || $plandispo->id_planning == $plan['id_planning']) {

			if ($plandispo->id_planning == $plan['id_planning'] && (int)$plan['id_rdv'] == 0) {
				$deslat = $contact->geolat;
				$deslng = $contact->geolng;
				$idc = $contact->id_contact;
			} else {
				$deslat = $plan['cgeolat'];
				$deslng = $plan['cgeolng'];
				$idc = $plan['id_contact'];
			}

			if ($plan['gmap'] == '' || $nxcalc)
				$strinfo = Tool::getDirection($nxcalc ? $deplat : $plan['geolat'], $nxcalc ? $deplng : $plan['geolng'], $deslat, $deslng, $deptime);
			else
				$strinfo = $plan['gmap'];

			if ($strinfo && !empty($strinfo)) {
				$info = json_decode($strinfo);
				$dis = $info->routes[0]->legs[0]->distance->text;
				if (isset($info->routes[0]->legs[0]->duration_in_traffic)) {
					$delval = $info->routes[0]->legs[0]->duration_in_traffic->value > $info->routes[0]->legs[0]->duration->value ? $info->routes[0]->legs[0]->duration_in_traffic->value : $info->routes[0]->legs[0]->duration->value;
				} else {
					$delval = $info->routes[0]->legs[0]->duration->value;
				}

				if ($i > 1 && $okrdv) {
					$deptime = strtotime('+ ' . $delval . ' second', $deptime);
				}
			}
			$endtime = strtotime(Tool::addTimeStr($sets->DURATION_RDV), $deptime);

			if ($nxcalc) {
				Planning::update(array('geolat' => $deplat, 'geolng' => $deplng), array('id_planning' => $plan['id_planning']));
				if ((int)$plan['id_rdv'] > 0) {
					RDV::update(array(
						'rdv_start' => date('Y-m-d H:i:s', $deptime),
						'rdv_end' => date('Y-m-d H:i:s', $endtime),
						'gmap' => $strinfo
					), array('id_rdv' => (int)$plan['id_rdv']));
				}
			}

			if ($plandispo->id_planning == $plan['id_planning'] && (int)$plan['id_rdv'] == 0) {
				$nxcalc = true;
				$okrdv = true;
				RDV::create(array(
					'id_planning' => (int)$plandispo->id_planning,
					'id_contact' => $contact->id_contact,
					'date_rdv' => $plandispo->date_planning,
					'rdv_start' => date('Y-m-d H:i:s', $deptime),
					'rdv_end' => date('Y-m-d H:i:s', $endtime),
					'gmap' => $strinfo
				));
			} else
			if ((int)$plan['id_rdv'] > 0)
				$okrdv = true;

			if ($nxcalc) {
				$deplat = $deslat;
				$deplng = $deslng;
			}
		} else {
			$endtime = strtotime(Tool::addTimeStr($sets->DURATION_RDV), $deptime);

			if ($nxcalc)
				Planning::update(array('geolat' => $deplat, 'geolng' => $deplng), array('id_planning' => $plan['id_planning']));

			$deptime = $endtime;
		}
	}
	CrmAction::create(array('id_crmuser' => $currentUser->id_crmuser, 'table_action' => 'Contacts', 'id_entity' => (int)$id_contact, 'type_action' => 'CREATION RENDEZ-VOUS', 'date_action' => date('Y-m-d H:i:s')));
}
*/

function IsoRemoveRDV($rdv)
{
	global $currentUser;

	//1 - update distance and delay from prior to all nexts
	$nextrdv = RDV::getNextRDV($rdv->id_entrepot, $rdv->num_planning, $rdv->date_rdv, $rdv->rdv_start, $rdv->type_rdv);
	if ($nextrdv) {
		$priorrdv = RDV::getPriorRDV($rdv->id_entrepot, $rdv->num_planning, $rdv->date_rdv, $rdv->rdv_start, $rdv->type_rdv);
		if ($priorrdv) {
			$deplat = $priorrdv->geolat;
			$deplng = $priorrdv->geolng;
		}
		else {
			$entrepot = Entrepot::findOne(array('id_entrepot' => $rdv->id_entrepot));
			$deplat = $entrepot->geolat;
			$deplng = $entrepot->geolng;
		}

		$strinfo = Tool::getDirection($deplat, $deplng, $nextrdv->geolat, $nextrdv->geolng, strtotime($nextrdv->date_rdv.' '.$nextrdv->rdv_start));
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
		}

		RDV::update(array('distance' => $dis, 'delay' => $delval, 'gmap' => $strinfo), array('id_rdv' => $nextrdv->id_rdv));
	}



	if (RDV::delete(array('id_rdv' => $rdv->id_rdv)))
		CrmAction::create(array('id_crmuser' => $currentUser->id_crmuser, 'table_action' => 'Contacts', 'id_entity' => $rdv->id_contact, 'type_action' => 'SUPPRESSION RENDEZ-VOUS', 'date_action' => date('Y-m-d H:i:s')));
}

/*
function IsoRemoveRDV($rdv)
{
	global $currentUser;
	$plans = Planning::getPlanningWithRDV(array('date_planning' => $rdv->date_planning, 'p.id_entrepot' => $rdv->id_entrepot));
	$sets = (object)Setting::getGlobalSettings();

	$nxcalc = false;
	$endtime = 0;
	$i = 0;
	$okrdv = false;
	$deplat = 0;
	$deplng = 0;
	foreach ($plans as $plan) {
		$i++;
		$deptime = $endtime > 0 ? $endtime : strtotime($plan['date_planning'] . ' ' . $plan['hour_start']);

		if ($nxcalc && $deplat > 0 && $deplng > 0) {
			Planning::update(array('geolat' => $deplat, 'geolng' => $deplng), array('id_planning' => (int)$plan['id_planning']));
			if ((int)$plan['id_rdv'] > 0) {
				$strinfo = Tool::getDirection($deplat, $deplng, $plan['cgeolat'], $plan['cgeolng'], $deptime);

				if ($strinfo && !empty($strinfo)) {
					$info = json_decode($strinfo);
					$dis = $info->routes[0]->legs[0]->distance->text;
					if (isset($info->routes[0]->legs[0]->duration_in_traffic)) {
						$delval = $info->routes[0]->legs[0]->duration_in_traffic->value > $info->routes[0]->legs[0]->duration->value ? $info->routes[0]->legs[0]->duration_in_traffic->value : $info->routes[0]->legs[0]->duration->value;
					} else {
						$delval = $info->routes[0]->legs[0]->duration->value;
					}

					if ($i > 1 && $okrdv) {
						$deptime = strtotime('+ ' . $delval . ' second', $deptime);
					}
				}
				$endtime = strtotime(Tool::addTimeStr($sets->DURATION_RDV), $deptime);

				RDV::update(array(
					'rdv_start' => date('Y-m-d H:i:s', $deptime),
					'rdv_end' => date('Y-m-d H:i:s', $endtime),
					'gmap' => $strinfo
				), array('id_rdv' => (int)$plan['id_rdv']));

				$deplat = $plan['cgeolat'];
				$deplng = $plan['cgeolng'];
				$nxcalc = false;
				$okrdv = true;
			}
		}

		if ($plan['id_planning'] == $rdv->id_planning) {
			if (RDV::delete(array('id_rdv' => $rdv->id_rdv)))
				CrmAction::create(array('id_crmuser' => $currentUser->id_crmuser, 'table_action' => 'Contacts', 'id_entity' => $rdv->id_contact, 'type_action' => 'SUPPRESSION RENDEZ-VOUS', 'date_action' => date('Y-m-d H:i:s')));

			$deplat = $plan['geolat'];
			$deplng = $plan['geolng'];

			$nxcalc = true;
		}
	}
}*/
