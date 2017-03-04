<?php

namespace Drupal\path\Form;

use Drupal\Core\Language\LanguageInterface;

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
    return [
      'source' => '',
      'alias' => '',
      'langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED,
      'pid' => NULL,
    ];
  }

}
