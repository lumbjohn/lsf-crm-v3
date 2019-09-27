<?php include 'inc/config.php'; ?>
<?php include 'inc/template_start.php'; ?>
<?php include 'inc/page_head.php'; ?> 

<!-- Page content -->
<div id="page-content" class="page-planning">
    <!-- eCommerce Dashboard Header --> 
    <div class="content-header">
        <?php include('inc/menutop.php'); ?>
    </div>
    <!-- END eCommerce Dashboard Header -->

	<!-- <div class="row text-center" id="rowinfosum">
		<div class="col-sm-6 col-lg-3">
			<a href="javascript:void(0)" class="widget widget-hover-effect2">
				<div class="widget-extra themed-background">
					<h5 class="widget-content-light"><strong>Nombre de chantier</strong></h5>
				</div>
				<div class="widget-extra-full"><span class="h4 animation-expandOpen" id="tot_rdv">0</span></div>
			</a>
		</div>
		<div class="col-sm-6 col-lg-3">
			<a href="javascript:void(0)" class="widget widget-hover-effect2">
				<div class="widget-extra themed-background-dark">
					<h5 class="widget-content-light"><strong>Nombre de 101</strong></h5>
				</div>
				<div class="widget-extra-full"><span class="h4 animation-expandOpen" id="tot_101">0</span>  <small class="text-danger">m2</small></div>
			</a>
		</div>
		<div class="col-sm-6 col-lg-3">
			<a href="javascript:void(0)" class="widget widget-hover-effect2">
				<div class="widget-extra themed-background-dark">
					<h5 class="widget-content-light"><strong>Nombre de 103</strong></h5>
				</div>
				<div class="widget-extra-full"><span class="h4 animation-expandOpen" id="tot_103">0</span>  <small class="text-danger">m2</small></div>
			</a>
		</div>
		<div class="col-sm-6 col-lg-3">
			<a href="javascript:void(0)" class="widget widget-hover-effect2">
				<div class="widget-extra themed-background-success">
					<h5 class="widget-content-light"><strong>Cumac cumulé</strong></h5>
				</div>
				<div class="widget-extra-full"><span class="h4 animation-expandOpen" id="tot_cumac">0</span></div>
			</a>
		</div>
	</div> -->
    <!-- eShop Overview Block --> 
    <div class="block full row" id="blkcal">
        <!-- eShop Overview Title -->		
		<div class="col-lg-12">
			<div class="block">
                <!-- Normal Form Title -->
                <div class="block-title">
					<h2><strong>Planning</strong> général</h2>
					<?php if (!CrmUser::isTelepro($currentUser) && !CrmUser::isManager($currentUser)) { ?>
						<div class="block-options pull-right">
							<div class="btn-group btn-group-sm">
								<a href="#" id="btfullscreen" class="btn btn-xs btn-default"><i class="fa fa-expand"></i></a>
							</div>
						</div>
						<div class="block-options pull-right">
							<div class="btn-group btn-group-sm">
								<a href="#" id="btattach" class="btn btn-xs btn-info">Attribuer un installateur</a>
							</div>
						</div>
						<div class="block-options pull-right">
							<div class="btn-group btn-group-sm">
								<a href="#" id="btdettach" class="btn btn-xs btn-danger">Détacher un installateur</a>
							</div>
						</div>
						<div class="block-options pull-right">
							<div class="btn-group btn-group-sm">
								<a href="#" id="btmailent" class="btn btn-xs btn-success">Envoyer le mail de stock aux entrepots</a>
							</div>
						</div>
					<?php } ?>
                </div>
                <!-- END Normal Form Title -->
				<div style="padding:5px">
					Nb chantiers : <strong id="tot_rdv">0</strong> | 
					Total m² 101 : <strong id="tot_101">0</strong> | 
					Total m² 102 : <strong id="tot_102">0</strong> | 
					Total m² 103 : <strong id="tot_103">0</strong> | 
					Nb Giga : <strong id="tot_cumac">0</strong>
				</div>
				<div id="calendar"></div>
				
            </div>
		</div>		
    </div>
    <!-- END eShop Overview Block -->

	<div id="modal-attachins" class="modal fade" tabindex="-1" role="dialog" aria-hidden="true">
		<div class="modal-dialog modal-lg">
			<div class="modal-content">
				<!-- Modal Header -->
				<div class="modal-header text-center">
					<h3 class="modal-title"><i class="fa fa-cogs"></i> <span id="titattach">Rattachement</span> entrepot / planning / installateur sur une période</h3>
				</div>
				<!-- END Modal Header -->

				<!-- Modal Body -->
				<div class="modal-body">
					<form action="crmajax.php" method="post" enctype="multipart/form-data" class="form-horizontal form-bordered" id="frmattach" onsubmit="return false;">
						<div class="form-group">
							<label for="dates" class="col-md-4 control-label">Date de début / fin</label>
							<div class="col-md-8">
								<div class="input-group input-daterange" data-date-format="dd/mm/yyyy">
									<input type="text" id="date_start" name="date_start" class="form-control text-center" placeholder="du" value="">
									<span class="input-group-addon"><i class="fa fa-angle-right"></i></span>
									<input type="text" id="date_end" name="date_end" class="form-control text-center" placeholder="au" value="">
									<span class="input-group-btn">
                                        <a href="#" class="btn btn-primary" id="btloadent"><i class="fa fa-refresh" data-toggle="tooltip" title="Charger les entrepots / planning à attribuer sur la période"></i></a>
                                    </span>
								</div>
							</div>
						</div>
						<div class="form-group display-none" id="blkent">
							<label for="entplan" class="col-md-4 control-label">Entrepot / Planning</label>
							<div class="col-md-8">
								<select id="entplan" name="entplan" class="form-control">

								</select>
							</div>
						</div>
						<div class="form-group display-none" id="blkins">
							<label for="id_installator" class="col-md-4 control-label">Installateur</label>
							<div class="col-md-8">
								<select id="id_installator" name="id_installator" class="form-control">

								</select>
							</div>
						</div>
						<div class="form-group form-actions">
							<div class="col-xs-12 text-right">
								<input type="hidden" name="action" id="action" value="attach-planning-installator" />
								<button type="button" class="btn btn-sm btn-default" data-dismiss="modal">Close</button>
								<button type="submit" class="btn btn-sm btn-primary display-none" id="btokattach">Rattacher</button>
							</div>
						</div>
					</form>
				</div>
				<!-- END Modal Body -->
			</div>
		</div>
	</div>

	<div id="modal-mailent" class="modal fade" tabindex="-1" role="dialog" aria-hidden="true">
		<div class="modal-dialog modal-lg">
			<div class="modal-content">
				<!-- Modal Header -->
				<div class="modal-header text-center">
					<h3 class="modal-title"><i class="fa fa-send-o"></i> Envoyer un email de préparation du stock aux entrepots</h3>
				</div>
				<!-- END Modal Header -->

				<!-- Modal Body -->
				<div class="modal-body">
					<form action="crmajax.php" method="post" enctype="multipart/form-data" class="form-horizontal form-bordered" id="frmmailent" onsubmit="return false;">
						<div class="form-group">
							<label for="dates" class="col-md-4 control-label">Date de début / fin</label>
							<div class="col-md-8">
								<div class="input-group input-daterange" data-date-format="dd/mm/yyyy">
									<input type="text" id="date_start" name="date_start" class="form-control text-center" placeholder="du" value="">
									<span class="input-group-addon"><i class="fa fa-angle-right"></i></span>
									<input type="text" id="date_end" name="date_end" class="form-control text-center" placeholder="au" value="">
								</div>
							</div>
						</div>
						<div class="form-group form-actions">
							<div class="col-xs-12 text-right">
								<input type="hidden" name="action" id="action" value="mail-entrepot" />
								<button type="button" class="btn btn-sm btn-default" data-dismiss="modal">Close</button>
								<button type="submit" class="btn btn-sm btn-primary" id="btokmailent">Envoyer</button>
							</div>
						</div>
					</form>
				</div>
				<!-- END Modal Body -->
			</div>
		</div>
	</div>

	<div id="modal-chent" class="modal fade" tabindex="-1" role="dialog" aria-hidden="true">
		<div class="modal-dialog modal-lg">
			<div class="modal-content">
				<!-- Modal Header -->
				<div class="modal-header text-center">
					<h3 class="modal-title"><i class="fa fa-refresh"></i> Changer l'entrepot des RDV <small id="infchent"></small></h3>
				</div>
				<!-- END Modal Header -->

				<!-- Modal Body -->
				<div class="modal-body">
					<form action="crmajax.php" method="post" enctype="multipart/form-data" class="form-horizontal form-bordered" id="frmchent" onsubmit="return false;">
						<div class="form-group">
							<label for="entplan" class="col-md-4 control-label">Entrepot</label>
							<div class="col-md-8">
								<select id="id_entrepot" name="id_entrepot" class="form-control">
									<option value="0">Sélectionner l'entrepot</option>
									<?php
										$ents = Entrepot::getAll();
										foreach($ents as $ent)
											echo '<option value="'.$ent['id_entrepot'].'">'.$ent['entrepot_name'].'</option>';
									?>
								</select>
							</div>
						</div>
						<div class="form-group form-actions">
							<div class="col-xs-12 text-right">
								<input type="hidden" name="action" id="action" value="change-rdv-entrepot" />
								<input type="hidden" name="id_entrepot_base" id="id_entrepot_base" value="" />
								<input type="hidden" name="num_planning_base" id="num_planning_base" value="" />
								<button type="button" class="btn btn-sm btn-default" data-dismiss="modal">Close</button>
								<button type="submit" class="btn btn-sm btn-primary" id="btokchent">Changer</button>
							</div>
						</div>
					</form>
				</div>
				<!-- END Modal Body -->
			</div>
		</div>
	</div>
