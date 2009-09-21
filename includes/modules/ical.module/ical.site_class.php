<?php
/**
 * KvScheduler - iCal Subscription SiteClass
 * @package KvScheduler
 * @subpackage Modules
 * @author Greg McWhirter <gsmcwhirter@gmail.comgsmcwhirter@gmail.com>
 * @version 1.0
 */

/**
 * iCal subscription generator
 *
 * @package KvScheduler
 * @subpackage Modules
 */
class ical_site_class extends kvframework_site{

  /**
   * Consultant ID
   *
   * @var integer
   */
  protected $id;

  /**
   * Removals of appointments on dates
   *
   * @var array
   */
  protected $removals = array();

  /**
   * Constructor - does nothing
   *
   */
  function __construct(){
  }

  /**
   * Generate the iCal file content
   *
   * @param string $type Not used
   */
  public function get_calendar($type = "appts"){
    if(!array_key_exists("id", self::$PARAMS)){

    } else {
      $result = "";

      $result .= "BEGIN:VCALENDAR\r\n";
      $result .= "PRODID:".CONFIG::rooturl."\r\n";
      $result .= "VERSION:2.0\r\n";
      $result .= "BEGIN:VTIMEZONE\r\n";
      $result .= "TZID:US/Eastern\r\n";
      $result .= "LAST-MODIFIED:19870101T000000Z\r\n";
      $result .= "BEGIN:STANDARD\r\n";
      $result .= "DTSTART:19671029T020000\r\n";
      $result .= "RRULE:FREQ=YEARLY;BYDAY=-1SU;BYMONTH=10;UNTIL=20071103T000000Z\r\n";
      $result .= "TZOFFSETFROM:-0400\r\n";
      $result .= "TZOFFSETTO:-0500\r\n";
      $result .= "TZNAME:EST\r\n";
      $result .= "END:STANDARD\r\n";
      $result .= "BEGIN:STANDARD\r\n";
      $result .= "DTSTART:20071104T020000\r\n";
      $result .= "RRULE:FREQ=YEARLY;BYDAY=1SU;BYMONTH=11\r\n";
      $result .= "TZOFFSETFROM:-0400\r\n";
      $result .= "TZOFFSETTO:-0500\r\n";
      $result .= "TZNAME:EST\r\n";
      $result .= "END:STANDARD\r\n";
      $result .= "BEGIN:DAYLIGHT\r\n";
      $result .= "DTSTART:19870405T020000\r\n";
      $result .= "RRULE:FREQ=YEARLY;BYDAY=1SU;BYMONTH=4;UNTIL=20070310T000000Z\r\n";
      $result .= "TZOFFSETFROM:-0500\r\n";
      $result .= "TZOFFSETTO:-0400\r\n";
      $result .= "TZNAME:EDT\r\n";
      $result .= "END:DAYLIGHT\r\n";
      $result .= "BEGIN:DAYLIGHT\r\n";
      $result .= "DTSTART:20070311T020000\r\n";
      $result .= "RRULE:FREQ=YEARLY;BYDAY=2SU;BYMONTH=3\r\n";
      $result .= "TZOFFSETFROM:-0500\r\n";
      $result .= "TZOFFSETTO:-0400\r\n";
      $result .= "TZNAME:EDT\r\n";
      $result .= "END:DAYLIGHT\r\n";
      $result .= "END:VTIMEZONE\r\n";

      //print_r(self::$PARAMS);
      $i = self::$PARAMS["id"];
      //var_dump($i);
      $this->id = substr((string)$i, 8, -8);
      //print $this->id;
      $data = array();
      if($type == "appts"){
        $tms = array("Ticket" => array(), "Meeting" => array(), "Meecket" => array());
        $sql = "SELECT *, consultants.id as consultant_id, appointments.id as appointment_id, consultantappts.rapid as consultantappt_id, locations.id as location_id, locations.name as location_name FROM appointments, consultants, consultantappts, locations WHERE consultants.status = 'active' AND consultants.id = '".$this->id."' AND consultantappts.consultant_id = consultants.id AND consultantappts.appointment_id = appointments.id AND locations.id = appointments.location_id AND (consultantappts.confirmed = 'TRUE' OR appointments.special = 'repeat_removal') ORDER BY appointments.special = 'repeat_removal' DESC";
        $q = self::$DB->query($sql);
        while($row = self::$DB->fetch($q)){
          $data[$row->appointment_id] = $row;
          $tms[$row->tm_type][$row->appointment_id] = $row->tm_id;
        }

        foreach($tms as $type => $ids){
          $tmd = array();
          if(count($ids) > 0){
            $sql = "SELECT *, id as tm_id FROM ".strtolower($type)."s WHERE id IN (".implode(",",$ids).")";
            //print $sql;
            $q = self::$DB->query($sql);
            while($row = self::$DB->fetch($q)){
              $tmd[$row->tm_id] = $row;
            }

            foreach($tms[$type] as $apid => $tmid){
              $data[$apid]->tm = $tmd[$tmid];
            }
          }
        }

        foreach($data as $appt){
          if($appt->special == "repeat_removal"){
            if(!array_key_exists($appt->removal_of, $this->removals) || !is_array($this->removals[$appt->removal_of])){
              $this->removals[$appt->removal_of] = array();
            }
            $this->removals[$appt->removal_of][] = $appt->startdate;
          } elseif($appt->lockout == "FALSE"){
            $result .= $this->generate_ical_for_appt($appt);
          }
        }
      }
      $result .= "END:VCALENDAR\r\n";

      header("Content-Type: text/calendar");
      header('Content-Disposition: attachment; filename="cal.ics"');
      echo $result;
    }
  }

