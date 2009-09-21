<?php
/**
 * KvScheduler - A lot of useful functions
 * @package KvScheduler
 * @subpackage Lib
 * @author Greg McWhirter <gsmcwhirter@gmail.com>
 * @version 1.0
 */

/**
 * A wrapper of useful functions
 *
 * @package KvScheduler
 * @subpackage Lib
 */
abstract class TOOLS extends kvframework_base{
  /**
   * Transform array weekday index => short abbreviation
   *
   * @var array
   */
  public static $transform = array('N','M','T','W','H','F','S');
  /**
   * Transform array short abbreviation => ical abbreviation
   *
   * @var array
   */
  public static $transform_alt = array('N'=>'SU', 'M'=>'MO', 'T'=>'TU', 'W'=>'WE', 'H'=>'TH', 'F' => 'FR', 'S' => 'SA');
  /**
   * Transform array month index => name
   *
   * @var array
   */
  public static $monthnames = array("January", "February", "March","April","May","June","July","August","September","October","November","December");
  /**
   * Transform array month index => abbreviation
   *
   * @var array
   */
  public static $monthabbrs = array("Jan","Feb","Mar","Apr","May","Jun","Jul","Aug","Sep","Oct","Nov","Dec");
  /**
   * Transform array weekday index => name
   *
   * @var array
   */
  public static $daynames = array("Sunday", "Monday", "Tuesday", "Wednesday","Thursday","Friday","Saturday");
  /**
   * Transform array weekday index => long abbreviation
   *
   * @var array
   */
  public static $dayabbrs = array("Sun","Mon","Tue","Wed","Thu","Fri","Sat","Sun");

  /**
   * Get today's date as a timestamp
   *
   * @return integer
   */
  public static function date_today(){
    return strtotime("today 12am");
  }

  /**
   * Get the current time to the nearest 30 mins as a timestamp
   *
   * @return integer
   */
  public static function time_now(){
    return self::round_to_30m(strtotime("now"));
  }

  /**
   * Generate an array of dates
   *
   * @param integer $start
   * @param integer $stop
   * @return array
   */
  public static function date_range($start, $stop){
    $ret = array();
    if($start == $stop){
      $ret = array($start);
      return $ret;
    } elseif($start < $stop){
      $mod = "+1";
    } else {
      $mod = "-1";
    }

    $last = $start;
    $ret[] = $start;
    kvframework_log::write_log("Date_Range: ".TOOLS::date_to_s($start)."($start) - ".TOOLS::date_to_s($stop)."($stop)", KVF_LOG_LDEBUG);
    while(($mod == "+1" && $last < $stop) || ($mod == "-1" && $last > $stop)){
      kvframework_log::write_log("Date_Range_it: ".$mod." ".TOOLS::date_to_s($last)."($last) ".TOOLS::date_to_s($stop)."($stop)", KVF_LOG_LDEBUG);
      $ret[] = $last = strtotime($mod." days", $last);
    }

    return $ret;
  }

  /**
   * Generate an array of dates for the week of some date
   *
   * @param integer $date
   * @return array
   */
  public static function week_of($date){
    if(!is_int($date)){throw new Exception("Parameter was not correctly formed (TOOLS::week_of).");}
    $s = self::x_days_since((-1 * self::wday_for($date)), $date);
    $p = self::x_days_since(6, $s);
    return self::date_range($s, $p);
  }

  /**
   * Generate an array of integers
   *
   * @param integer $start
   * @param integer $stop
   * @return array
   */
  public static function int_range($start, $stop){
    $s = (int)$start;
    $p = (int)$stop;

    $ret = array();
    $diff = abs($s - $p);
    if($s < $p){
      $mod = 1;
    } else {
      $mod = -1;
    }

    for($i = 0; $i <= $diff; $i++){
      $ret[] = $s + ($i * $mod);
    }

    return $ret;
  }

