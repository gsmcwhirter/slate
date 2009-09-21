<?php
/**
 * Framework Routing class
 * @package KvFramework
 * @subpackage SiteHandler
 * @author Greg McWhirter <gsmcwhirter@gmail.com>
 * @copyright Copyright &copy; 2006, Greg McWhirter
 * @license MIT License
 * @version 1.0
 */

/**
 * Routing class for the framework
 *
 * @package KvFramework
 * @subpackage SiteHandler
 */
abstract class kvframework_router extends kvframework_base {

  protected static $SITE_CLASS = null;

  /**
   * I kinda forget what this does at the moment
   * @staticvar $CALLS
   */
  protected static $CALLS = array();
  /**
   * I kinda forget what this does at the moment
   * @staticvar $SCALLS
   */
  protected static $SCALLS = array();
  /**
   * Temp params thing
   */
  public static $PARAM_TEMP = array();

  /**
   * Route the request
   * @return boolean true
   */
  final public static function do_route(){
    foreach(explode("/", self::$REQUEST) as $up){
      if(substr($up, 0, 1) == ":"){
        $pts = explode(":", substr($up, 1), 2);
        if(is_null($pts[1])){self::$SCALLS[] = $pts[0];}
        else{self::$PARAM_TEMP[$pts[0]] = $pts[1];}
      } elseif($up != "") {
        self::$CALLS[] = $up;
      }
    }

    kvframework_log::write_log("Routing request ".self::$REQUEST." {".self::$SERVER["REQUEST_METHOD"]."}");
    self::finish_params();
    self::perform_route_action((array_key_exists(0, self::$CALLS) && preg_match("/^c:/",self::$CALLS[0])) ? substr(self::$CALLS[0], 2)."_site_class" : CONFIG::DEFAULT_SITE_CLASS."_site_class", null, null, false, self::$SERVER["REQUEST_METHOD"]);
    return true;
  }

  /**
   * Merge $_GET, $_POST, and the values passed on the command line into self::$PARAMS
   * @return boolean true
   */
  final protected static function finish_params(){
    self::$PARAMS = array_merge(self::$PARAM_TEMP, self::$GET, self::$POST); #, self::$POST);
    unset(self::$PARAMS['url']);
    unset(self::$PARAMS['request_method']);
    self::clean_array_recursive(self::$PARAMS);
    kvframework_log::write_log("PARAMS dump: ".self::array_to_string(self::$PARAMS), KVF_LOG_LDEBUG);
    return true;
  }

  /**
   * Cleans an array, making empty string values null
   */
  final public static function clean_array_recursive(&$array){
    array_walk_recursive($array, array("kvframework_router", "clean_callback"));
  }

  /**
   * Used by clean_array_recursive as the callback
   */
  final public static function clean_callback(&$r, $i){
    if($r == "") { $r = null; }
  }

  /**
   * Actually perform the routing by instantiating the site class and calling the method
   * @param string $site_class Name of the site class to instantiate
   * @param string $method Name of the method to execute (null for a default)
   * @param string $newlayout Name of a new layout to use, overriding the site class default (null to not override)
   * @param boolean $inline Whether or not to execute without rendering a template
   * @return boolean true
   */
  final public static function perform_route_action($site_class, $method = null, $newlayout = null, $inline = false, $rtype = "GET"){
    //global $SITE_CLASS;
    self::$SITE_CLASS = new $site_class();
    if(!is_null($newlayout)){self::$SITE_CLASS->set_layout($newlayout);}

    kvframework_log::write_log("Passed \$method = ".serialize($method), KVF_LOG_LDEBUG);
    $p1 = $site_class;
    $p2 = implode("/", (array_key_exists(0, self::$CALLS) && preg_match("/^c:/",self::$CALLS[0])) ? array_slice(self::$CALLS, 1) : self::$CALLS);
    $p3 = $rtype;
    kvframework_log::write_log("Passed params = ".serialize($p1).", ".serialize($p2).", ".serialize($p3), KVF_LOG_LDEBUG);
    if(is_null($method)){$method = self::route_method_for_call($p1,$p2,$p3);}

    kvframework_log::write_log("Performing action ".$site_class."::".$method." {".$rtype."}");

    if(!is_callable(array(self::$SITE_CLASS, $method), false)){
      header("HTTP/1.1 404 Not Found");
      header("Status: 404 Not Found");
      readfile(ROOTDIR."/404.shtml");
      kvframework_log::close_logs();
      ob_end_flush();
      exit;
    }
    if(self::$SITE_CLASS->do_before_filters()){
      self::$SITE_CLASS->$method();
      self::$SITE_CLASS->do_after_filters();
    }
    if(!$inline){
      kvframework_log::close_logs();
      ob_end_flush();
      exit;
    }

    return true;
  }

