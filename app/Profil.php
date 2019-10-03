<?php

namespace App;

class Profil
{
    public static function getAll()
    {
        return DbQuery::query('iso_profils', '*');
    }
}
