<?


function admin_header() {
  include "config.inc";
 ?>
  <!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">
  <HTML>
   <HEAD><TITLE><? echo $sitename; ?> - administration</TITLE></HEAD>
   <STYLE>
    body    { font-family: helvetica, arial; }
    h1      { font-size: 14pt; font-weight: bold; color: #006699; }
    h2      { font-family: helvetica, arial; font-size: 12pt; font-weight: bold; }
    h3      { font-family: helvetica, arial; font-size: 14pt; font-weight: bold; }
    th	    { font-family: helvetica, arial; text-align: center; background-color: #C0C0C0; color: #447744; }
    td	    { font-family: helvetica, arial; }
   </STYLE>
   <BODY BGCOLOR="#ffffff" LINK="#0000ff" VLINK="#000099" ALINK="#ff0000">
 <?
}

function admin_footer() {
}

?>