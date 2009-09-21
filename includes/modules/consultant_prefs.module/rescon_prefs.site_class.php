<?php
/**
 * KvScheduler - Consultant Preferences SiteClass
 * @package KvScheduler
 * @subpackage Modules
 * @author Greg McWhirter <gsmcwhirter@gmail.com>
 * @version 1.0
 */

/**
 * Consultant preference management
 *
 * @package KvScheduler
 * @subpackage Modules
 */
class consultant_prefs_site_class extends application_site_class{
  /**
   * Consultant information
   *
   * @var mixed
   */
  protected $rcinfo = null;

  /**
   * Constructor
   *
   */
  function __construct(){
    parent::__construct();
    $this->auth_level = ACCESS::display;
  }

  /**
   * Preference form
   *
   */
  public function pref_form(){
    $this->prep();
    $this->output_page("form", "inline");
  }

  /**
   * Processing of preference form
   *
   */
  public function process_pref_form(){
    $this->prep();
    if(array_key_exists("form", self::$PARAMS) && is_array(self::$PARAMS["form"])){
      $res = Consultant::update_attributes($this->rcinfo->consultant_id, self::$PARAMS["form"]);
      if($res){
        self::throwMessage("Consultant Preferences Set Successfully.");
      } else {
        self::throwError("Consultant Preferences failed to be set successfully.");
      }
    } else {
      self::throwError("In-valid form parameters.");
    }
    $this->pref_form();
  }

  /**
   * Preparation of preferences form
   *
   */
  protected function prep(){
    $id = $this->USER->rcinfo("id");
    $sql = "SELECT *, id as consultant_id FROM consultants WHERE id = '".$id."' AND status = 'active' LIMIT 1";
    $q = self::$DB->query($sql);
    if(self::$DB->rows($q) == 1){
      $this->rcinfo = self::$DB->fetch($q);
    } else {
      self::throwError("You do not have a consultant account");
    }
  }
}
?>
