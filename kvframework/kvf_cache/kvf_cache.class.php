<?php
/**
 * KvFramework caching class
 * @package KvFramework_v2
 * @subpackage KvF_Cache
 * @author Greg McWhirter <gsmcwhirter@gmail.com>
 * @copyright Copyright &copy; 2006, Greg McWhirter
 * @version $Revision: 1.1 $
 */

/**
 * Caching class
 * @package KvFramework_v2
 * @subpackage KvF_Cache
 */
abstract class KvfCache
{

  /**
   * Tries to get a saved cache
   *
   * @param string $_cachekey
   * @return mixed
   */
  public static function GetCache($_cachekey)
  {
    if(!is_string($_cachekey))
    {
      return null;
    }

    if($ret = @unserialize(file_get_contents(CONFIG_CACHE::DIR."/".base64_encode($_cachekey).".kvfcache")))
    {
      if(isset($ret["timeout"]) && isset($ret["data"]) && time() < $ret["timeout"])
      {
        return $ret["data"];
      }
      else
      {
        return null;
      }
    }
    else
    {
      return null;
    }
  }

  /**
   * Tries to save a cache
   *
   * @param string $_cachekey Key to cache under
   * @param mixed $_cachevalue Value to cache
   * @param integer $_cachetimeout Number of seconds to keep the cache
   * @return boolean
   */
  public static function SaveCache($_cachekey, $_cachevalue, $_cachetimeout = CONFIG_CACHE::TIMEOUT)
  {
    if(!is_string($_cachekey))
    {
      return false;
    }

    if(@file_put_contents(CONFIG_CACHE::DIR."/".base64_encode($_cachekey).".kvfcache", serialize(array("timeout" => time() + $_cachetimeout, "data" => $_cachevalue))))
    {
      return true;
    }
    else
    {
      return false;
    }
  }

  /**
   * Tries to expire an existing cache
   *
   * @param string $_cachekey
   * @return boolean
   */
  public static function ExpireCache($_cachekey)
  {
    if(!is_string($_cachekey))
    {
      return false;
    }

    $fn = CONFIG_CACHE::DIR."/".base64_encode($_cachekey).".kvfcache";
    if(file_exists($fn))
    {
      return unlink($fn);
    }
    else
    {
      return true;
    }
  }
}
?>
