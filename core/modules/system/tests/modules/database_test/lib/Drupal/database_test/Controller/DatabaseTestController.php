<?php

/**
 * @file
 * Contains \Drupal\database_test\Controller\DatabaseTestController.
 */

namespace Drupal\database_test\Controller;

/**
 * Controller routines for database_test routes.
 */
class DatabaseTestController {

  /**
   * @todo Remove database_test_db_query_temporary().
   */
  public function dbQueryTemporary() {
    return database_test_db_query_temporary();
  }

  /**
   * @todo Remove database_test_even_pager_query().
   */
  public function pagerQueryEven($limit) {
    return database_test_even_pager_query($limit);
  }

  /**
   * @todo Remove database_test_odd_pager_query().
   */
  public function pagerQueryOdd($limit) {
    return database_test_odd_pager_query($limit);
  }

  /**
   * @todo Remove database_test_tablesort().
   */
  public function testTablesort() {
    return database_test_tablesort();
  }

  /**
   * @todo Remove database_test_tablesort_first().
   */
  public function testTablesortFirst() {
    return database_test_tablesort_first();
  }

}