  /**
   * Generate a date relative to another one by some number of days
   *
   * @param integer $x
   * @param integer $since
   * @return integer
   */
  public static function x_days_since($x, $since){
    if($x == 0){
      return $since;
    } elseif($x < 0){
      return strtotime((string)$x." days", $since);
    } else {
      return strtotime("+".$x." days", $since);
    }
  }

  /**
   * Generate a date relative to another one by some number of months
   *
   * @param integer $x
   * @param integer $since
   * @return integer
   */
  public static function x_months_since($x, $since){
    if($x == 0){
      return $since;
    } elseif($x < 0) {
      return strtotime((string)$x." months", strtotime(date("Y-m-15", $since)));
    } else {
      return strtotime("+".$x." months", strtotime(date("Y-m-15", $since)));
    }
  }

  /**
   * Generate a time relative to another by some number of minutes
   *
   * @param integer $x
   * @param integer $since
   * @return integer
   */
  public static function x_minutes_since($x, $since){
    if($x == 0){
      return $since;
    } elseif($x < 0) {
      return strtotime((string)$x." minutes", $since);
    } else {
      return strtotime("+".$x." minutes", $since);
    }
  }

  /**
   * Generate an array of times every 30 minutes between 2 times
   *
   * @param integer $start
   * @param integer $stop
   * @return array
   */
  public static function every_30m_between($start, $stop){
    $temp = array();
    if($start == $stop){
      return array($start);
    } elseif($start < $stop){
      $mod = "+30";
    } else {
      $mod = "-30";
    }

    $last = $start;
    $temp[] = $start;
    while(($mod == "+30" && $last < $stop) || ($mod == "-30" && $last > $stop)){
      $temp[] = $last = strtotime($mod." minutes", $last);
    }

    return $temp;
  }

  /**
   * Round a time to the nearest 30 minutes
   *
   * @param integer $time
   * @return integer
   */
  public static function round_to_30m($time){
    if($time % (30*60) == 0){ return $time;}
    return (($time % (30*60)) >= (15*60)) ? (($time + 30*60) - ($time % (30*60))) : ($time - ($time % (30*60)));
  }

  /**
   * Convert a date timestamp to a string representation
   *
   * @param integer $date
   * @return string
   */
  public static function date_to_s($date){
    if(!is_numeric($date)){ throw new Exception("Date parameter not numeric");}
    return date("Y-m-d", $date);
  }

  /**
   * Convert a time timestamp to a string representation
   *
   * @param integer $time
   * @param boolean $h12
   * @return string
   */
  public static function time_to_s($time, $h12 = false){
    return date(($h12) ? "h:i A" : "H:i", $time);
  }

  /**
   * Perform a collection on an array
   *
   * @param array $array
   * @param string $as
   * @param string $what
   * @return array
   */
  public static function array_collect(array $array, $as, $what){
    $temp = array();
    $todo = 'foreach($array as '.$as.'){$temp[] = '.$what.';}';
    eval($todo);
    return $temp;
  }

  /**
   * Perform a selection on an array
   *
   * @param array $array
   * @param string $as
   * @param string $when
   * @return array
   */
  public static function array_select(array $array, $as, $when){
    $temp = array();
    $todo = 'foreach($array as '.$as.'){if('.$when.'){$temp[] = '.$as.';}}';
    eval($todo);
    return $temp;
  }

  /**
   * Perform a filter on an array
   *
   * @param array $array
   * @param string $as
   * @param string $when
   * @return array
   */
  public static function array_reject(array $array, $as, $when){
    return self::array_select($array, $as, "!(".$when.")");
  }

  /**
   * Get the weekday index for a date timestamp
   *
   * @param integer $date
   * @return integer
   */
  public static function wday_for($date){
    return (int)date("w", $date);
  }

  /**
   * Convert a string representation to a date timestamp
   *
   * @param string $dstr
   * @return integer
   */
  public static function string_to_date($dstr){
    return strtotime($dstr." midnight");
  }

