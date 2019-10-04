<?php

if (!function_exists('successJSON')) {
    function successJSON($content)
    {
        return wrapJSON('SUCCESS', $content);
    }
}

if (!function_exists('errorJSON')) {
    function errorJSON($content)
    {
        return wrapJSON('ERROR', $content);
    }
}

if (!function_exists('wrapJSON')) {
    function wrapJSON($code, $content)
    {
        $content = json_encode((object)$content);
        $content = substr($content, 1, strlen($content) - 1);
        $buffer = '{"code":"' . $code . '",' . $content;

        return json_decode($buffer, true);
    }
}

if (!function_exists('checkFields')) {
    function checkFields($arr, $flds)
    {
        $ret = true;
        foreach ($flds as $fld) {
            if (!isset($arr[$fld]) || (empty($arr[$fld]) && $arr[$fld] !== '0')) {
                $ret = false;
                break;
            }
        }
        return $ret;
    }
}

if (!function_exists('getInfoImpot')) {
    function getInfoImpot($nofis, $refavis, &$body, &$elms)
    {
        $url = 'https://cfsmsp.impots.gouv.fr/secavis/';
        $info = Tool::getCurl($url);
        $DOM = new DOMDocument;
        $DOM->loadHTML($info);
        $vstate = $DOM->getElementById('j_id__v_0:javax.faces.ViewState:1')->getAttribute('value');
        if (empty($vstate) || $vstate == null)
            errorJSON(array('message' => 'Erreur avec le site des impots ...'));

        $pst = array(
            'j_id_7:spi' => $nofis,
            'j_id_7:num_facture' => $refavis,
            'j_id_7:j_id_l' => "Valider",
            'j_id_7_SUBMIT' => "1",
            'javax.faces.ViewState' => $vstate
        );
        $url = "https://cfsmsp.impots.gouv.fr/secavis/faces/commun/index.jsf";
        $info = Tool::getCurl($url, $pst);
        $DOM = new DOMDocument;
        $DOM->loadHTML($info);
        $elms = $DOM->getElementsByTagName('td');
        //$body = $DOM->getElementsByTagName('body');
        $body = $DOM->savehtml($DOM->getElementById('principal')); //$body->item(0));

        return;
    }
}

