<?php
/**
 * KvScheduler - Calendar SiteClass
 * @package KvScheduler
 * @subpackage Modules
 * @author Greg McWhirter <gsmcwhirter@gmail.com>
 * @version 1.0
 */

load_files(
  KVF_MODULES."/calendar.module/lib/schedulerday.class.php",
  KVF_MODULES."/calendar.module/lib/schedulerperson.class.php",
  KVF_MODULES."/calendar.module/lib/schedulerblock.class.php",
  KVF_MODULES."/stats.module/lib/statsgenerators.class.php",
  KVF_INCLUDES."/lib/fpdf.php",
  KVF_INCLUDES."/lib/fpdf/report_generator.fpdf.php"
);

/**
 * Manages output of the main calendar
 *
 * @package KvScheduler
 * @subpackage Modules
 */
class calendar_site_class extends application_site_class{

  /**
   * Date displayed
   *
   * @var integer
   */
  protected $date;

  /**
   * Rescheduling flag
   *
   * @var mixed
   */
  protected $reschedule;

  /**
   * Calendar info
   *
   * @var array
   */
  protected $thisday = array();

  /**
   * Consultant records
   *
   * @var array
   */
  protected $consultants = array();

  /**
   * Consultant IDs
   *
   * @var array
   */
  protected $consultant_ids = array();

  /**
   * Tickets to search for
   * @var array
   */
  protected $tickets = array();

  /**
   * Constructor
   *
   */
  function __construct(){
    parent::__construct();
    $this->before_filter("prep_vars");
  }

  /**
   * Display the information for some date
   *
   * @param string $view Which view to render
   */
  protected function display_date($view){
    $tstart = microtime(true);
    try{
      list($this->thisday, $this->consultant_ids, $this->consultants) = SchedulerDay::newsd($this->date);
    } catch(Exception $e){
      self::throwError($e->getMessage());
    }
    $tstop = microtime(true);
    kvframework_log::write_log("*****Prepping Calendar: ".($tstop - $tstart)."s");

    $this->output_page($view, "inline");
  }

  /**
   * Display a date completely
   *
   */
  public function display_date_full(){
    $this->display_date("one_day");
  }

  /**
   * Just refresh the internal grid
   *
   */
  public function display_date_part(){
    $this->display_date("calendar");
  }

  /**
   * Output the selector calendar
   */
  public function display_select(){
    $this->output_page("selector", "inline");
  }

  /**
   * Prepare associated variables
   *
   * @return boolean true
   */
  protected function prep_vars(){
    if(!array_key_exists("date", self::$PARAMS) || empty(self::$PARAMS["date"])){
      $this->date = TOOLS::date_today();
    } else {
      $this->date = TOOLS::string_to_date(self::$PARAMS["date"]);
    }

    $this->reschedule = (array_key_exists("reschedule", self::$PARAMS) && self::$PARAMS["reschedule"] != 0) ? self::$PARAMS["reschedule"] : null;
    return true;
  }

