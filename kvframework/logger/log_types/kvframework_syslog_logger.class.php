<?php
/**
 * Logging type for the KvFramework.
 * @package KvFramework
 * @subpackage Logger
 * @author Greg McWhirter <gsmcwhirter@gmail.com>
 * @copyright Copyright &copy; 2006, Greg McWhirter
 * @license MIT License
 * @version 1.0
 */

/**
 * Logs to the system log
 *
 * @package KvFramework
 * @subpackage Logger
 */
class kvframework_syslog_logger extends kvframework_logger
{
    /**
     * No idea....
     *
     * @var mixed
     */
    protected $facility;

    /**
     * Constructor
     *
     * @param mixed $parent_logger_instance
     * @param string $parent_application
     */
    public function __construct(&$parent_logger_instance, $parent_application)
    {
        parent::__construct ($parent_logger_instance, $parent_application);
        $this->syslog_set_facility ( kvframework_log::DEFAULT_SYSLOG_FACILITY );
        $this->queue_mode = FALSE;
        $this->type = "syslog";
    }

    /**
     * Turn on queuing
     *
     * @return boolean false
     */
    public function enable_queue_mode()
    { // Queue mode is not valid for this queue type
        $msg = sprintf ("Attempt to enable queueing mode for log instance of type syslog\n");
        $this->write_logger_log($msg);
        return (FALSE);
    }

    /**
     * Set the facility to which to log
     *
     * @param mix $facility
     */
    public function syslog_set_facility( $facility )
    {
        $this->facility = $facility;
        $this->needs_reset = TRUE;
    }

    /**
     * Does nothing
     *
     */
    public function type_specific_reset()
    {
    }

    /**
     * Open the log
     *
     * @return boolean
     */
    public function type_specific_open()
    {
        openlog ($this->parent_application, LOG_ODELAY | LOG_PID | LOG_CONS, $this->facility );
        return ( TRUE );
    }

    /**
     * Write a message to the log
     *
     * @param array $entry
     */
    public function type_specific_output_msg(array $entry)
    {
        // We have to pull this out because php doesn't like to use an array as an index to another array
        $severity = $entry["MSG_SEVERITY"];
        syslog ($severity,  str_pad($entry["MSG_CATEGORY"], 13).str_pad($this->syslog_levels[$severity], 13).$entry["MSG_MSG"]);
    }

    /**
     * Close the log
     *
     * @return boolean
     */
    public function type_specific_close()
    {
        return ( closelog() );
    }

}
?>
