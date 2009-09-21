<?php
/**
 * Logging session for the KvFramework.
 * @package KvFramework
 * @subpackage Logger
 * @author Greg McWhirter <gsmcwhirter@gmail.com>
 * @copyright Copyright &copy; 2006, Greg McWhirter
 * @license MIT License
 * @version 1.0
 */

/**
 * Logging session
 *
 * @package KvFramework
 * @subpackage Logger
 */
class kvframework_log_session
{
    /**
     * Name of the parent application
     *
     * @var string
     */
    protected $parent_application;
    /**
     * Categories of messages
     *
     * @var array
     */
    protected $message_categories;
    /**
     * Logger levels
     *
     * @var array
     */
    public $syslog_levels;

    /**
     * Has the console (output to webpage) been started
     *
     * @var boolean
     */
    protected $console_opened;
    /**
     * Queue of console messages
     *
     * @var mixed
     */
    protected $console_msg_queue;
    /**
     * Console queue mode
     *
     * @var mixed
     */
    protected $console_queue_mode;

    /**
     * Has an email log been opened
     *
     * @var boolean
     */
    protected $email_opened;
    /**
     * Email log recipient
     *
     * @var mixed
     */
    protected $email_log_recipient;
    /**
     * Email log sender
     *
     * @var mixed
     */
    protected $email_log_sender;
    /**
     * Email log subject
     *
     * @var mixed
     */
    protected $email_log_subject;
    /**
     * Email message queue
     *
     * @var mixed
     */
    protected $email_msg_queue;
    /**
     * Email queue mode
     *
     * @var mixed
     */
    protected $email_queue_mode;

    /**
     * Constructr
     *
     * @param string $parent_app
     */
    public function __construct($parent_app)
    {
        $this->log_mode_array = explode(",", kvframework_log::LOG_MODES_LIST);
        $this->message_categories = explode(",", kvframework_log::MESSAGE_CATEGORIES_LIST);
        $this->syslog_levels = explode(",", kvframework_log::SYSLOG_LEVELS_LIST);
        $this->parent_application = $parent_app;
    }

    /**
     * Start a given log instance
     *
     * @param string $type
     * @return mixed
     */
    public function enable_log_instance($type)
    {
        switch ($type)
        {
            case "local":
                {
                    $this->log_instances[] = new kvframework_local_logger($this, $this->parent_application);
                    $instance_index = sizeof ($this->log_instances) - 1;
                    break;
                }
            case "syslog":
                {
                    $this->log_instances[] = new kvframework_syslog_logger($this, $this->parent_application);
                    $instance_index = sizeof ($this->log_instances) - 1;
                    break;
                }
            case "email":
                {
                    $this->log_instances[] = new kvframework_email_logger($this, $this->parent_application);
                    $instance_index = sizeof ($this->log_instances) - 1;
                    break;
                }
            case "console":
                {
                    $this->log_instances[] = new kvframework_console_logger($this, $this->parent_application);
                    $instance_index = sizeof ($this->log_instances) - 1;
                    break;
                }
            default:
                {
                    $this->write_logger_log("php-logger->enable_log_instance called with invalid instance type ".$type."\n");
                    return (FALSE);
                }
        }
        return ($instance_index);
    }

    /**
     * Disable a logger instance
     *
     * @param mixed $instance
     * @return boolean
     */
    public function disable_log_instance($instance)
    {
        if ( is_num ($instance) )
        {
            if ( $instance < sizeof ($this->log_instances) )
            {
                $this->log_instances[$instance]->close();
            }
            else
            {
                $msg = sprintf ("php-logger->disable_log_instance called with non instantiated instance %s", $instance);
                $this->write_logger_log ($msg);
                return (FALSE);
            }
        }
        else
        {
            $msg = sprintf ("php-logger->disable_log_instance called with non integer parameter %s requires instance number", $instance);
            $this->write_logger_log ($msg);
            return (FALSE);
        }
    }

