<?php
// $Id: views_handler_field_file_status.inc,v 1.1 2008/09/03 19:21:30 merlinofchaos Exp $
/**
 * Field handler to translate a node type into its readable form.
 */
class views_handler_field_file_status extends views_handler_field {
  function render($values) {
    return _views_file_status($values->{$this->field_alias});
  }
}

