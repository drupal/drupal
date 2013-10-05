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
    return drupal_get_form('database_test_theme_tablesort');
  }

}
