<?php
include __DIR__ . '/../lib/uitools.php';


class GridManager
{

	public static function getval_fromgrid($param, $fld, $val)
	{
		$str = '';
		foreach ($param['grid']->options["colModel"] as $colmod)
			if ($colmod['name'] == $fld) {
				$lst = $colmod["searchoptions"]["value"];
				if ($lst != '') {
					$lst = explode(';', $lst);
					foreach ($lst as $l) {
						$v = explode(':', $l);
						if ($v[0] == $val) {
							$str = $v[1];
							break;
						}
					}
				}
			}
		return $str;
	}

	public static function getGrid($tbname, $infosup = '', $infosup2 = '')
	{
		global $currentUser;
		$arrMt = array('formatter' => 'currency', 'formatoptions' => array("thousandsSeparator" => " ", "decimalSeparator" => ",", "decimalPlaces" => 2));
		$arrPcts = array('formatter' => 'currency', 'formatoptions' => array("thousandsSeparator" => " ", "decimalSeparator" => ",", "decimalPlaces" => 2, "suffix" => '%'));

		switch ($tbname) {

			case 'iso_contacts':
				$sql = "SELECT c.*, u.id_team, sc.status_color, CONCAT(r.date_rdv, ' ', r.rdv_start) as date_rdv, r.date_create as date_create_rdv, r.status_rdv, 
								CONCAT(r2.date_rdv, ' ', r2.rdv_start) as date_sav, p.101_m2, p.102_m2, p.103_m2,
								COUNT(cm.id_comment) as nb_com, GROUP_CONCAT(CONCAT('<strong>', DATE_FORMAT(cm.date_comment, '%d/%m/%y %H:%I'), '</strong> - <span>', cm.text_comment, '</span>') ORDER BY cm.id_comment DESC SEPARATOR '<hr>') as comments
						FROM iso_contacts c 
							LEFT JOIN iso_statuscontconf sc ON sc.id_statuscontconf = c.id_statuscontconf
							LEFT JOIN iso_crmusers u ON u.id_crmuser = c.id_crmuser
							LEFT JOIN iso_rdv r ON r.id_contact = c.id_contact AND r.type_rdv = 0
							LEFT JOIN iso_rdv r2 ON r2.id_contact = c.id_contact AND r2.type_rdv = 1
							LEFT JOIN iso_prestations p ON p.id_contact = c.id_contact
							LEFT JOIN iso_comments cm ON cm.id_contact = c.id_contact";

				if ($infosup != '' && is_object($infosup)) {
					if (CrmUser::isTelepro($infosup))
						$sql .= " WHERE c.id_crmuser = " . $infosup->id_crmuser;
					else
					if (CrmUser::isConfirm($infosup)) {
						$depfilter = $infosup->depts != '' ? 'and c.dept IN ('.$infosup->depts.')' : '';
						$sql .= " WHERE u.id_team = ".$infosup->id_team." ".$depfilter;

						/* prise en compte adr, info fisc, stattu obligatoire 
						$sql .= " WHERE (c.id_crmuser = " . $infosup->id_crmuser . " 
							OR c.id_crmuser_conf = " . $infosup->id_crmuser . "
							OR (c.adr1 <> '' and c.post_code <> '' and c.city <> '' and q_no_fiscal_1 <> '' and q_ref_avis_1 <> '' ".$depfilter." 
								and c.id_statuscont IN (SELECT s.id_statuscont FROM iso_statuscont s WHERE s.visible_conf = 1)))";*/
					}
					else
					if (CrmUser::isConfPlus($infosup)) {
						$sql .= $infosup->depts != '' ? ' WHERE c.dept IN ('.$infosup->depts.')' : '';
					}
					else
					if (CrmUser::isManager($infosup)) 
						$sql .= " WHERE (u.id_team = ".$infosup->id_team.($infosup->teams != '' ? " OR u.id_team IN (".$infosup->teams.")" : '').")";
				}
				$sql .= " GROUP BY c.id_contact";
				

				$grid = UIGrid::getGrid('', $tbname, $sql);
				$grid->set_actions(array('export_pdf' => false, 'export_csv' => CrmUser::isAdmin($infosup) || CrmUser::isConfirmateur($infosup), 'export_excel' => CrmUser::isAdmin($infosup) || CrmUser::isConfirmateur($infosup), 'search' => true));
				$opt["multiselect"] = CrmUser::isAdmin($infosup) || CrmUser::isConfirmateur($infosup) || CrmUser::isManager($infosup);
				//$opt["rowNum"] =  20;
				$opt["rowList"] = array(20, 50, 100, 200);
				$opt["height"] = "100%";
				$opt["sortable"] = true;
				$opt["loadComplete"] = "function(ids) { gridcts_onload(ids); }";
				$opt["export"] = array(
					"range" => "filtered",
					"orientation" => "landscape"
				);


				$cols = array();
				UIGrid::add_cols($cols, 'id_contact', 'ID.', array('width' => 70, 'dbname' => 'c.id_contact'));
				UIGrid::add_cols($cols, 'first_name', 'Prénom', array('width' => 120));
				UIGrid::add_cols($cols, 'last_name', 'Nom', array('width' => 120));
				//UIGrid::add_cols($cols, 'email', 'Email');
				UIGrid::add_cols($cols, 'tel1', 'Tel');
				UIGrid::add_cols($cols, 'post_code', 'Code Postal');

				if (CrmUser::isAdmin($infosup) || CrmUser::isConfirmateur($infosup)) {
					$lsttms = $grid->get_dropdown_values("select DISTINCT id_team as k, name_team as v from iso_teams");

					UIGrid::add_cols($cols, 'id_team', 'Team', array(
						'dbname' => 'u.id_team',
						'formatter' => 'select',
						'edittype' => "select",
						'editoptions' => array("value" => ":;" . $lsttms),
						'stype' => "select", //-multiple",
						'searchoptions' => array("value" => $lsttms)
					));
				}

				UIGrid::add_cols($cols, 'date_rdv_pros', 'Date RDV <br>Provisoire', array(
					'dbname' => 'DATE(date_rdv_pros)',
					'formatter' => 'date',
					"formatoptions" => array("srcformat" => 'Y-m-d', "newformat" => 'd/m/Y'),
					'condition' => array('$row["date_rdv_pros"] > 0', "{date_rdv_pros}", ''),
					'hidden' => true //CrmUser::isTelepro($infosup)
				));

				UIGrid::add_cols($cols, 'status_color', 'Couleur', array('hidden' => true));
				$lststatus = $grid->get_dropdown_values("select DISTINCT id_statuscontconf as k, name_statuscontconf as v from iso_statuscontconf");
				UIGrid::add_cols($cols, 'id_statuscontconf', 'Statut <br>confirmateur', array(
					'dbname' => 'c.id_statuscontconf',
					'formatter' => 'select',
					'edittype' => "select",
					'editoptions' => array("value" => ":;" . $lststatus),
					'stype' => "select", //-multiple",
					'searchoptions' => array("value" => $lststatus)
				));

				$lststatus = $grid->get_dropdown_values("select DISTINCT id_statuscont as k, name_statuscont as v from iso_statuscont");
				UIGrid::add_cols($cols, 'id_statuscont', 'Statut <br>téléopérateur', array(
					'formatter' => 'select',
					'edittype' => "select",
					'editoptions' => array("value" => ":;" . $lststatus),
					'stype' => "select", //-multiple",
					'searchoptions' => array("value" => $lststatus)
				));

				
				UIGrid::add_cols($cols, '101_m2', '101 m²', array('width' => 70, 'hidden' => true));
				UIGrid::add_cols($cols, '102_m2', '102 m²', array('width' => 70, 'hidden' => true));
				UIGrid::add_cols($cols, '103_m2', '103 m²', array('width' => 70, 'hidden' => true));

				UIGrid::add_cols($cols, 'adr1', 'Adresse', array('hidden' => true));
				UIGrid::add_cols($cols, 'city', 'Ville', array('hidden' => true));
				UIGrid::add_cols($cols, 'dept', 'Département', array('hidden' => true, 'dbname' => 'c.dept'));
				if (CrmUser::isAdmin($currentUser)) {
					UIGrid::add_cols($cols, 'source', 'Source', array('hidden' => true));				
					UIGrid::add_cols($cols, 'campain', 'Campagne', array('hidden' => true));
				}


				$sqlsup = '';
				if (CrmUser::isTelepro($currentUser))
					$sqlsup = " WHERE id_team = ".$currentUser->id_team;
				else
				if (CrmUser::isManager($currentUser))
					$sqlsup = " WHERE (id_team = ".$currentUser->id_team.($infosup->teams != '' ? " OR id_team IN (".$infosup->teams.")" : '')." )";
				$lstusers = $grid->get_dropdown_values("select DISTINCT id_crmuser as k, user_name as v from iso_crmusers ".$sqlsup);

				UIGrid::add_cols($cols, 'id_crmuser', 'Téléopérateur', array(
					'dbname' => 'c.id_crmuser',
					'formatter' => 'select',
					'edittype' => "select",
					'editoptions' => array("value" => ":;" . $lstusers),
					'stype' => "select", //-multiple",
					'searchoptions' => array("value" => $lstusers)
				));

				
				$sqlsup = '';
				if (CrmUser::isManager($currentUser) || CrmUser::isTelepro($currentUser))
					$sqlsup = " AND id_team = ".$currentUser->id_team;
				$lstusers = $grid->get_dropdown_values("select DISTINCT id_crmuser as k, user_name as v from iso_crmusers WHERE id_profil = 4 || id_profil = 5 ".$sqlsup);

				UIGrid::add_cols($cols, 'id_crmuser_conf', 'Confirmateur', array(
					'formatter' => 'select',
					'edittype' => "select",
					'editoptions' => array("value" => ":;" . $lstusers),
					'stype' => "select", //-multiple",
					'searchoptions' => array("value" => $lstusers)
				));


				UIGrid::add_cols($cols, 'date_create_rdv', 'Date <br>création RDV', array(
					'dbname' => 'DATE(r.date_create)',
					'formatter' => 'date',
					"formatoptions" => array("srcformat" => 'Y-m-d H:i:s', "newformat" => 'd/m/Y H:i'),
					'condition' => array('$row["date_create_rdv"] > 0', "{date_create_rdv}", ''),
					'hidden' => true
				));

				UIGrid::add_cols($cols, 'date_rdv', 'Date RDV', array(
					'dbname' => 'DATE(r.date_rdv)',
					'formatter' => 'date',
					"formatoptions" => array("srcformat" => 'Y-m-d H:i:s', "newformat" => 'd/m/Y H:i'),
					'condition' => array('$row["date_rdv"] > 0', "{date_rdv}", '')
				));

				UIGrid::add_cols($cols, 'status_rdv', 'Statut RDV', array(
					'dbname' => 'r.status_rdv',
					'formatter' => 'select',
					'edittype' => "select",
					'editoptions' => array("value" => ":;0:A confirmer;1:OK Confirmé;2:Validé"),
					'stype' => "select",
					'searchoptions' => array("value" => '0:A confirmer;1:OK Confirmé;2:Validé')
				));
				
				/*
				UIGrid::add_cols($cols, 'date_sav', 'Date SAV', array(
					'dbname' => 'DATE(r2.date_rdv)',
					'formatter' => 'date',
					"formatoptions" => array("srcformat" => 'Y-m-d H:i:s', "newformat" => 'd/m/Y H:i'),
					'condition' => array('$row["date_sav"] > 0', "{date_sav}", '')
				));
				*/

				UIGrid::add_cols($cols, 'comments', 'Commentaires', array('hidden' => true, 'search' => false));				
			
				UIGrid::add_cols($cols, 'nb_com', 'Commentaires', array(
					'sortable' => false,
					'search' => false,
					'export' => false,
					'on_data_display' => array('display_comments', ''))
				);				
				
				function display_comments($data) {
					$btn = '';
					if ((int)$data['nb_com'] > 0)
						$btn = '<label data-toggle="tooltip" title="'.str_replace('"', "'", $data['comments']).'" data-placement="left" class="label label-success">'.$data['nb_com'].' comment.</label>';

					return $btn;
				}
				


				UIGrid::add_cols($cols, 'date_create', 'Créé le', array(
					'dbname' => 'DATE(c.date_create)',
					'formatter' => 'date',
					"formatoptions" => array("srcformat" => 'Y-m-d H:i:s', "newformat" => 'd/m/Y H:i'),
					'condition' => array('$row["date_create"] > 0', "{date_create}", '')
				));
				UIGrid::add_cols($cols, 'date_update', 'Dern. modif.', array(
					'dbname' => 'DATE(date_update)',
					'formatter' => 'date',
					"formatoptions" => array("srcformat" => 'Y-m-d H:i:s', "newformat" => 'd/m/Y H:i'),
					'condition' => array('$row["date_update"] > 0', "{date_update}", '')
				));

				UIGrid::add_cols(
					$cols,
					'action',
					'Action',
					array(
						'align' => 'center',
						'sortable' => false,
						'search' => false,
						'export' => false,
						'on_data_display' => array('display_action', '')
					)
				);

				function display_action($data)
				{
					$btn = '<div class="btn-group">'
						. '<a href="contact.php?id_contact=' . $data['id_contact'] . '" data-toggle="tooltip" title="Détails du contact" class="btn btn-xs btn-info"><i class="fa fa-eye"></i></a>'
						. '<a href="#" data-toggle="tooltip" data-id="' . $data['id_contact'] . '" title="Changer de statut téléopérateur" class="btn btn-xs btn-success btchangestone"><i class="fa fa-exchange"></i></a>'
						. '<a href="#" data-toggle="tooltip" data-id="' . $data['id_contact'] . '" title="Changer de statut confirmateur" class="btn btn-xs btn-warning btchangestoneconf"><i class="gi gi-iphone_exchange"></i></a>'
						. '</div>';

					return $btn;
				}


				function export_contacts($param)
				{
					//print_r($param); die;
					$data = &$param["data"]; // the complete grid object reference
					//error_log(print_r($data, true).PHP_EOL);
					$i = 0;
					$doc = new DOMDocument;
					foreach ($data as &$d) {
						// skip first column title
						if ($i++ == 0) {
							foreach($d as $c => $v)
								$d[$c] = str_replace('<br>', '', $v);
							continue;
						}

						$d["id_statuscont"] = GridManager::getval_fromgrid($param, 'id_statuscont', $d["id_statuscont"]);
						$d["id_statuscontconf"] = GridManager::getval_fromgrid($param, 'id_statuscontconf', $d["id_statuscontconf"]);
						$d["id_crmuser"] = GridManager::getval_fromgrid($param, 'id_crmuser', $d["id_crmuser"]);
						$d["id_crmuser_conf"] = GridManager::getval_fromgrid($param, 'id_crmuser_conf', $d["id_crmuser_conf"]);
						$d["id_team"] = GridManager::getval_fromgrid($param, 'id_team', $d["id_team"]);
						$d["status_rdv"] = GridManager::getval_fromgrid($param, 'status_rdv', $d["status_rdv"]);
						$d['comments'] = strip_tags($d['comments']);
					}
				}


				$grid->set_conditional_css(array(array('column' => 'date_rdv', 'css' => "'font-weight':'600'")));

				$grid->set_options($opt);

				$grid->set_events(array(
					'js_on_load_complete' => "isogridcomplete",
					'on_render_excel' => array("export_contacts", null, true),
					'on_render_pdf' => array("export_contacts", null, true)
				));
				$grid->set_columns($cols);
				return $grid->render("list_contacts");

				break;

			case 'iso_entrepots':
				$sql = "SELECT * from iso_entrepots ";
				$grid = UIGrid::getGrid('', $tbname, $sql);
				$cols = array();
				UIGrid::add_cols($cols, 'id_entrepot', 'ID.', array('width' => 70));
				UIGrid::add_cols($cols, 'entrepot_name', 'Nom de l\'entrepot', array('width' => 120));
				UIGrid::add_cols($cols, 'tel1', 'Tel');
				UIGrid::add_cols($cols, 'adr1', 'Adresse');
				UIGrid::add_cols($cols, 'post_code', 'Code Postal');
				UIGrid::add_cols($cols, 'city', 'Ville');
				UIGrid::add_cols(
					$cols,
					'action',
					'Action',
					array(
						'align' => 'center',
						'sortable' => false,
						'search' => false,
						'on_data_display' => array('display_action', '')
					)
				);

				function display_action($data)
				{
					$btn = '<a href="entrepot.php?id_entrepot=' . $data['id_entrepot'] . '" data-toggle="tooltip" title="Détails de l\'entrepot" class="btn btn-xs btn-info"><i class="fa fa-eye"></i></a>'
						  .'<a href="#" data-toggle="tooltip" title="Supprimer l\'entrepot" class="btn btn-xs btn-danger btdelent" data-id="'.$data['id_entrepot'].'"><i class="fa fa-trash"></i></a>';

					return $btn;
				}


				$grid->set_events(array('js_on_load_complete' => "isogridcomplete"));
				$grid->set_columns($cols);
				return $grid->render("list_entrepots");

				break;

			case 'iso_installators':
				$sql = "SELECT * from iso_installators ";
				$grid = UIGrid::getGrid('', $tbname, $sql);
				$cols = array();
				UIGrid::add_cols($cols, 'id_installator', 'ID.', array('width' => 70));
				UIGrid::add_cols($cols, 'installator_name', 'Raison sociale', array('width' => 120));
				UIGrid::add_cols($cols, 'tel1', 'Tel');
				UIGrid::add_cols($cols, 'tel2', 'Postable');
				UIGrid::add_cols($cols, 'adr1', 'Adresse');
				UIGrid::add_cols($cols, 'post_code', 'Code Postal');
				UIGrid::add_cols($cols, 'city', 'Ville');
				UIGrid::add_cols($cols, 'type_msg', 'Type de message à l\'attribution', array(
					'formatter' => 'select',
					'edittype' => "select",
					'editoptions' => array("value" => ":;0:Google Agenda;1:SMS;2:Google Agenda & SMS"),
					'stype' => "select",
					'searchoptions' => array("value" => '0:Google Agenda;1:SMS;2:Google Agenda & SMS')
				));

				UIGrid::add_cols(
					$cols,
					'action',
					'Action',
					array(
						'align' => 'center',
						'sortable' => false,
						'search' => false,
						'on_data_display' => array('display_action', '')
					)
				);

				function display_action($data)
				{
					$btn = '<a href="installator.php?id_installator=' . $data['id_installator'] . '" data-toggle="tooltip" title="Détails du chauffeur" class="btn btn-xs btn-info"><i class="fa fa-eye"></i></a>'
						  .'<a href="#" data-toggle="tooltip" title="Supprimer l\'installateur" class="btn btn-xs btn-danger btdelins" data-id="'.$data['id_installator'].'"><i class="fa fa-trash"></i></a>';

					return $btn;
				}


				$grid->set_events(array('js_on_load_complete' => "isogridcomplete"));
				$grid->set_columns($cols);
				return $grid->render("list_installators");

				break;

			case 'iso_rdv':
				$sql = "SELECT r.*, c.first_name, c.last_name, c.dept, c.city, c.tel1, pr.101_m2, pr.102_m2, pr.103_m2
						FROM iso_rdv r
						INNER JOIN iso_contacts c ON c.id_contact = r.id_contact
						LEFT JOIN iso_prestations pr ON pr.id_contact = c.id_contact
						LEFT JOIN iso_crmusers u ON u.id_crmuser = c.id_crmuser";

				if ($infosup != '' && is_object($infosup)) {
					if (CrmUser::isTelepro($infosup))
						$sql .= " WHERE c.id_crmuser = " . $infosup->id_crmuser;
					else
					if (CrmUser::isConfirm($infosup))
						$sql .= " WHERE u.id_team = " . $infosup->id_team;
				}

				$grid = UIGrid::getGrid('', $tbname, $sql);
				$grid->set_actions(array('export_pdf' => false));

				$opt["export"] = array(
					"range" => "filtered",
					"orientation" => "landscape"
				);

				$cols = array();
				UIGrid::add_cols($cols, 'id_rdv', 'ID.', array('width' => 70, 'hidden' => true));

				UIGrid::add_cols($cols, 'date_rdv', 'Date RDV', array(
					'formatter' => 'date',
					"formatoptions" => array("srcformat" => 'Y-m-d H:i:s', "newformat" => 'd/m/Y'),
					'condition' => array('$row["date_rdv"] > 0', "{date_rdv}", '')
				));
				UIGrid::add_cols($cols, 'rdv_start', 'Heure RDV', array(
					'formatter' => 'datetime',
					"formatoptions" => array("srcformat" => 'Y-m-d H:i:s', "newformat" => 'H:i'),
				));
				UIGrid::add_cols($cols, 'rdv_end', 'Fin RDV', array(
					'formatter' => 'datetime',
					"formatoptions" => array("srcformat" => 'Y-m-d H:i:s', "newformat" => 'H:i'),
				));
				UIGrid::add_cols($cols, 'duration', 'Durée (H)', array('width' => 120));

				UIGrid::add_cols($cols, 'id_contact', 'Id. contact', array('width' => 120, 'hidden' => true));
				UIGrid::add_cols($cols, 'first_name', 'Prénom', array('width' => 120, 'hidden' => true, 'export' => false));
				UIGrid::add_cols($cols, 'last_name', 'Nom', array('width' => 120, 'hidden' => true, 'export' => false));
				UIGrid::add_cols($cols, 'tel1', 'Tel', array('width' => 120, 'hidden' => true));

				$lstent = $grid->get_dropdown_values("select DISTINCT id_entrepot as k, entrepot_name as v from iso_entrepots");
				UIGrid::add_cols($cols, 'id_entrepot', 'Entrepot', array(
					'formatter' => 'select',
					'edittype' => "select",
					'editoptions' => array("value" => ":;" . $lstent),
					'stype' => "select",
					'searchoptions' => array("value" => $lstent)
				));
				UIGrid::add_cols($cols, 'num_planning', 'Planning #', array('width' => 120));

				$lstins = $grid->get_dropdown_values("select DISTINCT id_installator as k, installator_name as v from iso_installators");
				UIGrid::add_cols($cols, 'id_installator', 'Installateur', array(
					'dbname' => 'r.id_installator',
					'formatter' => 'select',
					'edittype' => "select",
					'editoptions' => array("value" => ":;" . $lstins),
					'stype' => "select",
					'searchoptions' => array("value" => $lstins)
				));

				UIGrid::add_cols($cols, 'first_name', 'Client', array(
					'default' => '<a href="contact.php?id_contact={id_contact}">{first_name} {last_name} <br>Tel: {tel1}</a>'
				));

				UIGrid::add_cols($cols, 'dept', 'Département', array('width' => 120));
				UIGrid::add_cols($cols, 'city', 'Ville', array('width' => 120));

				UIGrid::add_cols($cols, 'type_rdv', 'Type de RDV', array(
					'formatter' => 'select',
					'edittype' => "select",
					'editoptions' => array("value" => ":;0:Installation;1:Post visite"),
					'stype' => "select",
					'searchoptions' => array("value" => '0:Installation;1:Post visite')
				));

				UIGrid::add_cols($cols, 'status_rdv', 'Statut', array(
					'formatter' => 'select',
					'edittype' => "select",
					'editoptions' => array("value" => ":;0:A confirmer;1:OK Confirmé;2:Validé"),
					'stype' => "select",
					'searchoptions' => array("value" => '0:A confirmer;1:OK Confirmé;2:Validé')
				));

				UIGrid::add_cols($cols, 'sav_done', 'Statut Post visite', array(
					'formatter' => 'select',
					'edittype' => "select",
					'editoptions' => array("value" => ":;0:A effectuer;1:Effectué"),
					'stype' => "select",
					'searchoptions' => array("value" => '0:A effectuer;1:Effectué')
				));

				UIGrid::add_cols($cols, '101_m2', '101 m²', array('width' => 120));
				UIGrid::add_cols($cols, '102_m2', '102 m²', array('width' => 120));
				UIGrid::add_cols($cols, '103_m2', '103 m²', array('width' => 120));


				UIGrid::add_cols(
					$cols,
					'action',
					'Action',
					array(
						'align' => 'center',
						'sortable' => false,
						'search' => false,
						'export' => false,
						'on_data_display' => array('display_action', '')
					)
				);

				function display_action($data)
				{
					$btn = '<div class="btn-group">';
					$btn .= '<a href="planning.php?dt=' . strtotime($data['date_rdv']) . '" data-toggle="tooltip" title="Voir le planning" class="btn btn-xs btn-default"><i class="fa fa-calendar"></i></a>';
					$btn .= '<a href="tour.php?id_planning=' . $data['id_planning'] . '&view=1&id_rdv=' . $data['id_rdv'] . '" data-toggle="tooltip" title="Voir la tournée" class="btn btn-xs btn-info"><i class="fa fa-eye"></i></a>';
					//if ((int)$data['status_rdv'] < 2)
					//	$btn .= '<a href="#" data-toggle="tooltip" title="' . ((int)$data['status_rdv'] == 0 ? 'Confirmer' : 'Valider') . ' le RDV" class="btn btn-xs btn-success btconfrdv" data-status="' . (int)$data['status_rdv'] . '" data-id="' . $data['id_rdv'] . '"><i class="fa fa-check"></i></a>';
					$btn .= '<a href="#" data-toggle="tooltip" title="Annuler le Rendez-cous" class="btn btn-xs btn-danger btdelrdv" data-id="' . $data['id_rdv'] . '"><i class="fa fa-times"></i></a>';
					$btn .= '</div>';
					return $btn;
				}

				function export_rdvs($param)
				{
					$data = &$param["data"]; // the complete grid object reference
					$i = 0;
					$doc = new DOMDocument;
					foreach ($data as &$d) {
						// skip first column title
						if ($i++ == 0) continue;

						$doc->loadHTML($d['first_name']);
						$d["first_name"] = $doc->getElementsByTagName('a')->item(0)->nodeValue;

						$d["status_rdv"] = GridManager::getval_fromgrid($param, 'status_rdv', $d["status_rdv"]);
						$d["id_installator"] = GridManager::getval_fromgrid($param, 'id_installator', $d["id_installator"]);
					}
				}

				$grid->set_options($opt);
				$grid->set_events(array(
					'js_on_load_complete' => "isogridcomplete",
					'on_render_excel' => array("export_rdvs", null, true),
					'on_render_pdf' => array("export_rdvs", null, true)
				));
				$grid->set_columns($cols);
				return $grid->render("list_rdvs");

				break;

			case 'iso_statuscont':
				$sql = "SELECT * FROM iso_statuscont";
				$grid = UIGrid::getGrid('', $tbname, $sql, false, false);
				$grid->set_actions(array('add' => true, 'edit' => true, 'delete' => true, 'view' => false, 'clone' => false, 'export_pdf' => false, 'export_excel' => false, 'export_csv' => false, 'search' => false, 'showhidecolumns' => false,  'rowactions' => true));
				$cols = array();
				UIGrid::add_cols($cols, 'id_statuscont', 'Id.', array('editable' => false, 'width' => '50px'));
				UIGrid::add_cols($cols, 'name_statuscont', 'Nom statut');
				UIGrid::add_cols($cols, 'visible_conf', 'Visibilité confirmateur', array(
					'formatter' => 'select',
					'edittype' => "select",
					'editoptions' => array("value" => ":;0:Non;1:Oui"),
					'stype' => "select",
					'searchoptions' => array("value" => '0:Non;1:Oui')					
				));
				UIGrid::add_cols($cols, 'force_confirm', 'Forcer apres chgmnt. confirm.', array(
					'formatter' => 'select',
					'edittype' => "select",
					'editoptions' => array("value" => ":;0:Non;1:Oui"),
					'stype' => "select",
					'searchoptions' => array("value" => '0:Non;1:Oui')					
				));

				function check_delete_statuscont($data)
				{
					$ct = Contact::findOne(array('id_statuscont' => $data['id_statuscont']));
					if ($ct) {
						phpgrid_error('CE STATUT EST UTILISÉ PAR UN CLIENT / PROSPECT ! VEUILLEZ VÉRIFIER À CE QU\'AUCUN CLIENT / PROSPECT N\'A CE STATUT AVANT DE LE SUPPRIMER.');
						die;
					}
				}

				$grid->set_events(array(
					"on_delete" => array("check_delete_statuscont", null, true),
				));

				$grid->set_columns($cols);
				return $grid->render("list_statuscont");

				break;

			case 'iso_statuscontconf':
				$sql = "SELECT * FROM iso_statuscontconf";
				$grid = UIGrid::getGrid('', $tbname, $sql, false, false);
				$grid->set_actions(array('add' => true, 'edit' => true, 'delete' => true, 'view' => false, 'clone' => false, 'export_pdf' => false, 'export_excel' => false, 'export_csv' => false, 'search' => false, 'showhidecolumns' => false,  'rowactions' => true));
				$cols = array();
				$opt["loadComplete"] = "function(ids) { gridstcontconf_onload(ids); }";

				UIGrid::add_cols($cols, 'id_statuscontconf', 'Id.', array('editable' => false, 'width' => '50px'));
				UIGrid::add_cols($cols, 'name_statuscontconf', 'Nom statut');
				UIGrid::add_cols($cols, 'status_color', 'Couleur', array(
					'formatter' => 'select',
					'edittype' => "select",
					'editoptions' => array("value" => ":;#fff:Blanc;#f97979:Rouge;#9fdaff:Bleu;#c3f8c1:Vert clair;#5ab557:Vert;#efc466:Orange"),
					'stype' => "select",
					'searchoptions' => array("value" => '#fff:Blanc;#f97979:Rouge;#9fdaff:Bleu;#c3f8c1:Vert clair;#5ab557:Vert;#efc466:Orange')
				));
				UIGrid::add_cols($cols, 'cancel_rdv', 'Supprime le RDV<br>Retire des clients a Prox.', array(
					'formatter' => 'select',
					'edittype' => "select",
					'editoptions' => array("value" => ":;0:Non;1:Oui"),
					'stype' => "select",
					'searchoptions' => array("value" => '0:Non;1:Oui')					
				));
				UIGrid::add_cols($cols, 'after_rdv', 'Forcer aprés création d\'un RDV', array(
					'formatter' => 'select',
					'edittype' => "select",
					'editoptions' => array("value" => ":;0:Non;1:Oui"),
					'stype' => "select",
					'searchoptions' => array("value" => '0:Non;1:Oui')					
				));

				UIGrid::add_cols($cols, 'cancel_update_team', 'Désactive les modifs. prospects', array(
					'formatter' => 'select',
					'edittype' => "select",
					'editoptions' => array("value" => ":;0:Non;1:Oui"),
					'stype' => "select",
					'searchoptions' => array("value" => '0:Non;1:Oui')					
				));

				UIGrid::add_cols($cols, 'rdv_need_exists', 'Le RDV doit exister', array(
					'formatter' => 'select',
					'edittype' => "select",
					'editoptions' => array("value" => ":;0:Non;1:Oui"),
					'stype' => "select",
					'searchoptions' => array("value" => '0:Non;1:Oui')					
				));

				function check_delete_statuscontconf($data)
				{
					$ct = Contact::findOne(array('id_statuscontconf' => $data['id_statuscontconf']));
					if ($ct) {
						phpgrid_error('CE STATUT EST UTILISÉ PAR UN CLIENT / PROSPECT ! VEUILLEZ VÉRIFIER À CE QU\'AUCUN CLIENT / PROSPECT N\'A CE STATUT AVANT DE LE SUPPRIMER.');
						die;
					}
				}

				$grid->set_options($opt);
				$grid->set_events(array(
					"on_delete" => array("check_delete_statuscontconf", null, true),
				));

				$grid->set_columns($cols);
				return $grid->render("list_statuscontconf");

				break;

			case 'iso_campains':
				$sql = "SELECT * FROM iso_campains";
				$grid = UIGrid::getGrid('', $tbname, $sql, false, false);
				$grid->set_actions(array('add' => true, 'edit' => true, 'delete' => true, 'view' => false, 'clone' => false, 'export_pdf' => false, 'export_excel' => false, 'export_csv' => false, 'search' => false, 'showhidecolumns' => false,  'rowactions' => true));
				$cols = array();
				UIGrid::add_cols($cols, 'id_campain', 'Id.', array('editable' => false, 'width' => '50px'));
				UIGrid::add_cols($cols, 'name_campain', 'Nom campagne');

				$grid->set_columns($cols);
				return $grid->render("list_campains");

				break;	

			case 'iso_teams':
				$sql = "SELECT * FROM iso_teams";
				$grid = UIGrid::getGrid('', $tbname, $sql, false, false);
				$grid->set_actions(array('add' => true, 'edit' => true, 'delete' => true, 'view' => false, 'clone' => false, 'export_pdf' => false, 'export_excel' => false, 'export_csv' => false, 'search' => false, 'showhidecolumns' => false,  'rowactions' => true));
				$cols = array();
				UIGrid::add_cols($cols, 'id_team', 'Id.', array('editable' => false, 'width' => '50px'));
				UIGrid::add_cols($cols, 'name_team', 'Nom Team');

				function check_delete_team($data)
				{
					$ct = CrmUser::findOne(array('id_team' => $data['id_team']));
					if (!$ct)
						$ct = CrmUserTeam::findOne(array('id_team' => $data['id_team']));

					if ($ct) {
						phpgrid_error('CETTE TEAM EST UTILISÉE PAR UN UTILISATEUR CRM ! VEUILLEZ VÉRIFIER À CE QU\'AUCUN UTILISATEUR CRM N\'A CETTE TEAM AVANT DE LE SUPPRIMER.');
						die;
					}
				}

				$grid->set_events(array(
					"on_delete" => array("check_delete_team", null, true),
				));

				$grid->set_columns($cols);
				return $grid->render("list_teams");

				break;

			case 'iso_profils':
				$sql = "SELECT * FROM iso_profils";
				$grid = UIGrid::getGrid('', $tbname, $sql, false, false);
				$grid->set_actions(array('add' => false, 'edit' => true, 'delete' => false, 'view' => false, 'clone' => false, 'export_pdf' => false, 'export_excel' => false, 'export_csv' => false, 'search' => false, 'showhidecolumns' => false,  'rowactions' => true));
				$cols = array();
				UIGrid::add_cols($cols, 'id_profil', 'Id.', array('editable' => false, 'width' => '50px'));
				UIGrid::add_cols($cols, 'name_profil', 'Nom Profile');
				$grid->set_columns($cols);
				return $grid->render("list_profils");

				break;


			case 'iso_crmusers':
				$sql = "SELECT * FROM iso_crmusers";
				$grid = UIGrid::getGrid('', $tbname, $sql, false, false);
				$grid->set_actions(array('add' => false, 'edit' => false, 'delete' => false, 'view' => false, 'clone' => false, 'export_pdf' => false, 'export_excel' => false, 'export_csv' => false, 'search' => false, 'showhidecolumns' => false,  'rowactions' => true));
				$cols = array();
				UIGrid::add_cols($cols, 'id_crmuser', 'Id.', array('editable' => false, 'width' => '50px'));
				UIGrid::add_cols($cols, 'user_name', 'Nom');
				UIGrid::add_cols($cols, 'email', 'Email');
				UIGrid::add_cols($cols, 'tel', 'Tél');

				$lstteams = $grid->get_dropdown_values("select DISTINCT id_team as k, name_team as v from iso_teams");

				//gridOptionsTb
				UIGrid::add_cols($cols, 'id_team', 'Team', array(
					'formatter' => 'select',
					'edittype' => "select",
					'editoptions' => array("value" => ":;" . $lstteams),
					'stype' => "select",
					'searchoptions' => array("value" => $lstteams)
				));

				UIGrid::add_cols($cols, 'teams', 'Multi Team', array(
					'on_data_display' => array('display_teams', '')
				));
				
				function display_teams($data) {
					if ($data['teams'] != '')
						return Team::GiveNameFromTeams($data['teams']);						
				}


				$lstprofils = $grid->get_dropdown_values("select DISTINCT id_profil as k, name_profil as v from iso_profils");

				//gridOptionsTb
				UIGrid::add_cols($cols, 'id_profil', 'Profil', array(
					'formatter' => 'select',
					'edittype' => "select",
					'editoptions' => array("value" => ":;" . $lstprofils),
					'stype' => "select",
					'searchoptions' => array("value" => $lstprofils)
				));

				UIGrid::add_cols($cols, 'date_last_login', 'Dernier Login', array(
					'formatter' => 'datetime',
					"formatoptions" => array("srcformat" => 'Y-m-d H:i:s', "newformat" => 'd/m/Y H:i'),
					'condition' => array('$row["date_last_login"] > 0', "{date_last_login}", '')
				));

				UIGrid::add_cols(
					$cols,
					'action',
					'Action',
					array(
						'align' => 'center',
						'sortable' => false,
						'search' => false,
						'on_data_display' => array('display_action', '')
					)
				);

				function display_action($data)
				{
					$btn = '<div class="btn-group"><a href="#" data-toggle="tooltip" title="Détails de l\'utilisateur" class="btn btn-xs btn-info" onclick="showCrmUser(' . $data['id_crmuser'] . ')"><i class="fa fa-eye"></i></a><a href="#" data-toggle="tooltip" title="Suppression de l\'utilisateur" class="btn btn-xs btn-danger" onclick="deleteCrmUser(' . $data['id_crmuser'] . ')"><i class="fa fa-trash"></i></a></div>';

					return $btn;
				}

				$grid->set_events(array('js_on_load_complete' => "isogridcomplete"));
				$grid->set_columns($cols);
				return $grid->render("list_crmusers");

				break;

			case 'iso_mandators':
				$sql = "SELECT * FROM iso_mandators";
				$grid = UIGrid::getGrid('', $tbname, $sql, false, false);
				$grid->set_actions(array('add' => true, 'edit' => true, 'delete' => true, 'view' => false, 'clone' => false, 'export_pdf' => false, 'export_excel' => false, 'export_csv' => false, 'search' => false, 'showhidecolumns' => false,  'rowactions' => true));
				$cols = array();
				UIGrid::add_cols($cols, 'id_mandator', 'Id.', array('editable' => false, 'width' => '50px'));
				UIGrid::add_cols($cols, 'name_mandator', 'Raison sociale');
				UIGrid::add_cols($cols, 'first_name_mand', 'Nom');
				UIGrid::add_cols($cols, 'last_name_mand', 'Prenom');
				UIGrid::add_cols($cols, 'fonction_mand', 'Fonction');
				UIGrid::add_cols($cols, 'siret_mand', 'SIRET');
				UIGrid::add_cols($cols, 'adr_mand', 'Adr.');
				UIGrid::add_cols($cols, 'post_code_mand', 'CP');
				UIGrid::add_cols($cols, 'city_mand', 'Ville');
				UIGrid::add_cols($cols, 'tel1_mand', 'Tel 1');
				UIGrid::add_cols($cols, 'tel2_mand', 'Tel 2');
				UIGrid::add_cols($cols, 'email_mand', 'Email');
				UIGrid::add_cols($cols, 'footer_text', 'Texte footer doc.', array(
					'edittype' => 'textarea',
					'editoptions' => array("rows" => 5, "cols" => 80)
				));
				$opt['add_options'] = array('width' => '620');
				$opt['edit_options'] = array('width' => '620');
				$grid->set_options($opt);

				$grid->set_columns($cols);
				return $grid->render("list_mandators");

				break;

			case 'iso_contributors':
				$sql = "SELECT * FROM iso_contributors";
				$grid = UIGrid::getGrid('', $tbname, $sql, false, false);
				$grid->set_actions(array('add' => true, 'edit' => true, 'delete' => true, 'view' => false, 'clone' => false, 'export_pdf' => false, 'export_excel' => false, 'export_csv' => false, 'search' => false, 'showhidecolumns' => false,  'rowactions' => true));
				$cols = array();
				UIGrid::add_cols($cols, 'id_contributor', 'Id.', array('editable' => false, 'width' => '50px'));
				UIGrid::add_cols($cols, 'name_contributor', 'Nom Délégataire');

				$grid->set_columns($cols);
				return $grid->render("list_contributors");

				break;

			case 'iso_materials':
				$sql = "SELECT * FROM iso_materials";
				$grid = UIGrid::getGrid('', $tbname, $sql, false, false);
				$grid->set_actions(array('add' => true, 'edit' => true, 'delete' => true, 'view' => false, 'clone' => false, 'export_pdf' => false, 'export_excel' => false, 'export_csv' => false, 'search' => false, 'showhidecolumns' => false,  'rowactions' => true));
				$cols = array();
				UIGrid::add_cols($cols, 'id_material', 'Id.', array('editable' => false, 'width' => '50px'));
				UIGrid::add_cols($cols, 'type_baren', 'Type BAR-EN', array(
					'formatter' => 'select',
					'edittype' => "select",
					'editoptions' => array("value" => "101:101;102:102;103:103"),
					'stype' => "select",
					'searchoptions' => array("value" => ':;101:101;102:102;103:103')
				));
				UIGrid::add_cols($cols, 'name_material', 'Nom article');
				UIGrid::add_cols($cols, 'desc_material', 'Description', array(
					'edittype' => 'textarea',
					'editoptions' => array("rows" => 3, "cols" => 40)
				));
				UIGrid::add_cols($cols, 'ref_material', 'Référence');
				UIGrid::add_cols($cols, 'brand_material', 'Marque');
				UIGrid::add_cols($cols, 'thick', 'Epaisseur (mm)');
				UIGrid::add_cols($cols, 'resistance', 'Resistance m² K/W');
				UIGrid::add_cols($cols, 'acermi_material', 'Acermi');
				UIGrid::add_cols($cols, 'ratio', 'Ratio unité/m² pour entrepot');
				UIGrid::add_cols($cols, 'unit', 'Unité');


				$opt['add_options'] = array('width' => '430');
				$opt['edit_options'] = array('width' => '430');
				$grid->set_options($opt);

				$grid->set_columns($cols);
				return $grid->render("list_materials");

				break;

			case 'iso_plafonds_bonus':
				$sql = "SELECT * FROM iso_plafonds_bonus";
				$grid = UIGrid::getGrid('', $tbname, $sql, false, false);
				$grid->set_actions(array('add' => true, 'edit' => true, 'delete' => true, 'view' => false, 'clone' => false, 'export_pdf' => false, 'export_excel' => false, 'export_csv' => false, 'search' => false, 'showhidecolumns' => false, 'rowactions' => true));
				$cols = array();
				$fmtcur = array("suffix" => "€", "thousandsSeparator" => " ", "decimalSeparator" => ",", "decimalPlaces" => 2);
				UIGrid::add_cols($cols, 'id_plafond_bonus', 'Id.', array('editable' => false, 'width' => '50px'));
				UIGrid::add_cols($cols, 'nb_person', 'Nb. personne foyer');
				UIGrid::add_cols($cols, 'mt_gp_idf', 'Plafond Grande précarité IDF', array(
					"formatter" => "currency",
					"formatoptions" => $fmtcur
				));
				UIGrid::add_cols($cols, 'mt_gp', 'Plafond Grande précarité', array(
					"formatter" => "currency",
					"formatoptions" => $fmtcur
				));
				UIGrid::add_cols($cols, 'mt_p_idf', 'Plafond Précarité IDF', array(
					"formatter" => "currency",
					"formatoptions" => $fmtcur
				));
				UIGrid::add_cols($cols, 'mt_p', 'Plafond Précarité', array(
					"formatter" => "currency",
					"formatoptions" => $fmtcur
				));


				$grid->set_columns($cols);
				return $grid->render("list_plafonds_bonus");
				break;

			case 'iso_bonus_grid':
				$sql = "SELECT * FROM iso_bonus_grid";
				$grid = UIGrid::getGrid('', $tbname, $sql, false, false);
				$grid->set_actions(array('add' => true, 'edit' => true, 'delete' => true, 'view' => false, 'clone' => false, 'export_pdf' => false, 'export_excel' => false, 'export_csv' => false, 'search' => false, 'showhidecolumns' => false, 'rowactions' => true));
				$cols = array();
				$fmtcur = array("suffix" => "€", "thousandsSeparator" => " ", "decimalSeparator" => ",", "decimalPlaces" => 2);
				UIGrid::add_cols($cols, 'id_bonus', 'Id.', array('editable' => false, 'width' => '50px'));
				UIGrid::add_cols($cols, 'type_preca', 'Type social', array(
					'formatter' => 'select',
					'edittype' => "select",
					'editoptions' => array("value" => "0:Classique;1:Précarité;2:Grande Précarité"),
					'stype' => "select",
					'searchoptions' => array("value" => ':;0:Classique;1:Précarité;2:Grande Précarité')
				));
				UIGrid::add_cols($cols, 'type_prestation', 'Type BAR-EN', array(
					'formatter' => 'select',
					'edittype' => "select",
					'editoptions' => array("value" => "101:101;102:102;103:103"),
					'stype' => "select",
					'searchoptions' => array("value" => ':;101:101;102:102;103:103')
				));
				UIGrid::add_cols($cols, 'zone', 'Zone');
				UIGrid::add_cols($cols, 'type_chauf', 'Type chauffage', array(
					'formatter' => 'select',
					'edittype' => "select",
					'editoptions' => array("value" => "0:Electricité;1:Combustible"),
					'stype' => "select",
					'searchoptions' => array("value" => ':;0:Electricité;1:Combustible')
				));
				UIGrid::add_cols($cols, 'mt_bonus', 'Montant Bonus / m²', array(
					"formatter" => "currency",
					"formatoptions" => $fmtcur
				));


				$grid->set_columns($cols);
				return $grid->render("list_bonus_grid");
				break;

			case 'iso_rdv_range':
				$sql = "SELECT * FROM iso_rdv_range";
				$grid = UIGrid::getGrid('', $tbname, $sql, false, false);
				$grid->set_actions(array('add' => true, 'edit' => true, 'delete' => true, 'view' => false, 'clone' => false, 'export_pdf' => false, 'export_excel' => false, 'export_csv' => false, 'search' => false, 'showhidecolumns' => false, 'rowactions' => true));
				$cols = array();
				UIGrid::add_cols($cols, 'id_rdv_range', 'Id.', array('editable' => false, 'width' => '50px', 'hidden' => true));
				UIGrid::add_cols($cols, 'hour_start', 'Heure début', array(
					'formatter' => 'datetime',
					"formatoptions" => array("srcformat" => 'Y-m-d H:i:s', "newformat" => 'H:i'),
					'condition' => array('$row["hour_start"] > 0', "{hour_start}", '')
				));
				UIGrid::add_cols($cols, 'hour_end', 'Heure fin', array(
					'formatter' => 'datetime',
					"formatoptions" => array("srcformat" => 'Y-m-d H:i:s', "newformat" => 'H:i'),
					'condition' => array('$row["hour_end"] > 0', "{hour_end}", '')
				));

				$grid->set_columns($cols);
				return $grid->render("list_rdv_range");
				break;

			case 'iso_cumacs':
				$sql = "SELECT * FROM iso_cumacs";
				$grid = UIGrid::getGrid('', $tbname, $sql, false, false);
				$grid->set_actions(array('add' => true, 'edit' => true, 'delete' => true, 'view' => false, 'clone' => false, 'export_pdf' => false, 'export_excel' => false, 'export_csv' => false, 'search' => false, 'showhidecolumns' => false, 'rowactions' => true));
				$cols = array();
				UIGrid::add_cols($cols, 'id_cumac', 'Id.', array('editable' => false, 'width' => '50px'));
				UIGrid::add_cols($cols, 'type_prestation', 'Type BAR-EN', array(
					'formatter' => 'select',
					'edittype' => "select",
					'editoptions' => array("value" => "101:101;102:102;103:103"),
					'stype' => "select",
					'searchoptions' => array("value" => ':;101:101;102:102;103:103')
				));
				UIGrid::add_cols($cols, 'type_preca', 'Type social', array(
					'formatter' => 'select',
					'edittype' => "select",
					'editoptions' => array("value" => "0:Classique;1:Précarité;2:Grande Précarité"),
					'stype' => "select",
					'searchoptions' => array("value" => ':;0:Classique;1:Précarité;2:Grande Précarité')
				));
				UIGrid::add_cols($cols, 'nb_cumac', 'Nb. CUMAC / m²');

				$grid->set_columns($cols);
				return $grid->render("list_cumacs");
				break;

			case 'iso_parrains':
				$sql = "SELECT * FROM iso_parrains";
				$grid = UIGrid::getGrid('', $tbname, $sql, false, false);
				$grid->set_actions(array('add' => true, 'edit' => true, 'delete' => true, 'view' => false, 'clone' => false, 'export_pdf' => false, 'export_excel' => false, 'export_csv' => false, 'search' => false, 'showhidecolumns' => false,  'rowactions' => true));
				$cols = array();
				UIGrid::add_cols($cols, 'id_parrain', 'Id.', array('editable' => false, 'width' => '50px'));
				UIGrid::add_cols($cols, 'name_filleul', 'Nom Filleul');

				$grid->set_columns($cols);
				return $grid->render("list_parrains");

				break;


			case 'iso_activity':
				$libflds = array(
					'campain' => 'Campagne',
					'source' => 'Source',
					'name_team' => 'Team',
					'dept' => 'Département',
					'u.user_name' => 'Téléopérateur',
					'u2.user_name' => 'Confirmateur',
					'name_statuscont' => 'Statut',
					'name_statuscontconf' => 'Statut confirmateur',
					'installator_name' => 'Installateur',
					'type_chauf' => 'Type de chauffage',
					'type_preca' => 'Précarité',
					'zone' => 'Zone Géo'
				);

				if (!isset($infosup) || empty($infosup))
					$infosup = $_GET['strflds'];
				$withsum = true; //isset($_GET['sum']);

				$flds = explode(',', $infosup);
				$curflds = '';
				$curfldsgr = '';
				$titflds = '';
				$i = -1;
				$sqlbase = " SELECT #FLD#, 
									COUNT(DISTINCT c.id_contact) as nb,
									ROUND(SUM(IFNULL(p.101_m2, 0))) as 101m2,
									ROUND(SUM(IFNULL(p.102_m2, 0))) as 102m2,
									ROUND(SUM(IFNULL(p.103_m2, 0))) as 103m2,
									ROUND(SUM(IFNULL(p.101_m2, 0) + IFNULL(p.102_m2, 0) + IFNULL(p.103_m2, 0))) as totm2,
									ROUND(SUM(IFNULL(p.101_pu, 0) * IFNULL(p.101_m2, 0))) as 101px,
									ROUND(SUM(IFNULL(p.102_pu, 0) * IFNULL(p.102_m2, 0))) as 102px,
									ROUND(SUM(IFNULL(p.103_pu, 0) * IFNULL(p.103_m2, 0))) as 103px,
									SUM(IFNULL(p.101_cumac, 0)) as 101cumac,
									SUM(IFNULL(p.102_cumac, 0)) as 102cumac,
									SUM(IFNULL(p.103_cumac, 0)) as 103cumac,
									SUM(IFNULL(p.101_cumac, 0) + IFNULL(p.102_cumac, 0) + IFNULL(p.103_cumac, 0)) as totcumac,
									ROUND(SUM(IFNULL(p.101_bonus, 0) * IFNULL(p.101_m2, 0))) as 101bonus,
									ROUND(SUM(IFNULL(p.102_bonus, 0) * IFNULL(p.101_m2, 0))) as 102bonus,
									ROUND(SUM(IFNULL(p.103_bonus, 0) * IFNULL(p.101_m2, 0))) as 103bonus,
									SUM(IFNULL(p.totttc, 0)) as tot,
									SUM(IFNULL(p.mtcontrib, 0)) as contrib
								FROM iso_contacts c 
									LEFT JOIN iso_statuscont st ON st.id_statuscont = c.id_statuscont
									LEFT JOIN iso_statuscontconf stc ON stc.id_statuscontconf = c.id_statuscontconf
									LEFT JOIN iso_crmusers u ON u.id_crmuser = c.id_crmuser 
									LEFT JOIN iso_crmusers u2 ON u2.id_crmuser = c.id_crmuser_conf
									LEFT JOIN iso_teams t ON t.id_team = u.id_team
									LEFT JOIN iso_prestations p ON p.id_contact = c.id_contact 
									LEFT JOIN iso_installators i ON i.id_installator = p.id_installator 
							" . ($infosup2 != '' ? " WHERE c.date_create BETWEEN '" . explode('|', $infosup2)[0] . "' AND '" . explode('|', $infosup2)[1] . "'" : '');

				$totfld = '';
				if ($i < count($flds) - 1) {
					for ($k = 0; $k < count($flds); $k++)
						$totfld .= ($totfld != '' ? ',' : '') . ' \' Tous les ' . $libflds[$flds[$k]] . '\' as ' . str_replace('.', '', $flds[$k]);
				}
				$sql = '(' . str_replace('#FLD#', $totfld, $sqlbase) . ')';
				//die($sql);

				foreach ($flds as $fld) {
					$curflds .= ($curflds != '' ? ',' : '') . 'CASE WHEN ' . $fld . ' <> \'\' THEN ' . $fld . ' ELSE \'_Aucun ' . $libflds[$fld] . '\' END as ' . str_replace('.', '', $fld);
					$curfldsgr .= ($curfldsgr != '' ? ',' : '') . $fld;
					$titflds .= ($titflds != '' ? ',' : '') . $libflds[$fld];
					$i++;

					if (!$withsum && ($i < count($flds) - 1))
						continue;

					$selfld = $curflds;
					if ($i < count($flds) - 1) {
						for ($k = $i + 1; $k < count($flds); $k++)
							$selfld .= ', \' Tous les ' . $libflds[$flds[$k]] . '\' as ' . str_replace('.', '', $flds[$k]);
					}

					//if ($i > 0 && $withsum)
					$sql .= " UNION ALL ";

					$sql .= '(' . str_replace('#FLD#', $selfld, $sqlbase) . " GROUP BY ".$curfldsgr . ')';
				}

				$grid = UIGrid::getGrid('', $tbname, $sql, false, false);
				$opt["url"] = 'index.php?strflds=' . $infosup . ($withsum ? '&sum=1' : '') . ($infosup2 != '' ? '&dts=' . explode('|', $infosup2)[0] . '&dte=' . explode('|', $infosup2)[1] : '');
				$opt["height"] = "450px";
				$opt["rowList"] =  array(100, 500, 'All');
				$opt["rowNum"] = 100;
				$opt["toolbar"] = "both";
				$opt["editurl"] = $opt["url"];
				$opt["export"] = array(
					"range" => "filtered",
					"orientation" => "landscape"
				);
				//die(str_replace('.', '', $infosup));
				$opt["sortname"] = str_replace('.', '', $infosup);
				$opt["sortorder"] = "ASC";
				$opt["loadComplete"] = "function(ids) { gridstatacty_onload(ids); }";

				$grid->set_actions(
					array(
						"add" => false,
						"edit" => false,
						"delete" => false,
						"rowactions" => false, // show/hide row wise edit/del/save option
						"search" => false, // show single/multi field search condition (e.g. simple or advance)
						"showhidecolumns" => false,
						"export_excel" => true,
						"export_pdf" => false,
						"export_csv" => false
					)
				);

				$cols = array();
				$fst = true;
				$lnkflds = '{' . str_replace(',', '},{', $infosup) . '}';
				foreach ($flds as $fld) {
					if ($fst) {
						UIGrid::add_cols($cols, 'id', 'id', array('hidden' => true, 'dbname' => $fld, 'export' => false));
						$fst = false;
					}

					UIGrid::add_cols($cols, str_replace('.', '', $fld), $libflds[$fld], array(
						'dbname' => $fld,
						'sortalias' => true
						//'link' => 'admin.php?page=simple-contact&filterflds='.$strflds.'&filtervals='.$lnkflds, 
						//'linkoptions' => 'target="blank"'
					));
					//print_r($cols);
				}

				//print_r($cols); die;

				UIGrid::add_cols($cols, 'nb', 'Nombre de contacts', array(
					'search' => false,
					//'link' => 'admin.php?page=simple-contact&filterflds='.$strflds.'&filtervals='.$lnkflds, 
					//'linkoptions' => "target='_blank'"		
				));

				UIGrid::add_cols($cols, '101m2', '101 m²', array(
					'search' => false,
					//'link' => 'admin.php?page=simple-contact&filterflds='.$strflds.'&filtervals='.$lnkflds, 
					//'linkoptions' => "target='_blank'"		
				));

				UIGrid::add_cols($cols, '102m2', '102 m²', array(
					'search' => false,
					//'link' => 'admin.php?page=simple-contact&filterflds='.$strflds.'&filtervals='.$lnkflds, 
					//'linkoptions' => "target='_blank'"		
				));

				UIGrid::add_cols($cols, '103m2', '103 m²', array(
					'search' => false,
					//'link' => 'admin.php?page=simple-contact&filterflds='.$strflds.'&filtervals='.$lnkflds, 
					//'linkoptions' => "target='_blank'"		
				));

				UIGrid::add_cols($cols, 'totm2', 'Total m²', array(
					'search' => false,
					//'link' => 'admin.php?page=simple-contact&filterflds='.$strflds.'&filtervals='.$lnkflds, 
					//'linkoptions' => "target='_blank'"		
				));

				UIGrid::add_cols($cols, '101cumac', 'Cumac 101', array(
					'search' => false,
					'formatter' => 'currency',
					'formatoptions' => array("prefix" => "", "suffix" => '', "thousandsSeparator" => ",", "decimalSeparator" => ".", "decimalPlaces" => 0)
					//'link' => 'admin.php?page=simple-contact&filterflds='.$strflds.'&filtervals='.$lnkflds, 
					//'linkoptions' => "target='_blank'"		
				));

				UIGrid::add_cols($cols, '102cumac', 'Cumac 102', array(
					'search' => false,
					'formatter' => 'currency',
					'formatoptions' => array("prefix" => "", "suffix" => '', "thousandsSeparator" => ",", "decimalSeparator" => ".", "decimalPlaces" => 0)
					//'link' => 'admin.php?page=simple-contact&filterflds='.$strflds.'&filtervals='.$lnkflds, 
					//'linkoptions' => "target='_blank'"		
				));

				UIGrid::add_cols($cols, '103cumac', 'Cumac 103', array(
					'search' => false,
					'formatter' => 'currency',
					'formatoptions' => array("prefix" => "", "suffix" => '', "thousandsSeparator" => ",", "decimalSeparator" => ".", "decimalPlaces" => 0)
					//'link' => 'admin.php?page=simple-contact&filterflds='.$strflds.'&filtervals='.$lnkflds, 
					//'linkoptions' => "target='_blank'"		
				));

				UIGrid::add_cols($cols, 'totcumac', 'Total Cumac', array(
					'search' => false,
					'formatter' => 'currency',
					'formatoptions' => array("prefix" => "", "suffix" => '', "thousandsSeparator" => ",", "decimalSeparator" => ".", "decimalPlaces" => 0)
					//'link' => 'admin.php?page=simple-contact&filterflds='.$strflds.'&filtervals='.$lnkflds, 
					//'linkoptions' => "target='_blank'"		
				));


				UIGrid::add_cols($cols, '101px', 'Montant 101', array(
					'search' => false,
					'formatter' => 'currency',
					'formatoptions' => array("prefix" => "", "suffix" => '€', "thousandsSeparator" => ",", "decimalSeparator" => ".", "decimalPlaces" => 0)
					//'link' => 'admin.php?page=simple-order&filterfld=campain_name&filterval={campain_name}',
					//'linkoptions' => "target='_blank'"
				));

				UIGrid::add_cols($cols, '102px', 'Montant 102', array(
					'search' => false,
					'formatter' => 'currency',
					'formatoptions' => array("prefix" => "", "suffix" => '€', "thousandsSeparator" => ",", "decimalSeparator" => ".", "decimalPlaces" => 0)
					//'link' => 'admin.php?page=simple-order&filterfld=campain_name&filterval={campain_name}',
					//'linkoptions' => "target='_blank'"
				));

				UIGrid::add_cols($cols, '103px', 'Montant 103', array(
					'search' => false,
					'formatter' => 'currency',
					'formatoptions' => array("prefix" => "", "suffix" => '€', "thousandsSeparator" => ",", "decimalSeparator" => ".", "decimalPlaces" => 0)
					//'link' => 'admin.php?page=simple-order&filterfld=campain_name&filterval={campain_name}',
					//'linkoptions' => "target='_blank'"
				));

				UIGrid::add_cols($cols, 'tot', 'Total €', array(
					'search' => false,
					'formatter' => 'currency',
					'formatoptions' => array("prefix" => "", "suffix" => '€', "thousandsSeparator" => ",", "decimalSeparator" => ".", "decimalPlaces" => 0)
					//'link' => 'admin.php?page=simple-order&filterfld=campain_name&filterval={campain_name}',
					//'linkoptions' => "target='_blank'"
				));


				UIGrid::add_cols($cols, '101bonus', 'Bonus 101', array(
					'search' => false,
					'formatter' => 'currency',
					'formatoptions' => array("prefix" => "", "suffix" => '€', "thousandsSeparator" => ",", "decimalSeparator" => ".", "decimalPlaces" => 0)
					//'link' => 'admin.php?page=simple-contact&filterflds='.$strflds.'&filtervals='.$lnkflds, 
					//'linkoptions' => "target='_blank'"		
				));

				UIGrid::add_cols($cols, '102bonus', 'Bonus 102', array(
					'search' => false,
					'formatter' => 'currency',
					'formatoptions' => array("prefix" => "", "suffix" => '€', "thousandsSeparator" => ",", "decimalSeparator" => ".", "decimalPlaces" => 0)
					//'link' => 'admin.php?page=simple-contact&filterflds='.$strflds.'&filtervals='.$lnkflds, 
					//'linkoptions' => "target='_blank'"		
				));

				UIGrid::add_cols($cols, '103bonus', 'Bonus 103', array(
					'search' => false,
					'formatter' => 'currency',
					'formatoptions' => array("prefix" => "", "suffix" => '€', "thousandsSeparator" => ",", "decimalSeparator" => ".", "decimalPlaces" => 0)
					//'link' => 'admin.php?page=simple-contact&filterflds='.$strflds.'&filtervals='.$lnkflds, 
					//'linkoptions' => "target='_blank'"		
				));

				UIGrid::add_cols($cols, 'contrib', 'Contribution', array(
					'search' => false,
					'formatter' => 'currency',
					'formatoptions' => array("prefix" => "", "suffix" => '€', "thousandsSeparator" => ",", "decimalSeparator" => ".", "decimalPlaces" => 0)
					//'link' => 'admin.php?page=simple-order&filterfld=campain_name&filterval={campain_name}',
					//'linkoptions' => "target='_blank'"
				));


				$grid->set_options($opt);
				$grid->set_columns($cols);
				return $grid->render("list_activity_stats");
				break;

			case 'iso_crmaction_ct':
			
				$sql = "SELECT a.*, u.user_name
						FROM iso_crmaction a 
							INNER JOIN iso_crmusers u ON u.id_crmuser = a.id_crmuser
							INNER JOIN iso_contacts c ON c.id_contact = a.id_entity 
						WHERE a.table_action = 'Contacts' AND c.id_contact = " . (int)$infosup;
				$grid = UIGrid::getGrid('', $tbname, $sql, false, false);
				$grid->set_actions(array('add' => false, 'edit' => false, 'delete' => false, 'view' => false, 'clone' => false, 'export_pdf' => false, 'export_excel' => false, 'export_csv' => false, 'search' => false, 'showhidecolumns' => false, 'rowactions' => false));
				$opt["sortname"] = 'id_crmaction';
				$opt["sortorder"] = "DESC";
				$grid->set_options($opt);

				$cols = array();
				UIGrid::add_cols($cols, 'id_crmaction', 'Id.', array('editable' => false, 'width' => '50px', 'hidden' => true));
				UIGrid::add_cols($cols, 'user_name', 'Utilisateur');
				UIGrid::add_cols($cols, 'date_action', 'Date action', array(
					'dbname' => 'DATE(date_action)',
					'formatter' => 'date',
					"formatoptions" => array("srcformat" => 'Y-m-d H:i:s', "newformat" => 'd/m/Y H:i'),
					'condition' => array('$row["date_action"] > 0', "{date_action}", '')
				));
				UIGrid::add_cols($cols, 'type_action', 'Type action');
				UIGrid::add_cols(
					$cols,
					'details_action',
					'Détails',
					array(
						'on_data_display' => array('display_details', '')
					)
				);

				function display_details($data)
				{
					$btn = '<a href="#" data-toggle="tooltip" title="' . $data['details_action'] . '" class="btn btn-xs btn-success"><i class="gi gi-search"></i></a>';
					return $btn;
				}

				$grid->set_events(array('js_on_load_complete' => "isogridcomplete"));
				$grid->set_columns($cols);
				return $grid->render("list_crmaction_ct");
				break;

			case 'iso_audits':			
				$sql = "SELECT a.*, u.user_name
						FROM iso_audits a 
							INNER JOIN iso_crmusers u ON u.id_crmuser = a.id_crmuser
							INNER JOIN iso_contacts c ON c.id_contact = a.id_contact 
						WHERE a.id_contact = " . (int)$infosup;
				$grid = UIGrid::getGrid('', $tbname, $sql, false, false);
				$grid->set_actions(array('add' => false, 'edit' => false, 'delete' => false, 'view' => false, 'clone' => false, 'export_pdf' => false, 'export_excel' => false, 'export_csv' => false, 'search' => false, 'showhidecolumns' => false, 'rowactions' => false));
				$opt["sortname"] = 'id_audit';
				$opt["sortorder"] = "DESC";
				$grid->set_options($opt);

				$cols = array();
				UIGrid::add_cols($cols, 'id_audit', 'Id.', array('editable' => false, 'width' => '50px', 'hidden' => true));
				UIGrid::add_cols($cols, 'user_name', 'Utilisateur', array('width' => '120px'));
				UIGrid::add_cols($cols, 'date_update', 'Date', array(
					'width' => '80px',
					'dbname' => 'DATE(a.date_update)',
					'formatter' => 'date',
					"formatoptions" => array("srcformat" => 'Y-m-d H:i:s', "newformat" => 'd/m/Y H:i'),
					'condition' => array('$row["date_update"] > 0', "{date_update}", '')
				));
				
				UIGrid::add_cols($cols, 'details', 'Détails'); //, array('on_data_display' => array('display_details', '')));

				/*function display_details($data)
				{
					$btn = '<a href="#" data-toggle="tooltip" title="' . $data['details_action'] . '" class="btn btn-xs btn-success"><i class="gi gi-search"></i></a>';
					return $btn;
				}*/

				//$grid->set_events(array('js_on_load_complete' => "isogridcomplete"));
				$grid->set_columns($cols);
				return $grid->render("list_audits");
				break;

			case 'iso_crmactions':
				$sql = "SELECT a.*, u.user_name, CONCAT(c.raison_sociale, ' ', c.first_name, ' ', c.last_name) as cli
						FROM iso_crmaction a 
							LEFT JOIN iso_crmusers u ON u.id_crmuser = a.id_crmuser
							LEFT JOIN iso_contacts c ON c.id_contact = a.id_entity AND a.table_action = 'Contacts' ";
				$grid = UIGrid::getGrid('', $tbname, $sql, false, false);
				$grid->set_actions(array('add' => false, 'edit' => false, 'delete' => false, 'view' => false, 'clone' => false, 'export_pdf' => false, 'export_excel' => false, 'export_csv' => false, 'search' => false, 'showhidecolumns' => false, 'rowactions' => false));
				$opt["sortname"] = 'date_action';
				$opt["sortorder"] = "DESC";
				$grid->set_options($opt);

				$cols = array();
				UIGrid::add_cols($cols, 'id_crmaction', 'Id.', array('editable' => false, 'width' => '50px'));
				UIGrid::add_cols($cols, 'date_action', 'Date action', array(
					'formatter' => 'date',
					"formatoptions" => array("srcformat"=>'Y-m-d H:i:s',"newformat"=>'d/m/Y H:i'),
					'condition' => array('$row["date_action"] > 0', "{date_action}", '')
				));
				UIGrid::add_cols($cols, 'cli', 'Client', array(
					'dbname' => "CONCAT(c.raison_sociale, ' ', c.first_name, ' ', c.last_name)"
				));
				UIGrid::add_cols($cols, 'type_action', 'Type action');
				UIGrid::add_cols($cols, 'user_name', 'Utilisateur');
				UIGrid::add_cols(
					$cols,
					'details_action',
					'Détails',
					array(
						'on_data_display' => array('display_details', '')
					)
				);

				function display_details($data)
				{
					$btn = '<a href="#" data-toggle="tooltip" title="' . $data['details_action'] . '" class="btn btn-xs btn-success"><i class="gi gi-search"></i></a>';
					return $btn;
				}

				$grid->set_events(array('js_on_load_complete' => "isogridcomplete"));
				$grid->set_columns($cols);
				return $grid->render("list_crmaction");
				break;
		}
	}
}
