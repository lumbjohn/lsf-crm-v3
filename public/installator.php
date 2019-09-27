<?php include 'inc/config.php'; ?>
<?php
	if (!isset($_GET['id_installator']))
		header('Location: installators.php');
	if ($_GET['id_installator'] == '0')
		$curusr = false;
	else {
		$curusr = Installator::findOne(array('id_installator' => $_GET['id_installator']));	
		if (!$curusr) 
			header('Location: installators.php');
	}
?>
<?php include 'inc/template_start.php'; ?>
<?php include 'inc/page_head.php'; ?> 

<!-- Page content -->
<div id="page-content" class="page-installator"> 
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
                    <h2><strong>Informations</strong> installateur</h2>
					<?php if ($curusr) { ?>
						<div class="block-options pull-right">
							<span class="label label-primary">ID: <?php echo $curusr->id_installator; ?></span>
						</div>
					<?php } ?>
                </div>
                <!-- END Normal Form Title -->

                <!-- Normal Form Content -->
                <form onsubmit="return false;" class="form-bordered form-horizontal" method="post" action="crmajax.php" id="frminfousr">					
					<div class="form-group">
                        <label for="first_name_ins" class="col-md-3 control-label">Nom</label>
                        <div class="col-md-9">
							<input type="text" placeholder="Saisissez le nom du contact installateur..." class="form-control" name="first_name_ins" id="first_name_ins" value="<?php echo $curusr->first_name_ins; ?>">
							<span class="help-block">Nom du contact installateur</span>
						</div>
                    </div>
					<div class="form-group">
                        <label for="last_name_ins" class="col-md-3 control-label">Prénom</label>
                        <div class="col-md-9">
							<input type="text" placeholder="Saisissez le prénom du contact installateur..." class="form-control" name="last_name_ins" id="last_name_ins" value="<?php echo $curusr->last_name_ins; ?>">
							<span class="help-block">Prénom du contact installateur</span>
						</div>
                    </div>
                    <div class="form-group">
                        <label for="installator_name" class="col-md-3 control-label">Raison sociale</label>
                        <div class="col-md-9">
							<input type="text" placeholder="Saisissez le nom de l'installateur..." class="form-control" name="installator_name" id="installator_name" value="<?php echo $curusr->installator_name; ?>" required>
						</div>
                    </div>
                    <div class="form-group">
                        <label for="siret_ins" class="col-md-3 control-label">SIRET</label>
                        <div class="col-md-9">
							<input type="text" placeholder="Saisissez le numero SIRET" class="form-control" name="siret_ins" id="siret_ins" value="<?php echo $curusr->siret_ins; ?>">
						</div>
                    </div>
                    <div class="form-group">
                        <label for="adr1" class="col-md-3 control-label">Adresse</label>
                        <div class="col-md-9">
							<input type="text" placeholder="Saisissez l'adresse..." class="form-control" name="adr1" id="adr1" value="<?php echo $curusr->adr1; ?>" required>
						</div>
                    </div>
                    <div class="form-group">
                        <label for="adr2" class="col-md-3 control-label">Compl. adresse</label>
                        <div class="col-md-9">
							<input type="text" placeholder="Complément d'adresse..." class="form-control" name="adr2" id="adr2" value="<?php echo $curusr->adr2; ?>">
						</div>
                    </div>
                    <div class="form-group">
                        <label for="post_code" class="col-md-3 control-label">Code postal</label>
                        <div class="col-md-6">
							<input type="text" placeholder="Saisissez le code postal..." class="form-control" name="post_code" id="post_code" value="<?php echo $curusr->post_code; ?>" required>
						</div>
                    </div>
                    <div class="form-group">
                        <label for="city" class="col-md-3 control-label">Ville</label>
                        <div class="col-md-9">
							<input type="text" placeholder="Saisissez la ville..." class="form-control" name="city" id="city" value="<?php echo $curusr->city; ?>" required>
						</div>
                    </div>
                    <div class="form-group">
                        <label for="tel" class="col-md-3 control-label">Téléphone</label>
                        <div class="col-md-9">
							<input type="text" placeholder="Saisissez le téléphone..." class="form-control" name="tel1" id="tel1" value="<?php echo $curusr->tel1; ?>" required>
						</div>
                    </div>
                    <div class="form-group">
                        <label for="tel" class="col-md-3 control-label">Portable</label>
                        <div class="col-md-9">
							<input type="text" placeholder="Saisissez le portable..." class="form-control" name="tel2" id="tel2" value="<?php echo $curusr->tel2; ?>">
							<span class="help-block">Zone utilisé pour la réception de SMS (veuillez renseigner au format +33612345678)</span>
						</div>
                    </div>
					<div class="form-group">
						<label for="email" class="col-md-3 control-label">Email</label>
						<div class="col-md-9">
							<input type="email" placeholder="Enter the user email.." class="form-control" name="email" id="email" value="<?php echo $curusr->email; ?>">
						</div>
					</div>
					<div class="form-group">
						<label for="type_msg" class="col-md-3 control-label">Type d'envoi des messages d'attributions</label>
						<div class="col-md-9">
							<select class="form-control" name="type_msg" id="type_msg">
								<option value="0" <?php echo $curusr->type_msg == '0' ? 'selected="selected"' : ''; ?>>Google Agenda</option>
								<option value="1" <?php echo $curusr->type_msg == '1' ? 'selected="selected"' : ''; ?>>SMS</option>
								<option value="2" <?php echo $curusr->type_msg == '2' ? 'selected="selected"' : ''; ?>>Google Agenda & SMS</option>
							</select>
						</div>
					</div>
					<div class="form-group">
						<label for="agenda_id" class="col-md-3 control-label">ID Google Agenda</label>
						<div class="col-md-9">
							<input type="text" placeholder="Saisissez l'ID de Google Agenda.." class="form-control" name="agenda_id" id="agenda_id" value="<?php echo $curusr->agenda_id; ?>">
						</div>
					</div>
                    <div class="form-group">
                        <label for="date_update" class="col-md-3 control-label">Date modif.</label>
                        <div class="col-md-6">
							<input type="text" class="form-control" name="date_update" id="date_update" disabled="" value="<?php echo $curusr->date_update > 0 ? date('d/m/Y H:i', strtotime($curusr->date_update)) : ''; ?>">
						</div>
                    </div>
                    <div class="form-group">
                        <label for="date_create" class="col-md-3 control-label">Date création</label>
                        <div class="col-md-6">
							<input type="text" class="form-control" name="date_create" id="date_create" disabled="" value="<?php echo $curusr->date_create > 0 ? date('d/m/Y H:i', strtotime($curusr->date_create)) : ''; ?>">
						</div>
                    </div>
					
					<div class="form-group form-actions">
						<button class="btn btn-sm btn-primary" type="submit" id="btupdateusr"><i class="fa fa-user"></i> Valider</button>
						<button class="btn btn-sm btn-warning" type="reset"><i class="fa fa-repeat"></i> Annuler</button>
					</div>
					<input type="hidden" name="geolat" id="geolat" value="<?php echo $curusr->geolat; ?>" />
					<input type="hidden" name="geolng" id="geolng" value="<?php echo $curusr->geolng; ?>" />
					<input type="hidden" name="action" id="action" value="update-installator" />
					<input type="hidden" name="id_installator" id="id_installator" value="<?php echo $_GET['id_installator']; ?>" />
                </form>
                <!-- END Normal Form Content -->
            </div>
		</div>
		
		<?php if ($curusr) { ?>
		<div class="col-lg-4">
			<div class="block">
				<div class="block-title">            
					<h2><strong>Localisation</strong> installateur</h2>
				</div>
				<div class="row">
					<div class="col-md-12 col-lg-12">
						<div class="row push">
							<div id="map"></div>
						</div>
					</div>
				</div>
			</div>		
		</div>		
		<div class="col-lg-4">
			<div class="block">
				<div class="block-title">            
					<h2><strong>Commentaires</strong> installateur</h2>
				</div>
				<div class="row">
					<div class="col-md-12 col-lg-12">
						<div class="row push">
							<div class="form-group">
								<div class="col-md-12">
									<textarea placeholder="Commentaire général sur l'installateur..." class="form-control" name="comment" id="comment"><?php echo $curusr->comment; ?></textarea>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>		
		</div>
		<div class="col-lg-8">
			<div class="block">
				<div class="block-title">            
					<h2><strong>Planning</strong> de l'installateur</h2>
					<!-- <div class="block-options pull-right">
                        <div class="btn-group btn-group-sm">
							<a href="#" id="btnewplan" class="btn btn-xs btn-info">Ajouter des disponibilités planning</a>
						</div>
                    </div>-->
				</div>
				<!-- END eShop Overview Title -->

				<!-- eShop Overview Content -->
				<div class="row">
					<div class="col-md-12 col-lg-12">
						<div class="row push">
							<div id="calendar"></div>
						</div>
					</div>
				</div>
				<!-- END eShop Overview Content -->
			</div>
		</div>
		<?php } ?>
    </div>
    <!-- END eShop Overview Block -->

