<?php
/**
 * KvScheduler - Ticket Admin SiteClass
 * @package KvScheduler
 * @subpackage Modules
 * @author Greg McWhirter <gsmcwhirter@gmail.com>
 * @version 1.0
 */

/**
 * CRUD for Tickets, Meetings, and Meeckets
 *
 * @package KvScheduler
 * @subpackage Modules
 */
class ticket_site_class extends application_site_class{

  /**
   * The Ticket, Meeting, or Meecket
   *
   * @var mixed
   */
  protected $thing = null;

  /**
   * Constructor
   *
   */
  function __construct(){
    parent::__construct();
  }

  /**
   * Editing form
   *
   */
  public function modify_thing_form(){
    if($this->prep()){
      $this->output_page((in_array(self::$PARAMS["ttype"], array("Meeting","Meecket"))) ? "mod_meeting" : "mod_ticket", "inline");
    }
  }

  /**
   * Processing of editing form
   *
   */
  public function process_modify_thing_form(){
    if($this->prep() && is_array(self::$PARAMS["fi"])){
      $res = null;
      switch($this->thing->tm_type){
        case "Meeting":
          $res = Meeting::update_attributes($this->thing->tm_id, self::$PARAMS["fi"]);
          break;
        case "Meecket":
          $res = Meecket::update_attributes($this->thing->tm_id, self::$PARAMS["fi"]);
          break;
        case "Ticket":
          $res = Ticket::update_attributes($this->thing->tm_id, self::$PARAMS["fi"]);
          break;
      }
      if($res){
        self::throwMessage("Ticket or meeting updated successfully.");
        $sql = "UPDATE consultantappts, appointments SET consultantappts.confirmed = 'FALSE', consultantappts.confirm_version = consultantappts.confirm_version + 1 WHERE appointments.tm_id = '".$this->thing->tm_id."' AND appointments.tm_type = '".$this->thing->tm_type."' AND consultantappts.appointment_id = appointments.id";
        $q = self::$DB->query($sql);
        if($q){
          foreach($this->thing->appointments as $appt){
            foreach($appt->consultants as $rc){
              Mailer::deliver_modify_appointment($appt->appointment_id, $rc, $this->config_val("email_from"), $rc->confirm_version + 1, MyFunctions::datetime_in_appt(TOOLS::date_today(), TOOLS::string_to_time($appt->starttime), $appt), TOOLS::string_to_time($appt->starttime));
            }
          }
          $this->render_close_box();
        } else {
          self::throwError("Consultantappts could not be reset to unconfirmed.");
          $this->output_page((in_array(self::$PARAMS["ttype"], array("Meeting","Meecket"))) ? "mod_meeting" : "mod_ticket", "inline");
        }
      } else {
        $this->output_page((in_array(self::$PARAMS["ttype"], array("Meeting","Meecket"))) ? "mod_meeting" : "mod_ticket", "inline");
      }
    } else {
      self::throwError("Invalid parameters passed.");
      $this->output_page((in_array(self::$PARAMS["ttype"], array("Meeting","Meecket"))) ? "mod_meeting" : "mod_ticket", "inline");
    }

  }

  /**
   * Preparation of forms
   *
   * @return boolean
   */
  protected function prep(){
    if(!array_key_exists("ttype", self::$PARAMS) || !self::$PARAMS["ttype"] || !in_array(self::$PARAMS["ttype"], array("Meeting","Meecket","Ticket")) || !array_key_exists("tid", self::$PARAMS)){
      self::throwError("Incorrect parameters passed.");
      $this->render_close_box();
    } elseif((self::$PARAMS["ttype"] == "Meeting" || self::$PARAMS["ttype"] == "Meecket") && $this->USER->access() < ACCESS::modify){
      self::throwError("You are not allowed to edit meeting or meecket details.");
      $this->render_close_box();
    } else {
      $sql = "SELECT *, ".strtolower(self::$PARAMS["ttype"])."s.id as tm_id, appointments.id as appointment_id, consultants.id as consultant_id, '".self::$PARAMS["ttype"]."' as tm_type, locations.id as location_id, locations.name as location_name, loczones.id as loczone_id, loczones.name as loczone_name FROM ".strtolower(self::$PARAMS["ttype"])."s, appointments, consultants, consultantappts, locations, loczones WHERE consultants.status = 'active' AND ".strtolower(self::$PARAMS["ttype"])."s.id = '".self::$PARAMS["tid"]."' AND appointments.tm_id = ".strtolower(self::$PARAMS["ttype"])."s.id AND appointments.tm_type = '".self::$PARAMS["ttype"]."' AND consultantappts.appointment_id = appointments.id AND consultants.id = consultantappts.consultant_id AND locations.id = appointments.location_id AND loczones.id = locations.loczone_id";
      $q = self::$DB->query($sql);
      if(self::$DB->rows($q) >= 1){
        while($row = self::$DB->fetch($q)){
          if(is_null($this->thing)){$this->thing = $row;}
          if(!is_array($this->thing->appointments)){$this->thing->appointments = array();}
          if(!array_key_exists($row->appointment_id, $this->thing->appointments)){$this->thing->appointments[$row->appointment_id] = $row;}
          if(!is_array($this->thing->appointments[$row->appointment_id]->consultants)){$this->thing->appointments[$row->appointment_id]->consultants = array();}
          $this->thing->appointments[$row->appointment_id]->consultants[$row->consultant_id] = $row;
        }
        return true;
      } else {
        self::throwError("The requested ticket or meeting was not found.");
        $this->render_close_box();
      }
    }

    return false;
  }

  /**
   * Form for deleting a thing
   *
   */
  public function delete_thing_form(){
    if($this->prep()){
      if(in_array("TRUE", TOOLS::array_collect($this->thing->appointments, '$a', '$a->repeat'))){
        self::throwError("You are not allowed to delete that ticket or meeting.");
        $this->render_close_box();
      } else {
        $this->output_page((in_array(self::$PARAMS["ttype"], array("Meeting","Meecket"))) ? "del_meeting" : "del_ticket", "inline");
      }
    }
  }

  /**
   * Processing of deletion form
   */
  public function process_delete_thing_form(){
    if($this->prep()){
      if(in_array("TRUE", TOOLS::array_collect($this->thing->appointments, '$a', '$a->repeat'))){
        self::throwError("You are not allowed to delete that ticket or meeting.");
      } else {
        if(array_key_exists("confirm_delete", self::$PARAMS) && self::$PARAMS["confirm_delete"] == 'yes'){
          $res = null;
          switch($this->thing->tm_type){
            case "Meeting":
              $res = Meeting::destroy($this->thing->tm_id);
              break;
            case "Meecket":
              $res = Meecket::destroy($this->thing->tm_id);
              break;
            case "Ticket":
              $res = Ticket::destroy($this->thing->tm_id);
              break;
          }

          if($res){
            self::throwMessage("The ticket or meeting was deleted as requested.");
          } else {
            self::throwError("The ticket or meeting failed to be deleted.");
          }
        } else {
          self::throwMessage("The ticket or meeting was not deleted as per request.");
        }
      }
    }

    $this->render_close_box();
  }
}
