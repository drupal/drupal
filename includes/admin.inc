<?

function admin_header() {
 ?>
  <!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">
  <HTML>
   <HEAD><TITLE><? echo $site_name; ?> administration center</TITLE></HEAD>
   <STYLE>
    body    { font-family: helvetica, arial; }
    h1      { font-size: 14pt; font-weight: bold; color: #006699; }
    h2      { font-family: helvetica, arial; font-size: 12pt; font-weight: bold; }
    h3      { font-family: helvetica, arial; font-size: 14pt; font-weight: bold; }
    th	    { font-family: helvetica, arial; text-align: center; background-color: #CCCCCC; color: #885555; }
    td	    { font-family: helvetica, arial; }
   </STYLE>
   <BODY BGCOLOR="#FFFFFF" LINK="#006699" VLINK="#004499" ALINK="#FF0000">
    <H1>Administration center</H1>
 <?
}

function admin_footer() {
 ?>
   </BODY>
  </HTML>
 <?
}

?>
