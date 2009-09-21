<?php
/**
 * KvScheduler - PDF Template
 * @package KvScheduler
 * @subpackage Modules.Lib
 * @author Greg McWhirter <gsmcwhirter@gmail.com>
 * @version 1.0
 */

/**
 * PDF template
 *
 * @package KvScheduler
 * @subpackage Modules.Lib
 */
class PDF extends FPDF{

  /**
   * Set a common header
   *
   */
  function Header(){
    $this->SetFont('Helvetica', '', 10);
    $this->Cell(0, 10, 'KvScheduler Reports', 0, 1);
  }

  /**
   * Set a common footer
   *
   */
  function Footer(){
    # Position at 1.0 cm from bottom
    $this->SetY(-10);

    # Helvetica italic 8
    $this->SetFont('Helvetica', '', 10);

    # Page number
    $this->Cell(0, 10, 'Page ' . $this->PageNo(), 0, 0, 'C');
  }
}

?>
