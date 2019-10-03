<?php

namespace App;

class Template
{
    public static function displayListRDV($plans, $curusr, $sets)
    {
        $str = '';

        foreach ($plans as $plan) {
            $classdis = round($plan['dis'] / 1000) > $sets->DISTANCE_RDV_MAX ? 'danger' : 'success';
            $infsup = round($plan['dis'] / 1000) > $sets->DISTANCE_RDV_MAX ? '<i class="fa fa-warning" data-toggle="tooltip" title="Attention : La distance dépasse la limite de ' . $sets->DISTANCE_RDV_MAX . 'km"></i>' : '';
            $str .= '
					<tr>
						<td>
							<a href="tour.php?id_planning=' . $plan['id_planning'] . '&id_contact=' . $curusr->id_contact . '">
								<div class="pull-left">
									<small class="label label-info"><i class="fa fa-clock-o"></i> ' . date('H:i', strtotime($plan['hour_start'])) . ' - ' . date('H:i', strtotime($plan['hour_end'])) . '</small>
								</div>
								<div class="pull-right">
									<small class="text-danger">' . $plan['nbrdv'] . ' RDV</small>
								</div>
								<h4 style="text-align:center;padding:10px 0;">
									<strong>' . Tool::fulldatestr($plan['date_planning']) . '</strong>
								</h4>
								<div class="pull-left">
									<small class="text-warning"><i class="gi gi-kiosk"></i> ' . $plan['entrepot_name'] . '</small>
								</div>
								<div class="pull-right">
									<small class="label label-' . $classdis . '"><i class="fa fa-car"></i> ' . Tool::distancestr($plan['dis']) . ' ' . $infsup . '</small>
								</div>
							</a>
						</td>
					</tr>';
        }

        return $str;
    }

    public static function displayListNewRDV($curusr, $sets, $dtrdv = '', $ranges = '')
    {
        $str = '';
        $plans = Planning::getDispo($curusr->id_contact, $curusr->geolat, $curusr->geolng, $sets->DISTANCE_RDV, $dtrdv, $ranges);
        if ($plans) {
            $str .= '
				<div class="col-md-6">
					<div class="block">
						<div class="block-title">
							<h2>Affichage par <strong>Distance</strong></h2>
						</div>
						<div class="row">
							<table class="table table-striped table-vcenter table-condensed table-hover">' . Template::displayListRDV($plans, $curusr, $sets) . '</table>
						</div>
					</div>
				</div>';

            $plans = Planning::getDispo($curusr->id_contact, $curusr->geolat, $curusr->geolng, $sets->DISTANCE_RDV, $dtrdv, $ranges, false);
            $str .= '
				<div class="col-md-6">
					<div class="block">
						<div class="block-title">
							<h2>Affichage par <strong>Date</strong></h2>
						</div>
						<div class="row">
							<table class="table table-striped table-vcenter table-condensed table-hover">' . Template::displayListRDV($plans, $curusr, $sets) . '</table>
						</div>
					</div>
				</div>';
        }

        return $str;
    }
}
