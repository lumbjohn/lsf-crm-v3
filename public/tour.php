<?php include 'inc/config.php'; ?>
<?php

/* MODE CONSULTATION DE LA TOURNE */
if (isset($_GET['view'])) {
	if (!isset($_GET['id_rdv']))
		header('Location: index.php');
	
	$currdv = RDV::findOne(array('id_rdv' => (int)$_GET['id_rdv']));	
	if (!$currdv)
		header('Location: index.php');

	$entrepot = Entrepot::findOne(array('id_entrepot' => $currdv->id_entrepot));
	if (!$entrepot)
		header('Location: index.php');

	$contact = Contact::findOne(array('c.id_contact' => $currdv->id_contact));
	if (!$contact)
		header('Location: index.php');

	$curlat = $entrepot->geolat;
	$curlng = $entrepot->geolng;
	$numplan = $currdv->num_planning;
	$dtrdv = $currdv->date_rdv;
	$typerdv = $currdv->type_rdv;
	$rdvs = RDV::getBy(array('date_rdv' => $dtrdv, 'r.id_entrepot' => $entrepot->id_entrepot, 'r.num_planning' => $numplan, 'type_rdv' => $typerdv));
	foreach($rdvs as $rdv) {
		$rdvst = strtotime($rdv['date_rdv'].' '.$rdv['rdv_start']);
		$rdven = strtotime($rdv['date_rdv'].' '.$rdv['rdv_end']);

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
		}
	
		$m101 = (int)$rdv['101_m2'] > 0 ? '101 : '.$rdv['101_m2'].'m²' : '';
		$m103 = (int)$rdv['103_m2'] > 0 ? '103 : '.$rdv['103_m2'].'m²' : '';
		$stp = array(
			'geolatdep' => $curlat,
			'geolngdep' => $curlng,
			'geolatdes' => $rdv['geolat'],
			'geolngdes' => $rdv['geolng'],
			'gmap' => json_decode($rdv['gmap']),
			'hour_start' => $rdvst,
			'hour_end' => $rdven,
			'dis' => $dis,
			'del' => $del,
			'delval' => $delval,
			'meter' => $m101.($m101 != '' && $m103 != '' ? ' - ' : '').$m103,
			'infosup' => '',
			'cliname' => $rdv['first_name'] . ' ' . $rdv['last_name'],
			'cliadr' => $rdv['adr1'] . ' ' . $rdv['post_code'] . ' ' . $rdv['city'],
			'idc' => $rdv['id_contact'],
			'selected' => $contact->id_contact == $rdv['id_contact'],
			'typerdv' => $rdv['type_rdv']
		);
		
		$curlat = $rdv['curlat'];
		$curlng = $rdv['curlng'];

	
		$steps[] = $stp;
	}	
}
else {
	/* MODE CREATION DE NOUVEAU RENDEZ VOUS */
	if (!isset($_GET['id_entrepot']) || !isset($_GET['num_planning']) || !isset($_GET['date_rdv']) || !isset($_GET['heure_start']) || !isset($_GET['heure_end']) || !isset($_GET['duration']) || !isset($_GET['id_contact']))
		header('Location: index.php');

	$entrepot = Entrepot::findOne(array('id_entrepot' => (int)$_GET['id_entrepot']));
	if (!$entrepot)
		header('Location: index.php');

	$contact = Contact::findOne(array('c.id_contact' => (int)$_GET['id_contact']));
	if (!$contact)
		header('Location: index.php');


	$numplan = (int)$_GET['num_planning'];
	$duration = (int)$_GET['duration'];
	$dtrdv = $_GET['date_rdv'];
	$hrstart = $_GET['heure_start'];
	$hrend = $_GET['heure_end'];
	$typerdv = $_GET['typerdv'];

	$deptime = strtotime($dtrdv . ' ' . $hrstart);
	$starttime = $deptime;

	$steps = array();

	//1 - check if exist rdv	
	$existrdv = RDV::findExists($entrepot->id_entrepot, $numplan, $dtrdv, $hrstart, $hrend, $typerdv);
	if ($existrdv)
		header('Location: index.php');

	//2 - get prior rdv
	$priorrdv = RDV::getPriorsRDV($entrepot->id_entrepot, $numplan, $dtrdv, $hrstart, $typerdv);
	if (!$priorrdv || $priorrdv->num_rows == 0) {
		$strinfo = Tool::getDirection($entrepot->geolat, $entrepot->geolng, $contact->geolat, $contact->geolng, $deptime);
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

		$endtime = strtotime($dtrdv . ' ' . $hrend);

		$m101 = (int)$contact->{'101_m2'} > 0 ? '101 : '.$contact->{'101_m2'}.'m²' : '';
		$m103 = (int)$contact->{'103_m2'} > 0 ? '103 : '.$contact->{'103_m2'}.'m²' : '';
		$stp = array(
			'geolatdep' => $entrepot->geolat,
			'geolngdep' => $entrepot->geolng,
			'geolatdes' => $contact->geolat,
			'geolngdes' => $contact->geolng,
			'gmap' => $info,
			'hour_start' => $deptime,
			'hour_end' => $endtime,
			'dis' => $dis,
			'del' => $del,
			'delval' => $delval,
			'meter' => $m101.($m101 != '' && $m103 != '' ? ' - ' : '').$m103,
			'infosup' => '(depuis l\'adr. de l\'entrepot)',
			'cliname' => $contact->first_name . ' ' . $contact->last_name,
			'cliadr' => $contact->adr1 . ' ' . $contact->post_code . ' ' . $contact->city,
			'idc' => $contact->id_contact,
			'selected' => true,
			'typerdv' => (int)$_GET['typerdv'],
			'creneau_start' => (int)$_GET['crndeb'],
			'creneau_end' => (int)$_GET['crnend']
		);

		$steps[] = $stp;
	}
	else {
		$curlat = $entrepot->geolat;
		$curlng = $entrepot->geolng;
		$i=0;	
		foreach($priorrdv as $rdv) {
			$i++;
			$m101 = (int)$rdv['101_m2'] > 0 ? '101 : '.$rdv['101_m2'].'m²' : '';
			$m103 = (int)$rdv['103_m2'] > 0 ? '103 : '.$rdv['103_m2'].'m²' : '';

			$stp = array(
				'geolatdep' => $curlat,
				'geolngdep' => $curlng,
				'geolatdes' => $rdv['geolat'],
				'geolngdes' => $rdv['geolng'],
				'gmap' => json_decode($rdv['gmap']),
				'hour_start' => strtotime($rdv['date_rdv'].' '.$rdv['rdv_start']),
				'hour_end' => strtotime($rdv['date_rdv'].' '.$rdv['rdv_end']),
				'dis' => $rdv['distance'].'km',
				'del' => round($rdv['delay']/3600, 1).'h',
				'delval' => round($rdv['delay']/3600, 1),
				'meter' => $m101.($m101 != '' && $m103 != '' ? ' - ' : '').$m103,
				'infosup' => $i == 1 ? '(depuis l\'adr. de l\'entrepot)' : '',
				'cliname' => $rdv['first_name'] . ' ' . $rdv['last_name'],
				'cliadr' => $rdv['adr1'] . ' ' . $rdv['post_code'] . ' ' . $rdv['city'],
				'idc' => $rdv['id_contact'],
				'selected' => false,
				'typerdv' => $rdv['type_rdv']
			);
			$steps[] = $stp;
			$curlat = $rdv['geolat'];
			$curlng = $rdv['geolng'];
			$deptime = strtotime($rdv['date_rdv'].' '.$rdv['rdv_end']);
		}

		$strinfo = Tool::getDirection($curlat, $curlng, $contact->geolat, $contact->geolng, $deptime);
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

		/* VERSION DECALAGE DES RDV 
		$deptime = strtotime('+ '.$delval.' second', $deptime);
		if ($deptime < $starttime)
			$deptime = $starttime;*/
		$deptime = $starttime;
		$endtime = strtotime('+ '.$duration.' hour', $deptime);
		

		$m101 = (int)$contact->{'101_m2'} > 0 ? '101 : '.$contact->{'101_m2'}.'m²' : '';
		$m103 = (int)$contact->{'103_m2'} > 0 ? '103 : '.$contact->{'103_m2'}.'m²' : '';

		$stp = array(
			'geolatdep' => $curlat,
			'geolngdep' => $curlng,
			'geolatdes' => $contact->geolat,
			'geolngdes' => $contact->geolng,
			'gmap' => $info,
			'hour_start' => $deptime,
			'hour_end' => $endtime, 
			'dis' => $dis,
			'del' => $del,
			'delval' => $delval,
			'infosup' => '',
			'meter' => $m101.($m101 != '' && $m103 != '' ? ' - ' : '').$m103,
			'cliname' => $contact->first_name . ' ' . $contact->last_name,
			'cliadr' => $contact->adr1 . ' ' . $contact->post_code . ' ' . $contact->city,
			'idc' => $contact->id_contact,
			'selected' => true,
			'typerdv' => (int)$_GET['typerdv'],
			'creneau_start' => (int)$_GET['crndeb'],
			'creneau_end' => (int)$_GET['crnend']
		);
		$hrstart = date('H:i', $deptime);
		$hrend = date('H:i', $endtime);

		$steps[] = $stp;

	}

	//3 - get nexts rdv
	$nextrdv = RDV::getNextsRDV($entrepot->id_entrepot, $numplan, $dtrdv, $hrstart, $typerdv);
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

				/* VERSION DECALAGE RDV 
				if ($i == 1) {
					$decal = $delval - ($rdvst - $deptime);
					if ($decal < 0)
						$decal = 0;
				}*/
			}
		
			$m101 = (int)$rdv['101_m2'] > 0 ? '101 : '.$rdv['101_m2'].'m²' : '';
			$m103 = (int)$rdv['103_m2'] > 0 ? '103 : '.$rdv['103_m2'].'m²' : '';

			$stp = array(
				'geolatdep' => $curlat,
				'geolngdep' => $curlng,
				'geolatdes' => $rdv['geolat'],
				'geolngdes' => $rdv['geolng'],
				'gmap' => $i == 1 ? $info : json_decode($rdv['gmap']),
				/* VERSION DECALAGE RDV 
				'hour_start' => strtotime('+ '.$decal.' second', $rdvst),
				'hour_end' => strtotime('+ '.$decal.' second', $rdven), */
				'hour_start' => $rdvst,
				'hour_end' => $rdven,
				'dis' => $dis,
				'del' => $del,
				'delval' => $delval,
				'meter' => $m101.($m101 != '' && $m103 != '' ? ' - ' : '').$m103,
				'infosup' => '',
				'cliname' => $rdv['first_name'] . ' ' . $rdv['last_name'],
				'cliadr' => $rdv['adr1'] . ' ' . $rdv['post_code'] . ' ' . $rdv['city'],
				'idc' => $rdv['id_contact'],
				'selected' => false,
				'typerdv' => $rdv['type_rdv']
			);
			
			$curlat = $rdv['curlat'];
			$curlng = $rdv['curlng'];

		
			$steps[] = $stp;
		}
	}
}
?>
<?php include 'inc/template_start.php'; ?>
<?php include 'inc/page_head.php'; ?>

