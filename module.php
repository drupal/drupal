<?php

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