</div>
<!-- END Page Content -->

<?php /*
<!-- Modal generate planning -->
<div id="modal-genplan" class="modal fade" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <!-- Modal Header -->
            <div class="modal-header text-center">
                <h2 class="modal-title"><i class="fa fa-cogs"></i> Générer des disponibilités planning</h2>
            </div>
            <!-- END Modal Header -->

            <!-- Modal Body -->
            <div class="modal-body">
                <form action="index.php" method="post" enctype="multipart/form-data" class="form-horizontal form-bordered" id="frmnewplan" onsubmit="return false;">
                    <fieldset>
                        <div class="form-group">
                            <label class="col-md-3 control-label" for="startdt">Dates</label>
                            <div class="col-md-9">
                                <div class="input-group input-daterange" data-date-format="dd/mm/yyyy">
                                    <input id="startdt" name="startdt" class="form-control text-center" placeholder="Du" type="text">
                                    <span class="input-group-addon"><i class="fa fa-angle-right"></i></span>
                                    <input id="enddt" name="enddt" class="form-control text-center" placeholder="au" type="text">
                                </div>
                            </div>
                        </div>
						<div class="form-group">
                            <label class="col-md-3 control-label" for="geotype">Localisation installateur</label>
                            <div class="col-md-9">
								<select id="geotype" class="form-control">
									<option value="0">Localisation par défaut</option>
									<option value="1">Saisir la localisation</option>
								</select>
								<input type="text" placeholder="Saisissez l'adresse..." class="form-control" name="adrplanins" id="adrplanins" value="" style="display:none">
								<input type="hidden" id="geolatplan" name="geolatplan" value="0" />
								<input type="hidden" id="geolngplan" name="geolngplan" value="0" />
                            </div>
                        </div>
						<div class="form-group">
							<table class="table table-striped table-vcenter table-condensed table-hover">
								<tr>
									<th>&nbsp;</th>
									<?php
										$rdvrange = Setting::getRDVRange();
										foreach($rdvrange as $range) {
											?>											
											<th><?php echo date('H:i', strtotime($range['hour_start'])).' - '.date('H:i', strtotime($range['hour_end'])); ?></th>
											<?php
										}
									?>
								</tr>
							<?php for($i=1;$i<=7;$i++) { $v = $i == 7 ? 0 : $i; ?>
								<tr>
									<td><strong><?php echo Setting::$days[$v]; ?></strong></td>
										<?php
											$j=0;
											foreach($rdvrange as $range) {
												$j++;	
												?>											
												<td class="text-center"><label class="checkbox" for="day_<?php echo $v.'_'.$j; ?>">
													<input type="checkbox" id="day_<?php echo $v.'_'.$j; ?>" name="day_<?php echo $v.'_'.$j; ?>" value="1"> 												
												</label></td>
												<?php
											}
										?>
										<!-- 
										<select id="day_<?php echo $v; ?>" name="day_<?php echo $v; ?>" class="form-control">
											<option value="0">Non</option>
											<option value="1">Matin</option>
											<option value="2">Aprés midi</option>
											<option value="3">Matin et Aprés midi</option>
										</select>
										-->
								</tr>
							<?php } ?>
							</table>
						</div>
                    </fieldset>
                    <div class="form-group form-actions">
                        <div class="col-xs-12 text-right">
                            <button type="button" class="btn btn-sm btn-default" data-dismiss="modal">Close</button>
                            <button type="submit" class="btn btn-sm btn-primary">Générer</button>
                        </div>
                    </div>
                </form>
            </div>
            <!-- END Modal Body -->
        </div>
    </div>
</div>
<!-- END generate planning -->
*/ ?>

