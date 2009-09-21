<?php
/**
 * KvScheduler - Application SiteClass
 * @package KvScheduler
 * @subpackage Modules
 * @author Greg McWhirter <gsmcwhirter@gmail.com>
 * @version 1.0
 */

/**
 * Root of every site class in the application
 *
 * @package KvScheduler
 * @subpackage Modules
 */
class application_site_class extends kvframework_site {

  /**
   * The authentication level required for the site class
   *
   * @var integer
   * @see ACCESS
   */
  protected $auth_level;
  /**
   * Flag for whether or not the request is for a mobile page
   *
   * @var boolean
   */
  protected $mobile = false;

  /**
   * Config variable cache
   *
   * @var array
   */
  static protected $CONFCACHE = array();
  /**
   * Config Override variable cache
   *
   * @var array
   */
  static protected $CONFCACHE_OVERRIDE = array();
  /**
   * Send email flag
   *
   * @var boolean
   */
  static protected $SENDMAIL = false;
  /**
   * Application User
   *
   * @var mixed
   */
  static protected $APP_USER = null;
  /**
   * Whether or not messages have been reloaded from sessions this request
   *
   * @var boolean
   */
  static protected $RELOADED_MESSAGES = false;

  /**
   * Set up all sorts of fun things
   *
   */
  function __construct(){
    $this->auth_level = ACCESS::display;
    $this->setup_variables();
    $this->set_layout(CONFIG::tempname);
    $this->before_filter("session_login");
    $this->before_filter("check_credentials");
    $this->after_filter("save_session");
  }

  /**
   * Set up variables and reload stuff from sessions if necessary
   *
   * @param boolean $force
   * @return boolean
   */
  protected function setup_variables($force = false){
    $this->setup_config($force);
    $this->setup_overrides($force);
    if(!self::$RELOADED_MESSAGES){
      $this->reload_messages_from_session();
      self::$RELOADED_MESSAGES = true;
    }
    if(is_null(self::$APP_USER)){
      self::$APP_USER = new User($this->config_val("acd_on"));
    }
    $this->USER =& self::$APP_USER;

    return true;
  }

  /**
   * Set up config variables
   *
   * @param boolean $force
   * @return boolean
   * @see CONFIG::$VARS
   * @see application_site_class::$CONFCACHE
   */
  protected function setup_config($force){
    $toget = array();
    foreach(CONFIG::$VARS as $key => $value){
      if($force || !array_key_exists($key, self::$CONFCACHE)){
        $toget[] = $key;
      }
    }

    $dbg = array();
    if(count($toget) > 0){
      $sql = "SELECT * FROM settings";
      $q = self::$DB->query($sql);
      while($row = self::$DB->fetch($q)){
        $dbg[$row->id] = array($row->svalue, $row->svaluetype);
      }
    }

    //add in getting things from the vars that werent in the db
    foreach(CONFIG::$VARS as $key => $val){
      if(!array_key_exists($key, $dbg)){
        $dbg[$key] = array($val[2], $val[3]);
      }
    }

    foreach(array_keys($dbg) as $key){
      switch($dbg[$key][1]){
        case "string":
          break;
        case "integer":
          $dbg[$key][0] = (int)$dbg[$key][0];
          break;
        case "float":
          $dbg[$key][0] = (float)$dbg[$key][0];
          break;
        case "boolean":
          $dbg[$key][0] = (strtolower($dbg[$key][0]) == "true") ? true : false;
          break;
        case "aphash":
          $t = new ApHash($dbg[$key][0]);
          $dbg[$key][0] = $t->hash();
          break;
        default:
          $dbg[$key][0] = null;
      }
      self::$CONFCACHE[$key] = $dbg[$key][0];
    }

    self::$SENDMAIL = $this->config_val("send_email");
    return true;
  }

  /**
   * Get the value for a config variable
   *
   * @see CONFIG::$VARS
   * @param string $key
   * @return mixed
   */
  protected function config_val($key){
    if(array_key_exists($key, self::$CONFCACHE)){
      kvframework_log::write_log("Config dump: ".self::array_to_string(self::$CONFCACHE), KVF_LOG_LDEBUG);
      return self::$CONFCACHE[$key];
    } else {
      kvframework_log::write_log("Config problem getting: ".$key.", CONFCACHE: ".self::array_to_string(self::$CONFCACHE), KVF_LOG_LWARNING);
      return null;
    }
  }

