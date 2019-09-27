<ul class="nav-horizontal text-center">
	<?php if ($currentUser->id_profil == 1) { ?>
		<li <?php echo strstr($template['active_page'], 'index') ? 'class="active"' : ''; ?>>
			<a href="index.php"><i class="fa fa-bar-chart"></i> Tableau de bord</a>
		</li>
	<?php } ?>
	<li <?php echo strstr($template['active_page'], 'contact') ? 'class="active"' : ''; ?>>
		<a href="contacts.php"><i class="gi gi-parents"></i> Clients / Prospects</a>
	</li>
	<?php if ($currentUser->id_profil == 1) { ?>
		<li <?php echo strstr($template['active_page'], 'entrepot') ? 'class="active"' : ''; ?>>
			<a href="entrepots.php"><i class="gi gi-shop_window"></i> Installateurs</a>
		</li>
		<li <?php echo strstr($template['active_page'], 'installator') ? 'class="active"' : ''; ?>>
			<a href="installators.php"><i class="gi gi-cars"></i> Camions</a>
		</li>
	<?php } ?>
	<li <?php echo strstr($template['active_page'], 'rdv') ? 'class="active"' : ''; ?>>
		<a href="rdvs.php"><i class="gi gi-list"></i> Rendez vous</a>
	</li>
	<li <?php echo strstr($template['active_page'], 'planning') ? 'class="active"' : ''; ?>>
		<a href="planning.php"><i class="gi gi-calendar"></i> Planning</a>
	</li>
	<?php if ($currentUser->id_profil == 1) { ?>
		<li <?php echo strstr($template['active_page'], 'crmusers') ? 'class="active"' : ''; ?>>
			<a href="crmusers.php"><i class="fa fa-user-secret"></i> Utilisateurs CRM</a>
		</li>
		<li <?php echo strstr($template['active_page'], 'settings') ? 'class="active"' : ''; ?>>
			<a href="settings.php"><i class="gi gi-settings"></i> Paramétrage</a>
		</li>
	<?php } ?>
</ul>
