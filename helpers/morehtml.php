<?php defined('SYSPATH') or die('No direct script access.');
/**
 * html Class
 */
class morehtml {

    public static function anchor_l10n($uri, $title_key, $attributes = NULL, $protocol = NULL) {
        return html::anchor($uri, Kohana::lang($title_key), $attributes, $protocol);
    }

    public static function anchor_if_allowed($rights, $contr, $sufix = NULL, $prefix = NULL, $msg_key = NULL) {
        $output = '';
        if ($rights=='*' || strpos($rights, $contr)!==false) {
            if (!$msg_key) $msg_key = 'model.title-'.$contr;
            
            if ($prefix) $output .= $prefix;
            $output .= html::anchor('admin/'.$contr, Kohana::lang($msg_key));
            if ($sufix) $output .= $sufix;
        }
        return $output;
    }


    public static function anchoredit_if_allowed($rights, $contr, $sufix = NULL, $prefix = NULL, $msg_key = NULL) {
        $output = '';
        if ($rights=='*' || strpos($rights, $contr)!==false) {
            if (!$msg_key) $msg_key = 'model.action-create';
            
            if ($prefix) $output .= $prefix;
            $output .= html::anchor('admin/'.$contr."/edit", '&nbsp;&nbsp;&nbsp;&nbsp;+ '.Kohana::lang($msg_key));
            if ($sufix) $output .= $sufix;
        }
        return $output;
    }
}
