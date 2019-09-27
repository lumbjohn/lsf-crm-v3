<?php include 'inc/config.php'; ?>
<?php $outuser = GridManager::getGrid('iso_crmusers'); ?>
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
                    <a href="#" onclick="showCrmUser(0)" class="btn btn-xs btn-info">Nouvel utilisateur CRM</a>
                </div>
            </div>
			<h2>Liste des <strong>utilisateurs CRM</strong></h2>
        </div>
        <!-- END eShop Overview Title -->

        <!-- eShop Overview Content -->
        <div class="row">
                <div class="col-md-12 col-lg-12">
                    <div class="row push">
                        <?php echo $outuser; ?>
                    </div>
                </div>
            </div>
        <!-- END eShop Overview Content -->
    </div>
    <!-- END eShop Overview Block -->

</div>
<!-- END Page Content -->



<?php include 'inc/page_footer.php'; ?>

<!-- Remember to include excanvas for IE8 chart support -->
<!--[if IE 8]><script src="js/helpers/excanvas.min.js"></script><![endif]-->

<?php include 'inc/template_scripts.php'; ?>

<!-- Load and execute javascript code used only in this page -->
<script>	

	
	function deleteCrmUser(idcrmuser) {
		if (confirm("Voulez vous supprimer l\'utilisateur ?")) {
			HoldOn.open();
			$.post('crmajax.php', {action:'delete-crmuser', idcrmuser:idcrmuser}, function(resp) {
				HoldOn.close();
				if (resp.code == 'SUCCESS') {
					alert('Utilisateur supprimé !');
					$('#refresh_list_crmusers').click();
				}
				else
					alert(resp.message);
				
				
			}, 'json');
		}
		
		return false;
	}

	//$(function(){ Lottery.init(); });
</script>

<?php include 'inc/template_end.php'; ?>