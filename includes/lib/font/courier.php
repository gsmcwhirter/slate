<?php
/**
 * FPDF Font
 * @package KvScheduler
 * @subpackage Fonts
 * @author Greg McWhirter <gsmcwhirter@gmail.com>
 * @version 1.0
 */

for($i=0;$i<=255;$i++)
	$fpdf_charwidths['courier'][chr($i)]=600;
$fpdf_charwidths['courierB']=$fpdf_charwidths['courier'];
$fpdf_charwidths['courierI']=$fpdf_charwidths['courier'];
$fpdf_charwidths['courierBI']=$fpdf_charwidths['courier'];
?>
