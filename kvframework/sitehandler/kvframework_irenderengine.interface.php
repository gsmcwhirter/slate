<?php
/**
 * Render Engine interface for the KvFramework.
 * @package KvFramework
 * @subpackage SiteHandler
 * @author Greg McWhirter <gsmcwhirter@gmail.com>
 * @copyright Copyright &copy; 2006, Greg McWhirter
 * @license MIT License
 * @version 1.0
 */

/**
 * Interface required for RenderEngines
 * @package KvFramework
 * @subpackage SiteHandler
 */
interface kvframework_irenderengine{
  public function render_file($file, array $params = array(), $with_template = null);
  public function render_text($text, array $params = array(), $with_template = null);
}
?>