  /**
   * Actually output the selector calendar
   *
   * @return string
   */
  protected function selectorOutput(){
    $result = "";

    $mdays = TOOLS::month_days($this->date, 0);
    $pmdays = TOOLS::month_days($this->date, -1);

    $startson = TOOLS::wday_for(TOOLS::new_date(TOOLS::year_for($this->date), TOOLS::month_for($this->date), 1));
    $weeks = ceil(($mdays + $startson) / 7) + 1;

    $calendar = array();
    for($i = 0; $i < $weeks; $i++){
      $calendar[$i] = array();
      for($j = 0; $j < 7; $j++){
        $calendar[$i][$j] = 0;
      }
    }

    for($i = 0; $i < $weeks * 7; $i++){
      if($i < $startson){
        $calendar[floor($i / 7)][$i % 7] = $pmdays - $startson + $i + 1;
      } elseif($i >= ($startson + $mdays)){
        $calendar[floor($i / 7)][$i % 7] = $i + 1 - $startson - $mdays;
      } else {
        $calendar[floor($i / 7)][$i % 7] = $i + 1 - $startson;
      }
    }

    $mstart = $startson;
    $mend = $startson + $mdays;

    $result .= "<table class='cal' style='width: 100%;' border='0' id='tab_cal'>";
    $result .= "<tr>";
    $result .= "<th class='arrows' style='text-align: center;' scope='col' onclick=\"linktodate('".TOOLS::date_to_s(TOOLS::x_months_since(-1, $this->date))."', '".self::url_for("calendar", "display_date_part")."')\">&lt;</th>";
    $result .= "<th class='title' style='text-align: center;' scope='col' colspan='5'>".TOOLS::month_name_for(TOOLS::month_for($this->date))."</th>";
    $result .= "<th class='arrows' style='text-align: center;' scope='col' onclick=\"linktodate('".TOOLS::date_to_s(TOOLS::x_months_since(1, $this->date))."', '".self::url_for("calendar", "display_date_part")."')\">&gt;</th>";
    $result .= "</tr>";
    $result .= "<tr>";
    for($i = 0; $i < 7; $i++){
      $result .= "<td class='header'>".TOOLS::weekday_transform($i)."</td>";
    }
    $result .= "</tr>";
    $wc = 0;
    foreach($calendar as $week){
      $result .= "<tr>";
      $max_i = count($week);
      for($i = 0; $i < $max_i; $i++){
        $type = "day";
        $check = ($wc * 7 + $i);
        if($check < $mstart || $check >= $mend){
          $type = "header pointer";
        }
        $today = TOOLS::date_today();
        if($week[$i] == TOOLS::day_for($this->date) && $check >= $mstart && $check <= $mend){
          $type = "day viewing";
        } elseif($week[$i] == TOOLS::day_for($today) && $check >= $mstart && $check <= $mend && TOOLS::month_for($this->date) == TOOLS::month_for($today) && TOOLS::year_for($this->date) == TOOLS::year_for($today)){
          $type == "day today";
        }
        $nd = TOOLS::x_days_since($check - $mstart, TOOLS::new_date(TOOLS::year_for($this->date), TOOLS::month_for($this->date), 1));
        $result .= "<td class='".$type."' id='CD".TOOLS::leftzeropad(TOOLS::month_for($nd), 2).TOOLS::leftzeropad($week[$i], 2)."' onclick=\"linktodate('".TOOLS::date_to_s($nd)."', '".self::url_for("calendar", "display_date_part")."')\">".$week[$i]."</td>";
      }
      $result .= "</tr>";
      $wc++;
    }
    $result .= "</table>";

    return $result;
  }

  /**
   * Output the selector calendar's framing and the selector calendar
   *
   * @see calendar_site_class::selectorOutput()
   * @return string
   */
  protected function daySelectOutput(){
    $result = "";
    $url = self::url_for('calendar','display_date_part');
    $result .= "<div id='grid-2a'>";
    $result .= "<div class='button' id='today' style='color:#f68d91;' onclick=\"linktodate(document.getElementById('tddate').value, '".$url."')\" onmouseover=\"this.style.backgroundColor='#fff87f'\" onmouseout=\"this.style.backgroundColor='#ffffff'\">Today</div>";
    $result .= "<div class='button' id='prev_day' onclick=\"linktodate(document.getElementById('pddate').value, '".$url."')\" onmouseover=\"this.style.backgroundColor='#fff87f'\" onmouseout=\"this.style.backgroundColor='#ffffff'\">Prev</div>";
    $result .= "<div class=\"button\" id=\"next_day\" onclick=\"linktodate(document.getElementById('nddate').value, '".$url."')\" onmouseover=\"this.style.backgroundColor='#fff87f'\" onmouseout=\"this.style.backgroundColor='#ffffff'\">Next</div>";
    $result .= "<div class=\"button\" id=\"jump_day\" style=\"border-bottom:1px solid #b4b4b4;\" onclick=\"jumptodate('".$url."')\" onmouseover=\"this.style.backgroundColor='#fff87f'\" onmouseout=\"this.style.backgroundColor='#ffffff'\">Jump</div>";
    $result .= "</div>";
    $result .= "<div id='grid-2b'>";
    $result .= "<div id='selector_div'>";
    $result .= $this->selectorOutput();
    $result .= "</div></div>";

    return $result;
  }


