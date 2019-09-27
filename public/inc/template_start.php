<!DOCTYPE html>
<!--[if IE 8]>         <html class="no-js lt-ie9"> <![endif]-->
<!--[if IE 9]>         <html class="no-js lt-ie10"> <![endif]-->
<!--[if gt IE 9]><!--> <html class="no-js"> <!--<![endif]-->
    <head>
        <meta charset="utf-8">

        <title><?php echo $template['title'] ?></title>
	
        <meta name="description" content="<?php echo $template['description'] ?>">
        <meta name="author" content="<?php echo $template['author'] ?>">
        <meta name="robots" content="<?php echo $template['robots'] ?>">

        <meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1.0">
		
        <!-- Icons -->
        <!-- The following icons can be replaced with your own, they are used by desktop and mobile browsers -->
        <link rel="shortcut icon" href="img/favicon.png">
        <link rel="apple-touch-icon" href="img/icon57.png" sizes="57x57">
        <link rel="apple-touch-icon" href="img/icon72.png" sizes="72x72">
        <link rel="apple-touch-icon" href="img/icon76.png" sizes="76x76">
        <link rel="apple-touch-icon" href="img/icon114.png" sizes="114x114">
        <link rel="apple-touch-icon" href="img/icon120.png" sizes="120x120">
        <link rel="apple-touch-icon" href="img/icon144.png" sizes="144x144">
        <link rel="apple-touch-icon" href="img/icon152.png" sizes="152x152">
        <link rel="apple-touch-icon" href="img/icon180.png" sizes="180x180">
        <!-- END Icons -->

        <!-- Stylesheets -->
		<link rel="stylesheet" type="text/css" media="screen" href="js/themes/flick/jquery-ui.custom.css" />		
		<link rel="stylesheet" type="text/css" media="screen" href="js/jqgrid/css/ui.jqgrid.css" />	
		
        <!-- Bootstrap is included in its original form, unaltered -->
        <link rel="stylesheet" href="css/bootstrap.min.css">

        <!-- Related styles of various icon packs and plugins -->
        <link rel="stylesheet" href="css/plugins.css">

        <!-- The main stylesheet of this template. All Bootstrap overwrites are defined in here -->
        <link rel="stylesheet" href="css/main.css">

        <!-- Include a specific file here from css/themes/ folder to alter the default theme of the template -->
        <?php if ($template['theme']) { ?><link id="theme-link" rel="stylesheet" href="css/themes/<?php echo $template['theme']; ?>.css"><?php } ?>

        <!-- The themes stylesheet of this template (for using specific theme color in individual elements - must included last) -->
        <link rel="stylesheet" href="css/themes.css">
        <!-- END Stylesheets -->

		<!-- CUSTOM JO -->
		<link rel="stylesheet" href="css/HoldOn.min.css">
		<link rel="stylesheet" href="css/jquery.multiselect.filter.css">
		<link rel="stylesheet" href="css/jquery.multiselect.css">
		
		
        <!-- Modernizr (browser feature detection library) & Respond.js (enables responsive CSS code on browsers that don't support it, eg IE8) -->
        <script src="js/vendor/modernizr-respond.min.js"></script>
		
		<script src="js/vendor/jquery-1.12.0.min.js"></script>
		<script type="text/javascript" src="js/jqgrid/js/i18n/grid.locale-fr.js"></script>
		<script src="js/themes/jquery-ui.custom.min.js" type="text/javascript"></script>
		<script src="js/jqgrid/js/jquery.jqGrid.min.js" type="text/javascript"></script>		
		<?php if (strstr(basename($_SERVER['PHP_SELF']), 'contacts')) { ?>
			<script src="//cdn.jsdelivr.net/jstorage/0.1/jstorage.min.js" type="text/javascript"></script>	
			<script src="//cdn.jsdelivr.net/json2/0.1/json2.min.js" type="text/javascript"></script>
			<script src="//cdn.rawgit.com/gridphp/jqGridState/e06788e68cd9f97db9da4bb4ba1c4a82890aed9e/jqGrid.state.js" type="text/javascript"></script>
		<?php } ?>	
    </head>
    <body>