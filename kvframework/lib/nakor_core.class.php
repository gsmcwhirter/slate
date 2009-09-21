<?php
/**
 * Nakor's Input Scrubberr
 * @package KvFramework
 * @subpackage Lib
 * @author Nakor <nakor@clantemplates.com>
 * @copyright Copyright &copy; 2005, Nakor
 * @license MIT License
 * @version 1.0
 */
# ************************************************************************** #
# Core class
# ************************************************************************** #
# Version:        1.0
# Date Started:   May 1st, 2005
# Date Finished:  May 2nd, 2005
# Author:         Nakor
# Email:          Nakor@clantemplates.com
# ************************************************************************** #
# Miscellaneous core methods.
# ************************************************************************** #


# ************************************************************************** #
# Start the core class
# ************************************************************************** #

/**
 * Nakor's Input scrubber
 *
 * @package KvFramework
 * @subpackage Lib
 */
class nakor_core
{

    private $get_magic_quotes;

    /**
    * Clean Input
    *
    * Removes harmful tags from $HTTP_REQUEST_VARS
    *
    * @return    array
    */

    public function clean_input($type)
    {
        $this->get_magic_quotes = get_magic_quotes_gpc();

        $return = array();


      if($type == "GET"){
        # ************************************************************************** #
        #
        # Clean $_GET data
        #
        # ************************************************************************** #

        /*if( is_array($_GET) )
        {
            while( list($k, $v) = each($_GET) )
            {
                if ( is_array($_GET[$k]) )
                {
                    while( list($k2, $v2) = each($_GET[$k]) )
                    {
                        $return[ $this->clean_key($k) ][ $this->clean_key($k2) ] = $this->clean_value($v2);
                    }
                }
                else
                {
                    $return[ $this->clean_key($k) ] = $this->clean_value($v);
                }
            }
        }*/

        $return = $this->CleanArrayRec($_GET);

      }
      elseif($type == "POST"){

        # ************************************************************************** #
        #
        # Clean $_POST data
        #
        # ************************************************************************** #

        /*if( is_array($_POST) )
        {
            while( list($k, $v) = each($_POST) )
            {
                if ( is_array($_POST[$k]) )
                {
                    while( list($k2, $v2) = each($_POST[$k]) )
                    {
                        $return[ $this->clean_key($k) ][ $this->clean_key($k2) ] = $this->clean_value($v2);
                    }
                }
                else
                {
                    $return[ $this->clean_key($k) ] = $this->clean_value($v);
                }
            }
        }*/

        $return = $this->CleanArrayRec($_POST);
      }

      $return['request_method'] = strtolower($_SERVER['REQUEST_METHOD']);

      return $return;
    }

    /**
      * Recursively clean an array
      *
      * @param array $array
      * @return array
      */
     private function CleanArrayRec(array $array)
     {
       $ret = array();
       foreach($array as $k => $v)
       {
         $ret[$this->clean_key($k)] = (is_array($v)) ? $this->CleanArrayRec($v) : $this->clean_value($v);
       }

       return $ret;
     }



    /**
    * Clean Key
    *
    * Removes harmful tags from a variable key
    *
    * @param    string
    *
    * @return    string
    */

    private function clean_key($key)
    {
        if ($key == "")
        {
            return "";
        }

        $key = htmlspecialchars(urldecode($key));
        $key = preg_replace( "/\.\./"           , ""  , $key );
        $key = preg_replace( "/\_\_(.+?)\_\_/"  , ""  , $key );
        $key = preg_replace( "/^([\w\.\-\_]+)$/", "$1", $key );

        return $key;
    }



    /**
    * Clean Value
    *
    * Removes harmful tags from a variable value
    *
    * @param    string
    *
    * @return    string
    */

    public function clean_value($val)
    {
        if ($val == "")
        {
            return "";
        }

        $val = str_replace( "&#032;", " ", $val );
        $val = str_replace( "&"            , "&amp;"         , $val );
        $val = str_replace( "<!--"         , "&#60;&#33;--"  , $val );
        $val = str_replace( "-->"          , "--&#62;"       , $val );
        $val = preg_replace( "/<script/i"  , "&#60;script"   , $val );
        $val = str_replace( ">"            , "&gt;"          , $val );
        $val = str_replace( "<"            , "&lt;"          , $val );
        $val = str_replace( "\""           , "&quot;"        , $val );
        $val = preg_replace( "/\n/"        , "<br />"        , $val );
        $val = preg_replace( "/\\\$/"      , "&#036;"        , $val );
        $val = preg_replace( "/\r/"        , ""              , $val );
        $val = str_replace( "!"            , "&#33;"         , $val );
        $val = str_replace( "'"            , "&#39;"         , $val );
        $val = preg_replace("/&amp;#([0-9]+);/s", "&#\\1;", $val );

        // Strip slashes if not already done so.
        if ( $this->get_magic_quotes )
        {
            $val = stripslashes($val);
        }

        // Swap user inputted backslashes
        $val = preg_replace( "/\\\(?!&amp;#|\?#)/", "&#092;", $val );

        return $val;
    }

    public function unclean_value($val){
      if ($val == "")
        {
            return "";
        }

        $val = str_replace( "&amp;"            , "&"         , $val );
        $val = str_replace( "&#60;&#33;--"     , "<!--"      , $val );
        $val = str_replace( "--&#62;"          , "-->"       , $val );
        $val = str_replace( "&#60;script"     , "<script", $val );
        $val = str_replace( "&gt;"             , ">"         , $val );
        $val = str_replace( "&lt;"            , "<"          , $val );
        $val = str_replace( "&quot;"           , "\""        , $val );
        $val = str_replace( "<br />"        , "\n"        , $val );
        $val = str_replace( "&#036;"      , "\$"        , $val );
        $val = str_replace( "&#33;"            , "!"         , $val );
        $val = str_replace( "&#39;"            , "'"         , $val );

        // Strip slashes if not already done so.
        if ( $this->get_magic_quotes )
        {
            $val = stripslashes($val);
        }

        return $val;
    }

}
?>