<?php include 'inc/page_footer.php'; ?>

<!-- Remember to include excanvas for IE8 chart support -->
<!--[if IE 8]><script src="js/helpers/excanvas.min.js"></script><![endif]-->

<?php include 'inc/template_scripts.php'; ?>
<script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyArKK0Hvu_FtKgyvHkUUjyKOMK2Hmt9zY0&libraries=places"></script>
<!-- Load and execute javascript code used only in this page -->
<script>
	$(document).ready(function() {
		$('#frminfousr').submit(function() { 
			
			$('#btupdateusr').prop( "disabled", true );
			jQuery(this).ajaxSubmit({
				dataType:'json',
				data:{'comment':$('#comment').length > 0 ? $('#comment').val() : ''},
				success : function (resp) {	
					if (resp.code == 'SUCCESS') {						
						if ($('#id_installator').val() == '0') {
							location.href = 'installator.php?id_installator='+resp.id_installator;
						}
						else
							$.bootstrapGrowl('<h4>Confirmation!</h4> <p>Modification effectuée</p>', {
								type: 'success',
								delay: 2500,
								allow_dismiss: true
							});
					}
					else
					if (resp.code == 'ERROR')
						$.bootstrapGrowl('<h4>Erreur!</h4> <p>' + resp.message + '</p>', {
							type: 'danger',
							delay: 2500,
							allow_dismiss: true
						});
					$('#btupdateusr').prop( "disabled", false);	
				},
				error : function() {
					console.log('NO');
					$('#btupdateusr').prop( "disabled", false);	
				}
			}); 
			return false; 
		});
		
		/*
		$('#btnewplan').click(function() {
			$('#modal-genplan').modal();
		});
		
		$('#frmnewplan').submit(function() {
			HoldOn.open();
			$.post('crmajax.php', {action:'generate-plan', idi:$('#id_installator').val(), data:$('#frmnewplan').serialize()}, function(resp) {
				HoldOn.close();
				if (resp.code == 'SUCCESS') {
					$('#modal-genplan').modal('hide');
					window.location.reload();
				}
				else
					alert(resp.message);
			}, 'json');		
			
			return false;
		});
		*/
		/*$('#btchangeview').click(function() {
			if ($(this).attr('data-type') == '1') {
				$('#calendar').fullCalendar( 'changeView', 'listWeek');
				$(this).attr('data-type', '0');
				$(this).find('i').removeClass('fa-compress').addClass('fa-expand');
				}
			else {
				$('#calendar').fullCalendar( 'changeView', 'agendaWeek');
				$(this).attr('data-type', '1');
				$(this).find('i').removeClass('fa-expand').addClass('fa-compress');				
			}	
			return false;
		});*/
		
		//var options = {types: ['geocode']};
		var inp = document.getElementById('adr1');
		var autoComplete = new google.maps.places.Autocomplete(inp); //, options);
		google.maps.event.addListener(autoComplete, 'place_changed', function() {
			var place = autoComplete.getPlace();

			if (!place.geometry) {
			  return;
			}
			
			var numst = '';
			var adr = '';
			var cp = '';
			var ville = '';
			var pays = '';
			for(var d in place.address_components) {
				for(var d2 in place.address_components[d].types)
					if (place.address_components[d].types[d2] == 'locality')
						ville = place.address_components[d].short_name;
					else
					if (place.address_components[d].types[d2] == 'country')
						pays = place.address_components[d].long_name;
					else
					if (place.address_components[d].types[d2] == 'postal_code')
						cp = place.address_components[d].long_name;
					else
					if (place.address_components[d].types[d2] == 'route')
						adr = place.address_components[d].long_name;
					else
					if (place.address_components[d].types[d2] == 'street_number')
						numst = place.address_components[d].long_name;
			}

			
			$('#city').val(ville);
			$('#post_code').val(cp);
			$('#adr1').val(numst+' '+adr);
			$('#geolat').val(place.geometry.location.lat());
			$('#geolng').val(place.geometry.location.lng());
			console.log(place);
		});	
		
		<?php if ($curusr) { ?>
			var myLatlng = new google.maps.LatLng($('#geolat').val(), $('#geolng').val());
			var mapOptions = {
			  zoom: 15,
			  center: myLatlng,
			  mapTypeId: google.maps.MapTypeId.ROADMAP 
			};
			var map = new google.maps.Map(document.getElementById("map"), mapOptions);
				
			var marker = new google.maps.Marker({
				position: myLatlng,
				center: myLatlng,
				map: map,
				title: 'Adresse de '+$('#installator_name').val()
			});
			marker.setMap(map);

			<?php 			
				$plans = Planning::getByInstallator($curusr->id_installator);
				$evts = '';
				$minDt = 0;
				$maxDt = 0;
				if ($plans) {
					foreach($plans as $plan) {
						$strevents = '';
						$evts .= '{
							title: \''.$strevents.'\',
							start: \''.date('Y-m-d\TH:i', strtotime(Tool::addTimeStr($plan['hour_start']), strtotime($plan['date_planning']))).'\',
							end: \''.date('Y-m-d\TH:i', strtotime(Tool::addTimeStr($plan['hour_end']), strtotime($plan['date_planning']))).'\',
							allDay: false,
							color: \'#ffcccc\', 
							id:\''.$plan['id_planning'].'\',
							rendering: \'background\'
						},';
						
						if ($plan['hour_start'] < $minDt || $minDt == 0)
							$minDt = $plan['hour_start'];
							
						if ($plan['hour_end'] > $maxDt || $maxDt == 0)
							$maxDt = $plan['hour_end'];					
					}
				}
				$rdvs = RDV::getByInstallator($curusr->id_installator);
				if ($rdvs) {
					foreach($rdvs as $rdv) {
						$strevents = '<a href="contact.php?id_contact='.$rdv['id_contact'].'" data-toggle="tooltip" title="'.$rdv['adr1'].' '.$rdv['post_code'].' '.$rdv['city'].'">RDV avec '.$rdv['first_name'].' '.$rdv['last_name'].'</a> (<a href="tour.php?id_planning='.$rdv['id_planning'].'&view=1">Voir la tournée</a>)';
						$evts .= '{
							title: \''.addslashes($strevents).'\',
							start: \''.date('Y-m-d\TH:i', strtotime(Tool::addTimeStr($rdv['rdv_start']), strtotime($rdv['date_rdv']))).'\',
							end: \''.date('Y-m-d\TH:i', strtotime(Tool::addTimeStr($rdv['rdv_end']), strtotime($rdv['date_rdv']))).'\',
							allDay: false,
							color: \'#cccdff\', 
							id:\''.$rdv['id_rdv'].'\',
						},';
					}
				}
			?>
			/*
			$('#geotype').change(function() {
				if ($(this).val() == '0')
					$('#adrplanins').hide();
				else
					$('#adrplanins').show();
			});
			
			var inpplan = document.getElementById('adrplanins');
			var autoCompletePlan = new google.maps.places.Autocomplete(inpplan); //, options);
			google.maps.event.addListener(autoCompletePlan, 'place_changed', function() {
				var place = autoCompletePlan.getPlace();

				if (!place.geometry) {
				  return;
				}
				
				$('#geolatplan').val(place.geometry.location.lat());
				$('#geolngplan').val(place.geometry.location.lng());
			});	
			*/

			$('#calendar').fullCalendar({
				schedulerLicenseKey: 'CC-Attribution-NonCommercial-NoDerivatives',	
				header: {
					left: 'prev,next today',
					center: 'title',
					right: 'listWeek,month,agendaWeek'
				},
				locale:'fr',
				dayNames:['Dimanche', 'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi'],
                editable: false,
                droppable: false,
				defaultView: 'agendaWeek',
				allDaySlot: false,
				eventLimit: true,
				height:600,
				slotDuration:{minutes:30},
				minTime:'07:00:00',
				maxTime:'21:00:00',
				eventRender: function(event, element) {                                          
					element.find('div.fc-title').html(element.find('div.fc-title').text());
					element.find('span.fc-title').html(element.find('span.fc-title').text());
					element.find('.fc-list-item-title').html(element.find('.fc-list-item-title').text());
					$("body").tooltip({ selector: '[data-toggle="tooltip"]' });
				},
				events: function(fstart, fend, timezone, callback) {
					HoldOn.open();
					$.post('crmajax.php', {action:'get-planning', start:fstart.format(), end:fend.format(), id_installator: <?php echo (int)$curusr->id_installator; ?>, id_entrepot:0}, function(resp) {
						HoldOn.close();
						if (resp.code == "SUCCESS") {
							callback(resp.events);
						}
						else
							$.bootstrapGrowl('<h4>Erreur!</h4> <p>' + resp.message + '</p>', {
								type: 'danger',
								delay: 2500,
								allow_dismiss: true
							});
					}, 'json');
				},
				refetchResourcesOnNavigate: true,
				resources: function(callback, fstart, fend, timezone) {
					HoldOn.open();
					$.post('crmajax.php', {action:'get-resources-planning', start:fstart.format(), end:fend.format(), id_installator: <?php echo $curusr->id_installator; ?>, id_entrepot:0}, function(resp) {
						HoldOn.close();
						if (resp.code == "SUCCESS") {
							callback(resp.resources);
						}
						else
							$.bootstrapGrowl('<h4>Erreur!</h4> <p>' + resp.message + '</p>', {
								type: 'danger',
								delay: 2500,
								allow_dismiss: true
							});
					}, 'json');
				},
				resourceRender: function(resourceObj, labelTds, bodyTds) {
					labelTds.find('.fc-cell-content').html(resourceObj.title);
				}
			});
		<?php } ?>
	});
</script>

<?php include 'inc/template_end.php'; ?>