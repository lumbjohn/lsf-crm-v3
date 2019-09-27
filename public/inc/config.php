<?php
/**
 * config.php
 *
 * Author: pixelcave
 *
 * Configuration file. It contains variables used in the template as well as the primary navigation array from which the navigation is created
 *
 */


ini_set('display_errors', 1);
error_reporting(E_ALL);

include __DIR__ . '/isobl.php'; // business logic php file
include 'gridmanager.php'; // business logic php file

session_start();
if (isset($_GET['logoff'])) {
	$_SESSION['crmloggin'] = false;
	session_destroy();
} else {
	if (!isset($_SESSION['crmloggin']) && !isset($ispagelogin)) {
		header('location:login.php');
		die;
	} else
	if (isset($_SESSION['crmloggin']) && $_SESSION['crmloggin'] == true) {
		$currentUser = unserialize($_SESSION['crm_user']);

		if (!CrmUser::isAdmin($currentUser) && strstr(basename($_SERVER['PHP_SELF']), 'index'))
			header('location:contacts.php');
	}

	if (!isset($ispagelogin) && (!isset($currentUser) || !$currentUser || (int)$currentUser->id_profil == 0)) {
		header('location:login.php');
		die;
	}
}

/* Template variables */
$template = array(
	'name'              => 'LSF Energie',
	'version'           => '1.0',
	'author'            => 'JDDev',
	'robots'            => 'noindex, nofollow',
	'title'             => 'LSF Energie - CRM de Gestion',
	'description'       => 'LSF Energie - CRM de Gestion',
	'url'				=> 'https://lsf-iso.fr/crm',
	// true                     enable page preloader
	// false                    disable page preloader
	'page_preloader'    => false,
	// true                     enable main menu auto scrolling when opening a submenu
	// false                    disable main menu auto scrolling when opening a submenu
	'menu_scroll'       => true,
	// 'navbar-default'         for a light header
	// 'navbar-inverse'         for a dark header
	'header_navbar'     => 'navbar-default',
	// ''                       empty for a static layout
	// 'navbar-fixed-top'       for a top fixed header / fixed sidebars
	// 'navbar-fixed-bottom'    for a bottom fixed header / fixed sidebars
	'header'            => '',
	// ''                                               for a full main and alternative sidebar hidden by default (> 991px)
	// 'sidebar-visible-lg'                             for a full main sidebar visible by default (> 991px)
	// 'sidebar-partial'                                for a partial main sidebar which opens on mouse hover, hidden by default (> 991px)
	// 'sidebar-partial sidebar-visible-lg'             for a partial main sidebar which opens on mouse hover, visible by default (> 991px)
	// 'sidebar-mini sidebar-visible-lg-mini'           for a mini main sidebar with a flyout menu, enabled by default (> 991px + Best with static layout)
	// 'sidebar-mini sidebar-visible-lg'                for a mini main sidebar with a flyout menu, disabled by default (> 991px + Best with static layout)
	// 'sidebar-alt-visible-lg'                         for a full alternative sidebar visible by default (> 991px)
	// 'sidebar-alt-partial'                            for a partial alternative sidebar which opens on mouse hover, hidden by default (> 991px)
	// 'sidebar-alt-partial sidebar-alt-visible-lg'     for a partial alternative sidebar which opens on mouse hover, visible by default (> 991px)
	// 'sidebar-partial sidebar-alt-partial'            for both sidebars partial which open on mouse hover, hidden by default (> 991px)
	// 'sidebar-no-animations'                          add this as extra for disabling sidebar animations on large screens (> 991px) - Better performance with heavy pages!
	'sidebar'           => 'sidebar-partial',
	// ''                       empty for a static footer
	// 'footer-fixed'           for a fixed footer
	'footer'            => '',
	// ''                       empty for default style
	// 'style-alt'              for an alternative main style (affects main page background as well as blocks style)
	'main_style'        => '',
	// ''                           Disable cookies (best for setting an active color theme from the next variable)
	// 'enable-cookies'             Enables cookies for remembering active color theme when changed from the sidebar links (the next color theme variable will be ignored)
	'cookies'           => '',
	// 'night', 'amethyst', 'modern', 'autumn', 'flatie', 'spring', 'fancy', 'fire', 'coral', 'lake',
	// 'forest', 'waterlily', 'emerald', 'blackberry' or '' leave empty for the Default Blue theme
	'theme'             => '',
	// ''                       for default content in header
	// 'horizontal-menu'        for a horizontal menu in header
	// This option is just used for feature demostration and you can remove it if you like. You can keep or alter header's content in page_head.php
	'header_content'    => '',
	'active_page'       => basename($_SERVER['PHP_SELF'])
);

/* Primary navigation array (the primary navigation will be created automatically based on this array, up to 3 levels deep) */
$primary_nav = array();
if (!isset($ispagelogin)) {
	if ($currentUser->id_profil != 2) {
		$primary_nav[] = array(
			'name'  => 'Tableau de bord',
			'url'   => 'index.php',
			'icon'  => 'fa fa-bar-chart'
		);
	}

	$primary_nav[] =  array(
		'name'  => 'Clients / Prospects',
		'url'   => 'contacts.php',
		'icon'  => 'gi gi-parents'
	);

	if ($currentUser->id_profil == 1) {
		$primary_nav[] = array(
			'name'  => 'Installateurs',
			'url'   => 'installators.php',
			'icon'  => 'gi gi-cars'
		);

		$primary_nav[] = array(
			'name'  => 'Entrepôts',
			'url'   => 'entrepots.php',
			'icon'  => 'gi gi-shop_window'
		);
	}

	$primary_nav[] = array(
		'name'  => 'Rendez-vous',
		'url'   => 'rdvs.php',
		'icon'  => 'gi gi-list'
	);

	$primary_nav[] = array(
		'name'  => 'Planning',
		'url'   => 'planning.php',
		'icon'  => 'gi gi-calendar'
	);

	if ($currentUser->id_profil == 1) {
		$primary_nav[] = array(
			'name'  => 'Utilisateurs CRM',
			'url'   => 'crmusers.php',
			'icon'  => 'fa fa-user-secret'
		);
		$primary_nav[] = array(
			'name'  => 'Log utilisateurs',
			'url'   => 'logs.php',
			'icon'  => 'fa fa-database'
		);
		$primary_nav[] = array(
			'name'  => 'Paramétrage',
			'url'   => 'settings.php',
			'icon'  => 'gi gi-settings'
		);
	}
}