  /**
   * Convert a string representation to a time timestamp
   *
   * @param string $tstr
   * @return integer
   */
  public static function string_to_time($tstr){
    return strtotime($tstr);
  }

  /**
   * Convert a string representation to a datetime timestamp
   *
   * @param string $dtstr
   * @return integer
   */
  public static function string_to_datetime($dtstr){
    return strtotime($dtstr);
  }

  /**
   * Transform a weekday index to a short abbreviation or ical abbreviation
   *
   * @param integer $wday
   * @param boolean $alt_method
   * @return string
   */
  public static function weekday_transform($wday, $alt_method = false){

    if(!$alt_method){
      return self::$transform[(int)$wday];
    } else {
      $temp = "";
      foreach(explode(",", $wday) as $d){
        $temp .= self::$transform_alt[$d].",";
      }
      return substr($temp, 0, -1);
    }
  }

  /**
   * Wraps weekday_transform for use as a callback
   *
   * @see TOOLS::weekday_transform()
   * @param integer $wday
   * @return string
   */
  public static function weekday_transform_true($wday){
    return self::weekday_transform($wday, true);
  }

  /**
   * Un-Transforms a weekday abbreviation
   *
   * @param string $abbr
   * @return integer
   */
  public static function weekday_reverse($abbr){
    $abbr_rev = array_flip(self::$transform_alt);
    $transform = array_flip(self::$transform);

    if(array_key_exists($abbr, $abbr_rev)){
      return $transform[$abbr_rev[$abbr]];
    } elseif(array_key_exists($abbr, $transform)){
      return $transform[$abbr];
    } else {
      return null;
    }
  }

  /**
   * Calculate the number of days between 2 dates
   *
   * @param integer $d1
   * @param integer $d2
   * @return integer
   */
  public static function days_diff($d1, $d2){
    $d1t = $d1 - ($d1 % (24*60*60));
    $d2t = $d2 - ($d2 % (24*60*60));
    return (int)(abs(($d1t - $d2t) / (24*60*60)));
  }

  /**
   * Calculate the number of hours between 2 times
   *
   * @param integer $t1
   * @param integer $t2
   * @return integer
   */
  public static function hours_diff($t1, $t2){
    return round(abs($t1 - $t2) / (3600), 1);
  }

  /**
   * Determine whether two dates are on some multiple number of weeks
   *
   * @param integer $d1
   * @param integer $d2
   * @param integer $mult
   * @return boolean
   */
  public static function on_multiple_weeks($d1, $d2, $mult){
    $diff = self::days_diff(self::x_days_since(-1 * self::wday_for($d1), $d1), self::x_days_since(-1 * self::wday_for($d2), $d2));
    $weeks = $diff / 7;
    $div = ($weeks / $mult);
    kvframework_log::write_log("DATETIME_IN_APPT/ON_MULTI: $d1 (".TOOLS::date_to_s($d1).", ".TOOLS::wday_for($d1)."), $d2 (".TOOLS::date_to_s($d2).", ".TOOLS::wday_for($d2)."), ".self::days_diff($d1, $d2).", ".self::wday_for($d1).", ".self::wday_for($d2).", ".$diff.", ".$weeks.", ".$div, KVF_LOG_LINFO);
    return ((int)$div == $div) ? true : false;
  }

  /**
   * Calculate the span (time diff / 30 mins) of 2 times
   *
   * @param integer $start
   * @param integer $stop
   * @return integer
   */
  public static function calcspan($start, $stop){
    $s = self::round_to_30m($start);
    $p = self::round_to_30m($stop);

    return (int)(($p-$s) / (30*60));
  }

  /**
   * Pad a number with some zeros on the left
   *
   * @param integer $number
   * @param integer $length
   * @return string
   */
  public static function leftzeropad($number, $length){
    $num = (string)$number;
    $len = (int)$length;
    if(strlen($num) >= $len){
      return $num;
    } else{
      $result = "";
      for($i = 0; $i < ($len - strlen($num)); $i++){
        $result .= "0";
      }
      $result .= $num;

      return $result;
    }
  }

