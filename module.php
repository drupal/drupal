<?php
// $Id: module.php,v 1.13 2002/03/05 22:53:51 kjartan Exp $

include_once "includes/common.inc";

if (module_hook($mod, "page")) {
  page_header();
  module_invoke($mod, "page");
  page_footer();
}
else {
  header("Location: index.php");
}

?>