  /**
   * Output the calendar grid
   *
   * @param array $sday
   * @return string
   */
  protected function schedulerDayOutput(&$sday){
    $result = "";

    $result .= "<div id=\"dating\" class=\"clearfix\"><div style=\"float:left;\">".TOOLS::$daynames[TOOLS::wday_for($this->date)]." ".TOOLS::day_for($this->date)." ".TOOLS::month_name_for(TOOLS::month_for($this->date))." ".TOOLS::year_for($this->date)."</div><div style=\"float:right;\">".(($this->reschedule) ? "[RESCHEDULING APPOINTMENT | ".kvframework_markup::link_to_function("cancel", "document.getElementById('resch').value='0';linktodate('".TOOLS::date_to_s($this->date)."', '".self::url_for("calendar", "display_date_part")."')")."]" : "&nbsp;")."</div></div>";
    $result .= "<div id=\"gridcover\">";
    $result .= "<input type='hidden' name='resch' id='resch' value='".(($this->reschedule) ? $this->reschedule : 0)."' />";
    $result .= "<input type='hidden' name='formajax' id='formajax' value='".self::url_for("appointment", (($this->reschedule) ? "process_reschedule_select" : "create_form"))."' />";
    $result .= "<table id='maintable' style='width: 100%;' border='0'>";
    #Write the times etc
    $row1 = "";
    $row2 = "";
    $row3 = "";
    $row4 = "";

    $max_j = count($sday[0]);
    for($j = 0; $j < $max_j; $j++){
      $extras = 'scope="col" ';
      $type = "";
      if(($j % 2) == 1){
        $type = "header2";
        $row1 .= "<th colspan='2' ".$extras.">".TOOLS::hour_s_for(TOOLS::string_to_time($sday[0][$j]->h24), true)."</th>";
        $row4 .= "<th colspan='2' ".$extras." style='border-top:none;'>".TOOLS::hour_s_for(TOOLS::string_to_time($sday[0][$j]->h24), true)."</th>";
      } else {
        $type = "header2a";
      }

      if($j == 0){
        $row1 .= "<th style='width:122Px; border-bottom:none;' ".$extras."></th>";
        $row2 .= "<td class='header' style='width:122Px;' ".$extras.">Consultants</td>";
        $row3 .= "<td class='header' style='width: 122Px;' ".$extras." rowspan='2'> </td>";
      } else {
        $extras .= "id='L".$j."' ";
        $row2 .= "<td class='".$type."' ".$extras.">".TOOLS::minutes_s_for(TOOLS::string_to_time($sday[0][$j]->h24))."</td>";
        $row3 .= "<td class='".$type."' style='border-bottom: none;' ".$extras.">".TOOLS::minutes_s_for(TOOLS::string_to_time($sday[0][$j]->h24))."</td>";
      }
    }

    $result .= "<tr>";
    $result .= $row1;
    $result .= "</tr><tr>";
    $result .= $row2;
    $result .= "</tr>";

    $total = (count($sday) > 40) ? count($sday) : 39;

    $timerow = array_shift($sday);
    if($this->USER->access() < $this->override_val("override_randomlist")){
      $indices = array_rand($sday, count($sday));
    } else {
      $indices = array_keys($sday);
    }
    array_unshift($sday, $timerow);

    foreach($indices as $ind){
      $i = $ind + 1;
      $max_j = count($sday[$i]);
      for($j = 0; $j < $max_j; $j++){
        $extras = "";
        $data = "";
        $type = "";
        $color = false;
        $infos = array();
        if(!($i == 0 || $sday[$i][$j]->status == "I")){
          switch($sday[$i][$j]->status){
            case "A":
              $type = "timeslot available";
              $color = $this->consultants[$this->consultant_ids[$i-1]]->color;
              $extras = "onclick='blockClicked(\"".$sday[0][$j]->h24."\", \"".$this->consultant_ids[$i-1]."\", \"col_".$j."_".$i."\");'";
              $extras .= $this->appt_movmout_code($sday[$i][$j]->meta, $this->consultant_ids[$i-1], $total, $j, 1, false, false, $this->consultants[$this->consultant_ids[$i-1]]->label);
              break;
            case "B":
              $type = "timeslot scheduled";
              $extras = "colspan='".$sday[$i][$j]->span."' ";
              if(!$this->reschedule){$extras .= $this->appt_onclick_code($sday[$i][$j]->meta, $this->consultant_ids[$i-1]);}
              $extras .= $this->appt_movmout_code($sday[$i][$j]->meta, $this->consultant_ids[$i-1], $total, $j, $sday[$i][$j]->span);
              if($this->reschedule_if_case($sday[$i][$j]->meta->appointment_id)){
                //move to next case so it is selectable
              } else {
                break;
              }
            case "C":
              if($sday[$i][$j]->meta && $this->reschedule_if_case($sday[$i][$j]->meta->appointment_id)){
                for($m = 0; $m < $sday[$i][$j]->span; $m++){
                  $infos[$m] = array();
                  $infos[$m]["type"] = "timeslot oncall2";
                  $infos[$m]["extras"] = "onclick='blockClicked(\"".$sday[0][$j + $m]->h24."\", \"".$this->consultant_ids[$i-1]."\", \"col_".($j + $m)."_".$i."\");' ";
                  $infos[$m]["extras"] .= $this->appt_movmout_code($sday[$i][$j]->meta, $this->consultant_ids[$i-1], $total, $j + $m, 1, false, true);
                  $infos[$m]["color"] = false;
                  $infos[$m]["data"] = "";
                }
              } else {
                if($this->USER->access() >= ACCESS::modify){
                  $type = "timeslot oncall";
                  $extras = "onclick='blockClicked(\"".$sday[0][$j]->h24."\", \"".$this->consultant_ids[$i-1]."\", \"col_".$j."_".$i."\");' ";
                  //$extras .= $this->appt_movmout_code($sday[$i][$j]->meta, $this->consultant_ids[$i-1], $total, $j, 1, true);
                  $extras .= $this->appt_movmout_code($sday[$i][$j]->meta, $this->consultant_ids[$i-1], $total, $j, 1, true, false, $this->consultants[$this->consultant_ids[$i-1]]->label);
                } else {
                  $modspan = 1;
                  $k = 1;
                  while(array_key_exists($j+$k, $sday[$i]) && $sday[$i][$j+$k]->status == "C"){
                  //for($k = 1; $k < (int)$sday[$i][$j]->span; $k++){
                    //$sday[$i][$j+$k]->set_status("I");
                    //if($sday[$i][$j+$k]->status == "C"){
                      $sday[$i][$j+$k]->set_status("I");
                      $modspan++;
                      $k++;
                    //} else {
                      //break;
                    //}
                  }
                  $type = "timeslot scheduled";
                  //$extras = "colspan='".$sday[$i][$j]->span."' ";
                  $extras = "colspan='".$modspan."' ";
                  $extras .= $this->appt_movmout_code($sday[$i][$j]->meta, $this->consultant_ids[$i-1], $total, $j, $sday[$i][$j]->span, true, false, $this->consultants[$this->consultant_ids[$i-1]]->label);
                }
              }
              break;
            case "K":
              $type = "timeslot";
              $data = " ";
              break;
            case "O":
              $type = "timeslot lockout";
              $extras = "colspan='".$sday[$i][$j]->span."' ";
              $extras .= $this->appt_movmout_code($sday[$i][$j]->meta, $this->consultant_ids[$i-1], $total, $j, $sday[$i][$j]->span);
              break;
            case "L":
              $type = "name";
              $data = $sday[$i][$j]->content;
              break;
            default:
              self::throwError("Unexpected SchedulerBlock->status value.");
          }

          if(count($infos) == 0){
            $result .= "<td class='".$type."' id='col_".$j."_".$i."' ".(($color) ? "style='background-color: #".$color.";'" : "")." ".$extras." onmouseover=\"colorCol(".$j.",".$total.",this,1);\" onmouseout=\"resetCol(".$j.",".$total.",this,1);\">".$data."</td>";
          } else {
            $max_m = count($infos);
            for($m = 0; $m < $max_m; $m++){
              $result .= "<td class='".$infos[$m]["type"]."' id='col_".($j + $m)."_".$i."' ".(($infos[$m]["color"]) ? "style='background-color: #".$infos[$m]["color"].";'" : "")." ".$infos[$m]["extras"]." onmouseover=\"colorCol(".($j + $m).",".$total.",this,1);\" onmouseout=\"resetCol(".($j + $m).",".$total.",this,1);\">".$infos[$m]["data"]."</td>";
            }
          }
        }
      }

      $result .= "</tr>";
    }

    $t = (39 - count($sday));
    for($i = 0; $i < $t; $i ++){
      $result .= "<tr>";
      $result .= "<td class='name' id='col_0_".(count($sday) + $i)."'> </td>";
      $max_j = count($sday[0]);
      for($j = 0; $j < $max_j - 1; $j ++){
        $result .= "<td class='timeslot' id='col_".($j+1)."_".(count($sday) + $i)."'> </td>";
      }
      $result .= "</tr>";
    }

    $result .= "<tr>";
    $result .= $row3;
    $result .= "</tr><tr>";
    $result .= $row4;
    $result .= "</tr>";

    $result .= "<tr class='bottomers'>";
    $result .= "<td class='name2'>Last refresh</td>";
    $result .= "<td class='name3' colspan='".(count($sday[0]) - 1)."'><div id=\"countdown_div\"></div></td>";
    $result .= "</tr>";

    $result .= "<input type='hidden' name='date' id='date' value='".TOOLS::date_to_s($this->date)."' />";
    $result .= "<input type='hidden' name='pddate' id='pddate' value='".TOOLS::date_to_s(TOOLS::x_days_since(-1 , $this->date))."' />";
    $result .= "<input type='hidden' name='nddate' id='nddate' value='".TOOLS::date_to_s(TOOLS::x_days_since(1 , $this->date))."' />";
    $result .= "<input type='hidden' name='tddate' id='tddate' value='".TOOLS::date_to_s(TOOLS::date_today())."' />";
    $result .= "<input type='hidden' name='colcount' id='colcount' value='".(count($sday[0]))."' />";
    $result .= "<input type='hidden' name='startrow' id='startrow' />";

    $result .= "</table>";
    $result .= "</div>";

    return $result;
  }

