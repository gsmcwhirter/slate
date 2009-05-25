<?php

class User
{

  protected $_DBDATA;
  protected $_MYDATA = array();
  protected $_ISSYSOP = false;
  protected static $_PrivLookups = array();

  public function __construct(Krai_Db_Object $_data)
  {
    $this->_DBDATA = $_data;
    $q = Krai::$DB_DEFAULT->SelectQuery(array("user_roles as ur","roles as r"));
    $q->conditions = "ur.user_id = ? AND ur.role_id = ? AND r.role_id = ur.role_id";
    $q->parameters = array($this->_DBDATA->user_id, 'sysop');
    $q->fields = array("ur.role_id");

    $res = Krai::$DB_DEFAULT->Process($q);

    if($res)
    {
      $this->_ISSYSOP = true;
    }

    $q = Krai::$DB_DEFAULT->SelectQuery(array("user_openids as uoid"));
    $q->conditions = "uoid.user_id = ?";
    $q->parameters = array($this->_DBDATA->user_id);
    $q->fields = array("uoid.openid");

    $res = Krai::$DB_DEFAULT->Process($q);

    $oids = array();
    if($res)
    {
      foreach($res as $oid)
      {
        $oids[] = $oid->openid;
      }
    }

    $this->_MYDATA["openids"] = $oids;
  }

  public function __get($k)
  {
    return $this->_DBDATA->$k ? $this->_DBDATA->$k : (array_key_exists($k, $this->_MYDATA) ? $this->_MYDATA[$k] : null);
  }

  public function __set($k, $v)
  {
    if($this->_DBDATA->$k)
    {
      throw new UserException("Trying to overwrite a database value");
    }
    else
    {
      $this->_MYDATA[$k] = $v;
    }
  }

  public function HasPrivilegeFor(AccessScheme $as)
  {
    if($this->_ISSYSOP)
    {
      return true;
    }

    $c = count($as->requires);
    $p = "";
    for($i = 0; $i < $c; $i++)
    {
      $p .= "?, ";
    }

    $q = Krai::$DB_DEFAULT->SelectQuery(array("user_roles as ur","roles as r"));
    $q->conditions = "ur.user_id = ? AND ur.role_id IN (".substr($p,0,-2).") AND r.role_id = ur.role_id";
    $q->parameters = array_merge(array($this->_DBDATA->user_id),$as->requires);
    $q->fields = array("ur.role_id");

    $res = Krai::$DB_DEFAULT->Process($q);

    $res2 = array();
    foreach($res as $r)
    {
      $res2[] = $r->role_id;
    }

    $res2 = array_unique($res2);
    sort($res2);
    $asr = array_unique($as->requires);
    sort($asr);

    if($res2 !== $asr)
    {
      return false;
    }
    else
    {
      return true;
    }
  }

  public static function HasPrivilege($_user_id, $_priv_name)
  {
    if(array_key_exists(intval($_user_id), self::$_PrivLookups) && array_key_exists($_priv_name, self::$_PrivLookups))
    {
      return self::$_PrivLookups[$_user_id][$_priv_name];
    }

    if(!array_key_exists(intval($_user_id), self::$_PrivLookups))
    {
      self::$_PrivLookups[intval($_user_id)] = array();
    }

    $q = Krai::$DB_DEFAULT->SelectQuery(array("user_roles as ur","users as u","roles as r"));
    $q->conditions = "ur.role_id = ? AND ur.user_id = ? AND u.user_id = ur.user_id AND r.role_id = ur.role_id";
    $q->parameters = array($_priv_name, $_user_id);
    $q->fields = array("ur.role_id","ur.user_id");
    $q->limit = "1";

    $res = Krai::$DB_DEFAULT->Process($q);

    if($res)
    {
      self::$_PrivLookups[intval($_user_id)][$_priv_name] = true;
      return true;
    }
    else
    {
      self::$_PrivLookups[intval($_user_id)][$_priv_name] = false;
      return false;
    }
  }

}

class UserException extends Krai_Exception
{}