  /**
   * Use a Finger server to get a real name for a username
   *
   * @param string $uname
   * @return mixed
   */
  public static function finger_name($uname){
    $name = false;
    $buff = "";
    $finger = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
    socket_connect($finger, "136.142.11.143", 79);
    socket_write($finger, "$uname\r\n");
    while($r = @socket_read($finger, 1024, PHP_NORMAL_READ)){
      $buff .= $r;
    }
    socket_close($finger);

    $lines = explode("\n", preg_replace("/\r/", "", $buff));
    foreach($lines as $line){
      if(preg_match("/^Name:/", $line)){
        $name = trim(substr($line, 6));
        break;
      } elseif(preg_match("/No matches found/", $line)){
        $name = false;
        break;
      }
    }

    return $name;
  }

  /**
   * Use an LDAP server to get the real name for a username
   *
   * @param string $uname
   */
  public static function ldap_name($uname){
    $name = false;
    $ldaps = ldap_connect("directory.mydomain.com");
    if($ldaps){
      $r = ldap_bind($ldaps);
      $sr = ldap_search($ldaps, "o=My Organization, c=US","mail=".$uname."@*");
      $count = ldap_count_entries($ldaps, $sr);
      if( $count == 1){
        list($info) = ldap_get_entries($ldaps, $sr);
        ldap_close($ldaps);
        return $info["cn"][0];
      } elseif($count < 1){
        return false;
      } elseif($count > 1){
        return false;
      }
    } else {
      return false;
    }
  }

  /**
   * Escape special characters in a string
   *
   * @param string $string
   * @return string
   */
  public static function escape_quotes($string){
    return preg_replace("/\r|\n/", "", nl2br(htmlentities(addslashes(preg_replace("#<br>|<br\s/>|<br/>#","\n",$string)), ENT_QUOTES)));
  }

  /**
   * Calcuate a "cross-product" of 2 arrays
   *
   * @param array $a
   * @param array $b
   * @return array
   */
  public static function array_cross(array $a, array $b){
    $result = array();

    foreach($a as $ea){
      foreach($b as $eb){
        $result[] = array($ea, $eb);
      }
    }

    return $result;
  }

  /**
   * Prep an array thinger
   *
   * @param integer $start
   * @param integer $stop
   * @return array
   */
  public static function prep_blocks($start, $stop){
    $tstart = microtime(true);
    if(!$start || !$stop){return array(false, false);}
    $nblocks = (int)(($stop - $start) / (30*60));

    $blocks = array();
    $blocks[0] = $start;
    $blocks[1] = $stop;
    for($i = 0; $i < $nblocks; $i++){
      $blocks[$i+2] = array();
      $blocks[$i+2][0] = null;
      $blocks[$i+2][1] = 0;
      $blocks[$i+2][2] = null;
    }

    $tstop = microtime(true);
    kvframework_log::write_log("prep_blocks: executed in ".($tstop - $tstart)."s", KVF_LOG_LDEBUG);

    return $blocks;
  }

  /**
   * Get the number of days in a certain month
   *
   * @param integer $date
   * @param integer $month_mod
   * @return integer
   */
  public static function month_days($date, $month_mod){
    $mt = (int)date("n", $date);
    $m = (($mt + $month_mod - 1) % 12) + 1;
    $y = (int)date("Y", $date) + floor(($mt + $month_mod) / 12);

    return (int)date("t",mktime(0,0,0,$m,1,$y));
  }

