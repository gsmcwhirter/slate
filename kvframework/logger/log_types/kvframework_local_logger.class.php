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
 * Local file logger
 *
 * @package KvFramework
 * @subpackage Logger
 */
class kvframework_local_logger extends kvframework_logger
{
    /**
     * Path to the log
     *
     * @var string
     */
    protected $logfile_path;
    /**
     * Name of the log
     *
     * @var string
     */
    protected $logfile_name;
    /**
     * The actual file (path.filename)
     *
     * @var mixed
     */
    protected $logfile;
    /**
     * Logging mode
     *
     * @var mixed
     */
    protected $mode;
    /**
     * A File pointer
     *
     * @var mixed
     */
    protected $file_pointer;

    /**
     * Constructor
     *
     * @param mixed $parent_logger_instance
     * @param mixed $parent_application
     */
    public function __construct(&$parent_logger_instance, $parent_application)
    {
        parent::__construct ($parent_logger_instance, $parent_application);
        $this->local_set_logfile_path(DEFAULT_LOCAL_LOGFILE_PATH);
        $this->local_set_logfile_name(kvframework_log::DEFAULT_LOCAL_LOGFILE_NAME);
        $this->file_pointer = FALSE;
        $this->type = "local";
    }

    /**
     * Set the log mode
     *
     * @param mixed $mode
     */
    public function local_set_logfile_mode ($mode)
    {
        $this->mode = $mode;
    }

    /**
     * Set the log file name
     *
     * @param string $logfile_name
     */
    public function local_set_logfile_name($logfile_name)
    {
        $this->filename = $logfile_name;
        $this->needs_reset = TRUE;
    }

    /**
     * Set the log path
     *
     * @param string $logfile_path
     */
    public function local_set_logfile_path($logfile_path)
    {
        if ( substr ($logfile_path, strlen($logfile_path) - 1) !== "/" )
        {
            $logfile_path = $logfile_path."/";
        }
        $this->path = $logfile_path;
        $this->needs_reset = TRUE;
    }

    /**
     * Reset stuff
     *
     */
    public function type_specific_reset()
    {
        $this->logfile = $this->path.$this->filename;
    }

    /**
     * Open the logging instance
     *
     * @return boolean
     */
    public function type_specific_open()
    {
        if (file_exists($this->logfile))
        {
            if ($this->mode == "ARCHIVE")
            {
                rename($this->logfile, $this->logfile.date("Ymd-Gis"));
            }
        }
        if ($this->file_pointer = fopen ($this->logfile, "a+"))
        {
            $entry["MSG_TIME"] = date("r");
            $entry["MSG_CATEGORY"] = "LOGS";
            $entry["MSG_SEVERITY"] = KVF_LOG_LINFO;
            $entry["MSG_MSG"] = "LOG OPENED, REQUESTED BY APPLICATION ".$this->parent_application;
            $this->type_specific_output_msg($entry);
        }
        else
        {
            $log_error_msg = "Unable to open log file ".$this->filename;
            parent::write_logger_log($log_error_msg);
            return (FALSE);
        }
        return (TRUE);
    }

    /**
     * Write a message to the log
     */
    public function type_specific_output_msg($entry)
    {
        // We have to pull this out because php doesn't like to use an array as an index to another array
        $severity = $entry["MSG_SEVERITY"];
        fputs ($this->file_pointer, str_pad($entry["MSG_TIME"], 36).str_pad($entry["MSG_CATEGORY"], 13).str_pad($this->syslog_levels[$severity], 13).$entry["MSG_MSG"]."\n");
    }

    /**
     * Close the log file
     *
     * @return boolean
     */
    public function type_specific_close()
    {
        return ( fclose ($this->file_pointer) );
    }

}
?>
