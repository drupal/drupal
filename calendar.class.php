<?

class calendar {
  var $date;

  function calendar($date) {
    $this->date = $date;
  }

  function display() {
    global $PHP_SELF;

    ### Extract information from the given date:
    $month  = date("n", $this->date);
    $year = date("Y", $this->date);
    $day = date("d", $this->date);

    ### Extract first day of the month:
    $first = date("w", mktime(0, 0, 0, $month, 1, $year));
        
    ### Extract last day of the month:
    $last = date("t", mktime(0, 0, 0, $month, 1, $year));

    ### Calculate previous and next months dates:
    $prev = mktime(0, 0, 0, $month - 1, $day, $year);
    $next = mktime(0, 0, 0, $month + 1, $day, $year);

    ### Generate calendar header:
    $output .= "<TABLE WIDTH=\"100%\" BORDER=\"1\" CELLSPACING=\"0\" CELLPADDING=\"1\">";
    $output .= " <TR><TD ALIGN=\"center\" COLSPAN=\"7\"><A HREF=\"$PHP_SELF?date=$prev\">&lt;&lt;</A> &nbsp; ". date("F Y", $this->date) ." &nbsp; <A HREF=\"$PHP_SELF?date=$next\">&gt;&gt;</A></TH></TR>";
    $output .= " <TR><TD ALIGN=\"center\">S</TD><TD ALIGN=\"center\">M</TD><TD ALIGN=\"center\">T</TD><TD ALIGN=\"center\">W</TD><TD ALIGN=\"center\">T</TD><TD ALIGN=\"center\">F</TD><TD ALIGN=\"center\">S</TD></TR>\n";
 
    ### Initialize temporary variables:
    $nday = 1;
    $sday = $first;
   
    ### Loop through all the days of the month:
    while ($nday <= $last) {
      ### Set up blank days for first week of the month:
      if ($first) {
        $output .= "<TR><TD COLSPAN=\"$first\">&nbsp</TD>";
        $first = 0;
      }
        
      ### Start every week on a new line:
      if ($sday == 0) $output .=  "<TR>";
    
      ### Print one cell:
      $date = mktime(0, 0, 0, $month, $nday, $year);
      if ($nday == $day) $output .= "<TD ALIGN=\"center\"><B>$nday</B></TD>";
      else if ($date > time()) $output .= "<TD ALIGN=\"center\">$nday</TD>";
      else $output .= "<TD ALIGN=\"center\"><A HREF=\"$PHP_SELF?date=$date\">$nday</A></TD>";
     
      ### Start every week on a new line:
      if ($sday == 6) $output .=  "</TR>";
        
      ### Update temporary variables:
      $sday++;
      $sday = $sday % 7;
      $nday++;
    }
    
    ### Finish the calendar:
    if ($sday != 0) {
      $end = 7 - $sday;
      $output .= "<TD COLSPAN=\"$end\">&nbsp;</TD></TR>";
    }
    $output .= "</TABLE>";

    ### Return calendar:
    return $output;
  }
}

?>
