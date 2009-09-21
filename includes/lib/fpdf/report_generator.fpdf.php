<?php
/**
 * KvScheduler - Report PDF generator
 * @package KvScheduler
 * @subpackage Modules.Lib
 * @author Greg McWhirter <gsmcwhirter@gmail.com>
 * @version 1.0
 */

load_files(KVF_INCLUDES."/lib/fpdf/template.fpdf.php");

/**
 * Report generation wrapper
 *
 * @package KvScheduler
 * @subpackage Modules.Lib
 *
 */
abstract class PDF_Reports{

  /**
   * Initial entry point
   *
   * @param string $stat The type of statistic to generate a report for
   * @param array $data The report data
   * @param integer $start The startdate of the report
   * @param integer $stop The stopdate of the report
   * @param boolean $number Whether to include a counts report
   * @param boolean $list Whether to include a list report
   * @return string The name of the PDF file
   */
  public static function generate_pdf($stat,array $data, $start, $stop, $number, $list){
    if(!$number && !$list) { return null; }
    $path = CONFIG::abspath."/pdf/";

    $id = preg_replace("/-/","", TOOLS::date_to_s($start))."to".preg_replace("/-/","", TOOLS::date_to_s($stop));
    $id .= ($number) ? "_Count" : "_NoCount";
    $id .= ($list) ? "_List" : "_NoList";
    $id .= "_Gen".preg_replace("/-/","", TOOLS::date_to_s(TOOLS::date_today())).".pdf";
    $pdf = new PDF('P', 'mm', 'letter');
    $pdf->AddPage();
    $pdf->SetFont('Arial','U',16);
    switch($stat){
      case "user":
        $name = "UserScheduledApptsReport_" . $id;
        $pdf->Cell( 0, 10, "Report on Appointments Scheduled by Users", 0, 1, 'C');
        break;
      case "ticket":
        $name = "TicketApptsReport_" . $id;
        $pdf->Cell( 0, 10, "Report on Appointments for Remedy Tickets", 0, 1, 'C');
        break;
      case "percents":
        $name = "PercentApptsReport_" . $id;
        $pdf->Cell( 0, 10, "Report on Appointment Type Percentages", 0, 1, 'C');
        break;
      case "appttype":
        $name = "ApptTypesReport_" . $id;
        $pdf->Cell( 0, 10, "Report on Appointments by Type", 0, 1, 'C');
        break;
      case "metaloc":
        $name = "LocationApptsReport_" . $id;
        $pdf->Cell( 0, 10, "Report on Appointments by Location and Type", 0, 1, 'C');
        break;
      case "consultant":
        $name = "ConsultantApptsReport_" . $id;
        $pdf->Cell( 0, 10, "Report on Appointments by Consultant and Type", 0, 1, 'C');
        break;
      default:
        $name = "";
    }
    $pdf->SetFont('Arial','',14);
    if($stat != "ticket"){
      $pdf->Cell( 0, 10, TOOLS::date_to_s($start)." to ".TOOLS::date_to_s($stop), 'B', 1, 'C');
    }
    self::pdf_parse_data($pdf, $data, $stat, $number, $list);
    $pdf->Output($path . $name);

    return $name;
  }

  /**
   * Parse the data to make the pdf
   *
   * @param PDF $pdf The PDF file data structure
   * @param array $data The report data
   * @param string $stat The stat to generate
   * @param boolean $number Generate a count report or not
   * @param boolean $list Generate a list report or not
   */
  public static function pdf_parse_data(PDF &$pdf, array &$data, $stat, $number, $list){
    $pdf->SetFont('Arial','', 12);
    switch($stat){
      case "user":
        self::user_output($pdf, $data, $number, $list);
        break;
      case "ticket":
        self::ticket_output($pdf, $data, $number, $list);
        break;
      case "percents":
        self::percents_output($pdf, $data, $number, $list);
        break;
      case "appttype":
        self::appttype_output($pdf, $data, $number, $list);
        break;
      case "metaloc":
        self::metaloc_output($pdf, $data, $number, $list);
        break;
      case "consultant":
        self::consultant_output($pdf, $data, $number, $list);
        break;
    }
  }

