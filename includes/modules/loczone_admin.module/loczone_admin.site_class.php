<?php
/**
 * KvScheduler - Location Zone Admin SiteClass
 * @package KvScheduler
 * @subpackage Modules
 * @author Greg McWhirter <gsmcwhirter@gmail.com>
 * @version 1.0
 */

/**
 * CRUD for location zones (loczones)
 *
 * @package KvScheduler
 * @subpackage Modules
 */
class loczone_admin_site_class extends admin_site_class{
  /**
   * Values array of location zones for a selector
   *
   * @var array
   */
  protected $all_lzs;

  /**
   * The current location zone
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
      $id = Loczone::create(self::$PARAMS["form"]);
      if($id){
        self::throwMessage("Loczone added successfully.");
        $this->output_page("index","inline","admin");
      } else {
        self::throwError("Loczone failed to be added.");
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
      self::throwError("Requested loczone was not found.");
      $this->edit_select();
    }
  }

  /**
   * Processing of editing form
   *
   */
  public function process_edit_form(){
    if(!array_key_exists("form", self::$PARAMS) || !is_array(self::$PARAMS["form"])){
      self::throwError("Invalid parameters.");
      $this->edit_form();
    } else {
      if($this->prep_form()){
        if(Loczone::update_attributes($this->thisthing->loczone_id, self::$PARAMS["form"])){
          self::throwMessage("Loczone record modified successfully.");
          $this->output_page("index","inline","admin");
        } else {
          self::throwError("Loczone record not modified successfully.");
          $this->edit_form();
        }
      } else {
        self::throwError("Requested loczone was not found.");
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
      self::throwError("Requested loczone was not found");
      $this->remove_select();
    }
  }

  /**
   * Processing of removal
   *
   */
  public function process_remove_confirm(){
    if($this->prep_form()){
      if(!array_key_exists("confirm", self::$PARAMS) || self::$PARAMS["confirm"] != "yes"){
        self::throwMessage("Confirmation of removal was denied.");
      } elseif(!Loczone::destroy($this->thisthing->loczone_id)){
        self::throwError("Loczone was not able to be deleted for an unknown reason.");
      } else {
        self::throwMessage("Loczone deleted successfully.");
      }

      $this->output_page("index","inline","admin");
    } else {
      self::throwError("Requested loczone was not found");
      $this->remove_select();
    }
  }

  /**
   * Prep the select forms
   *
   * @return boolean true
   */
  protected function prep_select(){
    $this->all_lzs = array();
    $sql = "SELECT *, id as loczone_id FROM loczones ORDER BY name";
    $q = self::$DB->query($sql);
    while($row = self::$DB->fetch($q)){
      $this->all_lzs[] = array(Loczone::select_name($row), $row->loczone_id);
    }

    return true;
  }

  /**
   * Prep the adding and editing forms
   *
   * @return boolean true
   */
  protected function prep_form(){
    $sql = "SELECT *, id as loczone_id FROM loczones WHERE id = '".self::$PARAMS["lzid"]."' LIMIT 1";
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
