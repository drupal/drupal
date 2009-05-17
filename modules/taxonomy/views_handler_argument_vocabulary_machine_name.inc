<?php

/**
 * @file
 * Definition of views_handler_argument_vocabulary_machine_name.
 */

/**
 * Argument handler to accept a vocabulary machine name.
 *
 * @ingroup views_argument_handlers
 */
class views_handler_argument_vocabulary_machine_name extends views_handler_argument_string {
  /**
   * Override the behavior of title(). Get the name of the vocabulary..
   */
  function title() {
    $title = db_query("SELECT v.name FROM {taxonomy_vocabulary} v WHERE v.machine_name = :machine_name", array(':machine_name' => $this->argument))->fetchField();

    if (empty($title)) {
      return t('No vocabulary');
    }

    return check_plain($title);
  }
}