  /**#@+
   * @param PDF $pdf The PDF data structure
   * @param array $data The report data
   * @param boolean $number Generate a count report or not
   * @param boolean $list Generate a list report or not
   * @return boolean true
   */
  /**
   * Output to PDF an appointment type report
   *
   */
  protected static function appttype_output(PDF &$pdf,array &$data, $number, $list){
         if ($number) {
          $width = (int)floor($pdf->gregsFullWidth / 4.0);
          $pdf->Ln();
          $pdf->SetFont('Arial','B',14);
          $pdf->Cell( 0, 10, "Appointment Counts By Type", 'B', 1, 'C');
          $pdf->SetFont('Arial','',12);
          foreach($data["number"]["output"] as $type => $report){
            $pdf->Cell( $width, 10, "", 0, 0);
            $pdf->Cell( $width, 10, ucwords($type), 0, 0, 'C');
            $pdf->Cell( $width, 10, $report, 0, 1, 'C');
          }
          $pdf->Cell( $width, 10, "", 0, 0);
          $pdf->SetFont('Arial','B');
          $pdf->Cell( $width, 10, "Cumulative Total", 0, 0, 'R');
          $pdf->Cell( $width, 10, $data["number"]["Total"], 0, 1, 'C');
          $pdf->SetFont('Arial','');
        }

        if ($list) {
          $l1width = $pdf->gregsFullWidth - 4;
          $l2width = $pdf->gregsFullWidth - 6;
          $l3width = $pdf->gregsFullWidth - 8;
          $l4width = $pdf->gregsFullWidth - 10;

          $pdf->Ln();
          $pdf->SetFont('Arial','B',14);
          $pdf->Cell( 0, 10, "Appointment Lists By Type", 'B', 1, 'C');
          $pdf->SetFont('Arial','',12);
          foreach($data["list"]["output"] as $type => $report){
            $pdf->SetFont('Arial','B');
            $pdf->Cell( 2, 10, "", 0,0);
            $pdf->Cell( $l1width, 10, ucwords($type), 'B', 1);
            $pdf->SetFont('Arial','');
            ksort($report);
            foreach($report as $appt_hash){
              self::output_appt_data($pdf, $appt_hash, 4, $l2width);
            }
          }
        }

        return true;
  }

  /**
   * Output to PDF a ticket report
   *
   */
  protected static function ticket_output(PDF &$pdf, array &$data, $number, $list){
    $width = $pdf->gregsFullWidth;
    $l1width = $pdf->gregsFullWidth - 4;
    $l2width = $pdf->gregsFullWidth - 6;

    $pdf->Ln();
    $pdf->SetFont('Arial','B',14);
    $pdf->Cell( 0, 10, "Appointment List by Tickets", 'B', 1, 'C');
    $pdf->SetFont('Arial','',12);

    foreach($data["output"] as $ticket => $report){
      $pdf->SetFont('Arial','B');
      $pdf->Cell( 2, 10, "", 0,0);
      $pdf->Cell( $l1width, 10, "Ticket: ".$ticket, 'B', 1);
      $pdf->SetFont('Arial','');
      ksort($report);
      foreach($report as $appt_hash){
        self::output_appt_data($pdf, $appt_hash, 4, $l2width);
      }
    }

    return true;
  }

