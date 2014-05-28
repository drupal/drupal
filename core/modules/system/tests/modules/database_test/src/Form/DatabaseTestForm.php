<?php

/**
 * @file
 * Contains \Drupal\database_test\Form\DatabaseTestForm.
 */

namespace Drupal\database_test\Form;

/**
 * Temporary form controller for database_test module.
 */
class DatabaseTestForm {

  /**
   * @todo Remove database_test_theme_tablesort().
   */
  public function testTablesortDefaultSort() {
    return \Drupal::formBuilder()->getForm('database_test_theme_tablesort');
  }

}
