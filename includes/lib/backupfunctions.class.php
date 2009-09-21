<?php
/**
 * KvScheduler - Appointment flatfile backup functions
 * @package KvScheduler
 * @subpackage Lib
 * @author Greg McWhirter <gsmcwhirter@gmail.com>
 * @version 1.0
 */

/**
 * Appointment flatfile backup functions
 *
 * @package KvScheduler
 * @subpackage Lib
 */
abstract class BackupFunctions{

  /**
   * Write backups to flatfiles for an appointment
   *
   * @param mixed $appt
   * @param array $consultants
   * @param mixed $tm
   */
  public static function write_backup($appt, array $consultants, $tm){
    self::unwrite_backup($appt);
    if($appt->repeat == "TRUE"){
      foreach(TOOLS::date_range(TOOLS::string_to_date($appt->startdate), TOOLS::string_to_date($appt->stopdate)) as $date){
        if(MyFunctions::datetime_in_appt($date, TOOLS::string_to_time($appt->starttime), $appt)){
          self::actually_write_backup(TOOLS::date_to_s($date), $appt, $consultants, $tm);
        }
      }
    } else {
      self::actually_write_backup($appt->startdate, $appt, $consultants, $tm);
    }
  }

  /**
   * Remove flatfile entries for an appointment
   *
   * @param mixed $appt
   */
  public static function unwrite_backup($appt){
    if($appt->repeat == "TRUE"){
      foreach(TOOLS::date_range(TOOLS::string_to_date($appt->startdate), TOOLS::string_to_date($appt->stopdate)) as $date){
        if(MyFunctions::datetime_in_appt($date, TOOLS::string_to_time($appt->starttime), $appt)){
          self::clean_backups(TOOLS::date_to_s($date), $appt);
        }
      }
    } else {
      self::clean_backups($appt->startdate, $appt);
    }
  }

  /**
   * Actually clean the backip files
   *
   * @param string $fn Filename
   * @param mixed $appt
   * @return boolean true
   */
  public static function clean_backups($fn, $appt){
    $sem = fopen(application_site_class::config_vals("backup_dir")."SEMAPHORE", "r");
    if(flock($sem, LOCK_EX)){
      if(file_exists(application_site_class::config_vals("backup_dir").$fn)){
        $lines = file(application_site_class::config_vals("backup_dir").$fn);
        $newlines = array();
        foreach($lines as $l){
          if(!preg_match("/^".$appt->removal_of."\t.*$/", $l)){
            $newlines[] = $l;
          }
        }

        $file = fopen(application_site_class::config_vals("backup_dir").$fn, "w");
        foreach($newlines as $l){
          fwrite($file, $l);
        }
        fclose($file);
      } else {

      }
    }
    flock($sem, LOCK_UN);
    fclose($sem);
    return true;
  }

  /**
   * Actually write the flatfile entries for an appointment
   *
   * @param string $fn Filename
   * @param mixed $appt
   * @param array $consultants
   * @param mixed $tm
   * @return boolean true
   */
  public static function actually_write_backup($fn, $appt, array $consultants, $tm){
    $sem = fopen(application_site_class::config_vals("backup_dir")."SEMAPHORE", "r");
    if(flock($sem, LOCK_EX)){
      $file = fopen(application_site_class::config_vals("backup_dir").$fn, "a+");
        foreach($consultants as $r){
          if($appt->special != "repeat_removal"){
            if($appt->tm_type == "Ticket"){
              fwrite($file, $appt->appointment_id . "\t" . $r->username . "\t" . $tm->remedy_ticket . "\t" . $appt->starttime . "\t" . $appt->stoptime . "\n");
            } elseif ($appt->tm_type == "Meeting" or $appt->tm_type == "Meecket"){
              fwrite($file, $appt->appointment_id . "\t" . $r->username . "\t" . $tm->subject . "\t" . $appt->starttime . "\t" . $appt->stoptime . "\n");
            }
          } else {
            if($appt->tm_type == "Ticket"){
              fwrite($file, $appt->removal_of . "\t". "REMOVAL" . "\t" . $r->username . "\t" . $tm->remedy_ticket . "\t" . $appt->starttime . "\t" . $appt->stoptime . "\n");
            } elseif ($appt->tm_type == "Meeting" or $appt->tm_type == "Meecket"){
              fwrite($file, $appt->removal_of . "\t" . "REMOVAL" . "\t" . $r->username . "\t" . $tm->subject . "\t" . $appt->starttime . "\t" . $appt->stoptime . "\n");
            }
          }
        }
      fclose($file);
    }
    flock($sem, LOCK_UN);
    fclose($sem);
    return true;
  }

}
?>
