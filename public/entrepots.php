<?php include 'inc/config.php'; ?>
<?php $outch = GridManager::getGrid('iso_entrepots'); ?>
<?php include 'inc/template_start.php'; ?>
<?php include 'inc/page_head.php'; ?>

<!-- Page content -->
<div id="page-content" class="page-entrepots">  
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
                    <a href="entrepot.php?id_entrepot=0" class="btn btn-xs btn-info">Nouvel entrepôt</a>
                </div>
            </div>
			<h2>Liste des <strong>entrepôts</strong></h2>
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
        $(document).on('click', '.btdelent', function() {            
            if (confirm('Etes vous sure de vouloir supprimer cet entrepot ?')) {
                var obj = $(this);
                HoldOn.open();
                $.post('crmajax.php', {action: 'delete-entrepot', ident: obj.attr('data-id')}, function(resp) {
                    HoldOn.close();
					if (resp.code == 'SUCCESS') {
                        $('#refresh_list_entrepots').click();
					} else
						$.bootstrapGrowl('<h4>Erreur!</h4> <p>' + resp.message + '</p>', {
							type: 'danger',
							delay: 2500,
							allow_dismiss: true
						});

					$('.tooltip.in').remove();

                }, 'json');
            }
            return false;
        });
    });
</script>

<?php include 'inc/template_end.php'; ?>