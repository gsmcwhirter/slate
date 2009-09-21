<?php
/**
 * KvScheduler - Application config 2
 * @package KvScheduler
 * @author Greg McWhirter <gsmcwhirter@gmail.com>
 * @version 1.0
 */

/**
 * Essentially an ENUM of access levels
 *
 * @package KvScheduler
 */
abstract class ACCESS{
  /**
   * Sysop level
   *
   */
  const sysop = 5;
  /**
   * Supervisor level
   *
   */
  const modify = 4;
  /**
   * Helpdesk level
   *
   */
  const user = 3;
  /**
   * Consultant level
   *
   */
  const display = 2;
  /**
   * Not logged in level
   *
   */
  const noauth = 1;
}

/**
 * Holds the configuration constants and defaults for variables
 *
 * @package KvScheduler
 */
abstract class CONFIG{

  /**
   * Root URI of the requests
   *
   */
  const rooturi = "";
  /**
   * Base url of the request (domain)
   *
   */
  const baseurl = "http://www.mydomain.com";
  /**
   * Root url of the request (baseurl+rooturi)
   *
   */
  const rooturl = "http://www.mydomain.com";
  /**
   * URL where to find images
   *
   */
  const image_url = "http://www.mydomain.com/images";
  /**
   * absolute filesystem path of the application
   *
   */
  const abspath = ROOTDIR;
  /**
   * Default template name
   *
   */
  const tempname = "default";
  /**
   * Default mobile template name
   *
   */
  const tempname_mobile = "mobile";

  /**
   * Database mode
   *
   */
  const DB_MODE = "development";
  /**
   * Rendering engine class name
   *
   */
  const RENDER_ENGINE = 'kvframework_renderengine';
  /**
   * Default site class when none is supplied
   *
   */
  const DEFAULT_SITE_CLASS = "user";

  /**
   * SQL snippet
   *
   */
  const SQL_REALNAME_ORDER_CLAUSE = "SUBSTRING(consultants.realname FROM (INSTR(consultants.realname, ' ')+1)) ASC";

  /**
   * Holds defaults for system setting variables
   *
   * @var array
   */
  static $VARS = array(
    "travel_tolerance" => array(1, "Travel Tolerance", 3, 'integer', 'input'),
    "send_email" => array(3,"Send Emails", true, 'boolean', 'select'),
    "email_admin" => array(4,"Admin Address", "admin@mydomain.com", 'string', 'input'),
    "email_from" => array(5,"From Address", "fromaddr@mydomain.com", 'string', 'input'),
    "email_to" => array(6,"To Address username@", "mydomain.com", 'string', 'input'),
    "backup_dir" => array(7,"Backup Directory", "/path/to/backup/dir", 'string', 'input'),
    "acd_on" => array(8,"Use ACD Accounts", false, 'boolean', 'select'),
    "finals_flag" => array(21,"No Same Day Scheduling", false, 'boolean', 'select'),
    "at_generic" => array(9,"Regular Appointments", 1, 'integer', 'select', "Appttype"),
    "at_comcon" => array(10,"Limited Appointments", 2, 'integer', 'select', "Appttype"),
    "at_wireless" => array(11,"Wireless Appointments", 3, 'integer', 'select', "Appttype"),
    "at_other" => array(12,"Other Appointments", 4, 'integer', 'select', "Appttype"),
    "week_start" => array(17,"Weeks Start On", "N", 'string', 'select', array("Sunday","Monday", "Tuesday", "Wednesday", "Thursday", "Friday","Saturday")),
    "appt_buffer" => array(18,"Buffer Time (hours)", 2, 'integer', 'input'),
    "appt_sched" => array(19,"Appointment Scheduling", "111111111100", 'aphash', 'checkbox'),
    "sem_default" => array(2,"Current Semester", null, 'integer','select', "Semester"),
    "loc_time_buff" => array(20, "Location Time Buffer", 4.0, 'float', 'input')
  );

  /**
   * Holds defaults for more system setting varaibles
   *
   * @var array
   */
  static $VARS_OVERRIDES = array(
    "override_randomlist" => array(1, "Randomize Consultants (below)", ACCESS::modify, 'integer'),
    "override_concur" => array(1, "Concurrent Appts.", ACCESS::modify, 'integer'),
    "override_far" => array(1, "Appointment Distance.", ACCESS::modify, 'integer'),
    "override_gender" => array(1, "Location Gender", ACCESS::modify, 'integer'),
    "override_length" => array(1, "Appointment Length", ACCESS::modify, 'integer'),
    "override_time" => array(1, "Time of Scheduling", ACCESS::modify, 'integer'),
    "override_finals" => array(1, "Finals Week", ACCESS::modify, 'integer'),
    "override_appthour" => array(1, "Apppointment Hours", ACCESS::modify, 'integer'),
  );
}

?>
