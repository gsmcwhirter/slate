<?php
/**
 * E-Mailing utility
 * @package KvFramework
 * @subpackage MailHandler
 * @author Greg McWhirter <gsmcwhirter@gmail.com>
 * @copyright Copyright &copy; 2006, Greg McWhirter
 * @license MIT License
 * @version 1.0
 */

load_files(KVF_FRAMEWORK_DIR."/mailhandler/kvframework_mail_struct.class.php");

/**
 * E-Mailing utility
 * @package KvFramework
 * @subpackage MailHandler
 */
abstract class kvframework_mailer{

  /**
   * @var boolean Whether or not to actually send out email
   */
  public static $SEND_MAIL = true;

  /**
   * Factory to create a new mail struct
   * @return kvframework_mail_struct
   */
  public static function new_mail(){
    return new kvframework_mail_struct();
  }

  /**
   * Send (or just process and not send) an email
   * @param kvframework_mail_struct $mail The email to send
   * @return boolean The result of the mail() call
   */
  public static function send(kvframework_mail_struct $mail){

  if(!$mail->text_message){
    $headers = "";
    $headers .= 'Content-type: text/plain; charset=iso-8859-1; format=flowed'."\r\n";
    $headers .= "Content-Transfer-Encoding: 7bit\r\n";
    $headers .= "Content-Disposition: inline\r\n";
    $headers .= "Reply-To: ".$mail->from."\r\n";
      $headers .= "MIME-Version: 1.0\r\n";
      $headers .= "From: ".$mail->from_name." <".$mail->from.">\r\n";
  } else {
    $headers = null;
  }

  $content = $mail->content;

    if(self::$SEND_MAIL){
      return mail(implode(", ", $mail->recipients), $mail->subject, $content, $headers, $extras);
    } else { return true; }
  }
}
?>
