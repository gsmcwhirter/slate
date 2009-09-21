<?php
/**
 * KvScheduler - System Admin SiteClass
 * @package KvScheduler
 * @subpackage Modules
 * @author Greg McWhirter <gsmcwhirter@gmail.com>
 * @version 1.0
 */

/**
 * Editing of system settings
 *
 * @package KvScheduler
 * @subpackage Modules
 */
class system_admin_site_class extends admin_site_class{

  /**
   * Constructor
   *
   */
  function __construct(){
    parent::__construct();
    $this->auth_level = ACCESS::sysop;
  }

  /**
   * System settings form
   *
   */
  public function form(){
    $this->output_page("form","inline");
  }

  /**
   * Processing of system settings form
   *
   */
  public function process_form(){
    if(!array_key_exists("form", self::$PARAMS) || !is_array(self::$PARAMS["form"])){
      self::throwError("Malformed parameters.");
      $this->form();
    } else {
      foreach(self::$PARAMS["form"] as $key => $value){
        if(!in_array($key, array_keys(CONFIG::$VARS))){
          self::throwError("In-valid key ".$key." passed. Ignoring.");
          continue;
        }
        $svt = CONFIG::$VARS[$key][3];
        $sv = ($svt == "aphash") ? ApHash::checkbox_input_to_string($value) : $value;

        $sql = "SELECT * FROM settings WHERE id = '".$key."' LIMIT 1";
        $q = self::$DB->query($sql);
        if(self::$DB->rows($q) == 1){
          $sql2 = "UPDATE settings SET svalue='".$sv."', svaluetype = '".$svt."' WHERE id = '".$key."' LIMIT 1";
        } else {
          $sql2 = "INSERT INTO settings (id, svalue, svaluetype) VALUES ('".$key."','".$sv."','".$svt."')";
        }

        $q2 = self::$DB->query($sql2);
        if($q2){
          self::throwMessage("Setting for ".CONFIG::$VARS[$key][1]." saved successfully.");
        } else {
          self::throwError("An error occurred updating the setting for ".CONFIG::$VARS[$key][1].".");
        }
      }
      $this->setup_config(true);

      $this->output_page("index","inline","admin");
    }
  }

  /**
   * Generates the input field for a setting
   *
   * @param string $key
   * @param array $var_element
   * @return string
   */
  protected function output_option($key, $var_element){
  #Generates the HTML form elements from the VARS hash in order to update the global config variables
    switch($var_element[4]){
      case 'input':
        switch($var_element[3]){
          case 'string' :
          case 'integer' :
          case 'float' :
            return "<input type='text' name='form[".$key."]' id='form[".$key."]' value='".$this->value_for($key)."' class='txtbox1' />";
          default :
            return "Unknown type for value input.";
        }
        break;
      case 'select':
        switch($var_element[3]){
          case 'boolean' :
            return(kvframework_markup::select(array("name" => "form[".$key."]", "values" => array(array("True", "TRUE"),array("False", "FALSE")), "selected" => $this->value_for($key), "class" => 'txtbox2')));
          case 'string' :
          case 'integer' :
          case 'float' :
            if(is_array($var_element[5])){
              return(kvframework_markup::select(array("name" => "form[".$key."]", "values" => TOOLS::array_collect($var_element[5], '$v', 'array($v, $v)'), "selected" => $this->value_for($key), "class" => 'txtbox2')));
            } else {
              $sql = "SELECT * FROM ".strtolower($var_element[5])."s";
              $q = self::$DB->query($sql);
              $opts = array();
              while($row = self::$DB->fetch($q)){$opts[] = array(call_user_func(array($var_element[5], "select_name"), $row), $row->id);}
              return(kvframework_markup::select(array("name" => "form[".$key."]", "values" => $opts, "selected" => $this->value_for($key), "class" => 'txtbox2')));
            }
          default:
            return "Unknown type for value input.";
        }
        break;
      case 'checkbox' :
        switch($var_element[3]){
          case 'aphash' :
            $appt_sched = $this->config_val("appt_sched");
            $ret = "<table style='width: 100%;'>";
            $ret .= "<tr>";
            $ret .= "<td></td><td>Sys</td><td>Sup</td><td>HD</td>";
            $ret .= "</tr>";
            #Checkbox generation here
            $ret .= "<tr>";
            $ret .= "<td>Generic:</td>";
            $ret .= "<td style='text-align: center;'>";
            $ret .= kvframework_markup::hidden_field_tag("form[".$key."][1]", "0");
            $ret .= kvframework_markup::check_box_tag("form[".$key."][1]", "1", $appt_sched["sysop"]["generic"]);
            $ret .= "</td>";
            $ret .= "<td style='text-align: center;'>";
            $ret .= kvframework_markup::hidden_field_tag("form[".$key."][5]", "0");
            $ret .= kvframework_markup::check_box_tag("form[".$key."][5]", "1", $appt_sched["supervisor"]["generic"]);
            $ret .= "</td>";
            $ret .= "<td style='text-align: center;'>";
            $ret .= kvframework_markup::hidden_field_tag("form[".$key."][9]", "0");
            $ret .= kvframework_markup::check_box_tag("form[".$key."][9]", "1", $appt_sched["helpdesk"]["generic"]);
            $ret .= "</td>";
            $ret .= "</tr>";
            $ret .= "<tr>";
            $ret .= "<td>OSCAR:";
            $ret .= "</td>";
            $ret .= "<td style='text-align: center;'>";
            $ret .= kvframework_markup::hidden_field_tag("form[".$key."][2]", "0");
            $ret .= kvframework_markup::check_box_tag("form[".$key."][2]", "1", $appt_sched["sysop"]["comcon"]);
            $ret .= "</td>";
            $ret .= "<td style='text-align: center;'>";
            $ret .= kvframework_markup::hidden_field_tag("form[".$key."][6]", "0");
            $ret .= kvframework_markup::check_box_tag("form[".$key."][6]", "1", $appt_sched["supervisor"]["comcon"]);
            $ret .= "</td>";
            $ret .= "<td style='text-align: center;'>";
            $ret .= kvframework_markup::hidden_field_tag("form[".$key."][10]", "0");
            $ret .= kvframework_markup::check_box_tag("form[".$key."][10]", "1", $appt_sched["helpdesk"]["comcon"]);
            $ret .= "</td>";
            $ret .= "</tr>";
            $ret .= "<tr>";
            $ret .= "<td>Wireless:";
            $ret .= "</td>";
            $ret .= "<td style='text-align: center;'>";
            $ret .= kvframework_markup::hidden_field_tag("form[".$key."][3]", "0");
            $ret .= kvframework_markup::check_box_tag("form[".$key."][3]", "1", $appt_sched["sysop"]["wireless"]);
            $ret .= "</td>";
            $ret .= "<td style='text-align: center;'>";
            $ret .= kvframework_markup::hidden_field_tag("form[".$key."][7]", "0");
            $ret .= kvframework_markup::check_box_tag("form[".$key."][7]", "1", $appt_sched["supervisor"]["wireless"]);
            $ret .= "</td>";
            $ret .= "<td style='text-align: center;'>";
            $ret .= kvframework_markup::hidden_field_tag("form[".$key."][11]", "0");
            $ret .= kvframework_markup::check_box_tag("form[".$key."][11]", "1", $appt_sched["helpdesk"]["wireless"]);
            $ret .= "</td>";
            $ret .= "</tr>";
            $ret .= "<tr>";
            $ret .= "<td>Other:";
            $ret .= "</td>";
            $ret .= "<td style='text-align: center;'>";
            $ret .= kvframework_markup::hidden_field_tag("form[".$key."][4]", "0");
            $ret .= kvframework_markup::check_box_tag("form[".$key."][4]", "1", $appt_sched["sysop"]["other"]);
            $ret .= "</td>";
            $ret .= "<td style='text-align: center;'>";
            $ret .= kvframework_markup::hidden_field_tag("form[".$key."][8]", "0");
            $ret .= kvframework_markup::check_box_tag("form[".$key."][8]", "1", $appt_sched["supervisor"]["other"]);
            $ret .= "</td>";
            $ret .= "<td style='text-align: center;'>";
            $ret .= kvframework_markup::hidden_field_tag("form[".$key."][12]", "0");
            $ret .= kvframework_markup::check_box_tag("form[".$key."][12]", "1", $appt_sched["helpdesk"]["other"]);
            $ret .= "</td>";
            $ret .= "</tr>";
            $ret .= "</table>";
            return $ret;
          default :
            return "Unknown type for value input.";
        }
        break;
      default:
        return "Unknown type for value input.";
    }
  }

