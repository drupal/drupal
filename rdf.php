<?

include "functions.inc";
include "theme.inc";

class rdf {
  // Contains the raw rdf file:
  var $data;

  // Contains the parsed rdf file:
  var $title;   // website name
  var $items;   // latest headlines

  function url2sql($site, $timout = 10) {
    ### Connect to database:
    dbconnect();

    ### Get channel info:
    $result = mysql_query("SELECT * FROM channel WHERE site = '$site'");

    if ($channel = mysql_fetch_object($result)) {
      ### Decode URL:
      $url = parse_url($channel->rdf);
      $host = $url[host];
      $port = $url[port] ? $url[port] : 80;
      $path = $url[path];
     
      // print "<PRE>$url - $host - $port - $path</PRE>";
 
      ### Retrieve data from website:
      $fp = fsockopen($host, $port, &$errno, &$errstr, $timout);

      if ($fp) {
        ### Get data from URL:
        fputs($fp, "GET $path HTTP/1.0\n");
        fputs($fp, "User-Agent: headline grabber\n");
        fputs($fp, "Host: ". $host ."\n");
        fputs($fp, "Accept: */*\n\n");

        while(!feof($fp)) $data .= fgets($fp, 128);
        
        // print "<PRE>$data</PRE><HR>";

        if (strstr($data, "200 OK")) {

          ### Remove existing entries:
          $result = mysql_query("DELETE FROM headlines WHERE id = $channel->id");

          ### Strip all 'junk':
          $data = ereg_replace("<?xml.*/image>", "", $data);
          $data = ereg_replace("</rdf.*", "", $data);
          $data = chop($data);
     
          ### Iterating through our data processing each entry/item:
          $items = explode("</item>", $data);
          $number = 0;

          for (reset($items); $item = current($items); next($items)) {
            ### Extract data:
            $link = ereg_replace(".*<link>", "", $item);
            $link = ereg_replace("</link>.*", "", $link);
            $title = ereg_replace(".*<title>", "", $item);
            $title = ereg_replace("</title>.*", "", $title); 

            ### Clean headlines:
            $title = stripslashes(fixquotes($title));
           
            ### Count the number of stories:
            $number += 1;

            ### Insert item in database:
            $result = mysql_query("INSERT INTO headlines (id, title, link, number) VALUES('$channel->id', '$title', '$link', '$number')");
          }
 
          ### Mark channels as being updated:
          $result = mysql_query("UPDATE channel SET timestamp = '". time() ."' WHERE id = $channel->id");
        }
        else print "<HR>RDF parser: 404 error?<BR><BR><PRE>$data</PRE><HR>";
      }
    }
  }

  function displayHeadlines($site, $timout = 1800) {
    global $theme;

    ### Connect to database:
    dbconnect();

    ### Get channel info:
    $result = mysql_query("SELECT * FROM channel WHERE site = '$site'");

    if ($channel = mysql_fetch_object($result)) {

      ### Check to see whether we have to update our headlines first:
      if (time() - $channel->timestamp > $timout) $this->url2sql($site);

      ### Grab headlines from database:
      $result = mysql_query("SELECT * FROM headlines WHERE id = $channel->id ORDER BY number");
      while ($headline = mysql_fetch_object($result)) {
        $content .= "<LI><A HREF=\"$headline->link\">$headline->title</A></LI>";
      }
      ### Add timestamp:
      $update = round((time() - $channel->timestamp) / 60);
      $content .= "<P ALIGN=\"right\">[ <A HREF=\"rdf.php?op=reset&id=$channel->id\"><FONT COLOR=\"$theme->hlcolor2\">reset</FONT></A> | updated $update min. ago ]</P>";      
      
      ### Display box:
      $theme->box("$channel->site", $content);
    }
    else print "<P>Warning: something whiched happened: specified channel could not be found in database.</P>";
  }

