<?php
/**
 * Main framework configuration file.  Loads framework files and application configurations.
 * @package KvFramework
 * @author Greg McWhirter <gsmcwhirter@gmail.com>
 * @copyright Copyright &copy; 2006, Greg McWhirter
 * @license MIT License
 * @version 1.0
 */

/**
 * Compatibility Hack
 */
define("KVF_COMPAT", true);

ob_start("ob_gzhandler");
session_start();

/**
 * Holds the backlogs for files loaded before the logger starts
 * @global array $BACKLOGS
 */
$BACKLOGS = array ();
/**
 * Holds the database connection info.
 * @global array $DBINFO
 */
$DBINFO = array ();
/**
 * Holds the currently executing site_class instance (initially null)
 * @global application_site_class $SITE_CLASS
 */
$SITE_CLASS = null;
/**
 * Holds the user-defined logger information.
 * @global kvframework_loginfo_struct $LOGINFO
 */
 $LOGINFO = null;

// +----------------------------------------------------------------------
// |Settings and configuration
// +----------------------------------------------------------------------

/**
 * Application filesystem root directory constant
 */
define("ROOTDIR", dirname(__FILE__) . "/..");
 /**
  * Framwork filesystem directory constant
  */
define("KVF_FRAMEWORK_DIR", ROOTDIR."/kvframework"); //no trailing slash
/**
 * Application includes filesystem directory constant
 */
define("KVF_INCLUDES", ROOTDIR."/includes"); //no trailing slash
/**
 * Application modules filesystem directory constant
 */
define("KVF_MODULES", KVF_INCLUDES."/modules");
/**
 * Application layouts filesystem directory constant
 */
define("KVF_LAYOUTS_DIR", KVF_INCLUDES."/layouts");

define_syslog_variables();
/**#@+
 * Logger level constant
 */
define ("KVF_LOG_LEMERG", LOG_EMERG);
define ("KVF_LOG_LALERT", LOG_ALERT);
define ("KVF_LOG_LCRITICAL", LOG_CRIT);
define ("KVF_LOG_LERROR", LOG_ERR);
define ("KVF_LOG_LWARNING", LOG_WARNING);
define ("KVF_LOG_LNOTICE", LOG_NOTICE);
define ("KVF_LOG_LINFO", LOG_INFO);
define ("KVF_LOG_LDEBUG", LOG_DEBUG);
/**#@-*/

/**
 * A wrapper for including files and logging such. Uses func_get_args() for variable argument number. Uses $BACKLOGS to store logging until the logger is started.
 * @global array Back Log Records
 * @global kvframework_loginfo_struct Log config info
 * @global array Database config info
 * @return boolean true
 *
 */
function load_files_backlog() {
  global $BACKLOGS, $LOGINFO, $DBINFO;
  $all_okay = true;
  foreach(func_get_args() as $filename){
    if(!(include_once $filename)){
      $all_okay = false;
      throw new Exception("Failed loading file: ". $filename);
    }
    $BACKLOGS[] = array ("Loaded file: " . $filename, KVF_LOG_LDEBUG);
  }

  return true;
}

load_files_backlog(
  KVF_FRAMEWORK_DIR . "/ext/struct.class.php",
  KVF_FRAMEWORK_DIR . "/logger/kvframework_log.class.php"
);

$LOGINFO = new kvframework_loginfo_struct();

/**
 * A wrapper for including files and logging such. Uses func_get_args() for variable argument number.
 * @global array Back Log Records
 * @global kvframework_loginfo_struct Log config info
 * @global array Database config info
 * @return boolean true
 *
 */
function load_files() {
  global $BACKLOGS, $LOGINFO, $DBINFO;
  $all_okay = true;
  foreach(func_get_args() as $filename){
    if(!(include_once $filename)){
      $all_okay = false;
      kvframework_log :: write_log("Failed loading file: ". $filename, KVF_LOG_LERROR);
    }
    kvframework_log :: write_log("Loaded file: " . $filename, KVF_LOG_LDEBUG);
  }

  if($all_okay){
    return true;
  } else {
    throw new Exception("Error loading files.  Please check the application log file.");
  }
}

/**
 * PHP Magic autoload function for classes.  Set to only work on site_class files.
 * @param string $class Name of the site_class to be loaded.
 * @return boolean Return the result of the load_files call or false if the requested class was not a site_class
 */
function __autoload($class){
  $class = strtolower($class);
  if(substr($class,-10) == "site_class"){
    return load_files(KVF_MODULES."/".substr($class,0,-11).".module/".substr($class,0,-11).".site_class.php");
  } else {
    return false;
  }
}

load_files_backlog(KVF_INCLUDES . "/configs/log.config.php");
kvframework_log :: start_logger($LOGINFO, $BACKLOGS);

load_files(
  KVF_FRAMEWORK_DIR.  "/kvframework_base.class.php",
  KVF_FRAMEWORK_DIR . "/lib/nakor_core.class.php",
  KVF_FRAMEWORK_DIR . "/sitehandler/kvframework_site.class.php",
  KVF_FRAMEWORK_DIR . "/mailhandler/kvframework_mailer.class.php",
  KVF_FRAMEWORK_DIR . "/dbhandler/kvframework_dbtype.class.php",
  KVF_FRAMEWORK_DIR . "/dbhandler/dbtypes/kvframework_dbtype_mysql.class.php",
  KVF_FRAMEWORK_DIR . "/kvf_cache/kvf_cache.class.php",

  KVF_INCLUDES . "/configs/app.config.php",
  KVF_INCLUDES . "/configs/db.config.php"
);

/**
 * Holds the database connection globally.
 * @global kvframework_dbtype $db
 */
$db = new kvframework_dbtype_mysql($DBINFO[CONFIG::DB_MODE]);
$temp = CONFIG::RENDER_ENGINE;
$engine = new $temp();
kvframework_base :: startup_base($db);
kvframework_site :: startup($engine);
?>
