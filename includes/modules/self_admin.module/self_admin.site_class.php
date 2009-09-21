<?php
/**
 * KvScheduler - Self Admin SiteClass
 * @package KvScheduler
 * @subpackage Modules
 * @author Greg McWhirter <gsmcwhirter@gmail.com>
 * @version 1.0
 */

/**
 * Self account management
 *
 * @package KvScheduler
 * @subpackage Modules
 */
class self_admin_site_class extends admin_site_class{
  /**
   * Constructor
   *
   */
  function __construct(){
    parent::__construct();
    $this->auth_level = ACCESS::display;
  }

  /**
   * Change password form
   *
   */
  public function change_password_form(){
    $this->output_page("change_pass", "inline");
  }

  /**
   * Processing of change password form
   *
   */
  public function process_change_password(){
    try{
      self::validate_presence_of(array("oldpass", "password", "password_confirmation"), self::$PARAMS);
      self::validate_confirmation_of(array("password"), self::$PARAMS);
      self::validate_resolve();
    } catch (validation_exception $e){
      foreach($e->errors as $err){self::throwError($err);}
    }

    if(!self::is_errors()){
      if(!$this->USER->update_user_record(self::$PARAMS["oldpass"])){
        self::throwError("Incorrect current password.");
      } elseif($this->USER->usertype == "helpdeskers" && $this->USER->info("acdaccount") == "yes"){
        $admin = new User($this->config_val("acd_on"));
        if(!($admin->login(self::$PARAMS["admin_un"], self::$PARAMS["admin_pass"]) || $admin->access < ACCESS::modify)){
          self::throwError("Admin credentials were invalid");
        } else {
          if($this->do_pass_update(self::$PARAMS["password"])){
            self::throwMessage("Password updated successfully.");
          } else {
            self::throwError(self::$DB->error("text"));
          }
        }
      } else {
        if($this->do_pass_update(self::$PARAMS["password"])){
          self::throwMessage("Password updated successfully.");
        } else {
          self::throwError(self::$DB->error("text"));
        }
      }
      $this->output_page("reload", "inline", "user");
    } else {
      $this->output_page("change_pass", "inline");
    }
  }

  /**
   * Actually update a password
   *
   * @param string $npass
   * @return boolean
   */
  protected function do_pass_update($npass){
    $sql = "UPDATE ".$this->USER->usertype." SET password = '".$this->USER->crypt_password($npass)."', force_pass_change='0' WHERE id = '".$this->USER->info("id")."' LIMIT 1";
    $q = self::$DB->query($sql);
    if(self::$DB->affected() == 1){
      return true;
    } else {
      return false;
    }
  }
}
