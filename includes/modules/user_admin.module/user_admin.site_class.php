<?php
/**
 * KvScheduler - User Admin SiteClass
 * @package KvScheduler
 * @subpackage Modules
 * @author Greg McWhirter <gsmcwhirter@gmail.com>
 * @version 1.0
 */

/**
 * User management functions
 *
 * @package KvScheduler
 * @subpackage Modules
 */
class user_admin_site_class extends user_site_class{
  /**
   * Selector generator values for users
   *
   * @var array
   */
  protected $all_users;

  /**
   * Constructor
   *
   */
  function __construct(){
    parent::__construct();
    $this->auth_level = ACCESS::modify;
  }

  /**
   * Form to reset a lot of passwords at once
   *
   */
  public function mass_password_reset_form(){
    $this->all_users = array();
    $sql = "SELECT username, realname, 'consultant              ' as type FROM consultants UNION SELECT username, realname, 'supervisor' as type FROM supervisors UNION SELECT username, realname, 'helpdesk' as type FROM helpdeskers ORDER BY type desc, SUBSTRING(realname FROM (INSTR(realname, ' ')+1)) ASC";
    $q = self::$DB->query($sql);
    while($row = self::$DB->fetch($q)){
      $this->all_users[] = array(ucwords(trim($row->type))." - ".Consultant::select_name($row), $row->username);
    }
    $this->output_page("pass_reset_form", "inline");
  }

  /**
   * Processing of form for mass password resets
   *
   */
  public function process_mass_password_reset_form(){
    if(!array_key_exists("users", self::$PARAMS) || !is_array(self::$PARAMS["users"]) || count(self::$PARAMS["users"]) == 0){
      self::throwError("You must select one or more users.");
    } else {
      foreach(self::$PARAMS["users"] as $u){
        $this->process_lost_password($u);
      }
    }

    if(self::is_errors()){
      $this->mass_password_reset_form();
    } else {
      $this->output_page("index", "inline", "admin");
    }
  }

  /**
   * Form to add a lot of users
   *
   */
  public function mass_user_addition_form(){
    $this->output_page("people_add_form","inline");
  }

  /**
   * Processing of user mass-addition form
   *
   */
  public function process_mass_user_addition_form(){
    $sql = "SELECT *, id as tag_id FROM tags LIMIT 1";
    $q = self::$DB->query($sql);
    $tag = null;
    if(self::$DB->rows($q) == 1){
      $tag = self::$DB->fetch($q);
    } else {
      self::throwError("No tag found.");
    }

    if(array_key_exists("file", self::$FILES)){
      $lines = file(self::$FILES["file"]["tmp_name"]);
      foreach($lines as $line){
        $parts = explode(",", $line);
        if(!in_array($parts[0],array("R","H","S"))){
          self::throwError("Found an unrecognized user type.  Line: ".$line);
          continue;
        }

        $args = array("username" => $parts[1], "realname" => $parts[2], "password" => 1);

        switch($parts[0]){
          case "R":
            $args = array_merge($args, array("gender" => "M", "staff" => "FALSE", "tag_id" => $tag->tag_id));
            $id = Consultant::create($args);
            break;
          case "S":
            $id = Supervisor::create($args);
            break;
          case "H":
            $id = Helpdesker::create($args);
            break;
        }

        if($id){
          self::throwMessage("User ". $parts[1] ." added successfully.");
        }
      }
    } else {
      self::throwError("Error in the file upload.");
    }

    $this->save_session();
    self::redirect_to("user","welcome");
  }
}
