<?


class backend {

  // Channel properties:
  var $id;
  var $url;
  var $site;
  var $file;
  var $contact;
  var $timestamp;

  // Contains the raw rdf/rss/xml file:
  var $data;

  // Contains the parsed rdf/rss/xml file:
  var $headlines = array();   // latest headlines


  #####
  # Syntax.......: backend(...);
  # Description..: Constructor - initializes the internal variables.
  #
  function backend($id, $site, $url, $file, $contact, $timout = 1800) {
    ### Get channel info:
    $result = db_query("SELECT * FROM channel WHERE id = '$id' OR site = '$site'");

    if ($channel = db_fetch_object($result)) {
      ### Initialize internal variables:
      $this->id = $channel->id;
      $this->site = $channel->site;
      $this->file = $channel->file;
      $this->url = $channel->url;
      $this->contact = $channel->contact;
      $this->timestamp = $channel->timestamp;

      ### Check to see whether we have to update our headlines first:
      if (time() - $this->timestamp > $timout) $this->url2sql();

      ### Read headlines:
      $result = db_query("SELECT * FROM headlines WHERE id = $this->id ORDER BY number");
      while ($headline = db_fetch_object($result)) {
        array_push($this->headlines, "<A HREF=\"$headline->link\">$headline->title</A>");
      }

    }
    else {
      $this->site = $site;
      $this->url = $url;
      $this->file = $file;
      $this->contact = $contact;
    }
  }

  #####
  # Syntax.......: rdf2sql(optional timout value in seconds);
  # Description..: Reads a RDF file from a server, parses it and inserts
  #                the fresh data in a MySQL table.
  #
  function rdf2sql($timout = 10) {
    if ($this->file) {
      ### Decode URL:
      $url = parse_url($this->file);
      $host = $url[host];
      $port = $url[port] ? $url[port] : 80;
      $path = $url[path];
     
      // print "<PRE><B>Debug:</B> $url - $host - $port - $path</PRE>";
 
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
          $result = db_query("DELETE FROM headlines WHERE id = $this->id");

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
            $title = stripslashes($title);
           
            ### Count the number of stories:
            $number += 1;

            ### Insert item in database:
            $result = db_query("INSERT INTO headlines (id, title, link, number) VALUES('$this->id', '". stripslashes($title) ."', '". stripslashes($link) ."', '$number')");
          }
 
          ### Mark channels as being updated:
          $result = db_query("UPDATE channel SET timestamp = '". time() ."' WHERE id = $this->id");
          $this->timestamp = time();
        }
        else print "<HR>RDF parser: 404 error?<BR><BR><PRE>$data</PRE><HR>";
      }
    }
  }


  #####
  # Syntax.......: rss2sql(optional timout value in seconds);
  # Description..: Reads a RSS file from a server, parses it and inserts
  #                the fresh data in a MySQL table.
  #
  function rss2sql($timout = 10) {
    print "backend->rss2sql : TODO<BR>";
  }


  #####
  # Syntax.......: xml2sql(optional timout value in seconds);
  # Description..: Reads a XML file from a server, parses it and inserts
  #                the fresh data in a MySQL table.
  #
  function xml2sql($timout = 10) {
    print "backend->xml2sql : TODO<BR>";
  }


  #####
  # Syntax.......: url2sql(optional timout value in seconds);
  # Description..: Generic function to fetch fresh headlines.  It checks whether
  #                we are dealing with a remote RDF, RSS or XML file and calls
  #                the appropriate function to fetch the headline.  The function
  #                is an abstraction towards the programmer as he doesn't need
  #                to know with what file extension we are dealing.
  #
  function url2sql($timout = 10) {
    if (strstr($this->file, ".rdf")) $this->rdf2sql($timout);
    if (strstr($this->file, ".rss")) $this->rss2sql($timout);
    if (strstr($this->file, ".xml")) $this->xml2sql($timout);
  }


  #####
  # Syntax.......: 
  # Description..: 
  #
  function displayHeadlines($timout = 1800) {
    global $theme;

    ### Get channel info:
    $result = db_query("SELECT * FROM channel WHERE site = '$this->site'");

    if ($this->id) {

      ### Check to see whether we have to update our headlines first:
      if (time() - $this->timestamp > $timout) $this->url2sql();

      ### Grab headlines from database:
      $result = db_query("SELECT * FROM headlines WHERE id = $this->id ORDER BY number");
      while ($headline = db_fetch_object($result)) {
        $content .= "<LI><A HREF=\"$headline->link\">$headline->title</A></LI>";
      }
      ### Add timestamp:
      $update = round((time() - $this->timestamp) / 60);
      $content .= "<P ALIGN=\"right\">[ <A HREF=\"backend.php?op=reset&site=$this->site\"><FONT COLOR=\"$theme->hlcolor2\">reset</FONT></A> | updated $update min. ago ]</P>";      
      
      ### Display box:
      $theme->box("$this->site", $content);
    }
    else print "<P>Warning: something whiched happened: specified channel could not be found in database.</P>";
  }


  #####
  # Syntax.......: add()
  # Description..: Adds this backend to the database.
  #
  function add() {
    ### Add channel:    
    $result = db_query("INSERT INTO channel (site, file, url, contact, timestamp) VALUES ('$this->site', '$this->file', '$this->url', '$this->contact', 42)");
  }


  #####
  # Syntax.......: delete()
  # Description..: Deletes this backend
  #
  function delete() {
    ### Delete channel:    
    $result = db_query("DELETE FROM channel WHERE id = $this->id");

    ### Delete headlines:
    $result = db_query("DELETE FROM headlines WHERE id = $this->id");    
  }

  #####
  # Syntax.......: refresh()
  # Description..: Deletes all headlines associated with this backend.
  #
  function refresh() {
    ### Delete headlines:
    $result = db_query("DELETE FROM headlines WHERE id = $this->id");    

    ### Mark channel as invalid to enforce an update:
    $result = db_query("UPDATE channel SET timestamp = 42 WHERE id = $this->id");    
  }

  #####
  # Syntax.......: dump()
  # Description..: Dumps the content of this class to screen.
  #
  function dump() {
    print "<B>Dump backend:</B><BR>";
    print "Id: $this->id<BR>";
    print "Site: $this->site<BR>";
    print "URL: $this->url<BR>";
    print "File: $this->file<BR>";
    print "Contact: $this->contact<BR>";
  }
}

?>
