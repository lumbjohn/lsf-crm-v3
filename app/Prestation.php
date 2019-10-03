<?php

namespace App;

class Prestation
{
    public static function findOne($conds)
    {
        return DbQuery::query('iso_prestations', '*', $conds, true);
    }

    public static function findDoublonFiscal($prest)
    {
        $wh = "";
        for ($i = 1; $i <= 5; $i++) {
            if ($prest->{'no_fiscal_' . $i} != '') {
                for ($j = 1; $j <= 5; $j++)
                    $wh .= ($wh != '' ? ' OR ' : '') . " no_fiscal_" . $j . " = " . $prest->{'no_fiscal_' . $i};
            }
        }
        if ($wh == '')
            return false;

        $wh = '(' . $wh . ') AND id_contact <> ' . $prest->id_contact;
        return DbQuery::querySimple('iso_prestations', '*', $wh, true);
    }

    public static function getNextNoFac()
    {
        $res = DbQuery::query('iso_prestations', 'MAX(nofac) as MFAC', '', true);
        return $res && (int)$res->MFAC > 0 ? (int)$res->MFAC + 1 : 7720;
    }

    public static function create($flds)
    {
        return DbQuery::insert('iso_prestations', $flds);
    }

    public static function update($flds, $wh)
    {
        return DbQuery::update('iso_prestations', $flds, $wh);
    }

    public static function delete($conds)
    {
        return DbQuery::delete('iso_prestations', $conds);
    }

}
