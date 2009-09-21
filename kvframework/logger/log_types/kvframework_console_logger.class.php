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
 * Console (Web page) logger
 *
 * @package KvFramework
 * @subpackage Logger
 */
class kvframework_console_logger extends kvframework_logger
{
    /**
     * Line break character
     *
     * @var string
     */
    protected $break_character;

    /**
     * Constructor - give the instance references to its parent etc
     *
     * @param mixed $parent_logger_instance
     * @param string $parent_application
     */
    public function __construct(&$parent_logger_instance, $parent_application)
    {
        parent::__construct($parent_logger_instance, $parent_application);
        $this->break_character = "";
        $this->queue_mode = FALSE;
        $this->type = "console";
    }

    /**
     * Turn on queueing
     *
     * @return boolean false
     */
    public function enable_queue_mode()
    { // Queue mode is not valid for this queue type
        $msg = sprintf ("Attempt to enable queueing mode for log instance of type console\n");
        $this->write_logger_log($msg);
        return (FALSE);
    }

    /**
     * Set that the console is in fact the web
     *
     */
    public function console_set_web ( )
    {
        $this->break_character = "<br>";
    }

    /**
     * Does nothing
     *
     */
    public function type_specific_reset()
    {
    }

    /**
     * Does nothing
     *
     * @return boolean true
     */
    public function type_specific_open()
    {
        return ( TRUE );
    }

    /**
     * Actually output the message
     *
     * @param array $entry
     */
    public function type_specific_output_msg(array $entry)
    {
        // We have to pull this out because php doesn't like to use an array as an index to another array
        //Actually, it will, but might not have when this was written, so I won't change it b/c it works
        $severity = $entry["MSG_SEVERITY"];
        echo str_pad($entry["MSG_TIME"], 36).str_pad($entry["MSG_CATEGORY"], 13).str_pad($this->syslog_levels[$severity], 13).$entry["MSG_MSG"].$this->break_character."\n";
    }

    /**
     * Does nothing
     *
     */
    public function type_specific_close()
    {
    }

}
?>
