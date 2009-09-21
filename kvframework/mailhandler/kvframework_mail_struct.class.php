<?php
/**
 * Struct holding the information for an e-mail
 * @package KvFramework
 * @subpackage MailHandler
 * @author Greg McWhirter <gsmcwhirter@gmail.com>
 * @copyright Copyright &copy; 2006, Greg McWhirter
 * @license MIT License
 * @version 1.0
 */

/**
 * Struct holding the information for an e-mail
 * @package KvFramework
 * @subpackage MailHandler
 */
class kvframework_mail_struct extends struct{
  /**
   * @var array The allowed data fields in the struct
   */
  protected $FIELDS = array(
    "recipients" => array(),
    "content" => "",
    "from" => "",
    "from_name" => "",
    "subject" => "",
	"text_message" => false
  );
}
?>