  /**
   * Determine the url for a certain site class and method
   * @param string $sc Name of the site class
   * @param string $method Name of the method (null for a default)
   * @param array $params Parameters to be passed through the URL
   * @param array $scalls SCalls to be passed through the URL
   * @return string The URL
   */
  final public static function url_for($sc, $method = null, array $params = array(), array $scalls = array(), $rtype = "GET"){
    if(!is_null($method)){$call = self::route_call_for_method($sc."_site_class", $method, $rtype);}
    else{$call = implode("/", array_slice(self::$CALLS, 1));}
    $t = strtolower((substr($sc, -10) == "site_class") ? substr($sc, 0, -11) : $sc);

    $ret = CONFIG::rooturi.(($t == CONFIG::DEFAULT_SITE_CLASS) ? "/" : "/c:".$t.((empty($call)) ? "" : "/")).$call;
    $ret = implode("/:", array_merge(array($ret), $scalls));
    foreach($params as $k => $v){
      if(!is_numeric($k)){
        $ret .= "/:".$k.":".$v;
      }
    }
    return($ret);
  }

  /**
   * Determine the method to execute for certain $CALLS implosions
   * @param string $class Site class
   * @param string $call Implosion of $CALLS
   * @return string Name of method to execute
   */
  final protected static function route_method_for_call($class, $call, $rtype = null){

  if(!in_array($rtype, array("GET","POST","PUT"))){$rtype = "GET";}
    $rtype = strtoupper(self::$SERVER["REQUEST_METHOD"]);
    $temp = self::parse_routes_file($class);
    kvframework_log::write_log("Route Method Lookup: ($class, $call, $rtype) |-> ".$temp[$call][$rtype]." from ".serialize($temp));
    return($temp[$call][$rtype]);
  }

  /**
   * Determine the post-site-class uri for a certain method and class
   * @param string $class Site class
   * @param string $method Name of the method
   * @return string post-site-class uri
   */
  final protected static function route_call_for_method($class, $method, $rtype = "GET"){
    if(!in_array($rtype, array("GET","POST","PUT"))){$rtype = "GET";}
    $temp = self::parse_routes_file($class);
    if(is_array($temp)){
      $temp = self::route_map_flip($temp);
    } else {
      $temp = array();
    }

    return (array_key_exists($method, $temp) && array_key_exists($rtype, $temp[$method])) ? $temp[$method][$rtype] : null;
  }

  /**
   * Flips the route mapping for use in url_for and the like
   *
   * @param array $map
   * @return array
   */
  final protected static function route_map_flip(array $map){
    $newmap = array();
    foreach($map as $call => $rtm){
      foreach($rtm as $rtype => $method){
        if(!array_key_exists($method, $newmap)){$newmap[$method] = array();}
        $newmap[$method][$rtype] = $call;
      }
    }
    return $newmap;
  }

  /**
   * Parse the routes file for a certain site class
   * @param string $class Site class
   * @return array map of calls => method names
   */
  final protected static function parse_routes_file($class){
    $map = array();
    $class = strtolower($class);
    if(substr($class,-10) == "site_class"){
      $lines = file(KVF_MODULES."/".substr($class, 0, -11).".module/routes");
    } else {
      $lines = file(KVF_MODULES."/".$class.".module/routes");
    }
    foreach($lines as $l){
      list($call, $method) = preg_split("#=>#", $l);
      list($call, $method) = array(trim($call, "/ \t\n\r\0\x0B"), trim($method, "/ \t\n\r\0\x0B"));
      $m = array();
      if(preg_match("#{GET}|{POST}|{PUT}\s$#", $call, $m)){$call = trim(substr($call, 0, strlen($call) - strlen($m[0])), "/ \t\n\r\0\x0B");$rtype = substr(trim($m[0], "/ \t\n\r\0\x0B"), 1,-1);} else {$rtype = "GET";}

      if(!array_key_exists($call, $map)){$map[$call] = array();}
      $map[$call][$rtype] = $method;
    }
    return $map;
  }

  /**
   * Used by the default render engine in its view rendering
   * @param string $string String to eval
   */
  final public static function do_eval($string){
    if(self::$SITE_CLASS instanceOf kvframework_site){
      self::$SITE_CLASS->do_eval($string);
    } else {
      throw new Exception("Could not call do_eval on SITE_CLASS");
    }
  }

}
?>