  /**
   * Generate the onclick javascript
   *
   * @param mixed $appt
   * @param integer $consultant
   * @return string
   */
  protected function appt_onclick_code($appt, $consultant){
    $result = "";
    $result .= "onclick=\"linkViewFullDetails(".$consultant.", ".$appt->appointment_id.",'".TOOLS::date_to_s($this->date)."')\"";
    return $result;
  }

  /**
   * Generate onmouseover and onmouseout code
   *
   * @param mixed $appt
   * @param integer $consultant
   * @param integer $total
   * @param integer $row_j
   * @param integer $colspan
   * @param boolean $oncall
   * @param boolean $resch_oncall
   * @return string
   */
  protected function appt_movmout_code($appt, $consultant, $total, $row_j, $colspan, $oncall = false, $resch_oncall = false, $hours_label = false){
    $result = "";
    if($resch_oncall){
      $result .= "onmouseover=\"tooltip_init(findPosX(this), findPosY(this),'down','CURRENT APPOINTMENT','');colorCol(".$row_j.",".$total.",this,".$colspan.");\" onmouseout=\"tooltip_kill();resetCol(".$row_j.",".$total.",this,".$colspan.");\"";
    } elseif($oncall){
      //$result .= "onmouseover=\"tooltip_init(findPosX(this), findPosY(this),'down','ON CALL HOURS','');colorCol(".$row_j.",".$total.",this,".$colspan.");\" onmouseout=\"tooltip_kill();resetCol(".$row_j.",".$total.",this,".$colspan.");\"";
      $result .= "onmouseover=\"tooltip_init(findPosX(this), findPosY(this),'down','ON CALL HOURS','".($hours_label ? $hours_label : "")."', '', true);colorCol(".$row_j.",".$total.",this,".$colspan.");\" onmouseout=\"tooltip_kill();resetCol(".$row_j.",".$total.",this,".$colspan.");\"";
    } elseif($hours_label) {
      $result .= "onmouseover=\"tooltip_init(findPosX(this), findPosY(this),'down','".$hours_label."','','',true);colorCol(".$row_j.",".$total.",this,".$colspan.");\" onmouseout=\"tooltip_kill();resetCol(".$row_j.",".$total.",this,".$colspan.");\"";
    } elseif ($appt && $appt->lockout == 'FALSE'){
      $confirmed = (!is_null($appt->consultantappts[$consultant]) && $appt->consultantappts[$consultant]["confirmed"] == "TRUE") ? true : false;
      if($appt->tm_type == "Ticket"){
        $result .= "onmouseover=\"tooltip_init(findPosX(this),findPosY(this),'down', '".$appt->tm->remedy_ticket."', '".TOOLS::escape_quotes($appt->tm->person)."','".TOOLS::escape_quotes($appt->location_name)."',".(($confirmed) ? "true" : "false").");colorCol(".$row_j.",".$total.",this,".$colspan.");\" onmouseout=\"tooltip_kill();resetCol(".$row_j.",".$total.",this,".$colspan.");\""; #here
      } elseif($appt->tm_type == "Meeting" || $appt->tm_type == "Meecket"){
        $result .= "onmouseover=\"tooltip_init(findPosX(this),findPosY(this),'down', '<strong>Meeting</strong>', '".TOOLS::escape_quotes($appt->tm->subject)."','".TOOLS::escape_quotes($appt->location_name)."', ".(($confirmed) ? "true" : "false").");colorCol(".$row_j.",".$total.",this,".$colspan.");\" onmouseout=\"tooltip_kill();resetCol(".$row_j.",".$total.",this,".$colspan.");\""; #here
      }
    } else {
      $result .= "onmouseover=\"tooltip_init(findPosX(this), findPosY(this),'down','LOCKOUT','".TOOLS::escape_quotes($appt->lockout_user)."','');colorCol(".$row_j.",".$total.",this,".$colspan.");\" onmouseout=\"tooltip_kill();resetCol(".$row_j.",".$total.",this,".$colspan.");\"";
    }

    return $result;
  }

