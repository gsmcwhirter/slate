<?php
/**
 * Site class for the KvFramework.
 * @package KvFramework
 * @subpackage SiteHandler
 * @author Greg McWhirter <gsmcwhirter@gmail.com>
 * @copyright Copyright &copy; 2006, Greg McWhirter
 * @license MIT License
 * @version 1.0
 */

load_files(
  KVF_FRAMEWORK_DIR."/sitehandler/kvframework_markup.class.php",
  KVF_FRAMEWORK_DIR."/sitehandler/kvframework_irenderengine.interface.php",
  KVF_FRAMEWORK_DIR."/sitehandler/kvframework_renderengine.class.php",
  KVF_FRAMEWORK_DIR."/sitehandler/kvframework_router.class.php"
);

/**
 * Basis for application site_classes
 * @package KvFramework
 * @subpackage SiteHandler
 */
abstract class kvframework_site extends kvframework_base {

    /**
     * Holds the names of the filter functions
     * @var array
     */
    protected $KVF_FILTERS = array("before" => array(), "after" => array());
    /**
     * The name of the layout to be used by default
     * @var mixed
     */
    protected $KVF_LAYOUT = null;

    /**
     * Call the render engine with appropriate parameters and stuff
     * @param string $content Either a view name or text to output
     * @param string $type Either "full" or "inline" - determines whether or not to use a template
     * @param array $render_params Parameters passed to the render engine
     * @param boolean $text Wheter or not $content was text or a view name
     * @param integer $status HTTP Status Code to return
     * @return boolean
     */
    final protected function render($content, $type = "full", array $render_params = array(), $text = false, $status = 200){
      kvframework_log::write_log("Rendering ".get_class($this)."::".$content.", Type ".$type, KVF_LOG_LDEBUG);
      header("HTTP/1.1 $status");
      header("Status: $status");

      if(!self::$RENDERED && $type == "full"){
        self::$RENDERED = true;
        if($text){
          self::$RENDERENGINE->render_text($content, $render_params, self::layout_file($this->KVF_LAYOUT));
        } else {
          self::$RENDERENGINE->render_file(self::view_file(get_class($this), $content), $render_params, self::layout_file($this->KVF_LAYOUT));
        }
        return true;
      } elseif($type == "inline") {
        if($text){
          self::$RENDERENGINE->render_text($content, $render_params);
        } else {
          self::$RENDERENGINE->render_file(self::view_file(get_class($this), $content), $render_params);
        }
        return true;
      } else {
        return false;
      }
    }

    /**
     * Shortcut to render for $type = "inline", $text = false
     * @param string $view View to render
     * @param array $render_params Parameters to pass to the render engine
     * @return boolean
     */
    final protected function render_inline($view, array $render_params = array()){
      return $this->render($view, "inline", $render_params);
    }

    /**
     * Shortcut to render for $text = true
     * @param string $text Text to render
     * @param string $type Either "full" or "inline"
     * @param array $render_params Parameters to pass to the render engine
     * @return boolean
     */
    final protected function render_text($text, $type = "full", array $render_params = array()){
      return $this->render($text, $type, $render_params, true);
    }

    /**
     * Add a filter function to the list of before-filters
     * @param string $filt Name of the filter function
     * @return boolean true
     */
    final protected function before_filter($filt){
      $this->KVF_FILTERS["before"][] = $filt;
      return true;
    }

    /**
     * Add a filter funtion to the list of after-filters
     * @param string $filt Name of the filter function
     * @return boolean true
     */
    final protected function after_filter($filt){
      $this->KVF_FILTERS["after"][] = $filt;
      return true;
    }

    /**
     * Clear the list of filters
     * @return boolean true
     */
    final protected function flush_filters(){
      $this->KVF_FILTERS = array("before" => array(), "after" => array());
      return true;
    }

    /**
     * Execute the before filters declared by this instance
     * @return boolean true
     */
    final public function do_before_filters(){
      foreach($this->KVF_FILTERS["before"] as $filt){
        kvframework_log::write_log("Doing before filter ".$filt, KVF_LOG_LDEBUG);
        if(!$this->$filt()){return false;}
      }
      return true;
    }

    /**
     * Execute the after filters declared by this instance
     * @return boolean true
     */
    final public function do_after_filters(){
      foreach($this->KVF_FILTERS["after"] as $filt){
        kvframework_log::write_log("Doing after filter ".$filt, KVF_LOG_LDEBUG);
        if(!$this->$filt()){return false;}
      }
      return true;
    }

    /**
     * Set the default layout to be rendered with this call
     * @param string $layout Name of the layout
     * @return true
     */
    final public function set_layout($layout){
      $this->KVF_LAYOUT = $layout;
    }


    /**
     * A flag whether the template has been rendered yet
     * @staticvar boolean
     */
    protected static $RENDERED = false;
    /**
     * The render engine to be used
     * @staticvar $RENDERENGINE
     */
    protected static $RENDERENGINE;

