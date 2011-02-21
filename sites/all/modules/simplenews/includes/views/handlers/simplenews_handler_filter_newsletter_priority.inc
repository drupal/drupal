<?php
// $Id: simplenews_handler_filter_newsletter_priority.inc,v 1.2 2011/01/04 16:07:16 mirodietiker Exp $

/**
 * @file
 * Views filter handler for simplenews newsletter priorities
 */

/*
 * Display simplenews newsletter priorities. See simplenews.admin.inc
 * @row 1427 for definition of the simplenews_get_priority() function
 */

class simplenews_handler_filter_newsletter_priority extends views_handler_filter_in_operator {
  function get_value_options() {
    module_load_include('inc', 'simplenews', 'includes/simplenews.admin');
    $this->value_options = simplenews_get_priority();
  }
}
