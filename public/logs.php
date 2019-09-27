<?php include 'inc/config.php'; ?>
<?php $outlogs = GridManager::getGrid('iso_crmactions', $currentUser); ?>
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
			<h2>Liste des <strong>actions utilisateurs</strong></h2>
        </div>
        <!-- END eShop Overview Title -->

        <!-- eShop Overview Content -->
        <div class="row">
                <div class="col-md-12 col-lg-12">
                    <div class="row push">
                        <?php echo $outlogs; ?>
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
		//
	});
</script>

<?php include 'inc/template_end.php'; ?>