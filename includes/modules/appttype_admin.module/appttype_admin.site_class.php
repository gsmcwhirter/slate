<?php
/**
 * KvScheduler - Appointment Type Admin SiteClass
 * @package KvScheduler
 * @subpackage Modules
 * @author Greg McWhirter <gsmcwhirter@gmail.comgsmcwhirter@gmail.com>
 * @version 1.0
 */

/**
 * Appointment type admin actions
 *
 * @package KvScheduler
 * @subpackage Modules
 */
class appttype_admin_site_class extends admin_site_class{

  /**
   * Constructor
   *
   */
  function __construct(){
    parent::__construct();
  }

  /**
   * Select form for a type to edit
   *
   */
  public function edit_select(){
    $this->allats = array();
    $sql = "SELECT *, id as appttype_id FROM appttypes ORDER BY name";
    $q = self::$DB->query($sql);
    while($row = self::$DB->fetch($q)){
      $this->allats[] = $row;
    }
    $this->output_page("edit_select","inline");
  }


  /**
   * Editing form
   */
  public function edit_form(){
    $sql = "SELECT *, appttypes.id as appttype_id, appttypes.name as appttype_name, locations.id as location_id, locations.name as location_name, loczones.id as loczone_id, loczones.name as loczone_name FROM appttypes, locations, loczones WHERE appttypes.id = '".self::$PARAMS["atid"]."' AND locations.appttype_id = appttypes.id AND loczones.id = locations.loczone_id ORDER BY locations.name";
    $q = self::$DB->query($sql);
    $this->thisat = null;
    while($row = self::$DB->fetch($q)){
      if(is_null($this->thisat)){$this->thisat = $row; $this->thisat->locations = array();}
      $this->thisat->locations[$row->location_id] = $row;
    }

    if(is_null($this->thisat)){
      $sql = "SELECT *, id as appttype_id, name as appttype_name FROM appttypes WHERE id = '".self::$PARAMS["atid"][0]."' LIMIT 1";
      $q = self::$DB->query($sql);
      if(self::$DB->rows($q) == 1){
        $this->thisat = self::$DB->fetch($q);
        $this->thisat->locations = array();
      }
    }

    if(is_null($this->thisat)){
      self::throwError("Requested appointment type was not found");
      $this->edit_select();
    } else {
      $this->output_page("edit_form","inline");
    }
  }

  /**
   * Process editing form
   *
   */
  public function process_edit(){
    $sql = "SELECT *, appttypes.id as appttype_id, appttypes.name as appttype_name, locations.id as location_id, locations.name as location_name, loczones.id as loczone_id, loczones.name as loczone_name FROM appttypes, locations, loczones WHERE appttypes.id = '".self::$PARAMS["atid"]."' AND locations.appttype_id = appttypes.id AND loczones.id = locations.loczone_id ORDER BY locations.name";
    $q = self::$DB->query($sql);
    $this->thisat = null;
    while($row = self::$DB->fetch($q)){
      if(is_null($this->thisat)){$this->thisat = $row; $this->thisat->locations = array();}
      $this->thisat->locations[$row->location_id] = $row;
    }

    if(is_null($this->thisat)){
      $sql = "SELECT *, id as appttype_id, name as appttype_name FROM appttypes WHERE id = '".self::$PARAMS["atid"][0]."' LIMIT 1";
      $q = self::$DB->query($sql);
      if(self::$DB->rows($q) == 1){
        $this->thisat = self::$DB->fetch($q);
        $this->thisat->locations = array();
      }
    }

    if(!is_null($this->thisat)){
      $attrs = array_merge(self::$PARAMS["form"], array("min_appt_length" => (array_key_exists("min_appt_length", self::$PARAMS["form"])) ? (int)floor((float)self::$PARAMS["form"]["min_appt_length"] * 2) : null ), (array_key_exists("weekdays_allowed", self::$PARAMS) && is_array(self::$PARAMS["weekdays_allowed"])) ? array("weekdays_allowed" => implode(",", self::$PARAMS["weekdays_allowed"])) : array());
      if(Appttype::update_attributes($this->thisat->appttype_id, $attrs)){
        self::throwMessage("Appointment type record modified successfully.");
        $this->output_page("index", "inline", "admin");
      } else {
        $this->output_page("edit_form", "inline");
      }
    } else {
      self::throwError("Requested appointment type was not found.");
      $this->edit_select();
    }

  }
}
?>