  /**
   * Get the value for a config variable
   *
   * @see CONFIG::$VARS
   * @param string $key
   * @return mixed
   */
  public static function config_vals($key){
    if(array_key_exists($key, self::$CONFCACHE)){
      kvframework_log::write_log("Config dump: ".self::array_to_string(self::$CONFCACHE), KVF_LOG_LDEBUG);
      return self::$CONFCACHE[$key];
    } else {
      kvframework_log::write_log("Config problem getting: ".$key.", CONFCACHE: ".self::array_to_string(self::$CONFCACHE), KVF_LOG_LWARNING);
      return null;
    }
  }

  /**
   * Set up config override variables
   *
   * @see CONFIG::$VARS_OVERRIDES
   * @see application_site_class::$CONFCACHE_OVERRIDE
   * @param boolean $force
   * @return boolean
   */
  protected function setup_overrides($force){
    $toget = array();
    foreach(CONFIG::$VARS_OVERRIDES as $key => $value){
      if($force || !array_key_exists($key, self::$CONFCACHE_OVERRIDE)){
        $toget[] = $key;
      }
    }

    $dbg = array();
    if(count($toget) > 0){
      $sql = "SELECT * FROM settings";
      $q = self::$DB->query($sql);
      while($row = self::$DB->fetch($q)){
        $dbg[$row->id] = array($row->svalue, $row->svaluetype);
      }
    }

    //add in getting things from the vars that werent in the db
    foreach(CONFIG::$VARS_OVERRIDES as $key => $val){
      if(!array_key_exists($key, $dbg)){
        $dbg[$key] = array($val[2], $val[3]);
      }
    }

    foreach(array_keys($dbg) as $key){
      switch($dbg[$key][1]){
        case "string":
          break;
        case "integer":
          $dbg[$key][0] = (int)$dbg[$key][0];
          break;
        case "float":
          $dbg[$key][0] = (float)$dbg[$key][0];
          break;
        case "boolean":
          $dbg[$key][0] = (strtolower($dbg[$key][0]) == "true") ? true : false;
          break;
        case "aphash":
          $t = new ApHash($dbg[$key][0]);
          $dbg[$key][0] = $t->hash();
          break;
        default:
          $dbg[$key][0] = null;
      }
      self::$CONFCACHE_OVERRIDE[$key] = $dbg[$key][0];
    }
    return true;
  }

  /**
   * Get the value of an override variable
   *
   * @param string $key
   * @return unknown
   * @see CONFIG::$VARS_OVERRIDES
   */
  protected function override_val($key){
    if(array_key_exists($key, self::$CONFCACHE_OVERRIDE)){
      kvframework_log::write_log("Config dump: ".serialize(self::$CONFCACHE_OVERRIDE), KVF_LOG_LDEBUG);
      return self::$CONFCACHE_OVERRIDE[$key];
    } else {
      kvframework_log::write_log("Config problem getting: ".$key.", CONFCACHE_OVERRIDE: ".self::array_to_string(self::$CONFCACHE_OVERRIDE), KVF_LOG_LWARNING);
      return null;
    }
  }

  /**
   * Get the value of an override variable
   *
   * @param string $key
   * @return mixed
   * @see CONFIG::$VARS_OVERRIDES
   */
  public static function override_vals($key){
    if(array_key_exists($key, self::$CONFCACHE_OVERRIDE)){
      kvframework_log::write_log("Config dump: ".self::array_to_string(self::$CONFCACHE_OVERRIDE), KVF_LOG_LDEBUG);
      return self::$CONFCACHE_OVERRIDE[$key];
    } else {
      kvframework_log::write_log("Config problem getting: ".$key.", CONFCACHE_OVERRIDE: ".self::array_to_string(self::$CONFCACHE_OVERRIDE), KVF_LOG_LWARNING);
      return null;
    }
  }

  /**
   * Get an appointment type id for an ats_key
   *
   * @param string $type
   * @return mixed
   */
  public static function appttypes($type){
    $types = array();
    //foreach(array("generic","comcon","wireless","unavailable","other") as $t){
    foreach(array("generic","comcon","wireless","other") as $t){
      $id1 = "at_".$t;
      $types[$t] = self::config_vals($id1);
    }
    $ret = $types[$type];
    return (is_null($ret)) ? null : $ret;
  }

