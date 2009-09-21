<?php
/**
 * KvScheduler - User SiteClass
 * @package KvScheduler
 * @subpackage Modules
 * @author Greg McWhirter <gsmcwhirter@gmail.com>
 * @version 1.0
 */

/**
 * Does a lot of things.  Main entry point
 *
 * @package KvScheduler
 * @subpackage Modules
 */
class user_site_class extends application_site_class {
  /**
   * Whether a ConsultantAppt version is current or not
   *
   * @var boolean
   */
  protected $current;
  /**
   * Holds a consultant appointment record
   *
   * @var mixed
   */
  protected $rap;

  /**
   * Holds an appointment record
   *
   * @var mixed
   */
  protected $app;
  /**
   * Holds a consultant record
   *
   * @var mixed
   */
  protected $rc;

  /**
   * Constructor
   *
   */
  function __construct(){
    parent::__construct();
    $this->auth_level = ACCESS::noauth;
    $this->set_layout("default3");
  }

  /**
   * Form for logging in
   *
   */
  public function login_form(){
    $this->output_page("login", "full2");
  }

  /**
   * Processing a login
   */
  public function login_form_process(){
    if($this->USER->login(self::$PARAMS['username'], self::$PARAMS['password'])){
      $this->save_session();
      self::redirect_to("user","welcome");
    } else {
      self::throwError("Login failed.");
      $this->login_form();
    }
  }

  /**
   * Display the logout page
   *
   */
  public function logout_form(){
    $this->output_page("logout", "full2");
  }

  /**
   * Process a logout
   *
   */
  public function logout_form_process(){
    $this->USER = new User($this->config_val("acd_on"));
    $this->save_session();
    $this->logout_form();
  }

  /**
   * Generate the top-left menu buttons
   *
   */
  public function topbar(){
    $this->connected = $this->USER->connected();
    $this->USER->update_user_record(null, true);
    $this->rc = $this->USER->allrcinfo();
    $this->output_page("topbar", "inline");
  }

  /**
   * Login form for mobile
   *
   */
  public function mlogin_form(){
    $this->mobile = true;
    $this->output_page("mlogin", "full", false, array(), CONFIG::tempname_mobile);
  }

  /**
   * Process the login form for mobile
   *
   */
  public function mlogin_form_process(){
    $this->mobile = true;
    if($this->USER->login(self::$PARAMS['username'], self::$PARAMS['password'])){
      $this->save_session();
      self::redirect_to("mobile","index");
    } else {
      self::throwError("Login failed.");
      $this->mlogin_form();
    }
  }

  /**
   * Logout page for mobile
   *
   */
  public function mlogout_form(){
    $this->mobile = true;
    $this->output_page("mlogout", "full", false, array(), CONFIG::tempname_mobile);
  }

  /**
   * Process a logout for mobile
   *
   */
  public function mlogout_form_process(){
    $this->mobile = true;
    $this->USER = new User($this->config_val("acd_on"));
    $this->save_session();
    $this->mlogout_form();
  }

  /**
   * Top bar for mobile pages
   *
   */
  public function mtopbar(){
    $this->mobile = true;
    $this->connected = $this->USER->connected();
    $this->USER->update_user_record(null, true);
    $this->rc = $this->USER->allrcinfo();
    $this->output_page("mtopbar", "inline", false, array(), CONFIG::tempname_mobile);
  }

  /**
   * Appointment receipt page
   *
   */
  public function receipt_form(){
    $this->set_layout("default2");
    if(!array_key_exists("r", self::$PARAMS) || !array_key_exists("a", self::$PARAMS) || !array_key_exists("v", self::$PARAMS)){
      $this->save_session();
      self::redirect_to("user","welcome");
    }

    $sql = "SELECT *, appointments.id as appointment_id, consultants.id as consultant_id, consultantappts.rapid as consultantappt_id, locations.name as location_name, loczones.name as loczone_name FROM appointments, consultants, consultantappts, locations, loczones WHERE appointments.id = '".self::$PARAMS["a"]."' AND consultants.id = '".self::$PARAMS["r"]."' AND consultantappts.appointment_id = appointments.id AND consultantappts.consultant_id = consultants.id AND consultantappts.confirmed = 'FALSE' AND locations.id = appointments.location_id AND loczones.id = locations.loczone_id LIMIT 1";
    $q = self::$DB->query($sql);
    if(self::$DB->rows($q) == 1){
      $this->app = $this->rc = $this->rap = self::$DB->fetch($q);
      if((int)self::$PARAMS["v"] == (int)$this->rap->confirm_version){
        $sql = "SELECT *, id as tm_id FROM ".strtolower($this->rap->tm_type)."s WHERE id = '".$this->rap->tm_id."' LIMIT 1";
        $q = self::$DB->query($sql);
        $this->rap->tm = self::$DB->fetch($q);
        $this->current = "yes";
        $updates = array(
          "confirmed" => "TRUE",
          "confirm_ip" => getenv("REMOTE_ADDR"),
          "confirm_timestamp" => time()
        );
        Consultantappt::update_attributes($this->rap->consultantappt_id, $updates);
      } else {
        $this->current = "no";
      }
      $this->output_page("appt_confirmation","full2");
    } else {
      self::throwError("No unconfirmed appointment with those parameters exists.");
      $this->save_session();
      self::redirect_to("user","welcome");
    }
  }