    /**
     * Write a log entry
     *
     */
    public function log_entry()
    {
        $numargs = func_num_args();

        $severity = NULL; //Just to ensure that we don't have bleedover between calls
        $category = FALSE;
        $good_args = TRUE;

        $override = array();

        switch ($numargs)
        {
            case 0:
            case 1:     $log_error_msg = "Function: log_entry called with 0 or 1 arguement, that's not good";
                        $this->write_logger_log($log_error_msg);
                        $good_args = FALSE;
                        break;

            case 2:     $inst = func_get_arg(0);
                        $msg = func_get_arg(1);
                        break;

            default:    $args = func_get_args();
                        $inst = $args[0];
                        for ($i = 1; $i < sizeof($args)-1; $i++)
                        {
                            $good_arg = FALSE;
                            $arg = $args[$i];
                            if (ereg("[\+-].*", $arg)) // This conditional has to come before the severity test since "+/-[0-9]" return true for is_numeric
                            {
                                $action = substr($arg, 0, 1);
                                $requested_instance = substr($arg, 1);
                                for ($x = 0; $x < sizeof ($this->log_instances); $x++)
                                {
                                    $instance = &$this->log_instances[$x];
                                    // OK - this is stupid - but it's because PHP returns true on is_numeric ( string of ints) but does not return true on string of ints===ints
                                    if ( ( ( is_numeric ( $requested_instance ) )& ($requested_instance == $x ) ) | ( $requested_instance === $instance->get_instance_type() ) )
                                    {
                                        $override[$x] = "$action";
                                        $good_arg = TRUE;
                                    }
                                }
                                if (!$good_arg)
                                {
                                    $msg = sprintf ("instance override requested for non enabled instance %s with action %s\n", $requested_instance, $action);
                                    $this->write_logger_log($msg);
                                }
                            }
                            elseif ((is_numeric($arg))&&($arg < 8)) // numeric values < 8 are always interpreted as syslog sev levels
                            {
                                if ($severity)
                                {
                                    $log_error_msg = "Severity was already set - cannot reset!";
                                    $this->write_logger_log($log_error_msg);
                                    $good_arg = FALSE;
                                }
                                else
                                {
                                    $severity = $arg;
                                    $good_arg = TRUE;
                                }
                            }
                            else
                            {
                                foreach ($this->message_categories as $message_category)
                                {
                                    if ($arg == $message_category)
                                    {
                                        $category = $arg;
                                        $good_arg = TRUE;
                                    }
                                }
                            }
                            if (!$good_arg)
                            {
                                $good_args = FALSE;
                            }
                        }
                        $msg = $args[sizeof($args)-1];
                        break;
        }
        if (!$good_args || !$this->check_inst($inst))
        {
            $log_error_msg = "";
            $log_error_msg .= str_pad(date("r"), 36)." LOGGER CALLED WITH BAD ARGUMENTS from application ".$this->parent_application."\n";
            for ($i = 0; $i < sizeof($args); $i++)
            {
                $log_error_msg .= "\tArguement #".$i." => ".$args[$i]."\n";
            }
            $this->write_logger_log($log_error_msg);
        } else {

          if (!$category)
          {
              $category = "OTHER";
          }

          if ( $severity === NULL )
          {
              $severity = kvframework_log::DEFAULT_MSG_SEVERITY;
          }

          $orinst = false;

          foreach($override as $i => $val){
            $force = FALSE;
            if($i == $inst){
              $orinst = true;
            }
            if($val == "+"){
              $force = TRUE;
            }
            if(!($val == "-")){
              $this->log_instances[$i]->log_entry ($force, $category, $severity, $msg);
            }
          }

          if(!$orinst && (!array_key_exists($inst, $override) || $override[$inst] != "-")){
            $force = FALSE;
            $this->log_instances[$inst]->log_entry ($force, $category, $severity, $msg);
          }
        }
    }

    /**
     * Configure a logging instance
     *
     * @return boolean
     */
    public function configure_instance ()
    {
        $found_valid_instance = FALSE;

        $args = func_get_args();
        if ( sizeof( $args ) < 2 )
        {
            $msg = sprintf ("Configure instance requires at least 2 arguments - received %s", kvframework_base::array_to_string($args));
            return (FALSE);
        }

        $requested_instance = array_shift ($args);
        $function = array_shift ($args);
        $params = array_shift ($args);
        while ($param_temp = array_shift ($args) )
        {
            $params .= ", ".$param_temp;
        }

        for ($i = 0; $i < sizeof ($this->log_instances); $i++)
        {
            $instance = &$this->log_instances[$i];
            if ( ( $requested_instance === $i ) | ( $requested_instance === $instance->get_instance_type() ) )
            { // OK - either the requested instance was one of our types or it was an integer for a particular instance
                if ( method_exists ($instance, $function) )
                {
                    $instance->$function($params);
                    $found_valid_instance = TRUE;
                }
                else
                {
                    $msg = sprintf ("Attempt to use a non existant configuration %s public function for logger instance %s\n", $function, $requested_instance);
                    $this->write_logger_log($msg);
                    return (FALSE);
                }
            }
        }
        if ( !$found_valid_instance )
        {
            $msg = sprintf ("Attempt to modify a non existant logger instance %s\n", $requested_instance);
            $this->write_logger_log($msg);
            return (FALSE);
        }
        else
        {
            return (TRUE);
        }
    }

    /**
     * Write a message from the logger to the syslog
     *
     * @param string $msg
     */
    public function write_logger_log($msg)
    {
        echo ('LOGGER'.KVF_LOG_LCRITICAL.$msg);
        openlog ("LOGGER", LOG_ODELAY | LOG_PID | LOG_CONS, kvframework_log::DEFAULT_LOGGER_LOG_FACILITY );
        syslog (KVF_LOG_LCRITICAL, str_pad("LOGGER", 13).str_pad("CRITICAL", 13).$msg);
    }

    /**
     * Close all open logs
     *
     */
    public function close_log()
    {
        foreach($this->log_instances as $instance)
        {
            $instance->close();
        }
    }

    /**
     * Is the index a log instance?
     *
     * @param integer $i
     * @return boolean
     */
    protected function check_inst($i){
      if(!array_key_exists($i, $this->log_instances)){
        return false;
      } else {
        return true;
      }
    }
}
?>