  /**
   * The "if" clause for a reschedule
   *
   * @param integer $id
   * @return boolean
   */
  protected function reschedule_if_case($id){
    return($this->reschedule && (int)$id == (int)$this->reschedule);
  }

  /**
   * Ticket form
   *
   */
  public function form_ticket(){
    $this->output_page("form_ticket", "inline");
  }

  /**
   * Process ticket form
   *
   */
  public function process_ticket(){
    if(!$this->process_ticket_prep()){
      self::throwError("Ticket list was not found");
    }

    if(self::is_errors()){
      $this->form_ticket();
    } else {
      $this->run_ticket_report();
    }
  }

  /**
   * Prepare tickets on a processing
   *
   * @return boolean
   */
  protected function process_ticket_prep(){
    if(!array_key_exists("ticket", self::$PARAMS)){
      return false;
    } else {
      $this->tickets = array_unique(explode(":", preg_replace("#,\s|;\s|,|;|\s#", ":", self::$PARAMS["ticket"])));
      return true;
    }
  }

  /**
   * Run a ticket report
   *
   */
  protected function run_ticket_report(){
    $this->op_data = StatsGenerators::tickets_list($this->tickets);
    $this->pdf = PDF_Reports::generate_pdf("ticket", $this->op_data, TOOLS::date_today(), TOOLS::date_today(), false, true);
    $this->output_page("output_ticket","inline");
  }

