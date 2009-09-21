<?php
/**
 * KvScheduler - Meta-Location Admin SiteClass
 * @package KvScheduler
 * @subpackage Modules
 * @author Greg McWhirter <gsmcwhirter@gmail.com>
 * @version 1.0
 */

/**
 * CRUD for Meta-Locations (metalocs)
 *
 * @package KvScheduler
 * @subpackage Modules
 */
class metaloc_admin_site_class extends admin_site_class{
  /**
   * Values for a selector
   *
   * @var array
   */
  protected $all_mlcs;

  /**
   * Metaloc thing
   *
   * @var mixed
   */
  protected $thisthing;

  /**
   * Constructor
   *
   */
  function __construct(){
    parent::__construct();
  }

  /**
   * Addition form
   *
   */
  public function add_form(){
    $this->output_page("add_form","inline");
  }

  /**
   * Processing of addition form
   *
   */
  public function process_add_form(){
    if(!array_key_exists("form", self::$PARAMS) || !is_array(self::$PARAMS["form"])){
      self::throwError("Bad parameters");
      $this->add_form();
    } else {
      $id = Metaloc::create(self::$PARAMS["form"]);
      if($id){
        self::throwMessage("Meta-location added successfully.");
        $this->output_page("index","inline","admin");
      } else {
        self::throwError("Meta-location failed to be added.");
        $this->add_form();
      }
    }
  }

  /**
   * Select for editing
   *
   */
  public function edit_select(){
    $this->prep_select();
    $this->output_page("edit_select", "inline");
  }

  /**
   * Editing form
   *
   */
  public function edit_form(){
    if($this->prep_form()){
      $this->output_page("edit_form","inline");
    } else {
      self::throwError("Requested meta-location was not found.");
      $this->edit_select();
    }
  }

  /**
   * Processing for editing form
   *
   */
  public function process_edit_form(){
    if(!array_key_exists("form", self::$PARAMS) || !is_array(self::$PARAMS["form"])){
      self::throwError("Invalid parameters.");
      $this->edit_form();
    } else {
      if($this->prep_form()){
        if(Metaloc::update_attributes($this->thisthing->metaloc_id, self::$PARAMS["form"])){
          self::throwMessage("Meta-location record modified successfully.");
          $this->output_page("index","inline","admin");
        } else {
          self::throwError("Meta-location record not modified successfully.");
          $this->edit_form();
        }
      } else {
        self::throwError("Requested meta-location was not found.");
        $this->edit_select();
      }
    }
  }

  /**
   * Select for removal
   *
   */
  public function remove_select(){
    $this->prep_select();
    $this->output_page("remove_select", "inline");
  }

  /**
   * Confirmation of removal
   *
   */
  public function remove_confirm(){
    if($this->prep_form()){
      $this->output_page("remove_confirm","inline");
    } else {
      self::throwError("Requested meta-location was not found");
      $this->remove_select();
    }
  }

  /**
   * Processing of removal form
   *
   */
  public function process_remove_confirm(){
    if($this->prep_form()){
      if(!array_key_exists("confirm", self::$PARAMS) || self::$PARAMS["confirm"] != "yes"){
        self::throwMessage("Confirmation of removal was denied.");
      } elseif(!Metaloc::destroy($this->thisthing->metaloc_id)){
        self::throwError("Meta-location was not able to be deleted for an unknown reason.");
      } else {
        self::throwMessage("Meta-location deleted successfully.");
      }

      $this->output_page("index","inline","admin");
    } else {
      self::throwError("Requested meta-location was not found");
      $this->remove_select();
    }
  }

  /**
   * Prepare the selector pages
   *
   * @return boolean true
   */
  protected function prep_select(){
    $this->all_mlcs = array();
    $sql = "SELECT *, id as metaloc_id FROM metalocs ORDER BY name";
    $q = self::$DB->query($sql);
    while($row = self::$DB->fetch($q)){
      $this->all_mlcs[] = array(Metaloc::select_name($row), $row->metaloc_id);
    }

    return true;
  }

  /**
   * Prepare the editing and removal forms
   *
   * @return boolean
   */
  protected function prep_form(){
    $sql = "SELECT *, id as metaloc_id FROM metalocs WHERE id = '".self::$PARAMS["mlid"]."' LIMIT 1";
    $q = self::$DB->query($sql);
    if(self::$DB->rows($q) == 1){
      $this->thisthing = self::$DB->fetch($q);
      return true;
    } else {
      $this->thisthing = null;
      return false;
    }
  }
}