    /**
     * Initialize everything so we can route the request properly
     * @param kvframework_irenderengine $engine Render Engine instance to use
     * @return boolean true
     */
    final public static function startup(kvframework_irenderengine &$engine){
      self::$RENDERENGINE = $engine;
      return true;
    }

    /**
     * Render a method of a different site_class
     * @param string $site_class Name of the site class of method to render
     * @param string $method Name of the method to render
     * @param mixed $newlayout If non-null, name of the layout to use in the rendering
     * @param boolean $inline Whether or not to render the component without a layout
     * @return boolean true
     */
    final protected static function render_component($site_class, $method, $newlayout = null, $inline = false){
      kvframework_log::write_log("Rendering component ".$site_class."::".$method, KVF_LOG_LDEBUG);
      $sc = $site_class."_site_class";
      //self::flush_filters();
      kvframework_router::perform_route_action($sc, $method, $newlayout, $inline);
      return true;
    }

    /**
     * Shortcut to render component with $inline = true
     * @param string $site_class Name of the site class of method to render
     * @param string $method Name of the method to render
     * @param mixed $newlayout If non-null, name of the layout to use in the rendering
     * @return boolean true
     */
    final protected static function render_component_inline($site_class, $method, $newlayout = null){
      self::render_component($site_class, $method, $newlayout, true);
      return true;
    }

    /**
     * Redirect to a certain site class and method in the browser
     * @param string $site_class Name of the site class of method to which to redirect
     * @param string $method Name of the method to which to redirect
     */
    final protected static function redirect_to($site_class, $method){
      session_write_close();
      header("Location: ".CONFIG::baseurl.self::url_for($site_class, $method));
      kvframework_log::close_logs();
      ob_end_flush();
      exit;
    }

    /**
     * Redirect to an arbitrary URL in the browser
     * @param string $url URL to which to redirect
     */
    final protected static function redirect_to_url($url){
      session_write_close();
      header("Location: ".$url);
      kvframework_log::close_logs();
      ob_end_flush();
      exit;
    }

    /**
     * Get the relative url for a site class, method, etc
     *
     * @param string $sc
     * @param mixed $method
     * @param array $params
     * @param array $scalls
     * @param string $rtype
     * @return string
     */
    final protected static function url_for($sc, $method = null, array $params = array(), array $scalls = array(), $rtype = "GET"){
      return kvframework_router::url_for($sc, $method, $params, $scalls, $rtype);
    }

    /**
     * Get the full url for a site class, method, etc
     *
     * @param string $sc
     * @param mixed $method
     * @param array $params
     * @param array $scalls
     * @param string $rtype
     * @return string
     */
    final protected static function full_url_for($sc, $method = null, array $params = array(), array $scalls = array(), $rtype = "GET"){
      return CONFIG::baseurl.self::url_for($sc, $method, $params, $scalls, $rtype);
    }

    /**
     * Generate the full path of a view file
     * @param string $class Site class in which the view resides
     * @param string $view Name of the view
     * @return string Path and name of the view file
     */
    final protected static function view_file($class, $view){
      $view = strtolower($view);
      $class2 = strtolower($class);
      if(substr($class, -11) != "_site_class"){$class = $class."_site_class";}
      $class2 = $class;
      $fsc = null;
      while(substr($class2, -11) == "_site_class" && is_null($fsc)){
        $f = KVF_MODULES."/".substr($class2, 0, -11).".module/views/".$view.".phtml";
        if(file_exists($f)){
          $fsc = $f;
        } else {
          $class2 = get_parent_class($class2);
        }
      }

      if(is_null($fsc)){ return null; }
      else { return $fsc; }
      //if(substr($class,-10) == "site_class"){
      //  return(KVF_MODULES."/".substr($class, 0, -11).".module/views/".$view.".phtml");
      //} else {
      //  return(KVF_MODULES."/".$class.".module/views/".$view.".phtml");
      //}
    }

    /**
     * Generate the full path of a layout file
     * @param string $name Name of the layout file
     * @return string Full path and name of the layout file
     */
    final protected static function layout_file($name){
      return(KVF_LAYOUTS_DIR."/".$name.".phtml");
    }

    /**
     * Wrapper around $_SESSION to get values
     * @param mixed $key Key whose value to get
     * @return mixed Value of the key in $_SESSION or null
     */
    final protected static function session($key){
      return (array_key_exists($key, $_SESSION)) ? $_SESSION[$key] : null;
    }

    /**
     * Wrapper around $_SESSION to set values
     * @param mixed $key Key to set
     * @param mixed $val Value to set
     * @return boolean
     */
    final protected static function session_set($key, $val){
      return $_SESSION[$key] = $val;
    }

    /**
     * Used by the default render engine in its view rendering
     * @param string $string String to eval
     */
    final public function do_eval($string){
      eval($string);
      return true;
    }
}
?>
