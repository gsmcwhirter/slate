<?php

function my_array_walk(&$value, $key, $function)
{
  $value = $function($value);
}

function hex2bin($data) {
  $newdata = "";
  $len = strlen($data);
  for($i=0;$i<$len;$i+=2) {
    $newdata .= pack("C",hexdec(substr($data,$i,2)));
  }
  return $newdata;
}

function ZeroFill($str, $len)
{
  for($i = strlen($str); $i < $len; $i++)
  {
    $str = "0".$str;
  }
  return $str;
}

function HideEmail($email)
{
  return preg_replace(array("#@#","#\.#"), array(" -at- "," -dot- "), $email);
}

function UnscrubValue($val)
{
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
        if ( get_magic_quotes_gpc() )
        {
            $val = stripslashes($val);
        }

        return $val;
}

function FormatSize($size, $round = 0)
{
    //Size must be bytes!
    $sizes = array('B', 'kB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB');
    $total = count($sizes);
    for ($i=0; $size > 1024 && $i < $total; $i++)
    {
      $size /= 1024;
    }
    return round($size,$round).$sizes[$i];
}

function AssocArraySum(array $array, $key)
{
  $ret = "";
  foreach($array as $entry)
  {
    if(array_key_exists($key, $entry))
    {
      $ret += $entry[$key];
    }
  }
  return $ret;
}

function ObjArraySum(array $array, $key)
{
  $ret = "";
  foreach($array as $entry)
  {
    $ret += $entry->$key;
  }
  return $ret;
}

function FileMimeType($file)
{
  static $finfo;
  if(extension_loaded("fileinfo") || @dl("fileinfo.so"))
  {
    if(!($finfo instanceOf finfo))
    {
      $finfo = new finfo(FILEINFO_MIME|FILEINFO_SYMLINK|FILEINFO_PRESERVE_ATIME, SETTINGS::$MimeMagicFile);
    }

    if($finfo !== false)
    {
      return $finfo->file(realpath($file));
    }
  }

  if ( function_exists('mime_content_type '))
  {
    return mime_content_type(realpath($file));
  }
  else
  {
    return trim(exec('file -bi '.escapeshellarg( realpath($file) )));
  }
}