  /**
   * Form for lost passwords
   *
   */
  public function lost_password_form(){
    $this->output_page("lost_pass", "full2");
  }

  /**
   * Processing of lost password form
   *
   * @param string $u
   * @return boolean
   */
  public function process_lost_password($u = ""){
    try{
      if($u == ""){self::validate_presence_of(array("username"), self::$PARAMS, "cannot be left blank."); self::validate_resolve(); $name = self::$PARAMS["username"];}
      else{$name = $u;}
    } catch(validation_exception $e){
      foreach($e->errors as $err){self::throwError($err);}
    }

    if(!self::is_errors()){
      list($user, $dummy, $type) = $this->USER->get_user_record($name);
      if(is_null($user)){ self::throwError("No users with the username $name were found.");}
      else{
        $newpass = $this->USER->random_password(10);
        $sql = "UPDATE ".$type." SET password = '".$this->USER->crypt_password($newpass)."', force_pass_change = '1' WHERE id = '".$user["id"]."' LIMIT 1";
        $q = self::$DB->query($sql);
        if(self::$DB->affected() == 1){
          if(Mailer::deliver_lost_password($user, $newpass, $this->config_val("email_from"), ($type == "helpdeskers" && $user["acdaccount"] == "yes") ? "acd" : $type)){
            self::throwMessage("Password Reset successfully.  A new password was e-mailed to ".$user["username"].".");
          } else {
            self::throwError("Mailing failed for unknown reason.");
          }
        } else {
          self::throwError(self::$DB->error("text"));
        }
      }
    }

    if($u == ""){
      if(self::is_errors()){
        $this->output_page("lost_pass", "full2");
      } else {
        self::redirect_to("user","login_form");
      }
    } else {
      return true;
    }
  }

  /**
   * Access denied page
   *
   */
  public function denied(){
    $this->render_inline("denied");
  }

  /**
   * Process the welcome page
   *
   * @param string $type
   */
  protected function do_welcome($type){
    $rc = $this->USER->info("force_pass_change");
    if(!is_null($rc) && $rc == "1"){
      self::throwMessage("Password change forced");
      $this->output_page("change_password_form", $type, "self_admin");
    } elseif(array_key_exists("uploader", self::$PARAMS)){
      $this->output_page("index", $type, "admin");
    } else {
      switch($this->USER->access()){
        case ACCESS::display:
          $this->output_page("display_date_full", $type, "display", array("date" => (array_key_exists("date", self::$PARAMS)) ? self::$PARAMS["date"] : ""));
          break;
        case ACCESS::user:
          $this->output_page("display_date_full", $type, "calendar", array("date" => (array_key_exists("date", self::$PARAMS)) ? self::$PARAMS["date"] : ""));
        case ACCESS::modify:
          $this->output_page("display_date_full", $type, "calendar", array("date" => (array_key_exists("date", self::$PARAMS)) ? self::$PARAMS["date"] : ""));
        case ACCESS::sysop:
          $this->output_page("display_date_full", $type, "calendar", array("date" => (array_key_exists("date", self::$PARAMS)) ? self::$PARAMS["date"] : ""));
        default:
          self::redirect_to("user","login_form");
      }
    }
  }

  /**
   * The main entry point
   *
   */
  public function welcome(){
    $this->do_welcome("full");
  }

  /**
   * Reloading the default page for a user
   */
  public function reload(){
    $this->do_welcome("inline");
  }

  /**
   * Clear the lockouts for the current user
   *
   */
  public function clear_lockouts(){
    Lockouts::destroy($this->USER->info("username"));
    $this->render_text(".", "inline");
  }

  /**
   * Redirect to the mobile page
   *
   */
  public function mobile(){
    self::redirect_to("mobile","index");
  }

}

?>
