<?php include 'inc/config.php'; ?>
<?php 
	if (isset($_GET['strflds']) && is_array($_GET['strflds']))
		die(json_encode(array('html' => GridManager::getGrid('iso_activity', implode(',', $_GET['strflds']), (int)$_GET['dts'] > 0 ? $_GET['dts'].'|'.$_GET['dte'] : ''))));
	else
		$outactivity = GridManager::getGrid('iso_activity', 'name_statuscont');
	
	$steps = array();
	$entrepots = Entrepot::getAll();
	$rdvs = RDV::getWithSteps('r.date_rdv = DATE(NOW())', $steps);	

	$arrwh = array('cm.type_comment' => '1');
	if (CrmUser::isTelepro($currentUser))
		$arrwh['c.id_crmuser'] = $currentUser->id_crmuser;
	else
	if (CrmUser::isManager($currentUser))
		$arrwh['u.id_team'] = $currentUser->id_team;
	else
	if (CrmUser::isConfirmateur($currentUser))
		$arrwh['c.id_crmuser_conf'] = $currentUser->id_crmuser;	

	$recalls = Comment::getRecall($arrwh);
	$evts  = '';
	if ($recalls) {
		foreach($recalls as $recall) {
			$strevents = '<a href="contact.php?id_contact='.$recall['id_contact'].'" data-toggle="tooltip" title="'.addslashes ($recall['text_comment']).' - '.addslashes($recall['first_name'].' '.$recall['last_name']).' '.(!CrmUser::isTelepro($currentUser) ? '(Conseiller : '.$recall['user_name'].')' : '').'">'.addslashes($recall['text_comment']).' - '.addslashes($recall['first_name'].' '.$recall['last_name']).'</a>';
			$evts .= '{
				title: \''.$strevents.'\',
				start: \''.date('Y-m-d\TH:i', strtotime($recall['date_recall'])).'\',
				allDay: false,
				color: \'#cccdff\', 
				id:\''.$recall['id_comment'].'\',
			},';
		}
	}
?>
<?php include 'inc/template_start.php'; ?>
<?php include 'inc/page_head.php'; ?> 


