<?php
// $Id: views_handler_argument_vocabulary_vid.inc,v 1.1.6.1 2009/11/02 22:01:26 merlinofchaos Exp $

/**
 * Argument handler to accept a vocabulary id.
 */
class views_handler_argument_vocabulary_vid extends views_handler_argument {
  /**
   * Override the behavior of title(). Get the name of the user.
   */
  function title() {
    $title = db_query("SELECT v.name FROM {taxonomy_vocabulary} v WHERE v.vid = :vid", array(':vid' => $this->argument))->fetchField();

    if (empty($title)) {
      return t('No vocabulary');
    }

    return check_plain($title);
  }
}
