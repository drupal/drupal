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

    ### Extract first day of the month:
    $first = date("w", mktime(0, 0, 0, $month, 1, $year));
        
    ### Extract last day of the month:
    $last = date("t", mktime(0, 0, 0, $month, 1, $year));

    ### Calculate previous and next months dates:
    $prev = mktime(0, 0, 0, $month - 1, 1, $year);
    $next = mktime(0, 0, 0, $month + 1, 1, $year);

    ### Generate calendar header:
    print "<TABLE WIDTH=\"160\" BORDER=\"1\" CELLSPACING=\"0\" CELLPADDING=\"2\">";
    print " <TR><TH COLSPAN=\"7\"><A HREF=\"$PHP_SELF?date=$prev\">&lt;&lt;</A> &nbsp; ". date("F Y", $this->date) ." &nbsp; <A HREF=\"$PHP_SELF?date=$next\">&gt;&gt;</A></TH></TR>";
    print " <TR><TH>S</TH><TH>M</TH><TH>T</TH><TH>W</TH><TH>T</TH><TH>F</TH><TH>S</TH></TR>\n";
 
    ### Initialize temporary variables:
    $day = 1;
    $weekday = $first;
    $state = 1;
   
    ### Loop through all the days of the month:
    while ($day <= $last) {
      ### Set up blank days for first week of the month:
      if ($state == 1) {
        print "<TR><TD COLSPAN=\"$first\">&nbsp</TD>";
        $state = 2;
      }
        
      ### Start every week on a new line:
      if ($weekday == 0) print "<TR>";
    
      ### Print one cell:
      $date = mktime(0, 0, 0, $month, $day, $year);
      if ($day == date("d", $this->date)) {
        print "<TD ALIGN=\"center\"><B><A HREF=\"$PHP_SELF?date=$date\">$day</A></B></TD>";
      }
      else {
        print "<TD ALIGN=\"center\"><A HREF=\"$PHP_SELF?date=$date\">$day</A></TD>";
      }
     
      ### Start every week on a new line:
      if ($weekday == 6) print "</TR>";
        
      ### Update temporary variables:
      $weekday++;
      $weekday = $weekday % 7;
      $day++;
    }
    
    ### End the calendar:
    if ($weekday != 0) {
      $end = 7 - $weekday;
      print "<TD COLSPAN=\"$end\">&nbsp;</TD></TR>";
    }
    print "</TABLE>";
  }
}



// -----------------------------------------------------------------------
// ---------- TEMPORARY CODE - should be removed after testing -----------
// -----------------------------------------------------------------------

print "<H1>CALENDAR TEST</H1>";

// Code to initialize and display a calendar:
if ($date) $calendar = new calendar($date);
else $calendar = new calendar(time());
$calendar->display();

// Debug output:
print "<P><B>Selected date:</B><BR>". date("l, F d, Y", $date) ."</P>";

?>
