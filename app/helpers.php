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

function sanitize_for_js($str) {
    $str = addslashes($str);
    $str = preg_replace('/(\r\n|\r|\n)/','\n',$str);
    return $str;
}

function get_request_boolean($val) {
    if ($val === true) return true;
    if ($val === '1') return true;
    if ($val === 1) return true;
    if ($val === 'true') return true;
    return false;
}