<?php
/**
 * Markup wrapper
 * @package KvFramework
 * @subpackage SiteHandler
 * @author Greg McWhirter <gsmcwhirter@gmail.com>
 * @copyright Copyright &copy; 2006, Greg McWhirter
 * @license MIT License
 * @version 1.0
 */

/**
 * Markup wrapper
 * @package KvFramework
 * @subpackage SiteHandler
 */
abstract class kvframework_markup{

  /**
   * Generate a link to an AJAX call
   * @param string $text text to create the link on
   * @param array $params Parameters to pass to the function generator
   * @return string Link HTML
   */
  final public static function link_to_remote($text, array $params){
    $func = self::remote_function($params);
    return self::link_to_function($text, $func);
  }

  /**
   * Generate a link to some arbitrary javascript function
   * @param string $text Text on which to create the link
   * @param string $func Function to link via an onclick
   * @return string Link HTML
   */
  final public static function link_to_function($text, $func){
    return "<a href=\"#\" onclick=\"$func\">$text</a>";
  }

  /**
   * Generate a form tag which submits via an AJAX call
   * @param array $params Parameters for the generation
   * @return string Form tag html
   */
  final public static function form_remote_tag(array $params){
    $params["parameters"] = "Form.serialize(this)";
    return "<form action=\"".$params['url']."\" method=\"".((array_key_exists("method", $params)) ? $params["method"] : "POST")."\" onsubmit=\"".self::remote_function($params)."\" id=\"".((array_key_exists("id", $params)) ? $params["id"] : "")."\" name=\"".((array_key_exists("id", $params)) ? $params["id"] : "")."\" >";
  }

  /**
   * Generate an AJAX call
   * @param array $params Parameters for the generation
   * @return string The AJAX call
   */
  final public static function remote_function(array $params){
    $ret = "";
    if(array_key_exists("update", $params)){
      $ret .= "new Ajax.Updater('".$params['update']."', ";
    } else {
      $ret .= "new Ajax.Request(";
    }
    $ret .= "'".$params['url']."', {asynchronous:true, evalScripts:true";
    foreach($params as $par => $act){
      if($par == "complete"){
        $ret .= ", onComplete:function(request){".$act."}";
      } elseif($par == "loading"){
        $ret .= ", onLoading:function(request){".$act."}";
      } elseif($par == "success"){
        $ret .= ", onSuccess:function(request){".$act."}";
      } elseif($par == "failure"){
        $ret .= ", onFailure:function(request){".$act."}";
      } elseif(is_numeric($par)){
        $ret .= ", on".$par.":function(request){".$act."}";
      } elseif($par == "parameters"){
        $ret .= ", parameters:$act";
      }
    }
    $ret .= "}); return false;";
     return $ret;
  }

  /**
   * Generate a select tag
   * @param array $opts Parameters for the generation
   * @return string The select tag HTML
   */
  final public static function select(array $opts){

    if(!array_key_exists("name", $opts)){
      $opts["name"] = "select";
    }

    if(!array_key_exists("values", $opts)){
      $opts["values"] = array();
    }

    $ret = "";
    $ret .= "<select name='".$opts["name"]."' id='".$opts["name"]."' size='".((array_key_exists("size", $opts)) ? $opts["size"] : 1)."'";
    if(array_key_exists("multiple", $opts) && $opts["multiple"]){
      $ret .= " multiple='multiple'";
    }
    if(array_key_exists("class", $opts)){
      $ret .= " class='".$opts["class"]."'";
    }
    if(array_key_exists("style", $opts)){
      $ret .= " style='".$opts["style"]."'";
    }
    if(array_key_exists("onchange", $opts)){
      $ret .= " onchange=\"".$opts["onchange"]."\"";
    }
    $ret .= ">";

    if(array_key_exists("prompt", $opts) && $opts["prompt"]){
      $ret .= "<option value='0'>-Please Select-</option>";
    } elseif (array_key_exists("include_blank", $opts) && $opts["include_blank"]){
      $ret .= "<option value='0'> </option>";
    }

    foreach($opts["values"] as $v){
      $ret .= "<option value='".$v[1]."'";
      if(array_key_exists("selected", $opts) && ((is_array($opts["selected"]) && in_array($v[1], $opts["selected"])) || (!is_array($opts["selected"]) && $opts["selected"] == $v[1]))){
        $ret .= " selected='selected'";
      }
      $ret .= ">".$v[0]."</option>";
    }

    $ret .= "</select>";

    return $ret;
  }

  /**
   * Generate the html for a checkbox input tag
   * @param string $name Name/id of the field
   * @param string $value Value of the field
   * @param boolean $checked Whether the box is checked or not
   * @param array $opts Other parameters for the generation
   * @return string The checkbox HTML
   */
  final public static function check_box_tag($name, $value, $checked = false, array $opts = array()){
    return "<input type='checkbox' name='".$name."' id='".$name."' value='".$value."' ".(($checked) ? "checked='checked'" : "")." />";
  }

  /**
   * Generate the html for a hidden input tag
   * @param string $name Name/id of the field
   * @param string $value Value of the field
   * @param array $opts Other parameters for the generation
   * @return string The hidden field HTML
   */
  final public static function hidden_field_tag($name, $value, array $opts = array()){
    return "<input type='hidden' name='".$name."' id='".$name."' value='".$value."' />";
  }
}
?>
