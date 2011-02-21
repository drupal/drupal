<?php
// $Id: views_handler_argument_group_by_numeric.inc,v 1.1.4.2 2010/01/24 22:37:52 dereine Exp $

/**
 * Simple handler for arguments using group by.
 */
class views_handler_argument_group_by_numeric extends views_handler_argument  {
  function query($group_by = FALSE) {
    $this->ensure_my_table();
    $field = $this->get_field();

    $this->query->add_having(0, $field, $this->argument);
  }

  function ui_name() {
    return $this->get_field(parent::ui_name());
  }
}