  /**
   * Time select boxes generator
   *
   * @param array $opts
   * @return string
   */
  public static function mytime_select(array $opts){
    $hours = self::int_range((array_key_exists("min_hour", $opts) && $opts["min_hour"] >= 0 && $opts["min_hour"] <= 23) ? (int)$opts["min_hour"] : 0, (array_key_exists("max_hour", $opts) && $opts["max_hour"] >= 0 && $opts["max_hour"] <= 23) ? (int)$opts["max_hour"] : 23);
    $mins = self::int_range((array_key_exists("min_minute", $opts) && $opts["min_minute"] >= 0 && $opts["min_minute"] <= 59) ? (int)$opts["min_minute"] : 0, (array_key_exists("max_minute", $opts) && $opts["max_minute"] >= 0 && $opts["max_minute"] <= 59) ? (int)$opts["max_minute"] : 59);

    if(array_key_exists("on_hours", $opts) && $opts["on_hours"]){
      $hours = self::array_reject($hours, '$h', '(($h - '.$hours[0].') % (int)'.$opts["on_hours"].') != 0');
    }

    if(array_key_exists("on_minutes", $opts) && $opts["on_minutes"]){
      $mins = self::array_reject($mins, '$m', '(($m - '.$mins[0].') % (int)'.$opts["on_minutes"].') != 0');
    }
    if(array_key_exists("include_59", $opts) && $opts["include_59"] && !in_array(59, $mins)){
      $mins[] = 59;
    }

    if(!array_key_exists("selected", $opts) || !is_int($opts["selected"])){
      $sh = null;
      $sm = null;
    } elseif(array_key_exists("selected", $opts)){
      $sh = (int)date("H", $opts["selected"]);
      $sm = (int)date("i", $opts["selected"]);
    }

    $res = "";
    $res .= kvframework_markup::select(array("name" => (array_key_exists("name", $opts)) ? $opts["name"]."[]" : "mytime[]", "values" => self::array_collect($hours, '$h', 'array(('.((array_key_exists("display12h", $opts) && $opts["display12h"]) ? '1 == 1' : '1 == 0').') ? TOOLS::leftzeropad((($h - 1) % 12) + 1 ,2).(($h >= 12) ? "p":"a") : TOOLS::leftzeropad($h, 2), TOOLS::leftzeropad($h, 2))'), "selected" => $sh, "class" => (array_key_exists("class", $opts)) ? $opts["class"] : null));
    $res .= kvframework_markup::select(array("name" => (array_key_exists("name", $opts)) ? $opts["name"]."[]" : "mytime[]", "values" => self::array_collect($mins, '$h', 'array(TOOLS::leftzeropad($h, 2), TOOLS::leftzeropad($h, 2))'), "selected" => $sm, "class" => (array_key_exists("class", $opts)) ? $opts["class"] : null));

    return $res;
  }

  /**
   * Date select box generator
   *
   * @param array $opts
   * @return string
   */
  public static function date_select(array $opts){
    $months = self::int_range(1, 12);
    $days = self::int_range(1,31);
    $years = self::int_range((array_key_exists("start_year", $opts)) ? (int)$opts["start_year"] : TOOLS::year_for(TOOLS::date_today()), (array_key_exists("end_year", $opts)) ? (int)$opts["end_year"] : self::year_for(self::date_today()) + 10);

    if(!array_key_exists("selected", $opts) || !is_int($opts["selected"])){
      $sd = null;
      $sm = null;
      $sy = null;
    } elseif(array_key_exists("selected", $opts)){
      $sd = self::day_for($opts["selected"]);
      $sm = self::month_for($opts["selected"]);
      $sy = self::year_for($opts["selected"]);
    }

    $res = "";
    $res .= kvframework_markup::select(array("name" => (array_key_exists("name", $opts)) ? $opts["name"]."[]" : "date[]", "values" => self::array_collect($years, '$h', 'array($h, $h)'), "selected" => $sy, "class" => (array_key_exists("class", $opts)) ? $opts["class"] : null));
    $res .= kvframework_markup::select(array("name" => (array_key_exists("name", $opts)) ? $opts["name"]."[]" : "date[]", "values" => self::array_collect($months, '$h', 'array(TOOLS::month_abbr_for($h), TOOLS::leftzeropad($h, 2))'), "selected" => $sm, "class" => (array_key_exists("class", $opts)) ? $opts["class"] : null));
    $res .= kvframework_markup::select(array("name" => (array_key_exists("name", $opts)) ? $opts["name"]."[]" : "date[]", "values" => self::array_collect($days, '$h', 'array(TOOLS::leftzeropad($h, 2), TOOLS::leftzeropad($h, 2))'), "selected" => $sd, "class" => (array_key_exists("class", $opts)) ? $opts["class"] : null));

    return $res;
  }