if (!function_exists('IsoCreateRDV')) {
    function IsoCreateRDV($id_entrepot, $num_planning, $date_rdv, $rdv_start, $rdv_end, $duration, $id_contact, $crnstart, $crnend, $statusrdv, $dtcreaterdv = '', $typerdv = '0')
    {
        global $currentUser;

        $entrepot = Entrepot::findOne(array('id_entrepot' => $id_entrepot));
        if (!$entrepot)
            errorJSON(array('message' => 'Entepot invalide !'));

        $contact = Contact::findOne(array('c.id_contact' => $id_contact));
        if (!$contact)
            errorJSON(array('message' => 'Contact invalide !'));


        //1 - check if exist rdv
        /* on ne check plus si il existe rdv sur periode = autorise le chevauchement
        $existrdv = RDV::findExists($entrepot->id_entrepot, $num_planning, $date_rdv, $rdv_start, $rdv_end);
        if ($existrdv)
            errorJSON(array('message' => 'Il existe déjà un rendez vous à cette période !'));
        */

        $starttime = strtotime($date_rdv . ' ' . $rdv_start);
        //2 - get prior rdv
        $priorrdv = RDV::getPriorRDV($entrepot->id_entrepot, $num_planning, $date_rdv, $rdv_start, $typerdv);
        if (!$priorrdv) {
            $deplat = $entrepot->geolat;
            $deplng = $entrepot->geolng;
            $deptime = $starttime;
        } else {
            $deplat = $priorrdv->geolat;
            $deplng = $priorrdv->geolng;
            $deptime = strtotime($date_rdv . ' ' . $priorrdv->rdv_end);
        }


        $dis = 0;
        $strinfo = Tool::getDirection($deplat, $deplng, $contact->geolat, $contact->geolng, $deptime);
        if ($strinfo && !empty($strinfo)) {
            $info = json_decode($strinfo);
            $dis = $info->routes[0]->legs[0]->distance->text;
            if (isset($info->routes[0]->legs[0]->duration_in_traffic)) {
                $del = $info->routes[0]->legs[0]->duration_in_traffic->value > $info->routes[0]->legs[0]->duration->value ? $info->routes[0]->legs[0]->duration_in_traffic->text : $info->routes[0]->legs[0]->duration->text;
                $delval = $info->routes[0]->legs[0]->duration_in_traffic->value > $info->routes[0]->legs[0]->duration->value ? $info->routes[0]->legs[0]->duration_in_traffic->value : $info->routes[0]->legs[0]->duration->value;
            } else {
                $del = $info->routes[0]->legs[0]->duration->text;
                $delval = $info->routes[0]->legs[0]->duration->value;
            }
        }

        if ($priorrdv) {
            /* VERSION DECALAGE DES RDV
            $deptime = strtotime('+ '.$delval.' second', $deptime);
            if ($deptime < $starttime)
                $deptime = $starttime; */
            $deptime = $starttime;
        }
        $endtime = strtotime('+ ' . $duration . ' hour', $deptime);

        //if ($dis == 0)
        //	$dis = Tool::getDistance($entrepot->geolat, $entrepot->geolng, $contact->geolat, $contact->geolng);
        RDV::create(array(
            'id_entrepot' => $entrepot->id_entrepot,
            'num_planning' => $num_planning,
            'id_contact' => $contact->id_contact,
            'date_create' => $dtcreaterdv != '' ? $dtcreaterdv : date('Y-m-d H:i:s'),
            'date_rdv' => $date_rdv,
            'rdv_start' => date('Y-m-d H:i', $deptime),
            'rdv_end' => date('Y-m-d H:i', $endtime),
            'creneau_start' => $crnstart,
            'creneau_end' => $crnend,
            'status_rdv' => (int)$statusrdv,
            'duration' => $duration,
            'distance' => $dis,
            'delay' => $delval,
            'gmap' => $strinfo,
            'type_rdv' => (int)$typerdv
        ));

        CrmAction::create(array('id_crmuser' => $currentUser->id_crmuser, 'table_action' => 'Contacts', 'id_entity' => $contact->id_contact, 'type_action' => 'CREATION RENDEZ-VOUS', 'date_action' => date('Y-m-d H:i:s')));

        $arrupdct = array('date_rdv_pros' => $date_rdv, 'heure_rdv_pros' => date('Y-m-d H:i', $deptime), 'creneau_start' => $crnstart, 'creneau_end' => $crnend);
        if ($st = Setting::getStatusConf(array('after_rdv' => '1'))) {
            $arrupdct['id_statuscontconf'] = $st->id_statuscontconf;
        }
        Contact::update($arrupdct, array('id_contact' => $contact->id_contact));

        $nextrdv = RDV::getNextsRDV($entrepot->id_entrepot, $num_planning, $date_rdv, date('H:i', $deptime), $typerdv);
        if ($nextrdv && $nextrdv->num_rows > 0) {

            $curlat = $contact->geolat;
            $curlng = $contact->geolng;
            $deptime = $endtime;

            $i = 0;
            $decal = 0;
            foreach ($nextrdv as $rdv) {
                $i++;
                $rdvst = strtotime($rdv['date_rdv'] . ' ' . $rdv['rdv_start']);
                $rdven = strtotime($rdv['date_rdv'] . ' ' . $rdv['rdv_end']);

                $strinfo = $i == 1 ? Tool::getDirection($curlat, $curlng, $rdv['geolat'], $rdv['geolng'], $deptime) : $rdv['gmap'];
                if ($strinfo && !empty($strinfo)) {
                    $info = json_decode($strinfo);
                    $dis = $info->routes[0]->legs[0]->distance->text;
                    if (isset($info->routes[0]->legs[0]->duration_in_traffic)) {
                        $del = $info->routes[0]->legs[0]->duration_in_traffic->value > $info->routes[0]->legs[0]->duration->value ? $info->routes[0]->legs[0]->duration_in_traffic->text : $info->routes[0]->legs[0]->duration->text;
                        $delval = $info->routes[0]->legs[0]->duration_in_traffic->value > $info->routes[0]->legs[0]->duration->value ? $info->routes[0]->legs[0]->duration_in_traffic->value : $info->routes[0]->legs[0]->duration->value;
                    } else {
                        $del = $info->routes[0]->legs[0]->duration->text;
                        $delval = $info->routes[0]->legs[0]->duration->value;
                    }

                    if ($i == 1) {
                        $decal = $delval - ($rdvst - $deptime);
                        //echo $decal.' = '.$delval.' -  ('.$rdvst.' - '.$deptime.')';
                        if ($decal < 0)
                            $decal = 0;
                    }
                }

                /* VERSION DECALAGE DES RDV
                $arrupd = array(
                    'rdv_start' => date('Y-m-d H:i', strtotime('+ '.$decal.' second', $rdvst)),
                    'rdv_end' => date('Y-m-d H:i', strtotime('+ '.$decal.' second', $rdven))
                );
                if ($i == 1) {
                    $arrupd['distance'] = $dis;
                    $arrupd['delay'] = $delval;
                    $arrupd['gmap'] = $strinfo;
                }

                RDV::update($arrupd, array('id_rdv' => $rdv['id_rdv'])); */

                if ($i == 1) {
                    $arrupd = array(
                        'distance' => $dis,
                        'delay' => $delval,
                        'gmap' => $strinfo,
                    );

                    RDV::update($arrupd, array('id_rdv' => $rdv['id_rdv']));
                    break;
                }

                $curlat = $rdv['curlat'];
                $curlng = $rdv['curlng'];
            }
        }
    }
}

