<?php
/**
 * KvScheduler - Location Model
 * @package KvScheduler
 * @subpackage Lib
 * @author Greg McWhirter <gsmcwhirter@gmail.com>
 * @version 1.0
 */

/**
 * Wrapper for email sending
 *
 * @package KvScheduler
 * @subpackage Lib
 */
abstract class Mailer extends kvframework_mailer{

  /**
   * Send an email with a new password
   *
   * @param mixed $user
   * @param string $newpass
   * @param string $fromaddr
   * @param mixed $type
   * @return boolean
   */
  public static function deliver_lost_password($user, $newpass, $fromaddr, $type){
      $m = self::new_mail();
      $m->recipients = array(self::email_for($user["username"], $type));
      $m->from_name = "Kv";
      $m->from = application_site_class::config_vals("email_from");
      $m->subject = "KvScheduler Lost Password";
      $m->content = "Your password for the KvScheduler has been reset. Your new password is listed below.\n\n";
      if(array_key_exists("acdaccount", $user) && $user["acdaccount"] == "yes"){
        $m->content .= "Username: ".$user["username"]."\n";
      }
      $m->content .= "Password: $newpass\n";

      return self::send($m);
  }

  /**
   * Send an email about a new appointment
   *
   * @param integer $appt_id
   * @param mixed $rc
   * @param string $fromaddr
   * @param boolean $appt_today
   * @param integer $appt_time
   * @return boolean
   */
  public static function deliver_new_appointment($appt_id, $rc, $fromaddr, $appt_today = false, $appt_time = null){
    $n = null;
    $m = self::new_mail();
    $m->recipients = array(self::email_for($rc->username));
    $m->from_name = "Kv";
    $m->from = application_site_class::config_vals("email_from");
    $m->subject = "KvAppointment Notification [NEW]";

    if($rc->pref_send_text == "yes" && !empty($rc->pref_text_address) && $appt_today){
      $n = clone $m;
      $n->text_message = true;
      $n->recipients = array($rc->pref_text_address);
      $n->content = "You have a new appointment today at ".TOOLS::time_to_s($appt_time, true);
    }

    $m->content = "You have a new appointment.  Please visit the following URL to confirm ";
    $m->content .= "receipt of this notification and view the details of the appointment.\n\n";
    $m->content .= CONFIG::baseurl.kvframework_router::url_for("user","receipt_form",array("a" => $appt_id, "r" => $rc->consultant_id, "v" => 0))."\n";

    return(self::send($m) && ((is_null($n)) ? true : self::send($n)));
  }

  /**
   * Send an email about a modified appointment
   *
   * @param integer $appt_id
   * @param mixed $rc
   * @param string $fromaddr
   * @param integer $version
   * @param boolean $appt_today
   * @param integer $appt_time
   * @return boolean
   */
  public static function deliver_modify_appointment($appt_id, $rc, $fromaddr, $version, $appt_today = false, $appt_time = null){
    $n = null;
    $m = self::new_mail();
    $m->recipients = array(self::email_for($rc->username));
    $m->from_name = "Kv";
    $m->from = application_site_class::config_vals("email_from");
    $m->subject = "KvAppointment Notification [UPDATE]";

    if($rc->pref_send_text == "yes" && !empty($rc->pref_text_address) && $appt_today){
      $n = clone $m;
      $n->text_message = true;
      $n->recipients = array($rc->pref_text_address);
      $n->content = "You have a modified appointment today at ".TOOLS::time_to_s($appt_time, true);
    }

    $m->content = "One of your appointments was modified.  Please visit the following URL to confirm ";
    $m->content .= "receipt of this notification and view the details of the appointment.\n\n";
    $m->content .= CONFIG::baseurl.kvframework_router::url_for("user","receipt_form",array("a" => $appt_id, "r" => $rc->consultant_id, "v" => $version))."\n";

    return(self::send($m) && ((is_null($n)) ? true : self::send($n)));
  }

