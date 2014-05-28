<?php

/**
 * @file
 * Definition of Drupal\views\Tests\Handler\RelationshipTest.
 */

namespace Drupal\views\Tests\Handler;

use Drupal\views\Views;
use Drupal\views\Tests\ViewUnitTestBase;
use Drupal\views\Tests\Plugin\RelationshipJoinTestBase;

/**
 * Tests the base relationship handler.
 *
 * @see \Drupal\views\Plugin\views\relationship\RelationshipPluginBase
 */
class RelationshipTest extends RelationshipJoinTestBase {

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = array('test_view');

  /**
   * Maps between the key in the expected result and the query result.
   *
   * @var array
   */
  protected $columnMap = array(
    'views_test_data_name' => 'name',
    'users_views_test_data_uid' => 'uid',
  );

  public static function getInfo() {
    return array(
      'name' => 'Relationship: Standard',
      'description' => 'Tests the base relationship handler.',
      'group' => 'Views Handlers',
    );
  }

  /**
   * Tests the query result of a view with a relationship.
   */
  public function testRelationshipQuery() {
    // Set the first entry to have the admin as author.
    db_query("UPDATE {views_test_data} SET uid = 1 WHERE id = 1");
    db_query("UPDATE {views_test_data} SET uid = 2 WHERE id <> 1");

    $view = Views::getView('test_view');
    $view->setDisplay();

    $view->displayHandlers->get('default')->overrideOption('relationships', array(
      'uid' => array(
        'id' => 'uid',
        'table' => 'views_test_data',
        'field' => 'uid',
      ),
    ));

    $view->displayHandlers->get('default')->overrideOption('filters', array(
      'uid' => array(
        'id' => 'uid',
        'table' => 'users',
        'field' => 'uid',
        'relationship' => 'uid',
      ),
    ));

    $fields = $view->displayHandlers->get('default')->getOption('fields');
    $view->displayHandlers->get('default')->overrideOption('fields', $fields + array(
      'uid' => array(
        'id' => 'uid',
        'table' => 'users',
        'field' => 'uid',
        'relationship' => 'uid',
      ),
    ));

    $view->initHandlers();

    // Check for all beatles created by admin.
    $view->filter['uid']->value = array(1);
    $this->executeView($view);

    $expected_result = array(
      array(
        'name' => 'John',
        'uid' => 1
      )
    );
    $this->assertIdenticalResultset($view, $expected_result, $this->columnMap);
    $view->destroy();

    // Check for all beatles created by another user, which so doesn't exist.
    $view->initHandlers();
    $view->filter['uid']->value = array(3);
    $this->executeView($view);
    $expected_result = array();
    $this->assertIdenticalResultset($view, $expected_result, $this->columnMap);
    $view->destroy();

    // Set the relationship to required, so only results authored by the admin
    // should return.
    $view->initHandlers();
    $view->relationship['uid']->options['required'] = TRUE;
    $this->executeView($view);

    $expected_result = array(
      array(
        'name' => 'John',
        'uid' => 1
      )
    );
    $this->assertIdenticalResultset($view, $expected_result, $this->columnMap);
    $view->destroy();

    // Set the relationship to optional should cause to return all beatles.
    $view->initHandlers();
    $view->relationship['uid']->options['required'] = FALSE;
    $this->executeView($view);

    $expected_result = $this->dataSet();
    // Alter the expected result to contain the right uids.
    foreach ($expected_result as &$row) {
      // Only John has an existing author.
      if ($row['name'] == 'John') {
        $row['uid'] = 1;
      }
      else {
        // The LEFT join should set an empty {users}.uid field.
        $row['uid'] = NULL;
      }
    }

    $this->assertIdenticalResultset($view, $expected_result, $this->columnMap);
  }

}
