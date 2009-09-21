<?php
/**
 * KvScheduler - User class
 * @package KvScheduler
 * @subpackage Lib
 * @author Greg McWhirter <gsmcwhirter@gmail.com>
 * @version 1.0
 */

/**
 * Represents the current user
 *
 * @package KvScheduler
 * @subpackage Lib
 */
class User extends kvframework_base {

  /**
   * Application Key
   *
   */
  const AKey = "4bcd28caef528bdcb96acedf4fae5";
  /**
   * Password salt
   *
   */
  const Salt = 'jsjbvb8923riu(*^*&Tiub52873gt3782*&%&&^$731gr903fn20efb-01924-8oadignouqwrh 8YA8R7GWQIUEFBWEIUBF283';

  /**
   * User record info
   *
   * @var array
   */
  private $INFO = array();
  /**
   * Flag for ACD accounts
   *
   * @var boolean
   */
  private $acd = false;
  /**
   * User's username
   *
   * @var string
   */
  private $username = "";
  /**
   * User's access key
   *
   * @var mixed
   */
  private $key = false;
  /**
   * User's access level
   *
   * @var integer
   * @see ACCESS
   */
  private $access = ACCESS::noauth;
  /**
   * Whether user was logged in with a password or session
   *
   * @var boolean
   */
  public $usedpass = false;
  /**
   * User's access Appointment type key
   *
   * @var string
   */
  public $ats_key = "display";
  /**
   * User's consultant account information
   *
   * @var array
   */
  private $RCINFO = array();
  /**
   * Type of user
   *
   * @var mixed
   */
  public $usertype;

  /**
   * Constructor
   *
   * @param boolean $acd
   */
  function __construct($acd = false){ //done
    $this->acd = $acd;
  }

  /**
   * Get user info value for property
   *
   * @param string $key
   * @return mixed
   */
  final public function info ($key){
    if(count($this->INFO) == 0){$this->update_user_record();}
    return (array_key_exists($key, $this->INFO)) ? $this->INFO[$key] : null;
  }

  /**
   * Get a copy of all user info
   *
   * @return mixed
   */
  final public function allinfo(){
    if(count($this->INFO) == 0){$this->update_user_record();}
    return (count($this->INFO) > 0) ? $this->INFO : null;
  }

  /**
   * Get user consultant info value for property
   *
   * @param string $key
   * @return mixed
   */
  final public function rcinfo ($key){
    if(count($this->RCINFO) == 0){$this->update_user_record(null, true);}
    return (array_key_exists($key, $this->RCINFO)) ? $this->RCINFO[$key] : null;
  }

  /**
   * Get a copy of all user consultant info
   *
   * @return mixed
   */
  final public function allrcinfo(){
    if(count($this->RCINFO) == 0){$this->update_user_record(null, true);}
    return (count($this->RCINFO) > 0) ? $this->RCINFO : null;
  }

  /**
   * Determine whether or not a user is logged in
   *
   * @return boolean
   */
  final public function connected(){
    return ($this->key == self::AKey and $this->username != "");
  }

  /**
   * Get the user's access level
   *
   * @return integer
   */
  final public function access(){
    return $this->access;
  }

  /**
   * Attempt to log a user in
   *
   * @param string $username
   * @param string $password
   * @return boolean
   */
  final public function login($username, $password){
    if(!$this->key){
      list($u, $typ, $sys) = $this->find_user($username);
      if(!is_null($u)){
        if($this->local_bind($username, $password, $u)){
          $this->username = $username;
          $this->key = self::AKey;
          if($sys){
            $this->access = ACCESS::sysop;
            $this->ats_key = "sysop";
          } else {
            switch($typ){
              case "supervisors":
                $this->access = ACCESS::modify;
                $this->ats_key = "supervisor";
                break;
              case "helpdeskers":
                $this->access = ACCESS::user;
                $this->ats_key = "helpdesk";
                break;
              case "consultants":
                $this->access = ACCESS::display;
                $this->ats_key = "display";
                break;
            }
          }

          $this->INFO = $u;
          $this->usedpass = true;
          kvframework_log::write_log("USER_LOGIN: ".serialize($this), KVF_LOG_LDEBUG);
          return true;
        } else {
          return false;
        }
      } else {
        return false;
      }
    } else {
      return $this->connected();
    }
  }

  /**
   * Attempt to authenticate a user from the session
   *
   * @param string $username
   * @param string $key
   * @param string $access
   * @return boolean
   */
  final public function check_login($username, $key, $access){
    if($key == self::AKey){
      $this->key = $key;
      $ok = false;
      list($u, $typ, $sys) = $this->find_user($username);

      if(!$this->acd && $u['acdaccount'] == 'yes')
      {
      	return false;
      }

      switch($access){
        case ACCESS::noauth:
          $ok= true;
          break;
        case ACCESS::sysop:
          if($sys){$ok = true; $this->ats_key = "sysop";}
          break;
        case ACCESS::modify:
          if($typ == "supervisors"){$ok = true; $this->ats_key = "supervisor";}
          break;
        case ACCESS::user:
          if($typ == "helpdeskers"){$ok = true; $this->ats_key = "helpdesk";}
          break;
        case ACCESS::display:
          if($typ == "consultants"){$ok = true; $this->ats_key = "display";}
          break;
      }

      if($ok){
        $this->username = $username;
        $this->access = $access;
        $this->update_user_record();
        return true;
      } else {
        return false;
      }
    } else{
      return false;
    }
  }

