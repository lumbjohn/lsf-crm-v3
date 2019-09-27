<?php include 'inc/config.php'; ?>
<?php 
	$outuser = GridManager::getGrid('iso_contacts', $currentUser); 
	$sets = (object)Setting::getGlobalSettings();
?>
<?php include 'inc/template_start.php'; ?>
<?php include 'inc/page_head.php'; ?> 

<!-- Page content -->
<div id="page-content" class="page-lottery">
    <!-- eCommerce Dashboard Header --> 
    <div class="content-header">
        <?php include('inc/menutop.php'); ?> 
    </div>
    <!-- END eCommerce Dashboard Header -->


    <!-- eShop Overview Block -->
    <div class="block full">
        <!-- eShop Overview Title -->
        <div class="block-title">
            <div class="block-options pull-right"> 
                <div class="btn-group btn-group-sm">
                    <a href="contact.php?id_contact=0" class="btn btn-xs btn-info"><i class="fa fa-plus-circle"></i> Nouveau client / prospect</a>
					<?php if (!CrmUser::isTelepro($currentUser)) { ?>

						<?php if (CrmUser::isAdmin($currentUser)) { ?>
							<a href="#" class="btn btn-xs btn-success" id="btimportct"><i class="fa fa-cloud-download"></i> Importer</a>
						<?php } ?>

						<a href="#" class="btn btn-xs btn-danger dropdown-toggle" data-toggle="dropdown" aria-expanded="false"><i class="fa fa-check-square-o"></i> Pour la sélection <span class="caret"></span></a>
						
						<ul class="dropdown-menu dropdown-menu-right">
							<li>
								<a href="#" id="btassignct"><i class="fa fa-hand-o-right"></i> Assigner à un Télépro</a>
							</li>

							<?php if (CrmUser::isAdmin($currentUser)) { ?>
								<li>
									<a href="#" id="btdelct"><i class="fa fa-trash"></i> Supprimer</a>
								</li>
							<?php } ?>

							<?php if (!CrmUser::isManager($currentUser)) { ?>
								<li>
									<a href="#" id="btassignctconf"><i class="fa fa-hand-o-right"></i> Assigner à un Confirmateur</a>
								</li>
							<?php } ?>

							<li>
								<a href="#" id="btstatusct"><i class="fa fa-exchange"></i> Changer le statut</a>
							</li>
						</ul>
					<?php } ?>
                </div>
            </div>
			<h2>Liste des <strong>clients / prospects</strong></h2>
        </div>
        <!-- END eShop Overview Title -->
	
		<script>
			var opts = {
				"stateOptions": {         
							storageKey: "gridState-list_contacts",
							columns: true, // remember column chooser settings
							filters: true, // search filters
							selection: false, // row selection
							expansion: false, // subgrid expansion
							pager: true, // page number
							order: true // field ordering
				},
				'ondblClickRow': function (id) {
					if (jQuery(this).attr('id') == 'list_contacts') {
						location.href = 'contact.php?id_contact='+id;
						return false;
					}
				}

				};
		</script>
	
        <!-- eShop Overview Content -->
        <div class="row">
			<div class="col-md-12 col-lg-12">
				<div class="row push">
					<?php echo $outuser; ?>
				</div>
			</div>
			<div class="col-md-12 col-lg-12">
				<div class="pull-left"><a href="#" id="btreinitstate" class="btn btn-xs btn-info">Réinitialiser la grille</a></div>
			</div>
		</div>
        <!-- END eShop Overview Content -->
    </div>
    <!-- END eShop Overview Block -->

	<!-- Modal import -->
	<div id="modal-importct" class="modal fade" tabindex="-1" role="dialog" aria-hidden="true">
		<div class="modal-dialog">
			<div class="modal-content">
				<!-- Modal Header -->
				<div class="modal-header text-center">
					<h2 class="modal-title"><i class="fi fi-xlsx"></i> Importation de contacts</h2>
				</div>
				<!-- END Modal Header -->

				<!-- Modal Body -->
				<div class="modal-body">
					<form action="crmajax.php" class="dropzone" id="my-awesome-dropzone">
						<input type="hidden" name="action" id="action" value="import-ct" />					
					</form>
					<div class="row">
						<br>
						<div class="form-group form-actions">
							<div class="col-xs-6 text-left">
								<a href="uploads/IMPORT_LEADS_TYPE_ISO.xlsx" class="btn btn-sm btn-success">Exemple de fichier</a>
							</div>
							<div class="col-xs-6 text-right">
								<a href="#" class="btn btn-sm btn-default" data-dismiss="modal">Fermer</a>
								<a href="#" class="btn btn-sm btn-primary" id="btimportok">Importer</a>
							</div>
						</div>
					</div>
				</div>
				<!-- END Modal Body -->
			</div>
		</div>
	</div>
	<!-- END modal import -->


	<?php /*
	<!-- Modal import -->
	<div id="modal-importct" class="modal fade" tabindex="-1" role="dialog" aria-hidden="true">
		<div class="modal-dialog">
			<div class="modal-content">
				<!-- Modal Header -->
				<div class="modal-header text-center">
					<h2 class="modal-title"><i class="fi fi-xlsx"></i> Importation de contacts</h2>
				</div>
				<!-- END Modal Header -->

				<!-- Modal Body -->
				<div class="modal-body">
					<form action="crmajax.php" class="dropzone" id="my-awesome-dropzone">
						<input type="hidden" name="action" id="action" value="import-ct" />					
					</form>
					<div class="row">
						<br>
						<div class="form-group form-actions">
							<div class="col-xs-6 text-left">
								<a href="uploads/IMPORT_LEADS_TYPE_ISO.xlsx" class="btn btn-sm btn-success">Exemple de fichier</a>
							</div>
							<div class="col-xs-6 text-right">
								<a href="#" class="btn btn-sm btn-default" data-dismiss="modal">Fermer</a>
								<a href="#" class="btn btn-sm btn-primary" id="btimportok">Importer</a>
							</div>
						</div>
					</div>
				</div>
				<!-- END Modal Body -->
			</div>
		</div>
	</div>
	<!-- END modal import -->
	*/ ?>

	<!-- Modal import -->
	<div id="modal-columns" class="modal fade" tabindex="-1" role="dialog" aria-hidden="true">
		<div class="modal-dialog">
			<div class="modal-content">
				<!-- Modal Header -->
				<div class="modal-header text-center">
					<h2 class="modal-title"><i class="fi fi-xlsx"></i> Importation de contacts - Choix des colonnes</h2>
				</div>
				<!-- END Modal Header -->

				<!-- Modal Body -->
				<div class="modal-body">
					<form action="crmajax.php" method="post" id="frmimportcol">
						<div id="lstcolumns">
							
						</div>
						<div class="row">
							<br>
							<div class="form-group form-actions">
								<div class="col-xs-6 text-right">
									<input type="hidden" name="action" id="action" value="import-cols" />
									<a href="#" class="btn btn-sm btn-default" data-dismiss="modal">Fermer</a>
									<button type="submit" class="btn btn-sm btn-primary" id="btimportok">Importer</button>
								</div>
							</div>
						</div>					
					</form>
				</div>
				<!-- END Modal Body -->
			</div>
		</div>
	</div>
	<!-- END modal import -->

	<!-- Modal import -->
	<div id="modal-rapport" class="modal fade" tabindex="-1" role="dialog" aria-hidden="true">
		<div class="modal-dialog">
			<div class="modal-content">
				<!-- Modal Header -->
				<div class="modal-header text-center">
					<h2 class="modal-title"><i class="fi fi-log"></i> Rapport d'erreur</h2>
				</div>
				<!-- END Modal Header -->

				<!-- Modal Body -->
				<div class="modal-body">
				</div>
				<!-- END Modal Body -->
			</div>
		</div>
	</div>
	<!-- END modal import -->


	<!-- Modal assign -->
	<div id="modal-assignto" class="modal fade" tabindex="-1" role="dialog" aria-hidden="true">
		<div class="modal-dialog">
			<div class="modal-content">
				<!-- Modal Header -->
				<div class="modal-header text-center">
					<h2 class="modal-title"><i class="fa fa-hand-o-right"></i> Assigner à</h2>
				</div>
				<!-- END Modal Header -->

				<!-- Modal Body -->
				<div class="modal-body">
					<form action="crmajax.php" method="post" enctype="multipart/form-data" class="form-horizontal form-bordered" id="frmassignto" onsubmit="return false;">
						<fieldset>                        
							<div class="form-group">
								<label class="col-md-3 control-label" for="geotype">Utilisateur</label>
								<div class="col-md-9">
									<select id="assid_crmuser" name="assid_crmuser" class="form-control select-chosen">
										<?php
											$arrwh = array();
											if (!CrmUser::isAdmin($currentUser))
												$arrwh['id_team'] = $currentUser->id_team;
											if (CrmUser::isManager($currentUser))	
												$arrwh['id_profil'] = '2';
											if (CrmUser::isConfirmateur($currentUser))
												$arrwh['id_profil'] = '4';
											$cusers = CrmUser::getAll($arrwh);
											if ($cusers) {
												foreach($cusers as $cuser) {
													//if (CrmUser::isTelepro($cuser) || CrmUser::isConfirmateur($cuser))
													echo '<option value="'.$cuser['id_crmuser'].'" data-profil="'.$cuser['id_profil'].'">'.$cuser['user_name'].'</option>';
												}
											}
										?>
									</select>
								</div>
							</div>
							<div class="form-group form-actions">
								<div class="col-xs-12 text-right">
									<input type="hidden" name="action" id="action" value="assign-contacts" />
									<input type="hidden" name="id_profil" id="id_profil" />
									<a href="#" class="btn btn-sm btn-default" data-dismiss="modal">Fermer</a>
									<button type="submit" href="#" class="btn btn-sm btn-primary" id="btassignok">Assigner</button>
								</div>
							</div>
						</fieldset>
					</form>
				</div>
				<!-- END Modal Body -->
			</div>
		</div>
	</div>
	<!-- END modal assign -->


	<!-- Modal statut -->
	<div id="modal-changest" class="modal fade" tabindex="-1" role="dialog" aria-hidden="true">
		<div class="modal-dialog">
			<div class="modal-content">
				<!-- Modal Header -->
				<div class="modal-header text-center">
					<h2 class="modal-title"><i class="fa fa-exchange"></i> Changer le statut téléopérateur</h2>
				</div>
				<!-- END Modal Header -->

				<!-- Modal Body -->
				<div class="modal-body">
					<form action="crmajax.php" method="post" enctype="multipart/form-data" class="form-horizontal form-bordered" id="frmchangest" onsubmit="return false;">
						<fieldset>                        
							<div class="form-group">
								<label class="col-md-3 control-label" for="id_statuscont">Statut</label>
								<div class="col-md-9">
									<select id="id_statuscont" name="id_statuscont" class="form-control">
										<option value="0">Sélectionner le statut</option>
										<?php
											$sts = Setting::getAllStatus();
											if ($sts) {
												foreach($sts as $st) {
													echo '<option value="'.$st['id_statuscont'].'">'.$st['name_statuscont'].'</option>';
												}
											}
										?>
									</select>
								</div>
							</div>
							<div class="form-group form-actions">
								<div class="col-xs-12 text-right">
									<input type="hidden" name="action" id="stcont" value="0" />
									<input type="hidden" name="action" id="action" value="changest-contacts" />
									<a href="#" class="btn btn-sm btn-default" data-dismiss="modal">Fermer</a>
									<button type="submit" href="#" class="btn btn-sm btn-primary" id="btchangestok">Changer de statut</button>
								</div>
							</div>
						</fieldset>
					</form>
				</div>
				<!-- END Modal Body -->
			</div>
		</div>
	</div>
	<!-- END modal statut -->

	<!-- Modal statut conf -->
	<div id="modal-changestconf" class="modal fade" tabindex="-1" role="dialog" aria-hidden="true">
		<div class="modal-dialog">
			<div class="modal-content">
				<!-- Modal Header -->
				<div class="modal-header text-center">
					<h2 class="modal-title"><i class="fa fa-exchange"></i> Changer le statut confirmateur</h2>
				</div>
				<!-- END Modal Header -->

				<!-- Modal Body -->
				<div class="modal-body">
					<form action="crmajax.php" method="post" enctype="multipart/form-data" class="form-horizontal form-bordered" id="frmchangestconf" onsubmit="return false;">
						<fieldset>                        
							<div class="form-group">
								<label class="col-md-3 control-label" for="id_statuscontconf">Statut</label>
								<div class="col-md-9">
									<select id="id_statuscontconf" name="id_statuscontconf" class="form-control">
										<option value="0">Sélectionner le statut</option>
										<?php
											$sts = Setting::getAllStatusConf();
											if ($sts) {
												foreach($sts as $st) {
													echo '<option value="'.$st['id_statuscontconf'].'">'.$st['name_statuscontconf'].'</option>';
												}
											}
										?>
									</select>
								</div>
							</div>
							<div class="form-group form-actions">
								<div class="col-xs-12 text-right">
									<input type="hidden" name="action" id="stcontconf" value="0" />
									<input type="hidden" name="action" id="action" value="changestconf-contacts" />
									<a href="#" class="btn btn-sm btn-default" data-dismiss="modal">Fermer</a>
									<button type="submit" href="#" class="btn btn-sm btn-primary" id="btchangestokconf">Changer de statut</button>
								</div>
							</div>
						</fieldset>
					</form>
				</div>
				<!-- END Modal Body -->
			</div>
		</div>
	</div>
	<!-- END modal statut conf-->