  function addChannel($site, $url, $rdf) {
    ### Connect to database:
    dbconnect();

    ### Add channel:    
    $query = mysql_query("INSERT INTO channel (site, url, rdf, timestamp) VALUES ('$site', '$url', '$rdf', now())");
  }

  function resetChannel($id) {
    ### Connect to database:
    dbconnect();

    ### Delete headlines:
    $result = mysql_query("DELETE FROM headlines WHERE id = $id");    

    ### Mark channel as invalid to enforce an update:
    $result = mysql_query("UPDATE channel SET timestamp = 42 WHERE id = $id");    
  }
}

function adminAddChannel() {
  ?>
   <HR>
   <FORM ACTION="rdf.php" METHOD="post">
   <P>
    <B>Site name:</B><BR>
    <INPUT TYPE="text" NAME="site" SIZE="50">
   </P>

   <P>
    <B>URL:</B><BR>
    <INPUT TYPE="text" NAME="url" SIZE="50">
   </P>

   <P>
    <B>RDF file:</B><BR>
    <INPUT TYPE="text" NAME="rdf" SIZE="50">
   </P>
   <INPUT TYPE="submit" NAME="op" VALUE="Add RDF channel"> 
   </FORM>
  <?
}

function adminDisplayAll() {
  ### Connect to database:
  dbconnect();

  ### Get channel info:
  $result = mysql_query("SELECT * FROM channel ORDER BY id");

  print "<TABLE BORDER=\"0\">";
  while ($channel = mysql_fetch_object($result)) {
    if ($state % 3 == 0) print " <TR>";

    print " <TD ALIGN=\"center\" VALIGN=\"top\" WIDTH=\"33%\">";
    $rdf = new rdf();
    $rdf->displayHeadlines($channel->site);
    print " </TD>";

    if ($state % 3 == 2) print " </TR>";

    $state += 1;
  }  
  print "</TABLE>";
}

function adminDisplayInfo() {
  ?>
  <H1>Headlines</H1>
   <H3>Concept</H3>
   <P>
    RDF support can change a portal in a significant way: third party websites
    can become <I>channels</I> in our portal without having to make 'real' deals
    and with a minimum of extra work.  All they need to do is to publish an RDF,
    so we can include their latest updates in our portal.  Yet another easy way 
    to add content.
   </P>
   <P>
    That in and of itself is interesting, but it's not half so interesting as
    the fact that other sites can include our headlines as well.  Anyone can 
    grab our RDF, anyone can parse it, and anyone can put a list of our
    headlines.  Yet another way to generate more traffic.
   </P>

   <H3>Features</H3>
   <P>
    One of the most important features (if not the most important) is
    chaching support.  To avoid bogging down other portals with a continous 
    stream of headline grabbing, all headlines are cached and refreshed once
    in a while.  The 'while' can be costumized but is set to 30 minutes by
    default.
   </P>
   <P>
    You can reset a channel, that is force to update a channels headlines
    and you can add new channels.  If you don't know what channel to add, 
    check <A HREF="http://www.xmltree.com/">http://www.xmltree.com/</A>.
    Make sure you don't add anything except valid RDF files!
   </P>

   <H3>Status</H3>
   <P>
    The RDF parser is still in beta and needs proper integration in the engine.
    Until then this test page generates nothing more then an overview off all 
    subscribed channels along with their headlines: handy for news squatting. ;)
   </P>
   <P>
    RDF files are non-proprietary and publically available.  Unfortunatly,
    RDF is not the only standard: another commonly used format is RSS which
    would be nice to support as well.
   </P>
   <HR>
 <?
}

$theme->header();

switch($op) {
  case "reset":
    $channel = new rdf();
    $channel->resetChannel($id);
    print "<H2>channel has been reset</H2>";
    print "<A HREF=\"rdf.php\">back</A>";
    break;
  case "Add RDF channel":
    $channel = new rdf();
    $channel->addChannel($site, $url, $rdf);
    // fall through:
  default:
    adminDisplayInfo();
    adminDisplayAll();
    adminAddChannel();
}

$theme->footer();

?>