  /**
   * Send an email about a cancelled appointment
   *
   * @param mixed $appt
   * @param mixed $rc
   * @param string $fromaddr
   * @param mixed $rrmvl
   * @return boolean
   */
  public static function deliver_delete_appointment($appt, $rc, $fromaddr, $rrmvl = false){
    $n = null;
    $m = self::new_mail();
    $m->recipients = array(self::email_for($rc->username));
    $m->from_name = "Kv";
    $m->from = application_site_class::config_vals("email_from");
    $m->subject = "KvAppointment Notification [CANCELED]";

    $ast = TOOLS::string_to_time($appt->starttime);

    if($rc->pref_send_text == "yes" && !empty($rc->pref_text_address) && (($rrmvl && MyFunctions::datetime_in_appt(TOOLS::date_today(), $ast, $rrmvl)) || (!$rrmvl && MyFunctions::datetime_in_appt(TOOLS::date_today(), $ast, $appt)))){
      $n = clone $m;
      $n->text_message = true;
      $n->recipients = array($rc->pref_text_address);
      $n->content = "Your appointment today at ".TOOLS::time_to_s($ast, true)." was cancelled.";
    }

    $m->content .= "The following appointment was cancelled.  This will be reflected in your online calendar.\n\n";

    if($rrmvl && $rrmvl->special == "repeat_removal"){
      $m->content .= "### This instance of a repeating appointment has been removed ###\n\n";
      $m->content .= "Dates: Every ".(($appt->repetition_week == 1) ? "week" : $appt->repetition_week." weeks")." on ".implode(", ", TOOLS::array_collect(explode(",",$appt->repetition_day), '$i', 'TOOLS::$dayabbrs[TOOLS::weekday_reverse($i)]'))." from ".$appt->startdate." until ".$appt->stopdate."\n";
      $m->content .= "Removed On: ".$rrmvl->startdate."\n";
    } else {
      $m->content .= "### This appointment has been completely removed ###\n\n";
      if($appt->repeat == "TRUE"){
        $m->content .= "Dates: Every ".(($appt->repetition_week == 1) ? "week" : $appt->repetition_week." weeks")." on ".implode(", ", TOOLS::array_collect(explode(",",$appt->repetition_day), '$i', 'TOOLS::$dayabbrs[TOOLS::weekday_reverse($i)]'))." from ".$appt->startdate." until ".$appt->stopdate."\n";
      } else {
        $m->content .= "Date: ". $appt->startdate ."\n";
      }
    }
    $m->content .= "Start: ".TOOLS::time_to_s(TOOLS::string_to_time($appt->starttime, true))."\n";
    $m->content .= "Stop: ".TOOLS::time_to_s(TOOLS::string_to_time($appt->stoptime, true))."\n";
    $m->content .= "Location: ".$appt->location_name .": ".$appt->locdetails."\n";

    if($appt->tm_type == "Ticket"){
      $m->content .= "User: ".$appt->tm->person ."\n";
      $m->content .= "Phone: ".$appt->tm->phone ."\n";
      $m->content .= "Remedy Ticket: ".$appt->tm->remedy_ticket ."\n";
    } elseif($appt->tm_type == "Meeting" || $appt->tm_type == "Meecket"){
      $m->content .= "Meeting Subject: ".$appt->tm->subject ."\n";
    }

    return(self::send($m) && ((is_null($n)) ? true : self::send($n)));
  }

  /**
   * Generate an email address for a username and type of user
   *
   * @param string $username
   * @param string $type
   * @return string
   */
  private static function email_for($username, $type = "consultants"){
    if($type != "acd"){
      $ret = $username . "@" . application_site_class::config_vals("email_to");
    } else {
      $ret = application_site_class::config_vals("email_admin");
    }

    //kvframework_log::write_log("Mailer::email_for result: ".$ret, KVF_LOG_LDEBUG);
    return $ret;
  }

  /**
   * Actually send an email
   *
   * @param kvframework_mail_struct $mail
   * @return boolean
   */
  public static function send(kvframework_mail_struct $mail){
    if(application_site_class::config_vals("send_email") == "TRUE"){
      kvframework_log::write_log("Sent E-mail(true): ".serialize($mail), KVF_LOG_LINFO, array("maillog"), "MAIL");
      return parent::send($mail);
    } else {
      kvframework_log::write_log("Sent E-mail(false): ".serialize($mail), KVF_LOG_LINFO, array("maillog"), "MAIL");
      return true;
    }
  }

}
?>
