<?php
/**
 * Logging interface for the KvFramework.
 * @package KvFramework
 * @subpackage Logger
 * @author Greg McWhirter <gsmcwhirter@gmail.com>
 * @copyright Copyright &copy; 2006, Greg McWhirter
 * @license MIT License
 * @version 1.0
 */

load_files_backlog(
  KVF_FRAMEWORK_DIR."/logger/log_types/kvframework_logger.class.php",
  KVF_FRAMEWORK_DIR."/logger/log_types/kvframework_console_logger.class.php",
  KVF_FRAMEWORK_DIR."/logger/log_types/kvframework_email_logger.class.php",
  KVF_FRAMEWORK_DIR."/logger/log_types/kvframework_local_logger.class.php",
  KVF_FRAMEWORK_DIR."/logger/log_types/kvframework_syslog_logger.class.php",
  KVF_FRAMEWORK_DIR."/logger/kvframework_log_session.class.php",
  KVF_FRAMEWORK_DIR."/logger/kvframework_loginfo_struct.class.php"
);

/**
 * Log output path
 *
 */
define ("DEFAULT_LOCAL_LOGFILE_PATH", ROOTDIR."/log/");
/**
 * Email log subject template
 *
 */
define ("DEFAULT_EMAIL_SUBJECT", "LOG MESSAGE: ".date("r")); // The calling application is appended to the end of this.

/**
 * Logger interface
 *
 * @package KvFramework
 * @subpackage Logger
 */
abstract class kvframework_log{
  /**
   * System log level-ish thing
   *
   */
  const DEFAULT_LOGGER_LOG_FACILITY = LOG_LOCAL6;
  /**
   * Default log severity
   *
   */
  const DEFAULT_MSG_SEVERITY = LOG_INFO;
  /**
   * Default category
   *
   */
  const DEFAULT_MSG_CATEGORY = "OTHER";
  /**
   * Default threshold
   *
   */
  const DEFAULT_THRESH = LOG_WARNING;
  /**
   * Default queue size
   *
   */
  const DEFAULT_MAX_QUEUE_SIZE = 250;
  /**
   * Default trigger threshold
   *
   */
  const DEFAULT_ERROR_CONDITION_TRIGGER_THRESH = LOG_ERR;
  /**
   * Default error result threshold
   *
   */
  const DEFAULT_ERROR_CONDITION_THRESH = LOG_DEBUG;
  /**
   * Queue messages?
   *
   */
  const DEFAULT_QUEUE_MODE = false;
  /**
   * Default log name
   *
   */
  const DEFAULT_LOCAL_LOGFILE_NAME = "crash.log";
  /**
   * Default logging method
   *
   */
  const DEFAULT_LOCAL_METHOD = "ARCHIVE";
  /**
   * Default mail recipient
   *
   */
  const DEFAULT_EMAIL_RECIPIENT = "You <you@mydomain.com>";
  /**
   * Default mail sender
   *
   */
  const DEFAULT_EMAIL_SENDER = " Logger <admin@mydomain.com>";
  /**
   * Default syslog level
   */
  const DEFAULT_SYSLOG_FACILITY = LOG_LOCAL6;

  /**
   * List of message categories
   *
   */
  const MESSAGE_CATEGORIES_LIST = "LDAP,DATA,SIS,LOGIC,FTP,LOGS,SQL,OTHER,INTERNAL,MAIL";
  /**
   * List of log levels
   *
   */
  const SYSLOG_LEVELS_LIST = "KVF_LOG_LEMERG,KVF_LOG_LALERT,KVF_LOG_LCRITICAL,KVF_LOG_LERROR,KVF_LOG_LWARNING,KVF_LOG_LNOTICE,KVF_LOG_LINFO,KVF_LOG_LDEBUG";
  /**
   * List of log modes
   *
   */
  const LOG_MODES_LIST = "local,email,syslog,console";

  /**
   * The logger actual instance
   *
   * @var mixed
   */
  protected static $LOGSESSION;
  /**
   * The state of the logger
   *
   * @var boolean
   */
  private static $LCONNECTED = false;
  /**
   * The logs themselves
   *
   * @var array
   */
  public static $LOGS = array();
  /**
   * The default log
   *
   * @var mixed
   */
  protected static $LOGDEFAULT = null;

  /**
   * Initialize the logger
   *
   * @param kvframework_loginfo_struct $loginfo
   * @param array $backlogs
   */
  final public static function start_logger(kvframework_loginfo_struct $loginfo, array $backlogs){
    if(!self::$LCONNECTED){
      self::$LOGSESSION = new kvframework_log_session("KvFramework");
      foreach($loginfo->types as $name => $type){
        self::$LOGS[$name] = self::$LOGSESSION->enable_log_instance($type);
      }
      foreach($loginfo->configs as $key => $params){
        $inst = "";
        foreach($params as $dir => $par){
          if(substr($key,0,2) == "n:"){
            $temp = substr($key, 2);
            $inst = self::$LOGS[$temp];
          } elseif(substr($key,0,2) == "t:"){
            $inst = substr($key,2);
          }

          if(is_null($par)){
            self::$LOGSESSION->configure_instance($inst, $dir);
          } else {
            self::$LOGSESSION->configure_instance($inst, $dir, $par);
          }

        }
      }
      self::$LOGDEFAULT = $loginfo->default;
      self::$LCONNECTED = true;

      foreach($backlogs as $log){
        call_user_func_array(array("self", "write_log"), $log);
      }
    }
  }

  /**
   * Write a log message
   *
   * @param string $message
   * @param integer $level
   * @param array $logs
   * @param string $cat
   * @param array $forces
   */
  final public static function write_log($message, $level = KVF_LOG_LINFO, array $logs = array(), $cat = null, array $forces = array()){
    $pars = array();
    if(count($logs) == 0){
      $pars[0] = self::$LOGS[self::$LOGDEFAULT];
      foreach($forces as $f){
        $pars[] = $f;
      }
      if(!is_null($cat)){$pars[] = $cat;}
      $pars[] = $level;
      $pars[] = $message;

      //print_r($pars);
      call_user_func_array(array(self::$LOGSESSION, "log_entry"), $pars);
    } else {
      foreach($logs as $log){
        $pars[0] = self::$LOGS[$log];
        foreach($forces as $f){
          $pars[] = $f;
        }
        if(!is_null($cat)){$pars[] = $cat;}
        $pars[] = $level;
        $pars[] = $message;
        call_user_func_array(array(self::$LOGSESSION, "log_entry"), $pars);
      }
    }
  }

  /**
   * Close all the logs and stuff
   *
   * @return boolean
   */
  final public static function close_logs(){
    self::$LOGSESSION->close_log();
    return true;
  }

}

?>
