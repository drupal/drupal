<?php

/**
 * @file
 * Contains \Drupal\search_embedded_form\Form\SearchEmbeddedForm.
 */

namespace Drupal\search_embedded_form\Form;

/**
 * Temporary form controller for search_embedded_form module.
 */
class SearchEmbeddedForm {

  /**
   * @todo Remove search_embedded_form_form().
   */
  public function testEmbeddedForm() {
    return drupal_get_form('search_embedded_form_form');
  }

}
