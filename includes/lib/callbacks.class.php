<?php
/**
 * KvScheduler - Callback functions for validator use
 * @package KvScheduler
 * @subpackage Lib
 * @author Greg McWhirter <gsmcwhirter@gmail.com>
 * @version 1.0
 */

/**
 * Checks to see if the parameter is a comma-separated list of weekday abbreviations
 *
 * @param string $r
 * @return boolean
 */
function callback_weekdays_allowed($r){
  return(count(TOOLS::array_reject(explode(",",$r), '$v', 'in_array($v, array("M","T","W","H","F","S","N"))')) == 0);
}

/**
 * Checks to see if an id number is not obviously invalid (i.e. if it is a positive integer)
 *
 * @param mixed $r
 * @return boolean
 */
function callback_valid_id($r){
  return(is_int($r) && $r > 0);
}

/**
 * Checks to see if the parameter is a valid value for vpotential or hpotential
 *
 * @param mixed $r
 * @return true
 */
function callback_valid_potential($r){
  $rp = (int)$r;
  return(is_numeric($r) && in_array($rp, array(-1, 0, 1)));
}

/**
 * Checks to see if the date or time passed as the first parameter is after the one passed as the second parameter
 *
 * @param integer $r
 * @param integer $st
 * @param string $method
 * @return boolean
 */
function callback_datetime_check($r, $st, $method = "lax"){
  if($method == "strict"){
    return($r > ((is_int($st)) ? $st : 1000000000));
  } else {
    return($r >= ((is_int($st)) ? $st : 1000000000));
  }
}

/**
 * Checks the formatting of a string to see if it is a proper hex color code
 *
 * @param mixed $r
 * @return boolean
 */
function callback_hex_color($r){
  return(preg_match("/^[0-9a-fA-F]{6}$/",$r));
}

/**
 * Checks the formatting of a string to see if it is a proper remedy ticket number
 *
 * @param mixed $r
 * @return boolean
 */
function callback_remedy_ticket($r){
  return(!is_null($r) && preg_match("/[0-9]{7}/", $r) && preg_match("/[1-9]/", $r) && !preg_match("/[^0-9]{1}/", $r));
}

/**
 * Checks the formatting of a string to see if it is a proper phone number
 *
 * @see Ticket::phone_regexp
 *
 * @param mixed $r
 * @return boolean
 */
function callback_phone($r){
  return preg_match(Ticket::phone_regexp, $r);
}

/**
 * Sorts an array of arrays by index 0 entries
 *
 * @param array $a
 * @param array $b
 * @return integer
 */
function callback_sysadmin_sort(array $a, array $b){
  return(((int)$a[0] == (int)$b[0]) ? 0 : (((int)$a[0] < (int)$b[0]) ? -1 : 1 ));
}

/**
 * Sort an array or arrays by index 0 entries ascending
 *
 * @param array $a
 * @param array $b
 * @return integer
 */
function sort_by_index_0_asc(array $a, array $b){
  if($a[0] == $b[0]){return 0;}
  return ($a[0] < $b[0]) ? -1 : 1;
}

/**
 * Sort an array or arrays by index 0 entries descending
 *
 * @param array $a
 * @param array $b
 * @return integer
 */
function sort_by_index_1_desc(array $a, array $b){
  if($a[1] == $b[1]){return 0;}
  return ($a[1] < $b[1]) ? 1 : -1;
}

?>
