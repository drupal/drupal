<?php

/**
 * @file
 * Contains \Drupal\file_module_test\Form\FileModuleTestForm.
 */

namespace Drupal\file_module_test\Form;

/**
 * Temporary form controller for file_module_test module.
 */
class FileModuleTestForm {

  /**
   * @todo Remove file_module_test_form().
   */
  public function managedFileTest($tree, $extended, $multiple, $default_fids) {
    return drupal_get_form('file_module_test_form', $tree, $extended, $multiple, $default_fids);
  }

}
