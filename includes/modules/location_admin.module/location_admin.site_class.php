<?php
/**
 * KvScheduler - Location Admin SiteClass
 * @package KvScheduler
 * @subpackage Modules
 * @author Greg McWhirter <gsmcwhirter@gmail.com>
 * @version 1.0
 */

/**
 * CRUD for locations
 *
 * @package KvScheduler
 * @subpackage Modules
 */
class location_admin_site_class extends admin_site_class{
  /**
   * Selector values array
   *
   * @var array
   */
  protected $all_locs;

  /**
   * Location holder
   *
   * @var mixed
   */
  protected $thisthing;

  /**
   * Appointment types selector values
   *
   * @var array
   */
  protected $all_ats;

  /**
   * Loczones selector values
   *
   * @var array
   */
  protected $all_lzs;

  /**
   * Metalocs selector values
   *
   * @var array
   */
  protected $all_mls;

  /**
   * Loctags selector values
   *
   * @var array
   */
  protected $all_ltgs;

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
    $this->prep_form2();
    $this->output_page("add_form","inline");
  }

  /**
   * Processing of addition form
   */
  public function process_add_form(){
    if(!array_key_exists("form", self::$PARAMS) || !is_array(self::$PARAMS["form"])){
      self::throwError("Bad parameters");
      $this->add_form();
    } else {
      $id = Location::create(array_merge(self::$PARAMS["form"], (array_key_exists("appttype_id", self::$PARAMS["form"])) ? array("appttype_id" => (int)self::$PARAMS["form"]["appttype_id"]) : array(), (array_key_exists("loczone_id", self::$PARAMS["form"])) ? array("loczone_id" => (int)self::$PARAMS["form"]["loczone_id"]) : array()));
      if($id){
        self::throwMessage("Location added successfully.");
        if(array_key_exists("metalocs", self::$PARAMS) && count(self::$PARAMS["metalocs"]) > 0){
          $vals = "";
          foreach(self::$PARAMS["metalocs"] as $mlid){
            $vals .= "('".$id."', '".$mlid."'), ";
          }
          $sql = "INSERT IGNORE INTO metaloc_locations (location_id, metaloc_id) VALUES ".substr($vals,0,-2)."";
          try{
            $q = self::$DB->query($sql);
          } catch(kvframework_db_exception $e){
            kvframework_log::write_log("METALOCS: ".$sql, KVF_LOG_LERROR);
          }
          if($q){
            self::throwMessage("Metaloc information recorded successfully.");
          } else {
            self::throwError("An error occurred processing metaloc information");
          }
        }
        if(array_key_exists("loctags", self::$PARAMS) && count(self::$PARAMS["loctags"]) > 0)
        {
          $vals = "";
          foreach(self::$PARAMS["loctags"] as $ltid)
          {
            $vals .= "('".$id."','".$ltid."'), ";
          }
          $sql = "INSERT IGNORE INTO location_loctags (location_id, loctag_id) VALUES ".substr($vals,0,-2)."";
          try
          {
            $q = self::$DB->query($sql);
          }
          catch(kvframework_db_exception $e)
          {
            kvframework_log::write_log("LOCTAGS: ".$sql, KVF_LOG_ERROR);
          }

          if($q)
          {
            self::throwMessage("Location tag information recorded successfully.");
          }
          else
          {
            self::throwError("An error occurred processing location tag information.");
          }
        }
        $this->output_page("index","inline","admin");
      } else {
        self::throwError("Location failed to be added.");
        $this->add_form();
      }
    }
  }

  /**
   * Select form for editing
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
      $this->prep_form2();
      $this->output_page("edit_form","inline");
    } else {
      self::throwError("Requested location was not found.");
      $this->edit_select();
    }
  }

  /**
   * Processing of editing form
   *
   */
  public function process_edit_form(){
    if(!array_key_exists("form", self::$PARAMS) || !is_array(self::$PARAMS["form"]) || (array_key_exists("metalocs", self::$PARAMS) && !is_array(self::$PARAMS["metalocs"]))){
      self::throwError("Invalid parameters.");
      $this->edit_form();
    } else {
      if($this->prep_form()){
        $this->prep_form2();
        if(Location::update_attributes($this->thisthing->location_id, array_merge(self::$PARAMS["form"], (array_key_exists("appttype_id", self::$PARAMS["form"])) ? array("appttype_id" => (int)self::$PARAMS["form"]["appttype_id"]) : array(), (array_key_exists("loczone_id", self::$PARAMS["form"])) ? array("loczone_id" => (int)self::$PARAMS["form"]["loczone_id"]) : array()))){
          self::throwMessage("Location record modified successfully.");
          if(array_key_exists("metalocs", self::$PARAMS))
          {
            $sql = "DELETE FROM metaloc_locations WHERE location_id = '".$this->thisthing->location_id."'";
	    $q = self::$DB->query($sql);

            if(count(self::$PARAMS["metalocs"]) > 0)
            {
              $vals = "";
              foreach(self::$PARAMS["metalocs"] as $mlid)
              {
                $vals .= "('".$this->thisthing->location_id."', '".$mlid."'), ";
              }
              $sql = "INSERT IGNORE INTO metaloc_locations (location_id, metaloc_id) VALUES ".substr($vals,0,-2)."";
              $q = self::$DB->query($sql);
              if($q){
                self::throwMessage("Metaloc information recorded successfully.");
              } else {
                self::throwError("An error occurred processing metaloc information");
              }
            }

          }

          if(array_key_exists("loctags", self::$PARAMS))
          {
            $sql = "DELETE FROM location_loctags WHERE location_id = '".$this->thisthing->location_id."'";
            $q = self::$DB->query($sql);

            if(count(self::$PARAMS["loctags"]) > 0)
            {
              $vals = "";
              foreach(self::$PARAMS["loctags"] as $ltid)
              {
                $vals .= "('".$this->thisthing->location_id."', '".$ltid."'), ";
              }
              $sql = "INSERT IGNORE INTO location_loctags (location_id, loctag_id) VALUES ".substr($vals, 0, -2)."";
              $q = self::$DB->query($sql);
              if($q)
              {
                self::throwMessage("Location tag information recorded successfully.");
              }
              else
              {
                self::throwError("An error occurred processing location tag information.");
              }
            }
          }
          $this->output_page("index","inline","admin");
        } else {
          self::throwError("Location record not modified successfully.");
          $this->edit_form();
        }
      } else {
        self::throwError("Requested location was not found.");
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
   * Confirm removal
   */
  public function remove_confirm(){
    if($this->prep_form()){
      $this->output_page("remove_confirm","inline");
    } else {
      self::throwError("Requested location was not found");
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
      } elseif(!Location::destroy($this->thisthing->location_id)){
        self::throwError("Location was not able to be deleted for an unknown reason.");
      } else {
        self::throwMessage("Location deleted successfully.");
      }

      $this->output_page("index","inline","admin");
    } else {
      self::throwError("Requested location was not found");
      $this->remove_select();
    }
  }

  /**
   * Prepare the select forms
   */
  protected function prep_select(){
    $this->all_locs = array();
    $sql = "SELECT *, locations.id as location_id, locations.name as location_name, loczones.id as loczone_id, loczones.name as loczone_name FROM locations, loczones WHERE locations.loczone_id = loczones.id ORDER BY locations.name, loczones.name";
    $q = self::$DB->query($sql);
    while($row = self::$DB->fetch($q)){
      $this->all_locs[] = array(Location::select_name($row), $row->location_id);
    }

    return true;
  }

  /**
   * Prepare the editing and removal forms
   *
   * @return boolean
   */
  protected function prep_form(){
    $sql = "SELECT *, locations.id as location_id, locations.name as location_name, loczones.id as loczone_id, loczones.name as loczone_name FROM locations, loczones WHERE locations.id = '".self::$PARAMS["locid"]."' AND loczones.id = locations.loczone_id LIMIT 1";
    $q = self::$DB->query($sql);
    if(self::$DB->rows($q) == 1){
      $this->thisthing = self::$DB->fetch($q);
    } else {
      $this->thisthing = null;
      return false;
    }

    $this->thisthing->metalocs = array();
    $sql = "SELECT * FROM metaloc_locations WHERE location_id = '".$this->thisthing->location_id."'";
    $q = self::$DB->query($sql);
    while($row = self::$DB->fetch($q)){
      $this->thisthing->metalocs[] = $row->metaloc_id;
    }

    $this->thisthing->loctags = array();
    $sql = "SELECT * FROM location_loctags WHERE location_id = '".$this->thisthing->location_id."'";
    $q = self::$DB->query($sql);
    while($row = self::$DB->fetch($q))
    {
      $this->thisthing->loctags[] = $row->loctag_id;
    }

    return true;
  }

  /**
   * Prepare the adding and editing forms
   *
   * @return boolean true
   */
  protected function prep_form2(){
    $this->all_ats = array();
    $sql = "SELECT *, id as appttype_id FROM appttypes ORDER BY name";
    $q = self::$DB->query($sql);
    while($row = self::$DB->fetch($q)){
      $this->all_ats[] = array(Appttype::select_name($row), $row->appttype_id);
    }

    $this->all_lzs = array();
    $sql = "SELECT *, id as loczone_id FROM loczones ORDER BY name";
    $q = self::$DB->query($sql);
    while($row = self::$DB->fetch($q)){
      $this->all_lzs[] = array(Loczone::select_name($row), $row->loczone_id);
    }

    $this->all_mls = array();
    $sql = "SELECT *, id as metaloc_id FROM metalocs ORDER BY name";
    $q = self::$DB->query($sql);
    while($row = self::$DB->fetch($q)){
      $this->all_mls[] = array(Metaloc::select_name($row), $row->metaloc_id);
    }

    $this->all_ltgs = array();
    $sql = "SELECT *, id as loctag_id FROM loctags ORDER BY label";
    $q = self::$DB->query($sql);
    while($row = self::$DB->fetch($q))
    {
      $this->all_ltgs[] = array(Loctag::select_name($row), $row->loctag_id);
    }

    return true;
  }
}

?>
