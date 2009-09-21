<?php
/**
 * Logging base type for the KvFramework.
 * @package KvFramework
 * @subpackage Logger
 * @author Greg McWhirter <gsmcwhirter@gmail.com>
 * @copyright Copyright &copy; 2006, Greg McWhirter
 * @license MIT License
 * @version 1.0
 */

/*******************************************************
* File has been heavily modified from the original.    *
* Immediately below is the original heading of the file*
/*******************************************************
*  File: log_session_class.inc                         *
*  Authors: Jeremy Nelson and John Dunning             *
*  Date: April 22, 2003                                *
*******************************************************/

/**
 * Base log type
 *
 * @package KvFramework
 * @subpackage Logger
 */
class kvframework_logger{

    /**
     * Messages
     *
     * @var mixed
     */
    protected $messages;
    /**
     * Whether or not to reset stuff
     *
     * @var boolean
     */
    protected $needs_reset;
    /**
     * Instance of parent logger
     *
     * @var mixed
     */
    protected $parent_logger_instance;
    /**
     * Whether or not the instance is open for logging
     *
     * @var boolean
     */
    protected $instance_is_open;
    /**
     * Queue mode
     *
     * @var mixed
     */
    protected $queue_mode;
    /**
     * Log type
     *
     * @var mixed
     */
    protected $type;
    /**
     * Threshold
     *
     * @var mixed
     */
    protected $thresh;

    /**
     * Constructor
     *
     * @param mixed $parent_logger_instance
     * @param string $parent_application
     */
    public function __construct(&$parent_logger_instance, $parent_application)
    {
        $this->parent_logger_instance = &$parent_logger_instance;
        $this->parent_application = $parent_application;
        $this->syslog_levels = &$parent_logger_instance->syslog_levels;
        $this->thresh = kvframework_log::DEFAULT_THRESH;
        $this->queue_mode = kvframework_log::DEFAULT_QUEUE_MODE;
        $this->error_condition_trigger_thresh = kvframework_log::DEFAULT_ERROR_CONDITION_TRIGGER_THRESH;
        $this->error_condition_thresh = kvframework_log::DEFAULT_ERROR_CONDITION_THRESH;
        $this->in_error_condition = FALSE;
        $this->max_queue_size = kvframework_log::DEFAULT_MAX_QUEUE_SIZE;
        $this->messages = array();
        $this->needs_reset = TRUE;
    }

    /**
     * Open the log instance
     *
     * @return boolean
     */
    public function open()
    {
        if ( $this->needs_reset )
        {
            $this->reset();
        }
        if ( $this->type_specific_open() )
        {
            $this->instance_is_open = TRUE;
            return (TRUE);
        }
        else
        {
            $msg = sprintf ("Unable to open log instance\n");
            $this->write_logger_log($msg);
            return (FALSE);
        }
    }

    /**
     * Get the type of the instance
     *
     * @return string
     */
    public function get_instance_type()
    {
        return ( $this->type );
    }

    /**
     * Set the queue size
     *
     * @param integer $size
     */
    public function set_max_queue_size($size)
    {
        $this->enable_queue_mode();
        $this->max_queue_size = $size;
    }

    /**
     * Set error condition threshold
     *
     * @param integer $thresh
     */
    public function set_error_condition_trigger_thresh ($thresh)
    {
        $this->enable_queue_mode();
        $this->error_condition_trigger_thresh = $thresh;
    }

    /**
     * Set If error condition thresh, then what to log
     *
     * @param integer $thresh
     */
    public function set_error_condition_thresh ($thresh)
    {
        $this->enable_queue_mode();
        $this->error_condition_thresh = $thresh;
    }

    /**
     * Set threshold for what to log
     *
     * @param integer $threshold
     */
    public function set_thresh($threshold)
    {
        $this->thresh = $threshold;
    }

    /**
     * Turn on queuing
     *
     */
    public function enable_queue_mode()
    {
        $this->queue_mode = TRUE;
    }

    /**
     * Turn off queuing
     *
     */
    public function disable_queue_mode()
    {
        $this->flush_queue();
        $this->queue_mode = FALSE;
    }

    /**
     * Reset things
     *
     */
    public function reset()
    {
        $this->close();
        $this->type_specific_reset();
        $this->needs_reset = FALSE;
    }

    /**
     * Add a message to the queue
     *
     * @param array $queue_entry
     */
    public function queue_msg(array $queue_entry)
    {
        $queue_entry["MSG_TIME"] = date("r");
        array_push ($this->messages, $queue_entry);
        if ( $queue_entry["MSG_SEVERITY"] <= $this->error_condition_trigger_thresh )
        {
            $this->in_error_condition = TRUE;
            $this->flush_queue();
            $this->in_error_condition = FALSE;
            return;
        }
        $queue_has_messages = TRUE;
        while ( ( ( sizeof ( $this->messages ) > $this->max_queue_size ) | ( !$this->queue_mode ) ) & ( $queue_has_messages ) )
        {
            $queue_has_messages = $this->flush_msg();
        }
        return;
    }

    /**
     * Flush a message from the log queue
     *
     * @return boolean
     */
    public function flush_msg()
    {
        $entry = array_shift ($this->messages);
        if ($entry === NULL)
        {
            return (FALSE);
        }
        if ( $entry["MSG_FORCE"] )
        { // Message was requested to be forced regardless of thresholds
            $this->type_specific_output_msg($entry);
            return (TRUE);
        }
        if ( $this->in_error_condition )
        {
            $threshold = $this->error_condition_thresh;
        }
        else
        {
            $threshold = $this->thresh;
        }
        if ( $entry["MSG_SEVERITY"] <= $threshold )
        {
            $this->type_specific_output_msg($entry);
            return (TRUE);
        }
        // Message did not meet any output criteria - but we did get a message off the queue so return true
        return (TRUE);
    }

    /**
     * Log a message
     *
     * @param boolean $force
     * @param string $category
     * @param integer $severity
     * @param string $msg
     */
    public function log_entry($force, $category, $severity, $msg)
    {
        if ( !$this->instance_is_open )
        {
            $this->open();
        }
        $entry = array (    "MSG_FORCE"       =>  $force,
                            "MSG_CATEGORY"    =>  $category,
                            "MSG_SEVERITY"    =>  $severity,
                            "MSG_MSG"         =>  $msg
                        );
        $this->queue_msg($entry);
    }

    /**
     * Flush the log queue
     *
     */
    public function flush_queue()
    {
        do
        {
            $result = $this->flush_msg();
        } while ($result);
    }

    /**
     * Close the logging instance
     *
     * @return boolean
     */
    public function close()
    {
        if ( $this->instance_is_open )
        {
            $this->flush_queue();
            $this->type_specific_close();
            $this->instance_is_open = FALSE;
            return (TRUE);
        }
        else
        {
            // Well - we were already closed so nevermind
            return (TRUE);
        }
    }

    /**
     * Write a message to the syslog
     *
     * @param string $msg
     */
    public function write_logger_log($msg)
    {
        $this->parent_logger_instance->write_logger_log($msg);
    }
}

?>