<!-- Page content -->
<div id="page-content" class="page-lap">
	<!-- eCommerce Dashboard Header -->
	<div class="content-header">
		<?php include('inc/menutop.php'); ?>
	</div>
	<!-- END eCommerce Dashboard Header -->

	<!-- eShop Overview Block -->
	<div class="block full row">
		<!-- eShop Overview Title -->
		<div class="col-lg-4">
			<div class="block">
				<!-- Normal Form Title -->
				<div class="block-title">
					<h2><strong>Informations</strong> tournée</h2>
					<div class="block-options pull-right">
						<span class="label label-primary"><?php echo date('d/m/Y', strtotime($dtrdv)); ?></span>
					</div>
				</div>
				<!-- END Normal Form Title -->

				<!-- Normal Form Content -->
				<form onsubmit="return false;" class="form-bordered form-horizontal" method="post" action="crmajax.php" id="frminfousr">

					<div class="form-group">
						<label class="col-md-3 control-label">Entrepot</label>
						<div class="col-md-9">
							<p class="form-control-static"><?php echo $entrepot->entrepot_name; ?></p>
						</div>
					</div>
					<div class="form-group">
						<label class="col-md-3 control-label">Planning</label>
						<div class="col-md-9">
							<p class="form-control-static"><?php echo $numplan; ?></p>
						</div>
					</div>
					<div class="form-group">
						<label class="col-md-3 control-label">Installateur</label>
						<div class="col-md-9">
							<p class="form-control-static"><?php echo (int)$rdv['id_installator'] > 0 ? $rdv['installator_name'] : 'NON RENSEIGNÉ'; ?></p>
						</div>
					</div>
					<div class="form-group">
						<label class="col-md-3 control-label">Date des rendez-vous</label>
						<div class="col-md-9">
							<p class="form-control-static"><?php echo Tool::fulldatestr($dtrdv); ?></p>
						</div>
					</div>
				</form>
				<!-- END Normal Form Content -->
			</div>
		</div>

		<div class="col-lg-4">
			<div class="block">
				<div class="block-title">
					<h2><strong>Étapes</strong> de la tournée</h2>
				</div>
				<div class="row">
					<div class="col-md-12 col-lg-12">
						<div class="row push">
							<div class="timeline">
								<ul class="timeline-list timeline-hover">
									<?php
									$i = -1;
									$colors = array('#9999ff', '#ff0fff', '#ff9999', '#99ff99', '#fff888', '#fff000');
									//print_r($steps);
									foreach ($steps as $step) {
										$i++;
										$curct = $contact->id_contact == $step['idc'];
										?>
										<li <?php echo $step['selected'] ? 'style="background-color:#fffccc;"' : ''; ?>>
											<div class="timeline-icon"><i class="fa fa-user"></i></div>
											<div class="timeline-time" style="color:<?php echo $colors[$i]; ?>"><strong><?php echo date('H:i', $step['hour_start']); ?><br><?php echo date('H:i', $step['hour_end']); ?></strong></div>
											<div class="timeline-content">
												<p class="push-bit"><strong>Rendez vous <?php echo $step['typerdv'] == '1' ? '<span class="label label-info">Post visite</span>' : ''; ?> avec <a href="contact.php?id_contact=<?php echo $step['idc']; ?>"><?php echo $step['cliname']; ?></a></strong></p>
												<i><?php echo $step['cliadr']; ?></i>
												<?php echo $step['meter'] != '' ? '<br><small><b>'.$step['meter'].'</b></small>' : ''; ?>
												<div>
													<label class="label label-success">Info GMap: <?php echo $step['dis'] . ' - ' . $step['del'] . ' ' . $step['infosup']; ?></label>
												</div>
												<?php if ((int)$step['creneau_start'] > 0) { ?>
													<div style="padding-top:10px">
														Créneau de début : 
														<select name="creneau_start" id="creneau_start">
															<?php
															for ($hr = 7; $hr < 21; $hr++)
																echo '<option value="' . $hr . '" '.($hr == (int)$step['creneau_start'] ? 'selected="selected"' : '').'>' . $hr . 'h</option>';
															?>
														</select> | 
														Créneau de fin : 
														<select name="creneau_end" id="creneau_end">
															<?php
															for ($hr = 9; $hr < 22; $hr++)
																echo '<option value="' . $hr . '" '.($hr == (int)$step['creneau_end'] ? 'selected="selected"' : '').'>' . $hr . 'h</option>';
															?>
														</select>
													</div>	
													<div style="padding-top:10px">
														Statut RDV : 
														<select name="status_rdv" id="status_rdv">
															<option value="0"><?php echo $step['typerdv'] == '1' ? 'A Effectuer' : 'A Confirmer'; ?></option>
															<option value="1"><?php echo $step['typerdv'] == '1' ? 'Effectué' : 'OK Confirmé'; ?></option>
														</select>
													</div>	
													<input type="hidden" name="typerdv" id="typerdv" value="<?php echo $step['typerdv']; ?>"/>
												<?php } ?>
											</div>
										</li>
									<?php
								}
								?>
								</ul>
							</div>
						</div>
						<form onsubmit="return false;" class="form-bordered form-horizontal" method="post" action="crmajax.php" id="frmtour">
							<div class="form-group form-actions">
								<input type="hidden" name="action" id="action" value="update-tour" />
								<input type="hidden" name="id_entrepot" id="id_entrepot" value="<?php echo $entrepot->id_entrepot; ?>" />
								<input type="hidden" name="num_planning" id="num_planning" value="<?php echo $numplan; ?>" />
								<input type="hidden" name="date_rdv" id="date_rdv" value="<?php echo $dtrdv; ?>" />
								<input type="hidden" name="rdv_start" id="rdv_start" value="<?php echo $hrstart; ?>" />
								<input type="hidden" name="rdv_end" id="rdv_end" value="<?php echo $hrend; ?>" />
								<input type="hidden" name="duration" id="duration" value="<?php echo $duration; ?>" />
								<input type="hidden" name="id_contact" id="id_contact" value="<?php echo $contact->id_contact; ?>" />
								<?php if (!isset($_GET['view'])) { ?>
									<button class="btn btn-sm btn-primary" type="submit" id="btupdatetour"><i class="fa fa-user"></i> Valider le rendez-vous</button>
									<a href="contact.php?id_contact=<?php echo $contact->id_contact; ?>" class="btn btn-sm btn-warning"><i class="fa fa-repeat"></i> Annuler</a>
								<?php } else { ?>
									<a href="#" class="btn btn-sm btn-warning" onclick="window.history.back();"><i class="fa fa-repeat"></i> Retour</a>
								<?php } ?>
							</div>
						</form>

					</div>
				</div>
			</div>
		</div>
		<div class="col-lg-4">
			<div class="block">
				<div class="block-title">
					<h2><strong>Carte</strong> de la tournée</h2>
				</div>
				<div class="row">
					<div class="col-md-12 col-lg-12">
						<div class="row push">
							<div class="form-group">
								<div class="col-md-12">
									<div id="map"></div>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
	<!-- END eShop Overview Block -->
