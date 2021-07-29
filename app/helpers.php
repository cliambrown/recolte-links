<?php

function slack_special_chars($str) {
    $str = str_replace('&', '&amp;', $str);
    $str = str_replace('<', '&lt;', $str);
    $str = str_replace('>', '&gt;', $str);
    $str = str_replace('*', '∗', $str);
    $str = str_replace('_', '‗', $str);
    $str = str_replace('~', '～', $str);
    return $str;
}