<!-- Page content -->
<div id="page-content" class="page-dashboard"> 
    <!-- eCommerce Dashboard Header --> 
    <div class="content-header">
        <?php include('inc/menutop.php'); ?>
    </div>
	<!-- END eCommerce Dashboard Header -->
	
	<?php if (CrmUser::isAdmin($currentUser)) { ?>
		<div class="block full row">
				<div class="block-title">
					<h2><strong>Statistique des totaux</strong> de l'activité</h2>
					<div class="block-options pull-right">
						Groupé par :
						<select multiple="multiple" id="selcross" class="select-chosen2">
							<option value="name_statuscont" selected="selected">Statut</option>
							<option value="name_statuscontconf">Statut Conf.</option>
							<option value="campain">Campagne</option>
							<option value="source">Source</option>
							<option value="dept">Département</option>
							<option value="name_team">Team</option>
							<option value="u.user_name">Agent</option>
							<option value="u2.user_name">Confirmateur</option>
							<option value="installator_name">Installateur</option>
							<option value="type_chauf">Type de chauffage</option>
							<option value="type_preca">Type de précarité</option>
							<option value="zone">Zone Géo</option>
						</select>
						<div class="btn btn-default" id="dtrange">
							<i class="fa fa-calendar"></i>
							<span>Selectionner la date</span>
							<b class="caret"></b>
						</div>
						<a class="btn btn-info" href="#" id="nboktot">OK</a>						
					</div>
				</div>
				<div id="blcact"><?php echo $outactivity; ?></div>
		</div>
	<?php } ?>
	
	<div class="block full row">
			<div class="block-title">
				<h2><i class="fa fa-calendar"></i> <strong>Calendrier</strong> de rappel clients</h2>
			</div>
			<div id="cal"></div>
	</div>


	<div class="block full row">
		<div class="col-lg-3">
			<div class="block">
                <!-- Normal Form Title -->
                <div class="block-title">
                    <h2><strong>Tournées</strong> du <span id="dttourmap"><?php echo date('d/m/Y'); ?> </span></h2>
					<div class="block-options pull-right">
						<div class="btn-group btn-group-sm">
							<a class="btn btn-xs btn-danger" href="planning.php?dt=<?php echo time(); ?>" id="nblate"><?php echo $rdvs->num_rows; ?> RDV</a>
						</div>
					</div>
                </div>
                <!-- END Normal Form Title -->

                <table class="table table-striped table-vcenter" id="tbvac">
                    <tbody>
						<?php if (!$rdvs || $rdvs->num_rows == 0) { ?>
							<tr>
								<td>
								   <center>Pas de tournées ... </center>
								</td>								
							</tr>
						<?php 
						} else {
							$colors = array('#9999ff', '#ff0fff', '#fff000', '#ff9999', '#99ff99', '#fff888', '#c9d9ff', '#af05ff', '#9ff700', '#df9d93', '#895f9f', '#ffc84e', '#c9cff', '#af0af2', '#cff406', '#5f9a9f', '#a9ef94', '#8ff58d');										
							$i = -1;	
							foreach($rdvs as $rdv) {
								$i++;
								?>
									<tr>
										<td>											
											<a href="tour.php?id_planning=<?php echo $rdv['id_planning']; ?>&view=1&id_rdv=<?php echo $rdv['id_rdv']; ?>" class="label label-info">Tournée #<?php echo $rdv['id_planning']; ?></a> 
											<span class="label label-success" style="background-color:<?php echo $colors[$i]; ?>;">RDV #<?php echo $rdv['id_rdv']; ?></span><br>
											Entrepot : <a href="entrepot.php?id_entrepot=<?php echo $rdv['id_entrepot']; ?>"><?php echo $rdv['entrepot_name']; ?></a><br>
											Installateur : <?php echo (int)$rdv['id_instalaltor'] > 0 ? '<a href="installator.php?id_installator='.$rdv['id_installator'].'">'.$rdv['installator_name'].'</a>' : 'NON RENSEIGNÉ'; ?><br>
											Client : <a href="contact.php?id_contact=<?php echo $rdv['id_contact']; ?>"><?php echo $rdv['first_name'].' '.$rdv['last_name']; ?></a><br>
											Début : <?php echo date('H:i', strtotime($rdv['rdv_start'])); ?><br>
											Fin : <?php echo date('H:i', strtotime($rdv['rdv_end'])); ?><br>
										</td>
									</tr>
								<?php
							}
						}
						?>
                        
                    </tbody>
                </table>
            </div>
		</div>
		<div class="col-lg-9">
			<div class="block">
                <div class="block-title">
                    <h2><strong>Filtres:</strong></h2>
					<span>Date: </span>
					<input type="text" placeholder="dd/mm/yyyy" data-date-format="dd/mm/yyyy" class="input-datepicker" name="txtdate" id="txtdate" value="<?php echo date('d/m/Y'); ?>">
					<select id="selents">
						<option value="0">Tous les entrepots</option>
						<?php
							foreach($entrepots as $entrepot) {
								echo '<option value="'.$entrepot['id_entrepot'].'">'.$entrepot['entrepot_name'].'</option>';
							}
						?>
					</select>
					<a href="#" class="btn btn-alt btn-sm btn-info" id="btoktours"><i class="fa fa-refresh"></i> OK</a>
                </div>

				<div id="map2">...</div>
				<div id="txtmsg" style="position:absolute"></div>
			</div>
		</div>
    </div>
    <!-- eShop Overview Block -->
    <div class="block full row">
        <!-- eShop Overview Title -->		
		<div class="col-lg-3">
			<div class="block">
                <!-- Normal Form Title -->
                <div class="block-title">
                    <h2><strong>Informations</strong> globales</h2>
					<div class="block-options pull-right">
						<select id="selstpark">
							<option value="">Tous les statuts</option>
							<?php
								$sts = Setting::getAllStatus();
								if ($sts) {
									foreach($sts as $st) {
										echo '<option value="'.$st['id_statuscont'].'" '.($st['id_statuscont'] == 10 ? 'selected="selected"' : '').'>'.$st['name_statuscont'].'</option>';
									}
								}
							?>
						</select>
					</div>
                </div>
                <!-- END Normal Form Title -->
				<label id="infoloadpark" class="label label-danger">Chargement ...</label>
                <a class="widget widget-hover-effect2 themed-background-muted-light" href="contacts.php">
                    <div class="widget-simple">
                        <div class="widget-icon pull-right themed-background">
                            <i class="fa fa-child"></i>
                        </div>
                        <h4 class="text-left">
                            <strong id="nbct"></strong><br><small>Contacts au Total</small>
                        </h4>
                    </div>
                </a>
				
				<a class="widget widget-hover-effect2 themed-background-muted-light" href="installators.php">
                    <div class="widget-simple">
                        <div class="widget-icon pull-right themed-background-success">
                            <i class="fa fa-truck"></i>
                        </div>
                        <h4 class="text-left text-success">
                            <strong id="nbins"></strong><br><small>Installateurs au Total</small>
                        </h4>
                    </div>
				</a>				
			
				<a class="widget widget-hover-effect2 themed-background-muted-light" href="entrepots.php">
                    <div class="widget-simple">
                        <div class="widget-icon pull-right themed-background-danger">
                            <i class="fa fa-home"></i>
                        </div>
                        <h4 class="text-left text-danger">
                            <strong id="nbent"></strong><br><small>Entrepots au Total</small>
                        </h4>
                    </div>
                </a>								
            </div>
		</div>
		<div class="col-lg-9">
			<div id="map">...</div>
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
	$(document).ready(function() {
		

		$('#cal').fullCalendar({
			schedulerLicenseKey: 'CC-Attribution-NonCommercial-NoDerivatives',	
			header: {
				left: 'prev,next today',
				center: 'title',
				right: 'listWeek,month,agendaWeek,agendaDay'
			},
			locale:'fr',
			dayNames:['Dimanche', 'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi'],
			/*views: {
				listWeek: {columnFormat: 'dddd', buttonText: 'Semaine'},
				month: {buttonText: 'Mois'},
				listDay: { buttonText: 'Liste jour' },
				listWeek: { buttonText: 'Liste semaine' }
			},*/
			editable: false,
			droppable: false,
			defaultView: 'agendaWeek',
			allDaySlot: false,
			eventLimit: true,
			height:400,
			slotDuration:{minutes:60},
			/*defaultTimedEventDuration:'00:20:00',*/
			minTime:'06:00:00',
			maxTime:'22:00:00',
			eventRender: function(event, element) {                                          
				element.find('div.fc-title').html(element.find('div.fc-title').text());
				element.find('span.fc-title').html(element.find('span.fc-title').text());
				element.find('.fc-list-item-title').html(element.find('.fc-list-item-title').text());
				//element.find('div.fc-time').hide();
				//element.find('span.fc-time').hide();
				$("body").tooltip({ selector: '[data-toggle="tooltip"]', html:true, container: "body"});
			},
			events: [<?php echo $evts; ?>]
		});

		var DateRange = $('#dtrange');
        var DateRangeSpan = $('#dtrange span');

		var dtstart = 0;
		var dtend = 0;
        DateRange.daterangepicker({
			opens:'left',
            ranges: {
                'Aujourd\'hui': ['today', 'today'],
                'Hier': ['yesterday', 'yesterday'],
                '7 Derniers Jours': [Date.today().add({days: -6}), 'today'],
                '30 Derniers Jours': [Date.today().add({days: -29}), 'today'],
                'Ce Mois': [Date.today().moveToFirstDayOfMonth(), Date.today().moveToLastDayOfMonth()],
                'Dernier Month': [Date.today().moveToFirstDayOfMonth().add({months: -1}), Date.today().moveToFirstDayOfMonth().add({days: -1})]
            }
        },
        function(start, end) {
            DateRangeSpan.html(start.toString('dd/MM/yyyy') + ' - ' + end.toString('dd/MM/yyyy'));
			//$('#dtstcamp').val(start.toString('yyyy-MM-dd') + ' # ' + end.toString('yyyy-MM-dd'))
			dtstart = start.toString('yyyy-MM-dd');
			dtend = end.add(1).days().toString('yyyy-MM-dd');
			//loadRepartition();			
        });
		
			
		var mouse = {
			x: 0,
			y: 0
		};

		document.addEventListener('mousemove', function (e) {
			mouse.x = e.clientX || e.pageX;
			mouse.y = e.clientY || e.pageY
			//console.log(mouse.x+' '+mouse.y);
		}, false);		
		
		var myLatlng = new google.maps.LatLng(48.856614, 2.352222);  //paris
		var mapOptions = {
		  zoom: 8,
		  center: myLatlng,
		  mapTypeId: google.maps.MapTypeId.ROADMAP 
		};
		var map = new google.maps.Map(document.getElementById("map"), mapOptions);
		var map2 = new google.maps.Map(document.getElementById("map2"), mapOptions);
		var latlngbounds = new google.maps.LatLngBounds();
		var latlngbounds2 = new google.maps.LatLngBounds();
		var markersct = [];
		var markersins = [];
		
		var colors = ['#9999ff', '#ff0fff', '#fff000', '#ff9999', '#99ff99', '#fff888', '#c9d9ff', '#af05ff', '#9ff700', '#df9d93', '#895f9f', '#ffc84e', '#c9cff', '#af0af2', '#cff406', '#5f9a9f', '#a9ef94', '#8ff58d'];
		var markerstart = [];
		var markerdrv = [];
		var polydrv = [];
			
		var steps = <?php echo json_encode($steps); ?>;
		function loadRDVS() {
			var i = -1;
			for(var d in steps) {
				var step = steps[d];
				i++;
				
				if (i == 0 || step.is_first) {
					myLatlng = new google.maps.LatLng(step.geolatdep,step.geolngdep);
					markerstart[i] = new google.maps.Marker({
						position: myLatlng,
						map: map2,
						icon: {
							path: fontawesome.markers.HOME,
							scale: 0.5,
							strokeWeight: 0.5,
							strokeColor: 'black',
							strokeOpacity: 1,
							fillColor: '#e74c3c',
							fillOpacity: 1
						},
						label: { color: '#e74c3c', fontWeight: 'bold', fontSize: '12px', text: step.entname },
						//icon:'img/bus.png',
						title: 'Départ'
					});
					markerstart[i].setMap(map2);
				}

				myLatlng = new google.maps.LatLng(step.geolatdes, step.geolngdes);
				markerdrv[i] = new google.maps.Marker({
					position: myLatlng,
					map: map2,
					icon: {
						path: fontawesome.markers.CHILD,
						scale: 0.5,
						strokeWeight: 0.5,
						strokeColor: 'black',
						strokeOpacity: 1,
						fillColor: '#1bbae1',
						fillOpacity: 1
					},
					//icon:'img/man32.png',
					//animation: google.maps.Animation.BOUNCE,
					label:  { color: '#000', fontWeight: 'bold', fontSize: '16px', text: step.numplan },
					title: 'RDV avec '+step.cliname,
					url: 'contact.php?id_contact='+step.idc,
					info:'RDV avec '+step.cliname+'<br>'+step.infom2
				});
				markerdrv[i].setMap(map2);
				latlngbounds2.extend(myLatlng);
				google.maps.event.addListener(markerdrv[i], 'mouseover', function (event) {
					var scale = Math.pow(2, map2.getZoom());
					var nw = new google.maps.LatLng(map2.getBounds().getNorthEast().lat(),map2.getBounds().getSouthWest().lng());
					var worldCoordinateNW = map2.getProjection().fromLatLngToPoint(nw);
					var worldCoordinate = map2.getProjection().fromLatLngToPoint(this.getPosition());
					var pixelOffset = new google.maps.Point(
						Math.floor((worldCoordinate.x - worldCoordinateNW.x) * scale),
						Math.floor((worldCoordinate.y - worldCoordinateNW.y) * scale)
					);
					//console.log(pixelOffset);
					$('.tooltip').remove();
					$("#txtmsg").attr('title', this.info).attr('data-original-title', this.info).css("left", (pixelOffset.x + 45)  + "px").css("top", (pixelOffset.y + 30) + "px").tooltip({html:true}).tooltip('show');
				});
				google.maps.event.addListener(markerdrv[i], 'mouseout', function (event) {
					$("#txtmsg").tooltip('hide');
				});
				google.maps.event.addListener(markerdrv[i], 'click', function() {
					window.location.href = this.url;
				});				
				var latlngs = new google.maps.MVCArray();
				polydrv[i] = new google.maps.Polyline({
					map: map2,
					strokeColor: colors[i > colors.length-1 ? i-colors.length : i],
					strokeOpacity: 1.0,
					strokeWeight: 4,
					path: latlngs
				});
				if (step['gmap'] != null && step['gmap']['status'] != 'UNKNOWN_ERROR' && step['gmap']['status'] != 'INVALID_REQUEST' && step['gmap'].routes.length > 0) {
					var decodedPath = google.maps.geometry.encoding.decodePath(step['gmap'].routes[0].overview_polyline.points);
					for (var j = 0; j < decodedPath.length; ++j) {
						var newLocation = new google.maps.LatLng(decodedPath[j].lat().toFixed(5), decodedPath[j].lng().toFixed(5));
						latlngs.push(newLocation);
						polydrv[i].setPath(latlngs);
						latlngbounds2.extend(decodedPath[j]);
					}
				}
				
				map2.setCenter(latlngbounds2.getCenter());
				map2.fitBounds(latlngbounds2);
			}
		}
			
		
		function clearRDVS() {
			for(var d in markerstart) {
				markerstart[d].setMap(null);
			}
			for(var d in markerdrv) {
				markerdrv[d].setMap(null);
			}
			
			for(var d in polydrv) {
				polydrv[d].setMap(null);
			}
			
			latlngbounds2 = new google.maps.LatLngBounds();
		}
		
		
		loadRDVS();
		
			
		function loadRDVTb(rdvs) {
			var str = '';
			var i = -1;
			var oldent = '';
			var oldpan = '';
			for (var d in rdvs) {
				i++;
				var rdv= rdvs[d];
				str += '<tr><td '+(i > 0 && (rdv.num_planning != oldpan || rdv.id_entrepot != oldent) ? 'style="border-top:solid 2px #777;"' : '')+'>';
				str += '<a href="tour.php?view=1&id_rdv='+rdv.id_rdv+'"><span class="label label-success" style="background-color:'+colors[i > colors.length-1 ? i-colors.length : i]+';">RDV #'+rdv.id_rdv+'</span></a><br>';
				str += 'Entrepot : <a href="entrepot.php?id_entrepot='+rdv.id_entrepot+'">'+rdv.entrepot_name+' #'+rdv.num_planning+'</a><br>';
				str += 'Installateur : '+(rdv.id_installator > 0 ? '<a href="installator.php?id_installator='+rdv.id_installator+'">'+rdv.installator_name+'</a>' : 'NON RENSEIGNÉ')+'<br>';
				str += 'Client : <a href="contact.php?id_contact='+rdv.id_contact+'">'+rdv.first_name+' '+rdv.last_name+'</a><br>';
				str += 'Début : '+rdv.rdv_start+'<br>Fin : '+rdv.rdv_end+'<br>';
				str += '</td></tr>';

				oldent = rdv.id_entrepot;
				oldpan = rdv.num_planning;
			}
			$('#tbvac').html(str);
			$('#nblate').text((rdvs.length > 0 ? rdvs.length : '0')+' RDV');
			$('#nblate').attr('href', 'planning.php?dt='+($('#txtdate').datepicker('getUTCDate').getTime() / 1000));
			$('#dttourmap').text($('#txtdate').val());
		}
		
		$('#btoktours').click(function() {
			HoldOn.open();
			$.post('crmajax.php', {action:'get-map-rdvs', dt:$('#txtdate').val(), id_entrepot:$('#selents').val()}, function(resp) {
				HoldOn.close();
				if (resp.code == 'SUCCESS') {
					console.log(resp);
					loadRDVTb(resp.rdvs);
					steps = resp.steps;
					clearRDVS();
					loadRDVS();
				}
				else
					alert(resp.message);
			}, 'json');	

			return false;	
		});
		
		
		
		function clearPark() {
			for(var d in markersct) {
				markersct[d].setMap(null);
			}
			for(var d in markersins) {
				markersins[d].setMap(null);
			}
			latlngbounds = new google.maps.LatLngBounds();
		}
		
		function loadPark() {
			clearPark();
			markersct = [];
			markersins = [];
			markersent = [];
			$('#infoloadpark').show();
			$.post('crmajax.php', {action:'get-dash-park', idst:$('#selstpark').val()}, function(resp) {
				HoldOn.close();
				if (resp.code == 'SUCCESS') {				
					$('#infoloadpark').hide();					
					if (resp.contacts) {
						for(var d in resp.contacts) {
							$('#nbct').text(resp.contacts.length);
							var contact = resp.contacts[d];
							myLatlng = new google.maps.LatLng(contact.geolat, contact.geolng);
							var marker = new google.maps.Marker({
								position: myLatlng,
								map: map,
								icon: {
									path: fontawesome.markers.CHILD,
									scale: 0.5,
									strokeWeight: 0.5,
									strokeColor: 'black',
									strokeOpacity: 1,
									fillColor: '#1bbae1',
									fillOpacity: 1
								},
								url: 'contact.php?id_contact='+contact.id_contact,
								title: contact.first_name+' '+contact.last_name+' - '+contact.adr1+' '+contact.post_code+' '+contact.city
							});
							google.maps.event.addListener(marker, 'click', function() {
								window.location.href = this.url;
							});
							marker.setMap(map);
							markersct.push(marker);
							latlngbounds.extend(myLatlng);
						}
						var markerCluster = new MarkerClusterer(map, markersct, {imagePath: 'https://developers.google.com/maps/documentation/javascript/examples/markerclusterer/m'});
						map.setCenter(latlngbounds.getCenter());
						map.fitBounds(latlngbounds);
					}
					else
						$('#nbct').text('0');
					
					if (resp.installators) {
						$('#nbins').text(resp.installators.length);
						for(var d in resp.installators) {
							var installator = resp.installators[d];
							myLatlng = new google.maps.LatLng(installator.geolat, installator.geolng);
							var marker = new google.maps.Marker({
								position: myLatlng,
								map: map,
								icon: {
									path: fontawesome.markers.TRUCK,
									scale: 0.5,
									strokeWeight: 0.5,
									strokeColor: 'black',
									strokeOpacity: 1,
									fillColor: '#27ae60',
									fillOpacity: 1
								},
								url: 'installator.php?id_installator='+installator.id_installator,
								title: installator.installator_name+' - '+installator.adr1+' '+installator.post_code+' '+installator.city
							});
							google.maps.event.addListener(marker, 'click', function() {
								window.location.href = this.url;
							});
							marker.setMap(map);
							markersins.push(marker);
							latlngbounds.extend(myLatlng);
						}
						map.setCenter(latlngbounds.getCenter());
						map.fitBounds(latlngbounds);
					}
					else
						$('#nbins').text('0');

					if (resp.entrepots) {
						$('#nbent').text(resp.entrepots.length);
						for(var d in resp.entrepots) {
							var entrepot = resp.entrepots[d];
							myLatlng = new google.maps.LatLng(entrepot.geolat, entrepot.geolng);
							var marker = new google.maps.Marker({
								position: myLatlng,
								map: map,
								icon: {
									path: fontawesome.markers.HOME,
									scale: 0.5,
									strokeWeight: 0.5,
									strokeColor: 'black',
									strokeOpacity: 1,
									fillColor: '#e74c3c',
									fillOpacity: 1
								},
								url: 'entrepot.php?id_entrepot='+entrepot.id_entrepot,
								title: entrepot.entrepot_name+' - '+entrepot.adr1+' '+entrepot.post_code+' '+entrepot.city
							});
							google.maps.event.addListener(marker, 'click', function() {
								window.location.href = this.url;
							});
							marker.setMap(map);
							markersent.push(marker);
							latlngbounds.extend(myLatlng);
						}
						map.setCenter(latlngbounds.getCenter());
						map.fitBounds(latlngbounds);
					}
					else
						$('#nbent').text('0');
				}
			}, 'json');
		}
		loadPark();
		
		$('#selstpark').change(function() {
			HoldOn.open();
			loadPark();
			return false;
		});
		
		
		$('#nboktot').click(function() {
			if ($('#selcross').val().length == 0)
				return;
			
			HoldOn.open();
			$.get('index.php', {strflds: $('#selcross').getSelectionOrder(), dts: dtstart, dte:dtend}, function(resp) {
				HoldOn.close();
				$('#blcact').html(resp.html);
			}, 'json');
		});
	});
</script>
<?php include 'inc/template_end.php'; ?>