  /**
   * Output to PDF a user report
   *
   */
  protected static function user_output(PDF &$pdf, array &$data, $number, $list){
        if ($number) {
          $width = (int)floor($pdf->gregsFullWidth / 4.0);
          $pdf->Ln();
          $pdf->SetFont('Arial','B',14);
          $pdf->Cell( 0, 10, "Appointment Counts By User", 'B', 1, 'C');
          $pdf->SetFont('Arial','',12);
          foreach($data["number"]["output"] as $user => $report){
            $pdf->Cell( $width, 10, "", 0, 0);
            $pdf->Cell( $width, 10, $user, 0, 0, 'C');
            $pdf->Cell( $width, 10, $report, 0, 1, 'C');
          }
          $pdf->Cell( $width, 10, "", 0, 0);
          $pdf->SetFont('Arial','');
        }

        if ($list) {
          $l1width = $pdf->gregsFullWidth - 4;
          $l2width = $pdf->gregsFullWidth - 6;
          $l3width = $pdf->gregsFullWidth - 8;
          $l4width = $pdf->gregsFullWidth - 10;

          $pdf->Ln();
          $pdf->SetFont('Arial','B',14);
          $pdf->Cell( 0, 10, "Appointment Lists By User", 'B', 1, 'C');
          $pdf->SetFont('Arial','',12);
          foreach($data["list"]["output"] as $user => $report){
            $pdf->SetFont('Arial','B');
            $pdf->Cell( 2, 10, "", 0,0);
            $pdf->Cell( $l1width, 10, $user, 'B', 1);
            $pdf->SetFont('Arial','');
            ksort($report);
            foreach($report as $appt_hash){
              self::output_appt_data($pdf, $appt_hash, 4, $l2width);
            }
          }
        }

        return true;
  }

  /**
   * Output to PDF a consultant report
   *
   */
  protected static function consultant_output(PDF &$pdf,array &$data, $number, $list){
        if ($number) {
          $width = (int)floor($pdf->gregsFullWidth / 8.0);
          $pdf->Ln();
          $pdf->SetFont('Arial','B',14);
          $pdf->Cell( 0, 10, "Appointment Counts By Consultant and Type", 'B', 1, 'C');
          $pdf->SetFont('Arial','',12);
          foreach($data["number"]["output"] as $rc_name => $rc_hash){
            $pdf->Cell( $width, 10, "", 0, 0);
            $pdf->Cell( 6 * $width, 10, $rc_name, 0, 1);
            foreach($rc_hash as $type => $report){
              $pdf->Cell( 2 * $width, 10, "", 0, 0);
              $pdf->Cell( 2 * $width, 10, ucwords($type), 0, 0, 'C');
              $pdf->Cell( 2 * $width, 10, $report, 0, 1, 'C');
            }
            $pdf->SetFont('Arial','');
          }
        }

        if ($list) {
          $l1width = $pdf->gregsFullWidth - 4;
          $l2width = $pdf->gregsFullWidth - 6;
          $l3width = $pdf->gregsFullWidth - 8;
          $pdf->Ln();
          $pdf->SetFont('Arial','B',14);
          $pdf->Cell( 0, 10, "Appointment Lists By Consultant and Type", 'B', 1, 'C');
          $pdf->SetFont('Arial','',12);
          foreach($data["list"]["output"] as $rc_name => $rc_hash){
            $pdf->Cell( 2, 10, "", 0,0);
            $pdf->Cell( $l1width, 10, $rc_name, 0, 1);
            foreach($rc_hash as $type => $report){
              $pdf->SetFont('Arial','B');
              $pdf->Cell( 4, 10, "", 0,0);
              $pdf->Cell( $l2width, 10, ucwords($type), 0, 1);
              $pdf->SetFont('Arial','');
              ksort($report);
              foreach($report as $appt_hash){
                self::output_appt_data($pdf, $appt_hash, 6, $l3width);
              }
            }
          }
        }

        return true;
  }

  /**
   * Output to PDF a percentages report
   *
   */
  protected static function percents_output(PDF &$pdf, array &$data, $number, $list){
        $width = (int)floor($pdf->gregsFullWidth / 4.0);
        $pdf->Ln();
        $pdf->Cell( $width, 10, "", 0, 0);
        $pdf->SetFont('Arial','B');
        $pdf->Cell( $width, 10, "Appointment Type", 'B', 0, 'C');
        $pdf->Cell( $width, 10, "Percent", 'B', 0, 'C');
        $pdf->SetFont('Arial','');
        $pdf->Cell( $width, 10, "", 0, 1);
        foreach($data["output"] as $type => $pct){
          $pdf->Cell( $width, 10, "", 0, 0);
          $pdf->Cell( $width, 10, ucwords($type), 0, 0, 'C');
          $pdf->Cell( $width, 10, $pct."%", 0, 0, 'C');
          $pdf->Cell( $width, 10, "", 0, 1);
        }
        $pdf->Cell( $width, 10, "", 0, 0);
        $pdf->Cell( $width, 10, "Total Appointments:", 0, 0, 'C');
        $pdf->Cell( $width, 10, $data["Total"], 0, 0, 'C');
        $pdf->Cell( $width, 10, "", 0, 1);
        return true;
  }