  /**
   * Generate the snippet for a single appointment
   *
   * @param mixed $appt
   * @return string
   */
  protected function generate_ical_for_appt($appt){
    $result = "";

    $result .= "BEGIN:VEVENT\r\n";
    $result .= "TRANSP:OPAQUE\r\n";
    $result .= "CLASS:PUBLIC\r\n";
    $result .= "UID:".$appt->appointment_id."@".CONFIG::rooturl."\r\n";
    $result .= "SEQUENCE:".$appt->ical_sequence."\r\n";
    $result .= "DTSTART;TZID=US/Eastern:".preg_replace("/-/","",$appt->startdate)."T".preg_replace("/:/","",$appt->starttime)."\r\n";
    if($appt->repeat == "TRUE"){
      $dur = TOOLS::string_to_time($appt->stoptime) - TOOLS::string_to_time($appt->starttime);
      $result .= "DURATION:PT".floor(($dur - ($dur % 3600)) / 3600)."H".floor((($dur % 3600) - ($dur % 60)) / 60)."M".($dur % 60)."S\r\n";
      #$temp = explode(",", $appt->repetition_day);
      #array_walk($temp, array("TOOLS","weekday_transform_true"));
      #$result .= "RRULE:FREQ=WEEKLY;UNTIL=".preg_replace("/-/","",$appt->stopdate).";INTERVAL=".$appt->repetition_week.";BYDAY=".implode(",", $temp)."\r\n";
      $result .= "RRULE:FREQ=WEEKLY;UNTIL=".preg_replace("/-/","",$appt->stopdate).";INTERVAL=".$appt->repetition_week.";BYDAY=".TOOLS::weekday_transform($appt->repetition_day, true)."\r\n";
      if(array_key_exists($appt->appointment_id, $this->removals)){
        $result .= "EXDATE;TZID=US/Eastern:".preg_replace("/-/","",implode("T".preg_replace("/:/","",$appt->starttime).",", $this->removals[$appt->appointment_id]))."T".preg_replace("/:/","",$appt->starttime)."\r\n";
      }
    } else {
      $result .= "DTEND;TZID=US/Eastern:".preg_replace("/-/","",$appt->stopdate)."T".preg_replace("/:/","",$appt->stoptime)."\r\n";
    }
    $result .= "LOCATION:".$appt->location_name." ".$appt->locdetails."\r\n";

    if(gmdate("j") == date("j")){
      $result .= "DTSTAMP:".preg_replace("/-/","",TOOLS::date_to_s(TOOLS::date_today()))."T".gmdate("His")."Z\r\n";
    } else {
      $result .= "DTSTAMP:".preg_replace("/-/","",TOOLS::date_to_s(TOOLS::x_days_since(1, TOOLS::date_today())))."T".gmdate("His")."Z\r\n";
    }

    if($appt->tm_type == "Meeting" || $appt->tm_type == "Meecket"){
      $result .= "SUMMARY:Meeting: ".$appt->tm->subject."\r\n";
    } elseif($appt->tm_type == "Ticket"){
      $result .= "SUMMARY:Appointment: ".$appt->tm->person." (Ticket ".$appt->tm->remedy_ticket.")\r\n";
    }

    $result .= "DESCRIPTION:Location: ".$appt->location_name." ".$appt->locdetails."\\n".(($appt->tm_type == "Ticket") ? "Phone 1: ".$appt->tm->phone."\\nPhone 2: ".$appt->tm->altphone."\\n" : "").html_entity_decode(preg_replace(array("!<br\s/>!","/\n/","/\r/","/\s$/","/</","/>/"),array("\\n","\\n","","","{","}"),$appt->tm->description))."\\n\\n[Internal Tracking ID: ".$appt->appointment_id."]\r\n";
    $result .= "COMMENT: \r\n";
    $result .= "END:VEVENT\r\n";

    return $result;
  }
}