  /**
   * Get an ats key for an appointment type
   *
   * @param integer $id
   * @return mixed
   */
  public static function appttypes_rev($id){
    $types = array();
    //foreach(array("generic","comcon","wireless","unavailable","other") as $t){
    foreach(array("generic","comcon","wireless","other") as $t){
      $id1 = "at_".$t;
      $types[$t] = (string)self::config_vals($id1);
    }
    $itypes = array_flip($types);
    $ret = (array_key_exists((string)$id, $itypes)) ? $itypes[(string)$id] : null;
    return (is_null($ret)) ? null : $ret;
  }

  /**
   * Check whether or not a request can be executed according to user permissions
   *
   * @return boolean
   */
  protected function check_credentials(){
    if($this->auth_level == ACCESS::noauth){
      return true;
    } elseif(!$this->USER->connected()){
      if(!$this->mobile){
        self::redirect_to("user","login_form");
      } else {
        self::redirect_to("user","mlogin_form");
      }
      return false;
    } elseif($this->USER->access() < $this->auth_level){
      kvframework_log::write_log("ACCESS DENIED: ".$this->USER->info("username").":".$this->USER->access()." for level ".$this->auth_level, KVF_LOG_LERROR);
      if(get_class($this) == "user"){
        $this->denied();
      } else{
        self::render_component("user", "denied");
      }
      return false;
    } else {
      $this->save_session();
      return true;
    }
  }

  /**
   * Log in a returning user from the session
   *
   * @return boolean true
   */
  protected function session_login(){
    if(self::session("username") && self::session("connection_id") && self::session("access")){
      $this->USER->check_login(self::session("username"), self::session("connection_id"), self::session("access"));
    }

    return true;
  }

  /**
   * Reload messages and errors from the session
   *
   * @return boolean true
   */
  protected function reload_messages_from_session(){
    if(!is_array(self::session("messages"))){
      self::session_set("messages", array());
    }
    if(!is_array(self::session("errors"))){
      self::session_set("errors", array());
    }
    self::$MESSAGES["msgs"] = array_merge(self::$MESSAGES["msgs"], self::session("messages"));
    self::$MESSAGES["errors"] = array_merge(self::$MESSAGES["errors"], self::session("errors"));
    return true;
  }

  /**
   * Save variables to the session
   *
   * @return boolean true
   */
  protected function save_session(){
    self::session_set("username", $this->USER->info("username"));
    self::session_set("connection_id", $this->USER->connection_id());
    self::session_set("access", $this->USER->access());
    self::session_set("messages", self::$MESSAGES["msgs"]);
    self::session_set("errors", self::$MESSAGES["errors"]);
    return true;
  }

  /**
   * Output system messages
   *
   * @return string
   */
  protected function outputMessages(){
    $result = "";
    if(count(self::$MESSAGES["msgs"]) > 0){
      $result .= "<div class=\"output_message_main\">";
      $max = count(self::$MESSAGES["msgs"]);
      for($i = 0; $i < $max; $i++){
        $result .= "&raquo; " . array_pop(self::$MESSAGES["msgs"]) . "<br />";
      }
      $result .= "</div>";
    }
    $this->reset_messages();
    return $result;
  }

  /**
   * Output system errors
   *
   * @return string
   */
  protected function outputErrors(){
    $result = "";
    if(count(self::$MESSAGES["errors"]) > 0){
      $max = count(self::$MESSAGES["errors"]);
      $result .= "<div class=\"output_errors_main\">";
      for($i = 0; $i < $max; $i++){
        $result .= "&raquo; " . array_pop(self::$MESSAGES["errors"]) . "<br />";
      }
      $result .= "</div>";
    }
    $this->reset_errors();
    return $result;
  }

/**
   * Output system messages
   *
   * @return string
   */
  protected function outputMessages2(){
    $result = "";
    if(count(self::$MESSAGES["msgs"]) > 0){
      $max = count(self::$MESSAGES["msgs"]);
      for($i = 0; $i < $max; $i++){
        $result .= "<div style=\"overflow: hidden; opacity: 0.9999; visibility: visible;\">";
        $result .= array_pop(self::$MESSAGES["msgs"]) . "<hr />";
        $result .= "</div>";
      }
    }
    $this->reset_messages();
    return $result;
  }

  /**
   * Output system errors
   *
   * @return string
   */
  protected function outputErrors2(){
    $result = "";
    if(count(self::$MESSAGES["errors"]) > 0){
      $max = count(self::$MESSAGES["errors"]);
      for($i = 0; $i < $max; $i++){
        $result .= "<div style=\"overflow: hidden; opacity: 0.9999; visibility: visible;\">";
        $result .= array_pop(self::$MESSAGES["errors"]) . "<hr />";
        $result .= "</div>";
      }
    }
    $this->reset_errors();
    return $result;
  }

