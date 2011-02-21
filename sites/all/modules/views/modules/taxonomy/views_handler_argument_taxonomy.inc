<?php
// $Id: views_handler_argument_taxonomy.inc,v 1.1.6.1 2009/11/02 22:01:26 merlinofchaos Exp $

/**
 * Argument handler for basic taxonomy tid.
 */
class views_handler_argument_taxonomy extends views_handler_argument {

  /**
   * Override the behavior of title(). Get the title of the node.
   */
  function title() {
    $term = taxonomy_term_load($this->argument);
    if (!empty($term)) {
      return check_plain($term->name);
    }
    // TODO review text
    return t('No name');
  }
}