  /**
   * Output to PDF a Location report
   *
   */
  protected static function metaloc_output(PDF &$pdf,array &$data, $number, $list){
        if ($number) {
          $width = (int)floor($pdf->gregsFullWidth / 8.0);
          $pdf->Ln();
          $pdf->SetFont('Arial','B',14);
          $pdf->Cell( 0, 10, "Appointment Counts By Location and Type", 'B', 1, 'C');
          $pdf->SetFont('Arial','',12);
          foreach($data["number"]["output"] as $loc_name => $loc_hash){
            $pdf->Cell( $width, 10, "", 0, 0);
            $pdf->Cell( 6 * $width, 10, $loc_name, 0, 1);
            foreach($loc_hash as $type => $report){
              $pdf->Cell( 2 * $width, 10, "", 0, 0);
              $pdf->Cell( 2 * $width, 10, ucwords($type), 0, 0, 'C');
              $pdf->Cell( 2 * $width, 10, $report, 0, 1, 'C');
            }
            $pdf->SetFont('Arial','');
          }
        }

        if ($list) {
          $l1width = $pdf->gregsFullWidth - 4;
          $l2width = $pdf->gregsFullWidth - 6;
          $l3width = $pdf->gregsFullWidth - 8;
          $l4width = $pdf->gregsFullWidth - 10;
          $l5width = $pdf->gregsFullWidth - 12;

          $pdf->Ln();
          $pdf->SetFont('Arial','B',14);
          $pdf->Cell( 0, 10, "Appointment Lists By Location and Type", 'B', 1, 'C');
          $pdf->SetFont('Arial','',12);
          ksort($data["list"]["output"]);
          foreach($data["list"]["output"] as $loc_name => $loc_hash){
            $pdf->Cell( 2, 10, "", 0,0);
            $pdf->Cell( $l1width, 10, $loc_name, 0, 1);
            foreach($loc_hash as $type => $report){
              $pdf->SetFont('Arial','B');
              $pdf->Cell( 4, 10, "", 0,0);
              $pdf->Cell( $l2width, 10, ucwords($type), 0, 1);
              $pdf->SetFont('Arial','');
              ksort($report);
              foreach($report as $appt_hash){
                self::output_appt_data($pdf, $appt_hash, 6, $l3width);
              }
            }
          }
        }

        return true;
  }
  /**#@-*/

