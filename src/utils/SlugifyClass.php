<?php

namespace App\utils;

class SlugifyClass
{

    public static function slugify($text){
        // Replace special characters with ASCII equivalents
        $text = iconv('UTF-8', 'ASCII//TRANSLIT', $text);

        // Replace non letter or digits by -
        $text = preg_replace('~[^\pL\d]+~u', '-', $text);

        // Remove unwanted characters
        $text = preg_replace('~[^-\w]+~', '', $text);

        // Trim and lowercase
        $text = strtolower(trim($text, '-'));

        return $text;
    }
}