  /**
   * Get the current value for a setting
   *
   * @param string $key
   * @return mixed
   */
  protected function value_for($key){
    $temp = (array_key_exists("form", self::$PARAMS) && is_array(self::$PARAMS["form"]) && array_key_exists($key, self::$PARAMS["form"])) ? self::$PARAMS["form"][$key] : ((is_bool($this->config_val($key))) ? (($this->config_val($key)) ? "TRUE" : "FALSE") : $this->config_val($key));
    return($temp);
  }

  /**
   * Form for override settings
   *
   */
  public function warning_override_form(){
    $this->output_page("overrides_form","inline");
  }

  /**
   * Processing of override settings form
   *
   */
  public function process_warning_override_form(){
    if(!array_key_exists("form", self::$PARAMS) || !is_array(self::$PARAMS["form"])){
      self::throwError("Malformed parameters.");
      $this->warning_override_form();
    } else {
      $valid_keys = array_intersect(array_keys(CONFIG::$VARS_OVERRIDES), array_keys(self::$PARAMS["form"]));
      foreach($valid_keys as $key){
        $svt = CONFIG::$VARS_OVERRIDES[$key][3];
        $sv = ($svt == "aphash") ? ApHash::checkbox_input_to_string(self::$PARAMS["form"][$key]) : self::$PARAMS["form"][$key];

        $sql = "SELECT * FROM settings WHERE id = '".$key."' LIMIT 1";
        $q = self::$DB->query($sql);
        if(self::$DB->rows($q) == 1){
          $sql2 = "UPDATE settings SET svalue='".$sv."', svaluetype = '".$svt."' WHERE id = '".$key."' LIMIT 1";
        } else {
          $sql2 = "INSERT INTO settings (id, svalue, svaluetype) VALUES ('".$key."','".$sv."','".$svt."')";
        }

        $q2 = self::$DB->query($sql2);
        if($q2){
          self::throwMessage("Setting for ".CONFIG::$VARS_OVERRIDES[$key][1]." saved successfully.");
        } else {
          self::throwError("An error occurred updating the setting for ".CONFIG::$VARS_OVERRIDES[$key][1].".");
        }
      }
      $this->setup_overrides(true);

      $this->output_page("index","inline","admin");
    }
  }

  /**
   * Get the current value for an override setting
   *
   * @param string $key
   * @return mixed
   */
  protected function value_for_override($key){
    $temp = (array_key_exists("form", self::$PARAMS) && is_array(self::$PARAMS["form"]) && array_key_exists("override_".$key, self::$PARAMS["form"])) ? self::$PARAMS["form"]["override_".$key] : $this->override_val("override_".$key);
    return($temp);
  }
}
