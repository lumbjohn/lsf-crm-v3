<?php include 'inc/config.php'; ?>
<?php 
	$outstatus = GridManager::getGrid('iso_statuscont'); 
	$outstatusconf = GridManager::getGrid('iso_statuscontconf'); 
	$outcamps = GridManager::getGrid('iso_campains'); 
	$outteams = GridManager::getGrid('iso_teams'); 
	$outprofils = GridManager::getGrid('iso_profils');
	$outmandators = GridManager::getGrid('iso_mandators');
	$outcontributors = GridManager::getGrid('iso_contributors');
	$outmaterials = GridManager::getGrid('iso_materials');
	$outplafonds = GridManager::getGrid('iso_plafonds_bonus');
	$outbonus = GridManager::getGrid('iso_bonus_grid');
	//$outrdvrange = GridManager::getGrid('iso_rdv_range');
	$outcumacs = GridManager::getGrid('iso_cumacs');
	//$outparrain = GridManager::getGrid('iso_parrains');
?>
<?php include 'inc/template_start.php'; ?>
<?php include 'inc/page_head.php'; ?>

<!-- Page content -->
<div id="page-content" class="page-settings">
    <!-- eCommerce Dashboard Header -->
    <div class="content-header">
        <?php include('inc/menutop.php'); ?>
    </div>
    <!-- END eCommerce Dashboard Header -->

    <!-- Quick Stats -->
	<div class="block full">
		<div class="row">
			<div class="col-md-12">
				<div class="col-md-2">
					<ul class="nav nav-pills nav-stacked">
						<li class="active"><a href="#tabs-gen" data-toggle="pill"><i class="fa fa-cog"></i> Général</a></li>
						<li class=""><a href="#tabs-statuscont" data-toggle="pill"><i class="hi hi-list-alt"></i> Statut prospects (Télépro)</a></li>
						<li class=""><a href="#tabs-statuscontconf" data-toggle="pill"><i class="hi hi-list-alt"></i> Statut clients (Confirmateur)</a></li>
						<li class=""><a href="#tabs-campains" data-toggle="pill"><i class="fa fa-cloud"></i> Campagnes</a></li>
						<li class=""><a href="#tabs-teams" data-toggle="pill"><i class="fa fa-users"></i> Teams</a></li>
						<li class=""><a href="#tabs-profil" data-toggle="pill"><i class="fa fa-user-secret"></i> Profiles</a></li>
						<li class=""><a href="#tabs-mandator" data-toggle="pill"><i class="fa fa-suitcase"></i> Maitres d'oeuvres</a></li>
						<li class=""><a href="#tabs-contributor" data-toggle="pill"><i class="fa fa-mortar-board"></i> Délégataires</a></li>
						<li class=""><a href="#tabs-material" data-toggle="pill"><i class="gi gi-package"></i> Matériaux</a></li>
						<li class=""><a href="#tabs-plafond" data-toggle="pill"><i class="gi gi-umbrella"></i> Plafonds sociaux</a></li>
						<li class=""><a href="#tabs-bonus" data-toggle="pill"><i class="gi gi-money"></i> Grille de bonus</a></li>
						<?php /* <li class=""><a href="#tabs-rdv-range" data-toggle="pill"><i class="gi gi-ax"></i> Tranche horaires planning</a></li> */ ?>
						<li class=""><a href="#tabs-cumac" data-toggle="pill"><i class="gi gi-calculator"></i> Calcul des CUMACS</a></li>
						<!-- <li class=""><a href="#tabs-parrain" data-toggle="pill"><i class="gi gi-parents"></i> Parrainage / Filleuls</a></li> -->
					</ul>
				</div>
				<div class="tab-content col-md-10 text-center">
					<div id="tabs-gen" class="tab-pane active">
						<h2>Paramètres généraux</h2>
						<div class="full row text-center">
							<div class="col-lg-2">
							</div>
							<div class="col-lg-8">
								<div class="block">
								<?php 
									$sets = (object)Setting::getGlobalSettings();
								?>
									<form onsubmit="return false;" class="form-bordered form-horizontal" method="post" action="crmajax.php" id="frminfoset">
										<div class="form-group">
											<label for="DISTANCE_RDV" class="col-md-4 control-label">Périmètre de recherche de rendez vous (en km)</label>
											<div class="col-md-6">
												<input type="text" placeholder="Périmètre de recherche de rendez vous (km)" class="form-control" name="DISTANCE_RDV" id="DISTANCE_RDV" value="<?php echo $sets->DISTANCE_RDV; ?>" required>
											</div>
										</div>
										<div class="form-group">
											<label for="DISTANCE_RDV_MAX" class="col-md-4 control-label">Marquer en rouge les rendez vous dépassant les (en km)</label>
											<div class="col-md-6">
												<input type="text" placeholder="Marquer en rouge les rendez vous dépassant les (en km)" class="form-control" name="DISTANCE_RDV_MAX" id="DISTANCE_RDV_MAX" value="<?php echo $sets->DISTANCE_RDV_MAX; ?>" required>
											</div>
										</div>
										<?php /*
										<div class="form-group">
											<label for="DURATION_RDV" class="col-md-4 control-label">Durée d'un rendez vous</label>
											<div class="col-md-6">
												<div class="input-group bootstrap-timepicker">
													<input type="text"  name="DURATION_RDV" id="DURATION_RDV" value="<?php echo $sets->DURATION_RDV; ?>" class="form-control input-timepicker24">
													<span class="input-group-btn">
														<a href="javascript:void(0)" class="btn btn-primary"><i class="fa fa-clock-o"></i></a>
													</span>
												</div>
											</div>
										</div>
										<div class="form-group">
											<label for="MORNING_START" class="col-md-4 control-label">Matin - Heure de début</label>
											<div class="col-md-6">
												<div class="input-group bootstrap-timepicker">
													<input type="text"  name="MORNING_START" id="MORNING_START" value="<?php echo $sets->MORNING_START; ?>" class="form-control input-timepicker24">
													<span class="input-group-btn">
														<a href="javascript:void(0)" class="btn btn-primary"><i class="fa fa-clock-o"></i></a>
													</span>
												</div>
											</div>
										</div>
										<div class="form-group">
											<label for="MORNING_END" class="col-md-4 control-label">Matin - Heure de fin</label>
											<div class="col-md-6">
												<div class="input-group bootstrap-timepicker">
													<input type="text"  name="MORNING_END" id="MORNING_END" value="<?php echo $sets->MORNING_END; ?>" class="form-control input-timepicker24">
													<span class="input-group-btn">
														<a href="javascript:void(0)" class="btn btn-primary"><i class="fa fa-clock-o"></i></a>
													</span>
												</div>
											</div>
										</div>
										<div class="form-group">
											<label for="AFTERNOON_START" class="col-md-4 control-label">Aprés midi - Heure de début</label>
											<div class="col-md-6">
												<div class="input-group bootstrap-timepicker">
													<input type="text"  name="AFTERNOON_START" id="AFTERNOON_START" value="<?php echo $sets->AFTERNOON_START; ?>" class="form-control input-timepicker24">
													<span class="input-group-btn">
														<a href="javascript:void(0)" class="btn btn-primary"><i class="fa fa-clock-o"></i></a>
													</span>
												</div>
											</div>
										</div>
										<div class="form-group">
											<label for="AFTERNOON_END" class="col-md-4 control-label">Aprés midi - Heure de fin</label>
											<div class="col-md-6">
												<div class="input-group bootstrap-timepicker">
													<input type="text"  name="AFTERNOON_END" id="AFTERNOON_END" value="<?php echo $sets->AFTERNOON_END; ?>" class="form-control input-timepicker24">
													<span class="input-group-btn">
														<a href="javascript:void(0)" class="btn btn-primary"><i class="fa fa-clock-o"></i></a>
													</span>
												</div>
											</div>
										</div>
										*/ ?>
										<div class="form-group">
											<label for="DISTANCE_PROX" class="col-md-4 control-label">Périmètre de recherche des contacts à proximité (en km)</label>
											<div class="col-md-6">
												<input type="text" placeholder="Périmètre de recherche des contacts à proximité (km)" class="form-control" name="DISTANCE_PROX" id="DISTANCE_PROX" value="<?php echo $sets->DISTANCE_PROX; ?>" required>
											</div>
										</div>
										<div class="form-group">
											<label for="TX_TVA" class="col-md-4 control-label">Taux de TVA (%)</label>
											<div class="col-md-6">
												<input type="text" placeholder="Taux de TVA en pourcentage" class="form-control" name="TX_TVA" id="TX_TVA" value="<?php echo $sets->TX_TVA; ?>" required>
											</div>
										</div>
										<div class="form-group">
											<label for="GOOGLE_AGENDA_SENDER" class="col-md-4 control-label">Email d'envoi API Google Agenda</label>
											<div class="col-md-6">
												<input type="text" placeholder="Email d'envoi API Google Agenda" class="form-control" name="GOOGLE_AGENDA_SENDER" id="GOOGLE_AGENDA_SENDER" value="<?php echo $sets->GOOGLE_AGENDA_SENDER; ?>" required>
											</div>
										</div>
										<div class="form-group">
											<label for="CLICKSEND_UNAME" class="col-md-4 control-label">User name Click Send (SMS)</label>
											<div class="col-md-6">
												<input type="text" placeholder="Username Click Send" class="form-control" name="CLICKSEND_UNAME" id="CLICKSEND_UNAME" value="<?php echo $sets->CLICKSEND_UNAME; ?>">
											</div>
										</div>
										<div class="form-group">
											<label for="CLICKSEND_KEY" class="col-md-4 control-label">Clé API Click Send (SMS)</label>
											<div class="col-md-6">
												<input type="text" placeholder="Clé API Click Send" class="form-control" name="CLICKSEND_KEY" id="CLICKSEND_KEY" value="<?php echo $sets->CLICKSEND_KEY; ?>">
											</div>
										</div>
										<div class="form-group">
											<label for="SEND_SMS_CUST" class="col-md-4 control-label">Envoyer un SMS de confirmation de RDV au client</label>
											<div class="col-md-6">
												<select class="form-control" name="SEND_SMS_CUST" id="SEND_SMS_CUST">
													<option value="0" <?php echo (int)$sets->SEND_SMS_CUST == 0 ? 'selected="selected"' : ''; ?>>Non</option>
													<option value="1" <?php echo (int)$sets->SEND_SMS_CUST == 1 ? 'selected="selected"' : ''; ?>>Oui</option>
												</select>
											</div>
										</div>
										<div class="form-group">
											<label for="STATUS_MISS_CONFIRM" class="col-md-4 control-label">Statut des contacts à surligner en cas de manque de confirmateur</label>
											<div class="col-md-6">
												<select data-placeholder="Choisissez le statut..." class="select-chosen" id="STATUS_MISS_CONFIRM" name="STATUS_MISS_CONFIRM" class="form-control">
													<option value=""></option>
													<?php
														$sts = Setting::getAllStatus();
														if ($sts) {
															foreach($sts as $st) {
																echo '<option value="'.$st['id_statuscont'].'" '.($st['id_statuscont'] == $sets->STATUS_MISS_CONFIRM ? 'selected="selected"' : '').'>'.$st['name_statuscont'].'</option>';
															}
														}
													?>
												</select>
											</div>
										</div>
										<input type="hidden" name="action" id="action" value="update-settings" />
										<div class="form-group form-actions">
											<button class="btn btn-sm btn-primary" type="submit" id="btupdateset"><i class="fa fa-user"></i> Update</button>
											<button class="btn btn-sm btn-warning" type="reset"><i class="fa fa-repeat"></i> Reset</button>
										</div>										
									</form>
								</div>
							</div>
						</div>
					</div>
					<div id="tabs-statuscont" class="tab-pane">
						<h2>Statut prospects (Télépro)</h2>
						<?php echo $outstatus; ?>
					</div>
					<div id="tabs-statuscontconf" class="tab-pane">
						<h2>Statut clients (Confirmateur)</h2>
						<?php echo $outstatusconf; ?>
					</div>
					<div id="tabs-campains" class="tab-pane">
						<h2>Campagnes</h2>
						<?php echo $outcamps; ?>
					</div>
					<div id="tabs-teams" class="tab-pane">
						<h2>Teams</h2>
						<?php echo $outteams; ?>
					</div>
					<div id="tabs-profil" class="tab-pane">
						<h2>Profiles</h2>
						<?php echo $outprofils; ?>
					</div>
					<div id="tabs-mandator" class="tab-pane">
						<h2>Maitres d'oeuvres</h2>
						<?php echo $outmandators; ?>
					</div>
					<div id="tabs-contributor" class="tab-pane">
						<h2>Délégataires</h2>
						<?php echo $outcontributors; ?>
					</div>
					<div id="tabs-material" class="tab-pane">
						<h2>Matériaux</h2>
						<?php echo $outmaterials; ?>
					</div>
					<div id="tabs-plafond" class="tab-pane">
						<h2>Plafonds sociaux</h2>
						<?php echo $outplafonds; ?>
					</div>
					<div id="tabs-bonus" class="tab-pane">
						<h2>Grille de bonus</h2>
						<?php echo $outbonus; ?>
					</div>
					<?php /*
					<div id="tabs-rdv-range" class="tab-pane">
						<h2>Tranche horaire planning</h2>
						<?php echo $outrdvrange; ?>
					</div>
					*/ ?>
					<div id="tabs-cumac" class="tab-pane">
						<h2>Calcul des CUMACS</h2>
						<?php echo $outcumacs; ?>
					</div>
					<!-- <div id="tabs-parrain" class="tab-pane">
						<h2>Parrainage / Filleuls</h2>
						<?php //echo $outparrain; ?>
					</div> -->
				</div>
			</div>
			
		</div>
	</div>
    <!-- END Quick Stats -->

</div>
<!-- END Page Content -->

<?php include 'inc/page_footer.php'; ?>

<!-- Remember to include excanvas for IE8 chart support -->
<!--[if IE 8]><script src="js/helpers/excanvas.min.js"></script><![endif]-->

<?php include 'inc/template_scripts.php'; ?>

<!-- Load and execute javascript code used only in this page -->
<!-- <script src="js/pages/lottery.js"></script> -->
<script>	
		$(document).ready(function() {
			$('#frminfoset').submit(function() { 		
				$('#btupdateset').prop( "disabled", true );
				jQuery(this).ajaxSubmit({
					dataType:'json',
					success : function (resp) {	
						if (resp.code == 'SUCCESS') { 						
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
						$('#btupdateset').prop( "disabled", false);	
					},
					error : function() {
						console.log('NO');
						$('#btupdateset').prop( "disabled", false);	
					}
				}); 
				return false; 
			}); 
		});
</script>


<?php include 'inc/template_end.php'; ?>