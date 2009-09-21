<?php
/**
 * KvScheduler - ConsultantHour Admin Data Object Factory
 * @package KvScheduler
 * @subpackage Modules.Lib
 * @author Greg McWhirter <gsmcwhirter@gmail.com>
 * @version 1.0
 */

load_files(KVF_MODULES."/calendar.module/lib/schedulerblock.class.php");

/**
 * ConsultantHour Admin Data Object Factory
 *
 * @package KvScheduler
 * @subpackage Modules.Lib
 *
 */
abstract class RCHAdminObject extends kvframework_base {

  /**
   * Generate a new RCHAdminObject
   *
   * @param integer $consultant_id
   * @param integer $date
   * @return array
   */
  public static function newobj($consultant_id, $date){
    $self = array();
    $week_data = array();

    $dates = array();
    for($i = 0; $i < 7; $i++){
      $dates[($i + TOOLS::wday_for($date)) % 7] = TOOLS::x_days_since($i, $date);
    }

    if(!is_array($consultant_id)){
      $data = MyFunctions::consultantHoursDataFor(array($consultant_id), $dates, true);
      $rhd =& $data["rchours"]->blocks[$consultant_id];
      $ohd =& $data["ophours"]->blocks;
      foreach($dates as $day){
        $week_data[] = $ohd[$day];
      }

      $week_data = TOOLS::array_reject($week_data, '$i', '!$i["start"] || !$i["stop"]');

      if(count($week_data > 0)){
        $temp = TOOLS::array_collect($week_data, '$i','$i["start"]');
        sort($temp);
        $start = $temp[0];
        $temp = TOOLS::array_collect($week_data, '$i','$i["stop"]');
        rsort($temp);
        $stop = $temp[0];
      } else {
        $start = false;
        $stop = false;
      }

      if($date && $start && $stop){
        $self["times"] = array();
        $dur = (int)(($stop - $start) / (30*60));
        for($i = 0; $i < $dur; $i++){
          $self["times"][] = TOOLS::x_minutes_since( $i * 30, $start);
        }

        $self["week"] = array();
        for($i = 0; $i < 7; $i++){
          $self["week"][$i] = array();
          $self["week"][$i][0] = $dates[$i];
          $self["week"][$i][1] = array();
          $dur = (int)(($stop - $start) / (30*60));
          for($j = 0; $j < $dur; $j++){
            $self["week"][$i][1][$j] = new SchedulerBlock();
          }
          $self["week"][$i][2] = 0;
        }

        $max_i = count($dates);
        for($i = 0; $i < $max_i; $i++){
          $d = $rhd[$dates[$i]];
          $offset_time = ($d[0] >= $start) ? 0 : abs($d[0] - $start);
          $postset_time = ($d[1] <= $stop) ? 0 : abs($d[1] - $stop);
          $nat_dur_time = ($d[1] - $d[0]);
          $dur_time = ($nat_dur_time - $offset_time - $postset_time);
          $dur = (int)($dur_time / 1800);
          $offset = (int)($offset_time / 1800);
          $postset = (int)(-1 * ($postset_time / 1800));

          $temp = ($d[0] - $start);
          $j_delta = (($temp > 0) ? ($temp / 1800) : 0) - 1;

          if(is_array($d) && array_key_exists(0, $d) && $d[0] && $d[2] != 0){
            for($j = 1 + $offset; $j < $dur + 1 + $postset; $j++){
              if(TOOLS::bit_read($d[2], $j)){
                $hour =& $data["rchours"]->things[$d[3][$j]];
                $status = "A";
                $span = 1;
                if($hour->htype == "delete"){
                   $status = "K";
                } elseif($hour->oncall == "TRUE"){
                  $status = "C";
                }

                //if(TOOLS::bit_read($ohd[$dates[$i]]["intervals"][2], $j + $j_delta + 1) && MyFunctions::datetime_in_hour($dates[$i], TOOLS::string_to_time($hour->starttime), $hour)){
                if(MyFunctions::datetime_in_hour($dates[$i], TOOLS::string_to_time($hour->starttime), $hour)){
                  if(($status == "A" || $status == "C") && $self["week"][$i][1][$j + $j_delta]->status == "K"){$self["week"][$i][2] += 1;}
                  elseif($status == "K" && ($self["week"][$i][1][$j + $j_delta]->status == "A" || $self["week"][$i][1][$j + $j_delta]->status == "C")){$self["week"][$i][2] -= 1;}

                  $self["week"][$i][1][$j + $j_delta]->set_status($status);
                  $self["week"][$i][1][$j + $j_delta]->set_span($span);

                }
              }
            }
          }
        }
        return $self;
      } else {
        throw new Exception("We are closed.");
      }
    } else {
      $data = MyFunctions::consultantHoursDataFor($consultant_id, $dates, true);
      $ohd =& $data["ophours"]->blocks;
      foreach($dates as $day){
        $week_data[] = $ohd[$day];
      }

      $week_data = TOOLS::array_reject($week_data, '$i', '!$i["start"] || !$i["stop"]');

      if(count($week_data > 0)){
        $temp = TOOLS::array_collect($week_data, '$i','$i["start"]');
        sort($temp);
        $start = $temp[0];
        $temp = TOOLS::array_collect($week_data, '$i','$i["stop"]');
        rsort($temp);
        $stop = $temp[0];
      } else {
        $start = false;
        $stop = false;
      }

      if($date && $start && $stop){
        $self["times"] = array();
        $dur = (int)(($stop - $start) / (30*60));
        for($i = 0; $i < $dur; $i++){
          $self["times"][] = TOOLS::x_minutes_since( $i * 30, $start);
        }

        foreach($consultant_id as $rc){
          $self[$rc]["week"] = array();
          for($i = 0; $i < 7; $i++){
            $self[$rc]["week"][$i] = array();
            $self[$rc]["week"][$i][0] = $dates[$i];
            $self[$rc]["week"][$i][1] = array();
            for($j = 0; $j < $dur; $j++){
              $self[$rc]["week"][$i][1][] = new SchedulerBlock();
            }
            $self[$rc]["week"][$i][2] = 0;
          }

          $rhd =& $data["rchours"]->blocks[$rc];

          $max_i = count($dates);
          for($i = 0; $i < $max_i; $i++){
            $d = $rhd[$dates[$i]];
            $offset_time = ($d[0] >= $start) ? 0 : abs($d[0] - $start);
            $postset_time = ($d[1] <= $stop) ? 0 : abs($d[1] - $stop);
            $nat_dur_time = ($d[1] - $d[0]);
            $dur_time = ($nat_dur_time - $offset_time - $postset_time);
            $dur = (int)($dur_time / 1800);
            $offset = (int)($offset_time / 1800);
            $postset = (int)(-1 * ($postset_time / 1800));

            $temp = ($d[0] - $start);
            $j_delta = (($temp > 0) ? ($temp / 1800) : 0) - 1;

            if(is_array($d) && array_key_exists(0, $d) && $d[0] && $d[2] != 0){
              for($j = 1 + $offset; $j < $dur + 1 + $postset; $j++){
                if(TOOLS::bit_read($d[2], $j)){
                  $hour =& $data["rchours"]->things[$d[3][$j]];
                  $status = "A";
                  $span = 1;
                  if($hour->htype == "delete"){
                     $status = "K";
                  } elseif($hour->oncall == "TRUE"){
                    $status = "C";
                  }

                  if(TOOLS::bit_read($ohd[$dates[$i]]["intervals"][2], $j + $j_delta + 1) && MyFunctions::datetime_in_hour($dates[$i], TOOLS::string_to_time($hour->starttime), $hour)){
                    if(($status == "A" || $status == "C") && $self[$rc]["week"][$i][1][$j + $j_delta]->status == "K"){$self[$rc]["week"][$i][2] += 1;}
                    elseif($status == "K" && ($self[$rc]["week"][$i][1][$j + $j_delta]->status == "A" || $self[$rc]["week"][$i][1][$j + $j_delta]->status == "C")){$self[$rc]["week"][$i][2] -= 1;}
                    $self[$rc]["week"][$i][1][$j + $j_delta]->set_status($status);
                    $self[$rc]["week"][$i][1][$j + $j_delta]->set_span($span);
                  }
                }
              }
            }
          }
        }

        return $self;
      } else {
        throw new Exception("We are closed.");
      }
    }
  }
}
?>