  /**
   * Generate appointment output
   *
   * @param array $appt_hash
   * @return string
   */
  protected function page_display_appt_list_out($appt_hash){
    $result = "";
    $result .= "<li>";
    if($appt_hash["appt"]->tm_type == "Meeting" || $appt_hash["appt"]->tm_type == "Meecket"){
      $result .= "Meeting: ".$appt_hash["appt"]->tm->subject ." from ". TOOLS::time_to_s(TOOLS::string_to_time($appt_hash["appt"]->starttime), true) ." to ". TOOLS::time_to_s(TOOLS::string_to_time($appt_hash["appt"]->stoptime), true);
    } else {
      $result .= "Ticket: ". $appt_hash["appt"]->tm->remedy_ticket ." from ". TOOLS::time_to_s(TOOLS::string_to_time($appt_hash["appt"]->starttime), true) ." to ". TOOLS::time_to_s(TOOLS::string_to_time($appt_hash["appt"]->stoptime), true);
    }
    $result .= "<ul>";
    $result .= "Location:  ". $appt_hash["appt"]->locdetails ." ". Location::select_name($appt_hash["appt"])."<br />";
    if($appt_hash["appt"]->repeat == "TRUE"){
      $result .= "Every ". (($appt_hash["appt"]->repetition_week == "1") ? "week" : $appt_hash["appt"]->repetition_week." weeks") ." on ". implode(", ", TOOLS::array_collect(explode(",", $appt_hash["appt"]->repetition_day), '$i','TOOLS::$dayabbrs[TOOLS::weekday_reverse($i)]')) ." from ". $appt_hash["appt"]->startdate ." until ". $appt_hash["appt"]->stopdate;
      if(count($appt_hash["minus"]) > 0) {
        $result .= "<br />";
        $result .= "Except Dates: ". implode(", ", $appt_hash["minus"]);
      }
    } else {
      $result .= "Date: ". $appt_hash["appt"]->startdate;
    }
    $result .= "<br />";
    $result .= "Consultants: ". implode(", ", TOOLS::array_collect($appt_hash["appt"]->consultants, '$r', 'Consultant::select_name($r)'));
    $result .= "</ul></li>";

    return $result;
  }

}
?>
