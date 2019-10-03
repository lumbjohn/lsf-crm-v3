<?php

namespace App;

class Doc
{
    public static function findOne($conds)
    {
        return DbQuery::query('iso_contacts_docs', '*', $conds, true);
    }

    public static function getBy($conds)
    {
        return DbQuery::query('iso_contacts_docs', '*', $conds);
    }

    public static function create($flds)
    {
        return DbQuery::insert('iso_contacts_docs', $flds);
    }

    public static function delete($conds)
    {
        return DbQuery::delete('iso_contacts_docs', $conds);
    }

}
