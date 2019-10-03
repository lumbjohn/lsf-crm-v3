<?php

namespace App;

class Comment
{
    public static function findOne($conds)
    {
        return DbQuery::query('iso_comments', '*', $conds, true);
    }

    public static function getBy($conds)
    {
        return DbQuery::query('iso_comments c LEFT JOIN iso_crmusers u ON u.id_crmuser = c.id_user_comment', 'c.*, u.user_name', $conds);
    }

    public static function getFirstRecall($dt, $id_crmuser)
    {
        return DbQuery::querySimple('iso_comments cm
								INNER JOIN iso_contacts c ON c.id_contact = cm.id_contact',
            'cm.id_comment, cm.text_comment, cm.date_recall, c.id_contact, c.raison_sociale, c.first_name, c.last_name, c.tel1, c.email, c.post_code, c.city',
            "cm.type_comment = 1 AND cm.date_recall < '" . $dt . "' AND is_read = 0 AND (c.id_crmuser = " . $id_crmuser . " OR c.id_crmuser_conf = " . $id_crmuser . ")",
            true);
    }

    public static function getRecall($conds)
    {
        return DbQuery::query('iso_comments cm
								INNER JOIN iso_contacts c ON c.id_contact = cm.id_contact
								LEFT JOIN iso_crmusers u ON u.id_crmuser = c.id_crmuser',
            'cm.id_comment, cm.text_comment, cm.date_recall, c.id_contact, c.first_name, c.last_name',
            $conds);
    }

    public static function create($flds)
    {
        return DbQuery::insert('iso_comments', $flds);
    }

    public static function update($flds, $wh)
    {
        return DbQuery::update('iso_comments', $flds, $wh);
    }

    public static function delete($conds)
    {
        return DbQuery::delete('iso_comments', $conds);
    }
}