  /**
   * Get a user's records
   *
   * @param string $username
   * @param mixed $typ
   * @return array
   */
  final protected function find_user($username, $typ = null){
    $ret = null;
    $rett = null;
    $sys = false;
    $typs = array("supervisors", "helpdeskers", "consultants");
    $map = array("s" => "supervisors", "h" => "helpdeskers", "r" => "consultants");
    if(!is_null($typ)){$typs = array($typ);}
    $sql = "";
    foreach($typs as $type){
      $sql .= "UNION SELECT id, username, realname, password, force_pass_change, ".(($type == "helpdeskers") ? "acdaccount" : "'n o' as acdaccount").", '".substr($type, 0, 1)."' as union_table FROM $type WHERE username ='$username' ".(($type == "consultants") ? "AND status = 'active' " : "");
    }
    $q = self::$DB->query(substr($sql, 6)." LIMIT 1");
    if(self::$DB->rows($q) == 1){
      $ret = self::$DB->fetch_array($q);
      $rett = $map[$ret["union_table"]];
    }

    $sql = "SELECT * FROM sysops WHERE username = '$username'";
    $q = self::$DB->query($sql);
    if(self::$DB->rows($q) == 1){
      $sys = true;
    }

    return array($ret, $rett, $sys);
  }

  /**
   * Get a user's records
   *
   * @param string $username
   * @param mixed $password
   * @param mixed $consultant
   * @return array
   */
  final public function get_user_record($username, $password = null, $consultant = null){
    list($u, $typ, $sys) = $this->find_user($username, (is_null($consultant)) ? null : "consultants");
    if(!is_null($password)){
      return array(($u["password"] == $this->crypt_password($password)) ? $u : null, true, $typ);
    } else {
      return array($u, false, $typ);
    }
  }

  /**
   * Update the current user's records
   *
   * @param mixed $password
   * @param mixed $consultant
   * @return boolean
   */
  final public function update_user_record($password = null, $consultant = null){
    if($this->username != ""){
      list($u, $typ, $sys) = $this->find_user($this->username, (is_null($consultant)) ? null : "consultants");
      if(!is_null($password) && !is_null($consultant)){
        $this->RCINFO = ($u["password"] == $this->crypt_password($password)) ? $u : array();
      } elseif (!is_null($password)) {
        $this->INFO = ($u["password"] == $this->crypt_password($password)) ? $u : array();
        $this->usedpass = true;
      } elseif (!is_null($consultant)) {
        $this->RCINFO = (is_null($u)) ? array() : $u;
      } else {
        $this->INFO = (is_null($u)) ? array() : $u;
        $this->usedpass = false;
      }
      $this->usertype = $typ;
      if(!is_null($consultant)){
        return (count($this->RCINFO) > 0) ? true : false;
      } else {
        return (count($this->INFO) > 0) ? true : false;
      }
    } else {
      return false;
    }
  }

  /**
   * Authentication function
   *
   * @param string $username
   * @param string $password
   * @param array $record
   * @return boolean
   */
  final protected function local_bind($username, $password, array $record){
  	if(!$this->acd && $record['acdaccount'] == 'yes')
  	{
  		return false;
  	}
    if($record["username"] == $username && $record["password"] == $this->crypt_password($password)){
      return true;
    } else {
      return false;
    }
  }

  /**
   * Password encryption scheme
   *
   * @param string $text
   * @return string
   */
  final public function crypt_password($text){
    $text = self::$NAKOR_CORE->unclean_value($text);
    kvframework_log::write_log("CRYPT_PASS:".$text, KVF_LOG_LDEBUG);
    kvframework_log::write_log("CRYPT_PASS:".(($text == "thisisalongpassword!withanothercharacter") ? "match" : "no match"), KVF_LOG_LDEBUG);
    return sha1(self::Salt . $text . self::Salt . self::AKey);
  }

  /**
   * Generate a random password of some length
   *
   * @param integer $len
   * @return string
   */
  final public function random_password($len){
    $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890";
    srand((double)microtime()*1000000);
    $i = 0;
    $pass = "";

    while($i < $len){
      $num = rand(0, 62);
      $pass .= substr($chars, $num, 1);
      $i++;
    }

    return $pass;
  }

  /**
   * PHP magic function to make sure nothing can be set
   *
   * @param mixed $n
   * @param mixed $v
   */
  public function __set($n,$v){return null;}

  /**
   * Return the current connection id
   *
   * @return string
   */
  public function connection_id(){
    return self::AKey;
  }

  /**
   * Return the current usertype
   *
   * @return mixed
   */
  public function usertype(){
    return $this->usertype;
  }

}
?>
