<?php	
	if (!isset($_GET['pkey']) || $_GET['pkey'] != '099df7FxF5g7') {
		echo 'CANNOT ACCESS !!';	 
		die;
	}
	
    include '../inc/isobl.php';
    error_log("INFO CRON START : ".date('d/m/y H:i').PHP_EOL);
    if ((int)date('H') >= 18 && (int)date('H') <= 19) {
        $sets = (object)Setting::getGlobalSettings();
        if ($sets->SEND_SMS_CUST == '1') {
            $rdvs = RDV::getRDVToRemind();
            if ($rdvs && $rdvs->num_rows > 0) {
                error_log("INFO CRON : ".$rdvs->num_rows." RDV a traiter".PHP_EOL);
                foreach($rdvs as $rdv) {
                    $ct = Contact::findOne(array('id_contact' => $rdv['id_contact']));
                    if ($ct) {
                        //2 - Get Phone number - tel1 or tel2 - start with 6 or 7 - format for sms (remove leading 0, space, char / adding +33 - length 12)
                        $phone = preg_replace('/[^0-9]/', '', $ct->tel1);
                        if (strlen($phone) != 10 || (substr($phone, 0, 2) != '06' && substr($phone, 0, 2) != '07'))
                            $phone = preg_replace('/[^0-9]/', '', $ct->tel2);

                        if (strlen($phone) == 10 && (substr($phone, 0, 2) == '06' || substr($phone, 0, 2) == '07')) {
                            $fullnum = '+33'.substr($phone, 1);
                        }
                        if ($fullnum != '') {                            
                            $txt = 'Bonjour '.$ct->first_name.' '.$ct->last_name.',<br><br>
                                    Nous vous confirmons votre rendez-vous d’installation du '.date('d/m/Y', strtotime($rdv['date_rdv'])).' entre '.$rdv['creneau_start'].' et '.$rdv['creneau_end'].' avec nos équipes pour l\'isolation de votre habitation pour 1€.<br>
                                    VOTRE NUMÉRO DE DOSSIER EST LE : '.$ct->code_dossier.'<br>
                                    Merci de bien noter que ce numéro de dossier est strictement confidentiel et que vous ne devez le communiquer à quiconque sous aucun prétexte. Au contraire : exigez de chaque personne qui pourrait vous solliciter qu’il vous le communique; et même à l’équipe qui viendra isoler votre maison !<br>
                                    N\'hésitez pas à nous appeler au 0 805 240 650 (numéro vert) si vous avez la moindre question.<br><br>
                                    Merci de votre confiance.<br><br>					
                                    L\'équipe Euro Iso.';

                            SMS::SendMessage($txt, $fullnum);
                            RDV::update(array('sms_sent' => '1'), array('id_rdv' => $rdv['id_rdv']));
                            error_log("INFO CRON : MESSAGE ENVOYE RDV ".$rdv['id_rdv']." CLIENT ".$ct->first_name." ".$ct->last_name.PHP_EOL);
                        }
                        else
                            error_log("INFO CRON : MESSAGE NON ENVOYE - FULLNUM INCORRECT ".$fullnum.PHP_EOL);
                    }
                }
            }
        }
    }
?>