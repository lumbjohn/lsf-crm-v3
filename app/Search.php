<?php

namespace App;

class Search
{
    public static function get($txt, $currentUser)
    {
        $wh = '';
        if ((int)$txt > 0) {
            $whc = " WHERE (c.tel1 LIKE '" . $txt . "%' OR c.tel2 LIKE '" . $txt . "%' OR c.post_code LIKE '" . $txt . "%') ";
            $whi = " WHERE (tel1 LIKE '" . $txt . "%' OR tel2 LIKE '" . $txt . "%' OR post_code LIKE '" . $txt . "%') ";
        } else {
            $whc = " WHERE (c.first_name LIKE'%" . $txt . "%' OR c.last_name LIKE'%" . $txt . "%' OR c.email LIKE'%" . $txt . "%')";
            $whi = " WHERE (installator_name LIKE'%" . $txt . "%' OR email LIKE'%" . $txt . "%')";
        }

        $instl = "";
        if (CrmUser::isAdmin($currentUser))
            $instl = " UNION
					SELECT 1 as type, i.id_installator as id, i.installator_name, i.tel1, i.tel2, i.adr1, i.post_code, i.city, i.email, '', ''
					FROM iso_installators i
					" . $whi;

        $sqlsup = "";
        if (CrmUser::isTelepro($currentUser))
            $sqlsup = " AND u.id_crmuser = " . $currentUser->id_crmuser;
        else
            if (CrmUser::isManager($currentUser))
                $sqlsup = " AND (u.id_team = " . $currentUser->id_team . ($currentUser->teams != '' ? " OR u.id_team IN (" . $currentUser->teams . ")" : '') . " ) ";


        $sql = "SELECT REQ.* FROM (
					SELECT 0 as type, c.id_contact as id, CONCAT(c.first_name, ' ', c.last_name) as name, c.tel1, c.tel2, c.adr1, c.post_code, c.city, c.email, c.dept, u.user_name as conf_name
					FROM iso_contacts c
						LEFT JOIN iso_crmusers u ON u.id_crmuser = c.id_crmuser
					" . $whc . " " . $sqlsup . " " . $instl . "
				) REQ
				LIMIT 0, 30";

        //echo $sql;
        return DbQuery::querySQL($sql);
    }
}
