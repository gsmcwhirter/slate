<?php
/**
 * KvScheduler - Application Config 1 - Loads files
 * @package KvScheduler
 * @author Greg McWhirter <gsmcwhirter@gmail.com>
 * @version 1.0
 */

load_files(
  KVF_INCLUDES."/lib/tools.class.php",
  KVF_INCLUDES."/lib/callbacks.class.php",
  KVF_INCLUDES."/lib/apdata_struct.class.php",
  KVF_INCLUDES."/lib/chdata_struct.class.php",
  KVF_INCLUDES."/lib/ophdata_struct.class.php",
  KVF_INCLUDES."/lib/aphdata_struct.class.php",
  KVF_INCLUDES."/lib/myfunctions.class.php",
  KVF_INCLUDES."/lib/aphash.class.php",
  KVF_INCLUDES."/lib/mailer.class.php",
  KVF_INCLUDES."/lib/user.class.php",
  KVF_INCLUDES."/lib/lockouts.class.php",
  KVF_INCLUDES."/lib/apptchecks.class.php",
  KVF_INCLUDES."/lib/backupfunctions.class.php",
  KVF_INCLUDES."/lib/thing.class.php",
  KVF_INCLUDES."/lib/fpdf.php",
  KVF_INCLUDES."/lib/idbwrapper.interface.php",

  KVF_INCLUDES."/lib/appointment.class.php",
  KVF_INCLUDES."/lib/appttype.class.php",
  KVF_INCLUDES."/lib/appthour.class.php",
  KVF_INCLUDES."/lib/helpdesker.class.php",
  KVF_INCLUDES."/lib/location.class.php",
  KVF_INCLUDES."/lib/loczone.class.php",
  KVF_INCLUDES."/lib/loctag.class.php",
  KVF_INCLUDES."/lib/meecket.class.php",
  KVF_INCLUDES."/lib/meeting.class.php",
  KVF_INCLUDES."/lib/metaloc.class.php",
  KVF_INCLUDES."/lib/ophour.class.php",
  KVF_INCLUDES."/lib/consultant.class.php",
  KVF_INCLUDES."/lib/consultantappt.class.php",
  KVF_INCLUDES."/lib/consultanthour.class.php",
  KVF_INCLUDES."/lib/semester.class.php",
  KVF_INCLUDES."/lib/supervisor.class.php",
  KVF_INCLUDES."/lib/sysop.class.php",
  KVF_INCLUDES."/lib/tag.class.php",
  KVF_INCLUDES."/lib/ticket.class.php",

  KVF_INCLUDES."/configs/app2.config.php"
);
?>