</div>
<!-- END Page Content -->

<?php include 'inc/page_footer.php'; ?>

<!-- Remember to include excanvas for IE8 chart support -->
<!--[if IE 8]><script src="js/helpers/excanvas.min.js"></script><![endif]-->

<?php include 'inc/template_scripts.php'; ?>
<script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyArKK0Hvu_FtKgyvHkUUjyKOMK2Hmt9zY0&libraries=places"></script>
<!-- Load and execute javascript code used only in this page -->
<script>
	$(document).ready(function() {

		var modeattach = true;
		$('#btattach').click(function() {
			modeattach = true;
			$('#blkent').hide();
			$('#blkins').hide();
			$('#btokattach').hide();
			$('#titattach').text('Rattachement');
			$('#btokattach').text('Rattacher');
			$('#btokattach').addClass('btn-primary');
			$('#btokattach').removeClass('btn-danger');
			$('#modal-attachins').modal();
			return false;
		});

		$('#btdettach').click(function() {
			modeattach = false;
			$('#blkent').hide();
			$('#blkins').hide();
			$('#btokattach').hide();
			$('#titattach').text('Détachement');
			$('#btokattach').text('Détacher');
			$('#btokattach').removeClass('btn-primary');
			$('#btokattach').addClass('btn-danger');
			$('#modal-attachins').modal();
			return false;
		});

		$('#btloadent').click(function() {
			var err = '';

			if ($('#date_start').val() == '')
				err = 'Veuillez renseigner la date de debut';
			else	
			if ($('#date_end').val() == '')
				err = 'Veuillez renseigner la date de fin';
			
			if (err != '') {
				$.bootstrapGrowl('<h4>Erreur!</h4> <p>' + err + '</p>', {
					type: 'danger',
					delay: 2500,
					allow_dismiss: true
				});
				return false;
			}

			HoldOn.open();
			$.post('crmajax.php', {action:'load-entrepot-planning', dtstart:$('#date_start').val(), dtend:$('#date_end').val(), attach:modeattach ? 1 : 0}, function(resp) {
				HoldOn.close();
				if (resp.code == "SUCCESS") {
					var str = '<option value="0">Sélectionner l\'entrepot / planning</option>';
					for(var d in resp.entplans) 
						str += '<option value="'+resp.entplans[d].id_entrepot+'_'+resp.entplans[d].num_planning+'">'+resp.entplans[d].entrepot_name+' #'+resp.entplans[d].num_planning+'</option>';
					$('#entplan').html(str);
					$('#blkent').show();
					$('#blkins').hide();
				}
				else
					$.bootstrapGrowl('<h4>Erreur!</h4> <p>' + resp.message + '</p>', {
						type: 'danger',
						delay: 2500,
						allow_dismiss: true
					});
			}, 'json')

			return false;
		});

		$('#entplan').change(function() {
			var entplan = $(this).val();
			if (entplan == '0')
				$('#blkins').hide();
			else {				
				HoldOn.open();
				$.post('crmajax.php', {action:'load-installator-entrepot', 'entplan':entplan, dtstart:$('#date_start').val(), dtend:$('#date_end').val(), attach:modeattach ? 1 : 0}, function(resp) {
					HoldOn.close();
					if (resp.code == 'SUCCESS') {
						var str = '<option value="0">Sélectionner l\'installateur</option>';
						for(var d in resp.insts) 
							str += '<option value="'+resp.insts[d].id_installator+'">'+resp.insts[d].first_name_ins+' '+resp.insts[d].last_name_ins+' ('+(Math.round(resp.insts[d].dis / 1000))+'km)</option>';
						$('#id_installator').html(str);
						$('#blkins').show();
					}
					else
						$.bootstrapGrowl('<h4>Erreur!</h4> <p>' + resp.message + '</p>', {
							type: 'danger',
							delay: 2500,
							allow_dismiss: true
						});
				}, 'json');
			}
		});

		$('#id_installator').change(function(){
			if ($(this).val() == '0')
				$('#btokattach').hide();
			else	
				$('#btokattach').show();				
		});

				
		var calfirst = true;
		var saveview = '';
		var currdvid = 0;
		var arres = [];
		<?php 
			$nextday = '';
			if (isset($_GET['dt']))
				$nextday = date('Y-m-d', $_GET['dt']);
			else {
				$w = date('w');
				$decal = $w >= 5 ? 8 - $w : 1;
				$nextday = date('Y-m-d', strtotime('+'.$decal.' days'));
			}
		?>
		$('#calendar').fullCalendar({
			schedulerLicenseKey: 'CC-Attribution-NonCommercial-NoDerivatives',
			header: {
				left: 'prev,next today',
				center: 'title',
				right: 'listWeek,month,timelineWeek,timelineDay' /*agendaWeek */
			},
			locale:'fr',
			dayNames:['Dimanche', 'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi'],
			/*views: {
				listWeek: {columnFormat: 'dddd', buttonText: 'Semaine'},
				month: {buttonText: 'Mois'},
				listDay: { buttonText: 'Liste jour' },
				listWeek: { buttonText: 'Liste semaine' }
			},*/
			eventStartEditable: true,
			eventDurationEditable: false,
			eventResourceEditable: true,
			now:'<?php echo $nextday; ?>',
			defaultView: 'timelineDay', //'<?php echo isset($_GET['dt']) ? 'timelineDay' : 'month'; ?>',
			allDaySlot: false,
			eventLimit: true,
			height:'auto',			
			slotWidth:120,
			displayEventTime: true,
			lazyFetching: false,
			//eventOverlap:false,
			/*slotDuration:'00:10:00',*/
			/*defaultTimedEventDuration:'00:20:00',*/
			minTime: '07:00:00',
			maxTime: '21:00:00',
			resourceLabelText: 'Entrepots / Planning',
			resourceAreaWidth:'190px',
			slotDuration:{hours:2},
			snapDuration:{minutes:1},
			slotLabelFormat:['dddd DD MMM YYYY', 'HH:mm'],
			eventDrop: function(event, delta, revertFunc, jsEvent, ui, view) {
				/*if (modesimu) {
					revertFunc();
					setTimeout(function() {
						$(".elmrdv[data-id='"+event.id+"'] .elmok input").attr('checked', 'checked');
						//$(".elmrdv[data-id='"+event.id+"'] .elmok input").click();

					}, 300);
					return false;
				}*/

				//console.log(event);
				var instl  = arres.filter(function(o){return o.id == event.resourceId;})[0];
				//if (!confirm("Déplacement du Rendez vous sur entrepot "+instl.title.replace(/<[^>]+>/g, '')+" au "+event.start.format('L LT')+" ?  (Le programme calculera exactement l'heure en fonction des données de la tournée)")) {
				if (!confirm("Déplacement du Rendez vous sur entrepot "+instl.title.replace(/<[^>]+>/g, '')+" au "+event.start.format('L LT')+" ? ")) {
					revertFunc();
					return false;
				}				
				HoldOn.open();
				$.post('crmajax.php', {action:'move-rdv', idrdv:event.id, idins: event.resourceId, hrstart: event.start.format('YYYY-MM-DD HH:mm:ss')}, function(resp) {
					HoldOn.close();
					if (resp.code == 'SUCCESS') {
						currdvid = $("[data-id='"+event.id+"']").offset().top-420;
						$('#calendar').fullCalendar( 'refetchEvents');
						//$('#calendar').fullCalendar( 'removeEvents', function(ev) {return ev.id != 'canDrop';});
						//$('#calendar').fullCalendar( 'addEventSource', resp.events);						
					}
					else {						
						alert(resp.message);
						revertFunc();
					}
				}, 'json');
			
				//return false;
				
				/*alert(event.title + " was dropped on " + event.start.format());

				if (!confirm("Are you sure about this change?")) {
					revertFunc();
				}*/

			},
			eventRender: function(event, element) {
				var vw = $('#calendar').fullCalendar('getView');
				element.find('div.fc-title').html(element.find('div.fc-title').text());
				if (vw.name == 'month')
					element.find('span.fc-title').html(element.find('span.fc-title').text().replace('<br>', '').replace('<br>', ''));
				else
					element.find('span.fc-title').html(element.find('span.fc-title').text());
				
				if (vw.name == 'timelineDay' || vw.name == 'timelineWeek')
					element.find('span.fc-title .calins').remove();
				
				element.find('.fc-list-item-title').html(element.find('.fc-list-item-title').text());
				//element.find('div.fc-time').hide();
				//element.find('span.fc-time').hide();
				var issav = element.find('.issav');
				if (issav.length > 0)
					element.css("border", "solid 2px #0000ff");
				else {
					var clsout = element.find('.clsout');
					if (clsout.length > 0)
						element.css("border", "solid 2px #000");
				}

				if (modesimu) {
					var rdv  = globrdvs.filter(function(o){return o.id_rdv == event.id;})[0];
					if (rdv != null) {
						element.find('.elmok').addClass('blrzone');
						element.find('.elmok input').hide();
						element.find('.simudis').text(rdv.distance);
						element.find('.simudel').text(rdv.delay);
						element.find('.simudisdel').css('display', 'block');
					}
					else {
						element.find('.elmok input').attr('checked','checked');
					}

				}

				$("body").tooltip({ selector: '[data-toggle="tooltip"]' });
			},
			events: function(fstart, fend, timezone, callback) {
				HoldOn.open();
				$.post('crmajax.php', {action:'get-planning', start:fstart.format(), end:fend.format(), id_entrepot:0, id_installator:0}, function(resp) {
					HoldOn.close();
					if (resp.code == "SUCCESS") {
						$('#tot_rdv').text(resp.tot_rdv);
						$('#tot_101').text(resp.tot_101);
						$('#tot_102').text(resp.tot_102);
						$('#tot_103').text(resp.tot_103);
						$('#tot_cumac').text(resp.tot_cumac);
						callback(resp.events);
						if (currdvid > 0) {
							$('.fc-time-area.fc-widget-content .fc-scroller').animate({ scrollTop: currdvid }, "fast");
							currdvid = 0;
						}
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
				$.post('crmajax.php', {action:'get-resources-planning', start:fstart.format(), end:fend.format(), id_entrepot:0, id_installator:0}, function(resp) {
					HoldOn.close();
					if (resp.code == "SUCCESS") {
						arres = resp.resources;
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
				
				if (modesimu) {
					var ent  = globents.filter(function(o){return o.id_entrepot+'_'+o.num_planning+'_'+o.type_rdv == resourceObj.id;})[0];
					if (ent != null) {
						labelTds.find('.simuentdis').text(ent.distance);
						labelTds.find('.simuentdel').text(ent.delay);
						labelTds.find('.simuentdisdel').show();
					}
				}

			},
			eventAfterAllRender:function(view) {
				//setTimeout(function() {
					if (currdv > 0) {
						//var ftop = $(".elmrdv[data-id='"+currdv+"']").offset().top - 200;
						//console.log(ftop);
						$([document.documentElement, document.body]).animate({ scrollTop: currdv }, "fast");
						//$('.fc-resource-area.fc-widget-content .fc-scroller').animate({ scrollTop: ftop }, "fast");
					}
					else {
						$([document.documentElement, document.body]).animate({ scrollTop: 0 }, "fast");
						//$('.fc-resource-area.fc-widget-content .fc-scroller').animate({ scrollTop: 0 }, "fast");
					}

					/*if (!calfirst && (saveview != view.name)) {
						calfirst = true;
						$('#calendar').fullCalendar( 'refetchEvents');
						console.log('refresh');
					}
					saveview = view.name;
					calfirst = false;*/
				//}, 500);
				//alert('ok');
			},
			viewRender: function( view, element ) {			
				$('.btchent').hide();
				if (view.name == 'month')
					$('#calendar').fullCalendar('option', 'height', 600);
				else {
					$('#calendar').fullCalendar('option', 'height', 'auto');
					if (view.name == 'timelineDay')
						$('.btchent').show();
				}
			}
		});

		var modefull = false;
		$('#btfullscreen').click(function() {
			if (!modefull) {
				$('#blkcal').addClass('calfull');
				$('#blkcal').css('height', $('#calendar').height());
				modefull = true;
			}
			else {
				$('#blkcal').removeClass('calfull');
				$('#blkcal').css('height', '100%');
				modefull = false;
			}
		});


		var modesimu = false;
		var globrdvs = [];
		var currdv = 0;
		var globents = [];

		<?php if (!CrmUser::isTelepro($currentUser) && !CrmUser::isManager($currentUser)) { ?>

			$('#btokattach').click(function() {
				HoldOn.open();
				jQuery('#frmattach').ajaxSubmit({
					dataType:'json',
					data:{attach:modeattach ? 1 : 0},
					success : function (resp) {	
						HoldOn.close();
						if (resp.code == 'SUCCESS') { 						
							$('#modal-attachins').modal('hide');
							$.bootstrapGrowl('<h4>Rattachement effectué !</h4> <p>Opération effectuée avec succès !</p>', {
								type: 'success',
								delay: 2500,
								allow_dismiss: true
							});
							window.location.reload(true);
						}
						else
						if (resp.code == 'ERROR')
							$.bootstrapGrowl('<h4>Erreur!</h4> <p>' + resp.message + '</p>', {
								type: 'danger',
								delay: 2500,
								allow_dismiss: true
							});
					},
					error : function() {
						$.bootstrapGrowl('<h4>Erreur!</h4> <p>Erreur pendant le traitement, veuillez réessayer.</p>', {
							type: 'danger',
							delay: 2500,
							allow_dismiss: true
						});
					}
				}); 
				return false;
			});

			$('#btmailent').click(function() {
				$('#modal-mailent').modal();
				return false;
			});

			$('#btokmailent').click(function() {
				HoldOn.open();
				jQuery('#frmmailent').ajaxSubmit({
					dataType:'json',
					success : function (resp) {	
						HoldOn.close();
						if (resp.code == 'SUCCESS') { 						
							$('#modal-mailent').modal('hide');
							$.bootstrapGrowl('<h4>Email envoyé !</h4> <p>Opération effectuée avec succès !</p>', {
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
					},
					error : function() {
						HoldOn.close();
						$.bootstrapGrowl('<h4>Erreur!</h4> <p>Erreur pendant le traitement, veuillez réessayer.</p>', {
							type: 'danger',
							delay: 2500,
							allow_dismiss: true
						});
					}
				}); 
				return false;
			});
						
			$(document).on('click', '.chksimu', function() {		
				var idrdv = $(this).parents('.elmrdv').attr('data-id');
				var dtrdv = $(this).parents('.elmrdv').attr('data-dt');
				globrdvs = [];
				globents = [];
				if ($(this).is(':checked')) {
					var fst = $('#calendar').fullCalendar('getView').start.format();
					var fnd = $('#calendar').fullCalendar('getView').end.format();
					modesimu = true;
					currdv = $([document.documentElement, document.body]).scrollTop();
					HoldOn.open();
					$.post('crmajax.php', {action:'calculate-rdv', id: idrdv, dts:fst, dte:fnd}, function(resp) {
						HoldOn.close();
						if (resp.code == 'SUCCESS') {
							//parse response and display infos (+ bluring ..etc..)
							//console.log(resp.rdvs);
							//console.log(resp.ents);
							for(var d in resp.rdvs) {
								var rdv = resp.rdvs[d];
								var elm = $(".elmrdv[data-id='"+rdv.id_rdv+"']");
								if (elm.length == 1) {
									globrdvs.push(rdv);
								}
							}

							for(var d in resp.ents) {
								var ent = resp.ents[d];
								var elm = $(".simuentdisdel[data-id='"+ent.id_entrepot+"_"+ent.num_planning+"_"+ent.type_rdv+"']");
								if (elm.length == 1) {
									globents.push(ent);
								}
							}

							$('#calendar').fullCalendar('option', 'refetchResourcesOnNavigate', false);
							$('#calendar').fullCalendar('option', 'eventStartEditable', false);
							$('#calendar').fullCalendar('option', 'eventResourceEditable', false);								


						}
						else
							$.bootstrapGrowl('<h4>Erreur!</h4> <p>' + resp.message + '</p>', {
								type: 'danger',
								delay: 2500,
								allow_dismiss: true
							});
					}, 'json');
				}
				else {
					modesimu = false;
					//currdv = 0;
					$('#calendar').fullCalendar('option', 'eventStartEditable', true);
					$('#calendar').fullCalendar('option', 'eventResourceEditable', true);								
					$('#calendar').fullCalendar('option', 'refetchResourcesOnNavigate', true);

					$(".elmrdv .simudisdel").hide();
					$('.elmok input').show();
					$('.elmok').removeClass('blrzone');
					$(".simuentdisdel").hide();
				}
			});
						
			$(document).on('click', '.icordvstatut', function() {
				var cursts = $(this).attr('data-status');
				if (cursts == '2')
					return false;

				var str = cursts == '0' ? 'Confirmer' : 'Déconfirmer';
				if (confirm(str+' le RDV ?')) {	
					var obj = $(this);
					HoldOn.open();
					$.post('crmajax.php', {action:'confirm-rdv', idrdv:obj.parents('.elmrdv').attr('data-id'), status:cursts}, function(resp) {
						HoldOn.close();
						if (resp.code == 'SUCCESS') {
							$('.tooltip.in').remove();
							if (cursts == '0') {
								obj.attr('title', 'OK Confirmé');
								obj.attr('data-original-title', 'OK Confirmé');
								obj.attr('data-status', '1');
								obj.removeClass('fa-thumbs-down').removeClass('text-danger').addClass('fa-thumbs-up').addClass('text-success');
								//$("body").tooltip({ selector: '[data-toggle="tooltip"]' });
							}
							else
							if (cursts == '1') {
								obj.attr('title', 'A confirmer');
								obj.attr('data-original-title', 'A confirmer');
								obj.attr('data-status', '0');
								obj.removeClass('fa-thumbs-up').removeClass('text-success').addClass('fa-thumbs-down').addClass('text-danger');
							}
						}
						else
							$.bootstrapGrowl('<h4>Erreur!</h4> <p>' + resp.message + '</p>', {
								type: 'danger',
								delay: 2500,
								allow_dismiss: true
							});
					}, 'json');	
				}
			});

			$(document).on('click', '.btsavdone', function() {
				var cursts = $(this).attr('data-done');
				var str = cursts == '0' ? 'Valider' : 'Dévalider';
				if (confirm(str+' le RDV SAV ?')) {	
					var obj = $(this);
					HoldOn.open();
					$.post('crmajax.php', {action:'confirm-sav', idrdv:obj.parents('.elmrdv').attr('data-id'), status:cursts}, function(resp) {
						HoldOn.close();
						if (resp.code == 'SUCCESS') {
							$('.tooltip.in').remove();
							if (cursts == '0') {
								obj.find('span').removeClass('text-danger').addClass('text-success');
								obj.find('span').text('SAV Effectué');
								obj.find('i').removeClass('fa-user-times').removeClass('text-danger').addClass('fa-user-plus').addClass('text-success');
								obj.attr('data-done', '1');
							}
							else
							if (cursts == '1') {
								obj.find('span').addClass('text-danger').removeClass('text-success');
								obj.find('span').text('SAV Non effectué');
								obj.find('i').addClass('fa-user-times').addClass('text-danger').removeClass('fa-user-plus').removeClass('text-success');
								obj.attr('data-done', '0');
							}
						}
						else
							$.bootstrapGrowl('<h4>Erreur!</h4> <p>' + resp.message + '</p>', {
								type: 'danger',
								delay: 2500,
								allow_dismiss: true
							});
					}, 'json');	
				}
			});

			$(document).on('click', '.btchent', function() {
				var ident = $(this).attr('data-ent');
				var numplan = $(this).attr('data-plan');
				$('#infchent').html('<br>' + $(this).parent().find('> span').html()+' '+$(this).parent().find('> small').html());
				$('#id_entrepot option').show();
				$('#id_entrepot option[value="'+ident+'"]').hide();
				$('#id_entrepot_base').val(ident);
				$('#num_planning_base').val(numplan);			
				
				$('#modal-chent').modal();
				return false;
			});

			$('#btokchent').click(function() {
				if ($('#id_entrepot').val() == '0') {
					alert('Veuillez sélectionner l\'entrepot sur lequel attribuer les RDV');
					return false;
				}
				HoldOn.open();
				jQuery('#frmchent').ajaxSubmit({
					dataType:'json',
					data:{dt:$('#calendar').fullCalendar('getView').start.format()},
					success : function (resp) {	
						HoldOn.close();
						if (resp.code == 'SUCCESS') { 						
							$('#modal-chent').modal('hide');
							$.bootstrapGrowl('<h4>Changement effectué !</h4> <p>Opération effectuée avec succès !</p>', {
								type: 'success',
								delay: 2500,
								allow_dismiss: true
							});						
							$('#calendar').fullCalendar( 'refetchEvents');
							$('#calendar').fullCalendar( 'refetchResources');						
						}
						else
						if (resp.code == 'ERROR')
							$.bootstrapGrowl('<h4>Erreur!</h4> <p>' + resp.message + '</p>', {
								type: 'danger',
								delay: 2500,
								allow_dismiss: true
							});
					},
					error : function() {
						HoldOn.close();
						$.bootstrapGrowl('<h4>Erreur!</h4> <p>Erreur pendant le traitement, veuillez réessayer.</p>', {
							type: 'danger',
							delay: 2500,
							allow_dismiss: true
						});
					}
				}); 
				return false;
			});
		<?php } ?>
	});
</script>

<?php include 'inc/template_end.php'; ?>