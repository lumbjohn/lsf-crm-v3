<?php

include './inc/isobl.php';

function handleLogin($request_input)
{
    if (!checkFields($request_input, array('email', 'psw'))) {
        errorJSON(array('message' => 'Champs obligatoires manquants'));
    }

    $user = CrmUser::findOne(array('email' => $request_input['email']));
    if (!$user) {
        CrmAction::create(array('id_crmuser' => '0', 'table_action' => 'CRMUsers', 'id_entity' => '0', 'type_action' => 'LOGIN ECHEC EMAIL', 'date_action' => date('Y-m-d H:i:s'), 'details_action' => 'IP:' . Tool::getRealIpAddr() . ' - EMAIL:' . $request_input['email']));
        errorJSON(array('message' => 'Utilisateur non enregistré'));
    }

    if ($user->psw == md5($request_input['psw'])) {
        session_start();
        _session(['crmloggin' => true]);
        _session(['crm_user' => serialize($user)]);
        CrmUser::update(array('date_last_login' => date('Y-m-d H:i:s'), 'ip_login' => Tool::getRealIpAddr()), array('id_crmuser' => $user->id_crmuser));
        CrmAction::create(array('id_crmuser' => $user->id_crmuser, 'table_action' => 'CRMUsers', 'id_entity' => $user->id_crmuser, 'type_action' => 'LOGIN', 'date_action' => date('Y-m-d H:i:s')));
        successJSON(array('OK' => 'OK'));
    } else {
        CrmAction::create(array('id_crmuser' => '0', 'table_action' => 'CRMUsers', 'id_entity' => '0', 'type_action' => 'LOGIN ECHEC', 'date_action' => date('Y-m-d H:i:s'), 'details_action' => 'IP:' . Tool::getRealIpAddr() . ' - EMAIL:' . $request_input['email']));
        errorJSON(array('message' => 'Informations incorrectes'));
    }
}

handleLogin($_POST);
