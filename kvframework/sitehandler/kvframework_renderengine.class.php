<?php
/**
 * Default render engine class for the KvFramework.
 * @package KvFramework
 * @subpackage SiteHandler
 * @author Greg McWhirter <gsmcwhirter@gmail.com>
 * @copyright Copyright &copy; 2006, Greg McWhirter
 * @license MIT License
 * @version 1.0
 */

/**
 * Default Render Engine of the framework
 * @package KvFramework
 * @subpackage SiteHandler
 */
class kvframework_renderengine implements kvframework_irenderengine{
  function __construct(){}

  /**
   * Render a certain file
   * @param string $file Name of the file (with full path) to render
   * @param array $params Not Used
   * @param string $with_template Name (with full path) of the tempate to use (null for none)
   * @return boolean true
   */
  public function render_file($file, array $params = array(), $with_template = null){
    $layout_contents = "";
    $content_for_layout = "";
    if(is_null($with_template)){
      $layout_contents = "[CONTENT_FOR_LAYOUT]";
    } else {
      $layout_lines = file($with_template);
      $layout_contents = "";
      foreach($layout_lines as $l){$layout_contents .= $l."\n";}
    }

    $content_lines = file($file);
    $content_for_layout = "";
    foreach($content_lines as $l){$content_for_layout .= $l."\n";}

    kvframework_router::do_eval("?>". preg_replace("#\[CONTENT_FOR_LAYOUT\]#", $content_for_layout, $layout_contents));
    return true;
  }

  /**
   * Render text
   * @param string $text Text to render
   * @param array $params Not Used
   * @param string $with_template Name (with full path) of the tempate to use (null for none)
   * @return boolean true
   */
  public function render_text($text, array $params = array(), $with_template = null){
    $layout_contents = "";
    if(is_null($with_template)){
      $layout_contents = "[CONTENT_FOR_LAYOUT]";
    } else {
      $layout_lines = file($with_template);
      $layout_contents = "";
      foreach($layout_lines as $l){$layout_contents .= $l."\n";}
    }

    kvframework_router::do_eval("?>". preg_replace("#\[CONTENT_FOR_LAYOUT\]#", $text, $layout_contents));
    return true;
  }
}
?>