  /**
   * Get the month day for a date timestamp
   *
   * @param integer $date
   * @return integer
   */
  public static function day_for($date){
    return (int)date("j", $date);
  }

  /**
   * Get the month index for a date timestamp
   *
   * @param integer $date
   * @return integer
   */
  public static function month_for($date){
    return (int)date("n", $date);
  }

  /**
   * Get the year for a date timestamp
   *
   * @param integer $date
   * @return integer
   */
  public static function year_for($date){
    return (int)date("Y", $date);
  }

  /**
   * Wrapper around mktime
   *
   * @param integer $year
   * @param integer $month
   * @param integer $day
   * @return integer
   */
  public static function new_date($year, $month, $day){
    return mktime(0,0,0,$month,$day,$year);
  }

  /**
   * Generate the name of the month for a certain month index
   *
   * @param integer $month
   * @return string
   */
  public static function month_name_for($month){
    return self::$monthnames[abs((($month - 1) % 12))];
  }

  /**
   * Generate the abbreviation of a month for a certain month index
   *
   * @param integer $month
   * @return string
   */
  public static function month_abbr_for($month){
    return self::$monthabbrs[abs((($month - 1) % 12))];
  }

  /**
   * Get the hour part of a time for a certain time timestamp
   *
   * @param integer $time
   * @param boolean $h12
   * @return string
   */
  public static function hour_s_for($time, $h12 = false){
    return strftime(($h12) ? "%I" : "%H", $time);
  }

  /**
   * Get the minute part of a time for a certain time timestamp
   *
   * @param integer $time
   * @return string
   */
  public static function minutes_s_for($time){
    return strftime("%M", $time);
  }

  /**
   * Generate a select generator options array from a comma-separated list of weekday abbreviations
   *
   * @param string $options
   * @return array
   */
  public static function options_from_wdays_allowed($options){
    return TOOLS::array_collect(explode(",", $options), '$d', 'array(TOOLS::$daynames[TOOLS::weekday_reverse($d)], $d)');
  }

  /**
   * Set a certain bit of an integer
   *
   * @param integer $val
   * @param integer $bit
   * @return boolean true
   */
  public static function bit_set(&$val, $bit) {
      if($bit < 1 || $bit > 32){ throw new Exception("Bit value out of bounds.");}
        if (self::bit_read($val, $bit)) return true;
        $val += '0x'.dechex(1<<($bit-1));
        return true;
    }

    /**
     * Unset a certain bit of an integer
     *
     * @param integer $val
     * @param integer $bit
     * @return boolean true
     */
    public static function bit_clear(&$val, $bit) {
      if($bit < 1 || $bit > 32){ throw new Exception("Bit value out of bounds.");}
      if (!self::bit_read($val, $bit)) return true;
      $val = $val^(0+('0x'.dechex(1<<($bit-1))));
      return true;
    }

    /**
     * Read a certain bit of an integer
     *
     * @param integer $val
     * @param integer $bit
     * @return integer
     */
    public static function bit_read($val, $bit) {
      if($bit < 1 || $bit > 32){ throw new Exception("Bit value out of bounds.");}
        return ($val&(0+('0x'.dechex(1<<($bit-1)))))?'1':'0';
    }
}
?>