if (!function_exists('IsoRemoveRDV')) {
    function IsoRemoveRDV($rdv)
    {
        global $currentUser;

        //1 - update distance and delay from prior to all nexts
        $nextrdv = RDV::getNextRDV($rdv->id_entrepot, $rdv->num_planning, $rdv->date_rdv, $rdv->rdv_start, $rdv->type_rdv);
        if ($nextrdv) {
            $priorrdv = RDV::getPriorRDV($rdv->id_entrepot, $rdv->num_planning, $rdv->date_rdv, $rdv->rdv_start, $rdv->type_rdv);
            if ($priorrdv) {
                $deplat = $priorrdv->geolat;
                $deplng = $priorrdv->geolng;
            } else {
                $entrepot = Entrepot::findOne(array('id_entrepot' => $rdv->id_entrepot));
                $deplat = $entrepot->geolat;
                $deplng = $entrepot->geolng;
            }

            $strinfo = Tool::getDirection($deplat, $deplng, $nextrdv->geolat, $nextrdv->geolng, strtotime($nextrdv->date_rdv . ' ' . $nextrdv->rdv_start));
            if ($strinfo && !empty($strinfo)) {
                $info = json_decode($strinfo);
                $dis = $info->routes[0]->legs[0]->distance->text;
                if (isset($info->routes[0]->legs[0]->duration_in_traffic)) {
                    $del = $info->routes[0]->legs[0]->duration_in_traffic->value > $info->routes[0]->legs[0]->duration->value ? $info->routes[0]->legs[0]->duration_in_traffic->text : $info->routes[0]->legs[0]->duration->text;
                    $delval = $info->routes[0]->legs[0]->duration_in_traffic->value > $info->routes[0]->legs[0]->duration->value ? $info->routes[0]->legs[0]->duration_in_traffic->value : $info->routes[0]->legs[0]->duration->value;
                } else {
                    $del = $info->routes[0]->legs[0]->duration->text;
                    $delval = $info->routes[0]->legs[0]->duration->value;
                }
            }

            RDV::update(array('distance' => $dis, 'delay' => $delval, 'gmap' => $strinfo), array('id_rdv' => $nextrdv->id_rdv));
        }


        if (RDV::delete(array('id_rdv' => $rdv->id_rdv)))
            CrmAction::create(array('id_crmuser' => $currentUser->id_crmuser, 'table_action' => 'Contacts', 'id_entity' => $rdv->id_contact, 'type_action' => 'SUPPRESSION RENDEZ-VOUS', 'date_action' => date('Y-m-d H:i:s')));
    }
}

if (!function_exists('_session')) {
    function _session($key = null, $default = null)
    {
        // if (session_status() === PHP_SESSION_NONE) {
        session_start();
        // }

        if ($key === null) {
            return $_SESSION;
        }

        if (is_array($key)) {
            $_SESSION = array_merge($_SESSION, $key);
            return null;
        }

        return $_SESSION[$key] ?? $default;
    }
}

if (!function_exists('_user')) {
    function _user()
    {
        return unserialize(_session('crm_user'), ['allowed_classes' => true]);
    }
}

if (!function_exists('filename')) {
    function filename($url)
    {
        // Extract filename from URL
        $parts = explode('/', $url);
        return end($parts);
    }
}

if (!function_exists('dtformat')) {
    function dtformat($dt)
    {
        try {
            $when = new DateTime($dt);
            $dt = $when->format('Y-m-d H:i:s');
            return $dt;
        } catch (Exception $e) {
            return false;
        }
    }
}