  /**
   * Output system messages - alt method
   *
   * @return string
   */
  protected function outputMessages1(){
    $result = "";
    if(count(self::$MESSAGES["msgs"]) > 0){
      $result .= "<div id=\"output_message\">";
      $max = count(self::$MESSAGES["msgs"]);
      for($i = 0; $i < $max; $i++){
        $result .= "&raquo; " . array_pop(self::$MESSAGES["msgs"]) . "<br />";
      }
      $result .= "</div>";
    }
    $this->reset_messages();
    return $result;
  }

  /**
   * Output system errors - alt method
   *
   * @return string
   */
  protected function outputErrors1(){
    $result = "";
    if(count(self::$MESSAGES["errors"]) > 0){
      $result .= "<div id=\"output_error\">";
      $max = count(self::$MESSAGES["errors"]);
      for($i = 0; $i < $max; $i++){
        $result .= "&raquo; " . array_pop(self::$MESSAGES["errors"]) . "<br />";
      }
      $result .= "</div>";
    }
    $this->reset_errors();
    return $result;
  }

  /**
   * Output a page - wraps rendering
   *
   * @param string $view File from this controller w/o extension or method from another controller
   * @param string $type enum(full, full2, inline)
   * @param mixed $contlr False for current else string
   * @param array $pars parameters to pass
   * @param mixed $newlayout Null for default, else file name without extension
   */
  protected function output_page($view, $type = "full", $contlr = false, array $pars = array(), $newlayout = null){
    $to_render = $view;

      if($type == "inline"){
        self::$PARAMS = array_merge(self::$PARAMS, $pars);
        if($contlr && $contlr != substr(get_class($this), 0, -11)){self::render_component($contlr, $to_render);}
        else{$this->render_inline($to_render);}
      } elseif(!$this->mobile && $type == "full2") {
        if(!$contlr || $contlr == substr(get_class($this), 0, -11)){
          if($newlayout){$this->set_layout($newlayout);}
          $this->render($to_render, "full");
        } else {
          self::render_component($contlr, $to_render, $newlayout);
        }
      } elseif(!$this->mobile){
        self::$PARAMS['r_hash'] = array("controller" => ($contlr) ? $contlr : substr(get_class($this), 0, -11),"action" => $to_render, "params" => $pars);
        self::render_component("application", "index", $newlayout);
      } else {
        if(!$contlr || $contlr == substr(get_class($this), 0, -11)){
          if($newlayout){$this->set_layout($newlayout);}
          $this->render($to_render, "full");
        } else {
          self::render_component($contlr, $to_render, $newlayout);
        }
      }

  }

  /**
   * Reset the system messages
   *
   * @return boolean
   */
  protected function reset_messages(){
    return(self::$MESSAGES['msgs'] = array());
  }

  /**
   * Reset the system errors
   *
   * @return boolean
   */
  protected function reset_errors(){
    return(self::$MESSAGES['errors'] = array());
  }

  /**
   * Render the index page
   *
   */
  public function index(){
    $this->render("index");
  }

  /**
   * Render a call that will close the box
   *
   */
  public function render_close_box(){
    $this->render("", "inline", array(), true, 302);
  }

  /**
   * Output system messages and errors
   *
   */
  public function output_messages_and_errors(){
    $msg_func = (array_key_exists("msg_func", self::$PARAMS)) ? self::$PARAMS["msg_func"] : "outputMessages2";
    $err_func = (array_key_exists("err_func", self::$PARAMS)) ? self::$PARAMS["err_func"] : "outputErrors2";

    $ret = $this->$err_func() . $this->$msg_func();
    if(empty($ret)){$ret = " ";}
    $this->render_text($ret, "inline");
  }

  public function username_lookup(){
    $res = array(
      "status" => 0,
      "result" => ""
    );
    if(array_key_exists("username",self::$PARAMS)){
      $lres = TOOLS::ldap_name(self::$PARAMS["username"]);
      if($lres){
        $res["result"] = $lres;
      } else {
        $res["status"] = 1;
        $res["result"] = "Unable to find user.";
      }
    } else {
      $res["status"] = 1;
      $res["result"] = "No username in request";
    }

    $this->render_text(json_encode($res),"inline");
  }

}
?>
