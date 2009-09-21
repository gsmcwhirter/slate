<?php
/**
 * KvScheduler - Tag Admin SiteClass
 * @package KvScheduler
 * @subpackage Modules
 * @author Greg McWhirter <gsmcwhirter@gmail.com>
 * @version 1.0
 */

/**
 * CRUD for Tags (consultant specialties)
 *
 * @package KvScheduler
 * @subpackage Modules
 */
class tag_admin_site_class extends admin_site_class{
  /**
   * Selector generator values for tags
   *
   * @var array
   */
  protected $all_tags;
  /**
   * Selected tag record
   *
   * @var mixed
   */
  protected $thistag;

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
      self::throwError("Invalid parameters passed.");
      $this->add_form();
    } else {
      if(Tag::create(self::$PARAMS["form"])){
        self::throwMessage("Specialty tag added successfully.");
        $this->output_page("index","inline","admin");
      } else {
        $this->add_form();
      }
    }
  }

  /**
   * Selector for editing
   *
   */
  public function edit_select(){
    $this->select_prep();
    $this->output_page("edit_select", "inline");
  }

  /**
   * Editing form
   *
   */
  public function edit_form(){
    if($this->form_prep()){
      $this->output_page("edit_form","inline");
    } else {
      self::throwError("The desired Specialty Tag's record was not found in the system");
      $this->edit_select();
    }
  }

  /**
   * Processing of editing form
   *
   */
  public function process_edit_form(){
    if($this->form_prep()){
      if(!array_key_exists("form", self::$PARAMS) || !is_array(self::$PARAMS["form"])){
        self::throwError("Invalid parameters passed.");
        $this->edit_select();
      } else {
        if(Tag::update_attributes($this->thistag->tag_id, self::$PARAMS["form"])){
          self::throwMessage("Specialty tag updated successfully.");
          $this->output_page("index","inline","admin");
        } else {
          $this->edit_form();
        }
      }
    } else {
      self::throwError("The desired Specialty Tag's record was not found in the system");
      $this->edit_select();
    }
  }

  /**
   * Select for removal
   *
   */
  public function remove_select(){
    if($this->last_check()){
      $this->output_page("remove_select", "inline");
    } else {
      self::throwError("There is only one specialty tag in the database. You may not remove the last specialty tag.");
      $this->output_page("index","inline","admin");
    }
  }

  /**
   * Confirmation of removal
   *
   */
  public function remove_confirm(){
    if($this->form_prep()){
      if($this->last_check()){
        $this->output_page("remove_confirm", "inline");
      } else {
        self::throwError("There is only one specialty tag in the database. You may not remove the last specialty tag.");
        $this->output_page("index","inline","admin");
      }
    } else {
      self::throwError("The desired Specialty Tag's record was not found in the system");
      $this->remove_select();
    }
  }

  /**
   * Processing of removal
   *
   */
  public function process_remove_confirm(){
    if($this->form_prep()){
      if($this->last_check()){
        if(array_key_exists("confirm", self::$PARAMS) && self::$PARAMS["confirm"] == 'yes'){
          if(Tag::destroy($this->thistag->id)){
            self::throwMessage("Specialty tag was deleted successfully.");
          } else {
            self::throwError("Specialty tag was unable to be deleted.");
          }
        } else {
          self::throwMessage("Confirmation for removal was denied.");
        }

        $this->output_page("index","inline","admin");
      } else {
        self::throwError("There is only one specialty tag in the database. You may not remove the last specialty tag.");
        $this->output_page("index","inline","admin");
      }
    } else {
      self::throwError("The desired Specialty Tag's record was not found in the system");
      $this->remove_select();
    }
  }

  /**
   * Prepare the editing and removal forms
   *
   * @return boolean
   */
  protected function form_prep(){
    if(!array_key_exists("tid", self::$PARAMS)){
      return false;
    } else {
      $sql = "SELECT *, id as tag_id FROM tags WHERE id = '".self::$PARAMS["tid"]."' LIMIT 1";
      $q = self::$DB->query($sql);
      if(self::$DB->rows($q) == 1){
        $this->thistag = self::$DB->fetch($q);
        return true;
      } else {
        return false;
      }
    }
  }

  /**
   * Prepare the selection forms
   *
   * @return boolean true
   */
  protected function select_prep(){
    $this->all_tags = array();
    $sql = "SELECT *, id as tag_id FROM tags ORDER BY label";
    $q = self::$DB->query($sql);
    while($row = self::$DB->fetch($q)){
      $this->all_tags[] = array(Tag::select_name($row), $row->tag_id);
    }
    return true;
  }

  /**
   * Check to make sure that there is not just one tag left
   *
   * @return unknown
   */
  protected function last_check(){
    $this->select_prep();
    if(count($this->all_tags) <= 1){
      return false;
    } else {
      return true;
    }
  }
}