  /**
   * Output data for an appointment for some report type
   *
   * @param PDF $pdf The PDF data structure
   * @param array $appt_hash Some data to output
   * @param integer $spad The left-padding
   * @param integer $swidth The cell width
   * @return boolean true
   */
  protected static function output_appt_data(PDF &$pdf, array &$appt_hash, $spad, $swidth){
    if ($appt_hash["appt"]->tm_type == "Meeting" || $appt_hash["appt"]->tm_type == "Meecket") {
      $pdf->Cell( $spad, 8, "", 0,0);
      $pdf->Cell( $swidth, 8, "Meeting: ".$appt_hash["appt"]->tm->subject." from ".TOOLS::time_to_s(TOOLS::string_to_time($appt_hash["appt"]->starttime), true)." to ".TOOLS::time_to_s(TOOLS::string_to_time($appt_hash["appt"]->stoptime), true)."", 0, 1);
    } elseif ($appt_hash["appt"]->tm_type == "Ticket") {
      $pdf->Cell( $spad, 8, "", 0,0);
      $pdf->Cell( $swidth, 8, "Ticket: ".$appt_hash["appt"]->tm->remedy_ticket." from ".TOOLS::time_to_s(TOOLS::string_to_time($appt_hash["appt"]->starttime), true)." to ".TOOLS::time_to_s(TOOLS::string_to_time($appt_hash["appt"]->stoptime), true)."", 0, 1);
    }

    $pdf->Cell( $spad + 2, 8, "", 0,0);
    $pdf->Cell( $swidth - 2, 8, "Location: ".$appt_hash["appt"]->locdetails." ".Location::select_name($appt_hash["appt"]), 'L', 1);

    if ($appt_hash["appt"]->repeat == "TRUE") {
      $pdf->Cell( $spad + 2, 8, "", 0,0);
      $dcontent = "Every ".(($appt_hash["appt"]->repetition_week == "1") ? "week" : $appt_hash["appt"]->repetition_week." weeks")." on ".implode(", ", TOOLS::array_collect(explode(",", $appt_hash["appt"]->repetition_day), '$i','TOOLS::$dayabbrs[TOOLS::weekday_reverse($i)]'))." from ".$appt_hash["appt"]->startdate." until ".$appt_hash["appt"]->stopdate;
      $pdf->Cell( $swidth - 2, 8, $dcontent, 'L', 1);
      if(count($appt_hash["minus"]) > 0) {
        $pdf->Cell( $spad + 2, 8, "", 0,0);
        $pdf->Cell( 2, 8, "", 'L',0);
        $pdf->Cell( $swidth - 4, 8, "Except Dates: ".implode(", ", $appt_hash["minus"]), 0, 1);
      }
    } else {
      $pdf->Cell( $spad + 2, 8, "", 0,0);
      $pdf->Cell( $swidth - 2, 8, "Date: ".$appt_hash["appt"]->startdate, 'L', 1);
    }
    $pdf->Cell( $spad + 2, 8, "", 0,0);
    $pdf->Cell( $swidth - 2, 8, "Consultant: ".implode(", ", TOOLS::array_collect($appt_hash["appt"]->consultants, '$r', 'Consultant::select_name($r)')), 'L', 1);

    return true;
  }

  /**
   * Generate a PDF report for consultants' hours
   *
   * @param array $data The report data
   * @param integer $start The starting date of the report
   * @param integer $stop The ending date of the report
   * @param mixed $sem The semester data structure, if any
   * @return string The name of the PDF file
   */
  public static function generate_pdf_rch(array $data, $start, $stop, $sem = null){
    $path = CONFIG::abspath."/pdf/";

    $id = preg_replace("/-/","", TOOLS::date_to_s($start))."to".preg_replace("/-/","", TOOLS::date_to_s($stop));
    $id .= "_Gen".preg_replace("/-/","", TOOLS::date_to_s(TOOLS::date_today())).".pdf";
    $pdf = new PDF('P', 'mm', 'letter');
    $pdf->AddPage();
    $pdf->SetFont('Arial','U',16);
    $name = "ConsultantHoursReport_" . $id;
    $pdf->Cell( 0, 10, "Report on Consultant Hours", 0, 1, 'C');
    $pdf->SetFont('Arial','',14);
    $pdf->Cell( 0, 10, TOOLS::date_to_s($start)." to ".TOOLS::date_to_s($stop).(($sem) ? " (".Semester::select_name($sem).")" : ""), 'B', 1, 'C');

    $l1width = $pdf->gregsFullWidth - 4;
    $l2width = $pdf->gregsFullWidth - 6;
    $pdf->Ln();
    foreach($data["info"] as $rc => $week){
      $pdf->SetFont('Arial','B',12);
      $pdf->Cell( 2, 10, "", 0,0);
      $pdf->Cell( $l1width, 10, Consultant::select_name($data["consultants"][$rc]), 0, 1);
      $pdf->SetFont('Arial','');
      foreach($week as $wday => $wdayinfo){
        if(count($wdayinfo) > 0){
          $pdf->Cell( 4, 10, "", 0,0);
          $pdf->Cell( $l2width, 10, TOOLS::$daynames[$wday] .": ". implode(", ", TOOLS::array_collect($wdayinfo, '$e', 'TOOLS::time_to_s($e[0], true)." - ".TOOLS::time_to_s($e[1], true)." ".(($e[2]) ? "(ONCALL)" : "")')), 0, 1);
        }
      }
    }

    $pdf->Output($path . $name);

    return $name;
  }

}

?>