</div>
<!-- END Page Content -->
<?php include 'inc/page_footer.php'; ?>

<!-- Remember to include excanvas for IE8 chart support -->
<!--[if IE 8]><script src="js/helpers/excanvas.min.js"></script><![endif]-->

<?php include 'inc/template_scripts.php'; ?>

<!-- Load and execute javascript code used only in this page -->
<script>
	//$(function(){ Lottery.init(); });
	$(document).ready(function() {
		$('#btimportct').click(function() {
			$('#modal-importct').modal();
		});		
		
		Dropzone.options.myAwesomeDropzone = false;
		Dropzone.autoDiscover = false;
		
		var dz = new Dropzone('#my-awesome-dropzone', {
		  maxFiles:1,
		  autoProcessQueue: false,
		  init: function() {
			this.on("success", function(file, txt) {
				var resp = JSON.parse(txt);
				if (resp.code == 'SUCCESS') {
					$('#modal-importct').modal('hide');
					this.removeFile(file);
					$('#lstcolumns').html(resp.html);
					$('.select-chosen').chosen({width: "100%"});
					$('#modal-columns').modal();
					$('#refresh_list_contacts').click();					
				}
			});
		  }		
		});
		

		$('#btimportok').click(function() {
			dz.processQueue();
		});
		
		$('#frmimportcol').submit(function() {
			HoldOn.open();
			jQuery(this).ajaxSubmit({
				dataType:'json',
				success : function (resp) {	
					if (resp.code == 'SUCCESS') {						
						$('#list_contacts').trigger("reloadGrid",[{page:1}]);
						$('#modal-columns').modal('hide');
						$('#modal-rapport .modal-body').html(resp.log);
						$('#modal-rapport').modal();
					}
					else
					if (resp.code == 'ERROR')
						alert(resp.message);
					HoldOn.close();
				},
				error : function() {
					console.log('NO');
					HoldOn.close();
				}
			}); 
			return false; 
		});
		
		$('#btdelct').click(function() {
			var selrows = $('#list_contacts').jqGrid('getGridParam','selarrrow');
			if (selrows.length == 0) {
				alert('Veuillez sélectionner les lignes à supprimer');
				return false;
			}
			
			if (confirm('Etes vous SURE de vouloir supprimer les contacts sélectionnés ?')) {
				HoldOn.open();
				$.post('crmajax.php', {action:'delete-contacts', rws:selrows}, function(resp) {
					HoldOn.close();
					if (resp.code == 'SUCCESS') {
						//console.log(resp);
						$('#list_contacts').trigger("reloadGrid",[{current:true}]);
					}
					else
						alert(resp.message);
				}, 'json');
			
				return false;
			}
		});
		
		$('#btassignct').click(function() {
			var selrows = $('#list_contacts').jqGrid('getGridParam','selarrrow');
			if (selrows.length == 0) {
				alert('Veuillez sélectionner les lignes à assigner');
				return false;
			}
			
			$('#id_profil').val('2');
			$('#assid_crmuser option').show();
			//$('#id_crmuser option[data-profil="4"]').hide();
			$('#modal-assignto').modal();
		});
		
		
		$('#btassignctconf').click(function() {
			var selrows = $('#list_contacts').jqGrid('getGridParam','selarrrow');
			if (selrows.length == 0) {
				alert('Veuillez sélectionner les lignes à assigner');
				return false;
			}
			$('#id_profil').val('4');
			$('#assid_crmuser option').show();
			$('#assid_crmuser option[data-profil="2"]').hide();
			$('#modal-assignto').modal();
		});
		
		$('#frmassignto').submit(function() {
			var selrows = $('#list_contacts').jqGrid('getGridParam','selarrrow');
			if (selrows.length == 0) {
				alert('Veuillez sélectionner les lignes à assigner');
				return false;
			}
			
			HoldOn.open();
			jQuery(this).ajaxSubmit({
				dataType:'json',
				data:{rws:selrows},
				success : function (resp) {	
					if (resp.code == 'SUCCESS') {						
						$('#list_contacts').trigger("reloadGrid",[{current:true}]);
						$('#modal-assignto').modal('hide');
					}
					else
					if (resp.code == 'ERROR')
						alert(resp.message);
					HoldOn.close();
				},
				error : function() {
					console.log('NO');
					HoldOn.close();
				}
			}); 
			return false; 
		});
		
		
		$('#btstatusct').click(function() {
			var selrows = $('#list_contacts').jqGrid('getGridParam','selarrrow');
			if (selrows.length == 0) {
				alert('Veuillez sélectionner les lignes à statuer');
				return false;
			}
			$('#stcont').val('0');			
			
			$('#modal-changest').modal();
		});
		
		
		$('#frmchangest').submit(function() {
			if ($('#id_statuscont').val() == '0') {
				alert('Veuillez seléctionner le statut');
				return false;
			}

			var selrows = $('#list_contacts').jqGrid('getGridParam','selarrrow');
			if (selrows.length == 0 && $('#stcont').val() == '0') {
				alert('Veuillez sélectionner les lignes à statuer');
				return false;
			}
			if (selrows.length == 0)
				selrows = '-';
			
			HoldOn.open();
			jQuery(this).ajaxSubmit({
				dataType:'json',
				data:{rws:selrows, stcont:$('#stcont').val()},
				success : function (resp) {	
					if (resp.code == 'SUCCESS') {						
						$('#list_contacts').trigger("reloadGrid",[{current:true}]);
						$('#modal-changest').modal('hide');
					}
					else
					if (resp.code == 'ERROR')
						alert(resp.message);
					HoldOn.close();
				},
				error : function() {
					console.log('NO');
					HoldOn.close();
				}
			}); 
			return false; 
		});
		
		$(document).on('click', '.btchangestone', function() {
			$('#stcont').val($(this).attr('data-id'));
			$('#modal-changest').modal();
		});

		$('#frmchangestconf').submit(function() {
			if ($('#id_statuscontconf').val() == '0') {
				alert('Veuillez seléctionner le statut');
				return false;
			}
			var selrows = $('#list_contacts').jqGrid('getGridParam','selarrrow');
			if (selrows.length == 0 && $('#stcontconf').val() == '0') {
				alert('Veuillez sélectionner les lignes à statuer');
				return false;
			}
			if (selrows.length == 0)
				selrows = '-';
			
			HoldOn.open();
			jQuery(this).ajaxSubmit({
				dataType:'json',
				data:{rws:selrows, stcontconf:$('#stcontconf').val()},
				success : function (resp) {	
					if (resp.code == 'SUCCESS') {						
						$('#list_contacts').trigger("reloadGrid",[{current:true}]);
						$('#modal-changestconf').modal('hide');
					}
					else
					if (resp.code == 'ERROR')
						alert(resp.message);
					HoldOn.close();
				},
				error : function() {
					console.log('NO');
					HoldOn.close();
				}
			}); 
			return false; 
		});

		$(document).on('click', '.btchangestoneconf', function() {
			$('#stcontconf').val($(this).attr('data-id'));
			$('#modal-changestconf').modal();
		});

		$('#btreinitstate').click(function() {
			localStorage.clear();
			window.location.reload();
			return false;
		});
	});
	
	function gridcts_onload(ids) {	
		if(ids.rows) 
			jQuery.each(ids.rows,function(i) {
				if (this.id_statuscont == "<?php echo $sets->STATUS_MISS_CONFIRM != '' ? $sets->STATUS_MISS_CONFIRM : '9999'; ?>" && this.id_crmuser_conf == "0")
					jQuery('#list_contacts tr.jqgrow:eq('+i+')').css('background-image','inherit').css({'background-color':'#f3edc2'});
				else
				if (this.status_color != '' && this.status_color != undefined)	
					jQuery('#list_contacts tr.jqgrow:eq('+i+')').css('background-image','inherit').css({'background-color':this.status_color});
				
				//console.log(this);
				/*if (this.order_status == 'Paid' || this.order_status == 'Payé'){
					// highlight row
					jQuery('#list_catalog tr.jqgrow:eq('+i+')').css('background-image','inherit').css({'background-color':'#c7f3c2'});
				}
				else
				if (this.id_cmd > 0) {
					jQuery('#list_catalog tr.jqgrow:eq('+i+')').css('background-image','inherit').css({'background-color':'#f3edc2'});
				}*/
			});
			
		$('.ui-pg-selbox').val(jQuery('#list_contacts').getGridParam('rowNum'));
		jQuery('#list_contacts').jqGrid('resetSelection');
	}

</script>

<?php include 'inc/template_end.php'; ?>