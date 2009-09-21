<?php
/**
 * KvScheduler - Index file
 * @package KvScheduler
 * @author Greg McWhirter <gsmcwhirter@gmail.com>
 * @version 1.0
 */

/**
 * Are we in debug mode?
 */
define("DEBUG", false);

/**
 * require the framework config and the cataclysm of events that causes
 */
require_once "./kvframework/config.php";

/**
 * Start the wheel rolling
 */
kvframework_router::do_route();

?>
