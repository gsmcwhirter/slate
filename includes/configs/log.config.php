<?php
/**
 * KvScheduler - Logs config
 * @package KvScheduler
 * @author Greg McWhirter <gsmcwhirter@gmail.com>
 * @version 1.0
 */

$LOGINFO->types = array("requests" => "local", "sql" => "local", "maillog" => "local", "email" => "email");
$LOGINFO->configs = array(
  "n:requests" => array("local_set_logfile_name" => "requests.log"),
  "n:sql" => array("local_set_logfile_name" => "sql.log"),
  "n:maillog" => array("local_set_logfile_name" => "mail.log"),
  "t:local" => array("enable_queue_mode" => null, "set_thresh" => ((DEBUG) ? KVF_LOG_LDEBUG : KVF_LOG_LERROR), "set_error_condition_trigger_thresh" => KVF_LOG_LERROR, "set_error_condition_thresh" => KVF_LOG_LDEBUG),
  "t:email" => array("enable_queue_mode" => null, "set_thresh" => KVF_LOG_LERROR, "set_error_condition_trigger_thresh" => KVF_LOG_LALERT, "set_error_condition_thresh" => KVF_LOG_LDEBUG, "email_set_sender" => "Scheduler Logger <admin@mydomain.com>")
);
$LOGINFO->default = "requests";
?>
