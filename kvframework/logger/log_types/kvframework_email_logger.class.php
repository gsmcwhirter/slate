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
 * Logs to email
 *
 * @package KvFramework
 * @subpackage Logger
 */
class kvframework_email_logger extends kvframework_logger
{
    /**
     * Email to
     *
     * @var string
     */
    protected $recipient;
    /**
     * Email from
     *
     * @var string
     */
    protected $sender;
    /**
     * Email subject
     *
     * @var string
     */
    protected $subject;
    /**
     * Email content
     *
     * @var string
     */
    protected $mailbody;

    /**
     * Constructor
     *
     * @param mixed $parent_logger_instance
     * @param string $parent_application
     */
    public function __construct(&$parent_logger_instance, $parent_application)
    {
        parent::__construct ($parent_logger_instance, $parent_application);
        $this->email_set_recipient ( kvframework_log::DEFAULT_EMAIL_RECIPIENT );
        $this->email_set_sender ( kvframework_log::DEFAULT_EMAIL_SENDER );
        $this->email_set_subject ( DEFAULT_EMAIL_SUBJECT );
        $this->type = "email";
    }

    /**
     * Set recipient
     *
     * @param string $recipient
     */
    public function email_set_recipient ( $recipient )
    {
        $this->recipient = $recipient;
        $this->needs_reset = TRUE;
    }

    /**
     * Set sender
     *
     * @param string $sender
     */
    public function email_set_sender ( $sender )
    {
        $this->sender = $sender;
        $this->needs_reset = TRUE;
    }

    /**
     * Set subject
     *
     * @param string $subject
     */
    public function email_set_subject ( $subject )
    {
        $this->subject = $subject;
        $this->needs_reset = TRUE;
    }

    /**
     * Doesnt do anything
     *
     */
    public function type_specific_reset()
    {
    }

    /**
     * Initializes the instance
     *
     * @return boolean true
     */
    public function type_specific_open()
    {
        unset ($this->mailbody);
        return ( TRUE );
    }

    /**
     * Writes a message to the email body
     *
     * @param array $entry
     */
    public function type_specific_output_msg(array $entry)
    {
        $severity = $entry["MSG_SEVERITY"];
        $this->mailbody .=  str_pad($entry["MSG_TIME"], 36).str_pad($entry["MSG_CATEGORY"], 13).str_pad($this->syslog_levels[$severity], 13).$entry["MSG_MSG"]."\n";
    }

    /**
     * Send the mail and clean up
     *
     * @return boolean
     */
    public function type_specific_close()
    {
        if(!mail($this->recipient, $this->subject, $this->mailbody, $this->sender, "-fjenelso2@wsc.edu"))
        {
            $msg = "EMAILING FAILED: to ".$this->recipient.", from ".$this->sender.", subject ".$this->subject.", body ".$this->mailbody;
            $this->write_logger_log($msg);
            return ( FALSE );
        }
        return ( TRUE );
    }

}
?>
