<?php

namespace App;

use Illuminate\Support\Facades\DB;

class DbQuery
{
    public static function query($tb, $fld, $conds = '', $returnone = false, $orderby = '', $groupby = '', $lmtst = 0, $lmtend = 0)
    {
        // global $db;
        $strconds = '';
        if (!empty($conds)) {
            $i = 0;

            foreach ($conds as $k => $v) {
                $strconds .= ($i == 0) ? ' WHERE ' : ' AND ';
                $strconds .= $k . ' = \'' . addslashes($v) . '\' ';
                $i++;
            }
        }
        $strorderby = '';
        if (!empty($orderby))
            $strorderby = 'ORDER BY ' . $orderby;

        $strgroupby = '';
        if (!empty($groupby))
            $strgroupby = 'GROUP BY ' . $groupby;

        $sql = ' SELECT ' . $fld . ' FROM ' . $tb . ' ' . $strconds . ' ' . $strgroupby . ' ' . $strorderby;
        if ($returnone)
            $sql .= ' LIMIT 0, 1 ';
        else
            if ($lmtend > 0)
                $sql .= ' LIMIT ' . $lmtst . ', ' . $lmtend;

        return DbQuery::querySQL($sql, $returnone);
    }

    public static function querySimple($tb, $fld, $wh, $returnone = false, $ord = '', $gby = '', $lmtst = 0, $lmtend = 0)
    {
        $strwh = !empty($wh) ? ' WHERE ' . $wh : '';
        $strord = !empty($ord) ? ' ORDER BY ' . $ord : '';
        $strgby = !empty($gby) ? ' GROUP BY ' . $gby : '';
        $sql = ' SELECT ' . $fld . ' FROM ' . $tb . $strwh . $strgby . $strord;
        if ($returnone)
            $sql .= ' LIMIT 0, 1 ';
        else
            if ($lmtend > 0)
                $sql .= ' LIMIT ' . $lmtst . ', ' . $lmtend;

        return DbQuery::querySQL($sql, $returnone);
    }

    public static function querySQL($sql, $returnone = false)
    {
        // global $db;
        //echo '##'.$sql;
        //error_log("INFO : ##".$sql.PHP_EOL);
        $res = DB::statement(DB::raw($sql)); // $res = mysqli_query($db, $sql);
        if (!$res)
            return false;

        //try{
        if ($returnone)
            return $res[0]; // return mysqli_fetch_object($res);
        else
            return $res;
        /*} catch(Exception $e){
            error_log("INFO : " . $e->getMessage());
        }*/

    }

    public static function insert($tb, $flds)
    {
        // global $db;
        $sql = ' INSERT INTO ' . $tb;
        $fl = '';
        $vl = '';
        $i = 0;
        foreach ($flds as $k => $v) {
            $fl .= ($i == 0) ? ' ( ' : ' , ';
            $fl .= $k;

            $vl .= ($i == 0) ? ' ( ' : ' , ';
            $vl .= '\'' . addslashes($v) . '\'';

            $i++;
        }
        $sql .= $fl . ' ) VALUES ' . $vl . ' )';

        //echo $sql;
        //error_log("INFO : INS##".$sql.PHP_EOL);
        $res = DB::insert(DB::raw($sql)); // mysqli_query($db, $sql);
        if (!$res)
            return false;

        $id = DB::getPdo()->lastInsertId(); // mysqli_insert_id($db);
        if ($id && $id > 0)
            return $id;
        else
            return false;

    }

    public static function update($tb, $flds, $wh)
    {
        // global $db;
        $sql = ' UPDATE ' . $tb . ' SET ';
        $i = 0;
        foreach ($flds as $k => $v) {
            $sql .= ($i == 0 ? '' : ',') . $k . '=\'' . addslashes($v) . '\'';
            $i++;
        }

        $sql .= ' WHERE ';
        $i = 0;
        foreach ($wh as $k => $v) {
            $sql .= ($i == 0 ? '' : ' AND ') . $k . '=\'' . addslashes($v) . '\'';
            $i++;
        }

        //echo $sql;
        //error_log("INFO : UPD##".$sql.PHP_EOL);
        $res = DB::update(DB::raw($sql)); // $res = mysqli_query($db, $sql);
        return !$res ? false : true;
    }

    public static function updateSimple($tb, $flds, $wh)
    {
        // global $db;
        $sql = ' UPDATE ' . $tb . ' SET ' . $flds . ' WHERE ' . $wh;
        $res = DB::update(DB::raw($sql)); // mysqli_query($db, $sql);
        //echo $sql.'<br><br>';
        return !$res ? false : true;
    }

    public static function delete($tb, $wh)
    {
        // global $db;
        $sql = ' DELETE FROM ' . $tb . ' WHERE ';
        $i = 0;
        foreach ($wh as $k => $v) {
            $sql .= ($i == 0 ? '' : ' AND ') . $k . '=\'' . addslashes($v) . '\'';
            $i++;
        }

        //echo $sql;
        $res = DB::delete(DB::raw($sql)); // mysqli_query($db, $sql);

        return !$res ? false : true;
    }

    public static function deleteSimple($tb, $wh)
    {
        // global $db;
        $sql = ' DELETE FROM ' . $tb . ' WHERE ' . $wh;
        $res = DB::delete(DB::raw($sql)); // mysqli_query($db, $sql);
        //echo $sql.'<br><br>';
        return !$res ? false : true;
    }
}
