<?php

/**
 * @file
 * Contains \Drupal\path\Form\AddForm.
 */

namespace Drupal\path\Form;

use Drupal\Core\Language\Language;

/**
 * Provides the path add form.
 */
class AddForm extends PathFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'path_admin_add';
  }

  /**
   * {@inheritdoc}
   */
  protected function buildPath($pid) {
    return array(
      'source' => '',
      'alias' => '',
      'langcode' => Language::LANGCODE_NOT_SPECIFIED,
      'pid' => NULL,
    );
  }

}
