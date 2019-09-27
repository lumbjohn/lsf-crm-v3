            <!-- Footer -->
            <footer class="clearfix">
                <div class="pull-right">
                    Crafted with <i class="fa fa-heart text-danger"></i> by <a href="<?php echo $template['url']; ?>" target="_blank">LSF Energie CRM <?php echo date('d/m/Y H:i');?></a>
                </div>
                <div class="pull-left">
                    <span id="year-copy"></span> &copy; <a href="<?php echo $template['url']; ?>" target="_blank"><?php echo $template['name'] . ' ' . $template['version']; ?></a>
                </div>
            </footer>
            <!-- END Footer -->
        </div>
        <!-- END Main Container -->
    </div>
    <!-- END Page Container -->
</div>
<!-- END Page Wrapper -->

<!-- Scroll to top link, initialized in js/app.js - scrollToTop() -->
<a href="#" id="to-top"><i class="fa fa-angle-double-up"></i></a>

<!-- User Settings, modal which opens from Settings link (found in top right user menu) and the Cog link (found in sidebar user info) -->
<div id="modal-crmuser" class="modal fade" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <!-- Modal Header -->
            <div class="modal-header text-center">
                <h2 class="modal-title"><i class="fa fa-pencil"></i> Utilisateur CRM</h2>
            </div>
            <!-- END Modal Header -->

            <!-- Modal Body -->
            <div class="modal-body">
                <form action="index.php" method="post" enctype="multipart/form-data" class="form-horizontal form-bordered" id="frmcrmuser" onsubmit="return false;">
                    <fieldset>
						<div class="form-group text-center" id="row_dts">
							<div class="col-md-4"><label class="control-label">Date création</label><br><span id="date_create"></span></div>
							<div class="col-md-4"><label class="control-label">Date modif.</label><br><span id="date_upd"></span></div>
							<div class="col-md-4"><label class="control-label">Denière connexion</label><br><span id="date_last_login"></span></div>
						</div>
                        <div class="form-group">
                            <label class="col-md-4 control-label" for="user_name">Username</label>
                            <div class="col-md-8">
                                <input type="text" id="user_name" name="user_name" class="form-control" value="" required>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-md-4 control-label" for="email">Email</label>
                            <div class="col-md-8">
                                <input type="email" id="email" name="email" class="form-control" value="" required>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-md-4 control-label" for="email">Téléphone</label>
                            <div class="col-md-8">
                                <input type="text" id="tel" name="tel" class="form-control" value="">
                            </div>
                        </div>
						<?php if (CrmUser::isAdmin($currentUser)) { ?>
                            <div class="form-group">
                                <label class="col-md-4 control-label" for="id_profil">Profile</label>
                                <div class="col-md-8">
                                    <select data-placeholder="Choisissez le profile..." class="select-chosen" id="id_profil" name="id_profil" class="form-control">
                                        <?php
                                            $profils = Profil::getAll();
                                            if ($profils) {
                                                foreach($profils as $profil) {
                                                    echo '<option value="'.$profil['id_profil'].'">'.$profil['name_profil'].'</option>';
                                                }
                                            }
                                        ?>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group" id="row_depts">
                                <label class="col-md-4 control-label" for="depts">Départements</label>
                                <div class="col-md-8">
                                    <select data-placeholder="Choisissez les départements..." class="select-chosen" id="depts" name="depts" class="form-control" multiple>
                                        <?php
                                            $depts = Setting::getDepartments();
                                            if ($depts) {
                                                foreach($depts as $dept) {
                                                    echo '<option value="'.$dept['code'].'">'.$dept['name'].' ('.$dept['code'].')</option>';
                                                }
                                            }
                                        ?>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group" id="row_team">
                                <label class="col-md-4 control-label" for="id_team">Team</label>
                                <div class="col-md-8">
                                    <select data-placeholder="Choisissez la Team..." class="select-chosen" id="id_team" name="id_team" class="form-control">
                                        <?php
                                            $teams = Team::getAll();
                                            if ($teams) {
                                                foreach($teams as $team) {
                                                    echo '<option value="'.$team['id_team'].'">'.$team['name_team'].'</option>';
                                                }
                                            }
                                        ?>
                                    </select>
                                </div>
                            </div>
						<?php } ?>
                        <div class="form-group">
                            <label class="col-md-4 control-label" for="psw">Mot de passe</label>
                            <div class="col-md-8">
								<input type="password" id="psw" name="psw" class="form-control">
                            </div>
                        </div>
                    </fieldset>
                    <div class="form-group form-actions">
                        <div class="col-xs-12 text-right">
							<input type="hidden" id="id_crmuser" name="id_crmuser"  value="">
                            <button type="button" class="btn btn-sm btn-default" data-dismiss="modal">Close</button>
                            <button type="submit" class="btn btn-sm btn-primary">Save Changes</button>
                        </div>
                    </div>
                </form>
            </div>
            <!-- END Modal Body -->
        </div>
    </div>
</div>
<!-- END User Settings -->

<!-- Argu commerciale -->
<div id="modal-display-recall" class="modal fade" tabindex="-1" role="dialog" aria-hidden="true" data-id="" data-cli="">
    <div class="modal-dialog">
        <div class="modal-content">
            <!-- Modal Header -->
            <div class="modal-header text-center">
                <h2 class="modal-title"><i class="fa fa-phone"></i> Rappel client</h2>
            </div>
            <!-- END Modal Header -->

            <!-- Modal Body -->
            <div class="modal-body">
				<div class="recinfcli">
					<div id="recallinfodt"></div>
					<div id="recallinfocli1"></div>
					<div id="recallinfocli2"></div>
					<div id="recallinfocli3"></div>
					<div id="recallinfocli4"></div>
				</div>
				<div class="text-center">
					<div class="btn-group">
                        <a href="javascript:void(0)" data-toggle="dropdown" class="btn btn-danger dropdown-toggle">Me rappeler plus tard <span class="caret"></span></a>
                        <ul class="dropdown-menu text-left" id="lstremind">
                            <li><a href="javascript:void(0)" data-id="5">Dans 5 minutes</a></li>
							<li><a href="javascript:void(0)" data-id="10">Dans 10 minutes</a></li>
							<li><a href="javascript:void(0)" data-id="15">Dans 15 minutes</a></li>
							<li><a href="javascript:void(0)" data-id="30">Dans 30 minutes</a></li>
							<li><a href="javascript:void(0)" data-id="60">Dans 1 heure</a></li>
							<li><a href="javascript:void(0)" data-id="120">Dans 2 heures</a></li>
                            
                        </ul>
                    </div>
					<div class="btn-group">
                        <a href="javascript:void(0)" data-toggle="dropdown" class="btn btn-success dropdown-toggle">Ne plus me rappeler <span class="caret"></span></a>
                        <ul class="dropdown-menu text-left" id="lstvalidrec">
                            <li><a href="javascript:void(0)" data-id="0">Fermer</a></li>
                            <li><a href="javascript:void(0)" data-id="1">Allez sur la fiche client</a></li>
                        </ul>
                    </div>
				</div>
            </div>
            <!-- END Modal Body -->
        </div>
    </div>
</div>
<!-- END Argu commerciale -->