</div>
<!-- END Page Content -->

<?php include 'inc/page_footer.php'; ?>

<!-- Remember to include excanvas for IE8 chart support -->
<!--[if IE 8]><script src="js/helpers/excanvas.min.js"></script><![endif]-->

<?php include 'inc/template_scripts.php'; ?>
<script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyArKK0Hvu_FtKgyvHkUUjyKOMK2Hmt9zY0&libraries=geometry,places"></script>
<!-- Load and execute javascript code used only in this page -->
<script>
	function dynamicSort(property) {
		var sortOrder = 1;
		if (property[0] === "-") {
			sortOrder = -1;
			property = property.substr(1);
		}
		return function(a, b) {
			var result = (a[property] < b[property]) ? -1 : (a[property] > b[property]) ? 1 : 0;
			return result * sortOrder;
		}
	}


	$(document).ready(function() {
		var colors = ['#9999ff', '#ff0fff', '#ff9999', '#99ff99', '#fff888', '#fff000'];
		var markerstart;
		var markerdrv = [];
		var polydrv = [];
		var myLatlng = new google.maps.LatLng(<?php echo $steps[0]['geolatdep'] . ',' . $steps[0]['geolngdep']; ?>);
		var mapOptions = {
			zoom: 8,
			center: myLatlng,
			mapTypeId: google.maps.MapTypeId.ROADMAP
		};
		var map = new google.maps.Map(document.getElementById("map"), mapOptions);
		var latlngbounds = new google.maps.LatLngBounds();

		<?php
		$i = -1;
		foreach ($steps as $step) {
			$i++;
			if ($i == 0) {
				?>
				myLatlng = new google.maps.LatLng(<?php echo $step['geolatdep'] . ',' . $step['geolngdep']; ?>);
				markerstart = new google.maps.Marker({
					position: myLatlng,
					map: map,
					icon: {
						path: fontawesome.markers.HOME,
						scale: 0.5,
						strokeWeight: 0.5,
						strokeColor: 'black',
						strokeOpacity: 1,
						fillColor: '#e74c3c',
						fillOpacity: 1,
						anchor: new google.maps.Point(20,-15)
					},					
					//icon: 'img/bus.png',
					title: 'Départ entrepot'
				});
				markerstart.setMap(map);
			<?php
		}
		?>
			myLatlng = new google.maps.LatLng(<?php echo $step['geolatdes'] . ',' . $step['geolngdes']; ?>);
			markerdrv[<?php echo $i; ?>] = new google.maps.Marker({
				position: myLatlng,
				map: map,
				icon: {
					path: fontawesome.markers.CHILD,
					scale: 0.5,
					strokeWeight: 0.5,
					strokeColor: 'black',
					strokeOpacity: 1,
					fillColor: '#1bbae1',
					fillOpacity: 1,
					anchor: new google.maps.Point(20,-15)
				},
				//icon: 'img/man32.png',
				//animation: google.maps.Animation.BOUNCE,
				title: 'RDV avec <?php echo $step['cliname']; ?>'
			});
			markerdrv[<?php echo $i; ?>].setMap(map);
			latlngbounds.extend(myLatlng);

			var latlngs = new google.maps.MVCArray();
			polydrv[<?php echo $i; ?>] = new google.maps.Polyline({
				map: map,
				strokeColor: colors[<?php echo $i; ?>],
				strokeOpacity: 1.0,
				strokeWeight: 4,
				path: latlngs
			});

			var decodedPath = google.maps.geometry.encoding.decodePath('<?php echo str_replace('\\', '\\\\', $step['gmap']->routes[0]->overview_polyline->points); ?>');
			for (var i = 0; i < decodedPath.length; ++i) {
				var newLocation = new google.maps.LatLng(decodedPath[i].lat().toFixed(5), decodedPath[i].lng().toFixed(5));
				latlngs.push(newLocation);
				polydrv[<?php echo $i; ?>].setPath(latlngs);
				latlngbounds.extend(decodedPath[i]);
			}


			map.setCenter(latlngbounds.getCenter());
			map.fitBounds(latlngbounds);
		<?php
	}
	?>

		$('#frmtour').submit(function() {
			if (parseInt($('#creneau_start').val()) > parseInt($('#creneau_end').val())) {
				$.bootstrapGrowl('<h4>Erreur!</h4> <p>Le créneau de début est supérieur au créneau de fin !</p>', {
						type: 'danger',
						delay: 2500,
						allow_dismiss: true
					});
				return false;
			}

			HoldOn.open();
			jQuery(this).ajaxSubmit({
				dataType: 'json',
				data:{crnstart:$('#creneau_start').val(), crnend:$('#creneau_end').val(), status: $('#status_rdv').val(), typerdv: $('#typerdv').val()},
				//data:{steps: <?php echo json_encode($steps); ?>},
				success: function(resp) {
					if (resp.code == 'SUCCESS') {
						<?php if (isset($_GET['id_contact'])) { ?>
							location.href = 'contact.php?id_contact=<?php echo $_GET['id_contact']; ?>';
						<?php } else { ?>	
							window.history.back();
						<?php } ?>
					} else
					if (resp.code == 'ERROR')
						alert(resp.message);

					HoldOn.close();
				},
				error: function() {
					console.log('NO');
					HoldOn.close();
				}
			});
			return false;
		});
	});
</script>

<?php include 'inc/template_end.php'; ?>