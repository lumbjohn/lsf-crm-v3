<?php include 'inc/config.php'; ?>
<?php $outch = GridManager::getGrid('iso_rdv', $currentUser); ?>
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
			<h2>Liste des <strong>Rendez-Vous</strong></h2>
        </div>
        <!-- END eShop Overview Title -->

        <!-- eShop Overview Content -->
        <div class="row">
                <div class="col-md-12 col-lg-12">
                    <div class="row push">
                        <?php echo $outch; ?>
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
	$(document).ready(function() {
		$(document).on('click', '.btconfrdv', function() {
			var cursts = $(this).attr('data-status');
			var str = cursts == '0' ? 'Confirmer' : 'Valider';
			if (confirm(str+' le RDV ?')) {	
				var obj = $(this);
				HoldOn.open();
				$.post('crmajax.php', {action:'confirm-rdv', idrdv:obj.attr('data-id'), status:cursts}, function(resp) {
					HoldOn.close();
					if (resp.code == 'SUCCESS') {
						$('.tooltip.in').remove();
						if (cursts == '0') {
							obj.attr('title', 'Valider le RDV');
							obj.attr('data-original-title', 'Valider le RDV');
							obj.attr('data-status', '1');
							obj.parents('td').prev().text('Confirmé (A valider)');
							$("body").tooltip({ selector: '[data-toggle="tooltip"]' });
						}
						else
						if (cursts == '1') {
							obj.parents('td').prev().text('Validé');
							obj.remove();							
						}
					}
					else
						alert(resp.message);
				}, 'json');	
			}
			return false;
		});
		
		$(document).on('click', '.btdelrdv', function() {
			if (confirm('Supprimer le RDV ?')) {	
				var obj = $(this);
				HoldOn.open();
				$.post('crmajax.php', {action:'delete-rdv', idrdv:obj.attr('data-id')}, function(resp) {
					HoldOn.close();
					if (resp.code == 'SUCCESS') {
						$('#refresh_list_rdvs').click();
					}
					else
						alert(resp.message);
				}, 'json');	
			}
			return false;
		});
		
	});
</script>

<?php include 'inc/template_end.php'; ?>