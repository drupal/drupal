<?php

namespace Drupal\Tests\views\Kernel\Plugin;

use Drupal\simpletest\UserCreationTrait;
use Drupal\views\Views;

/**
 * Tests the base relationship handler.
 *
 * @group views
 * @see \Drupal\views\Plugin\views\relationship\RelationshipPluginBase
 */
class RelationshipTest extends RelationshipJoinTestBase {
  use UserCreationTrait;

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
    'users_field_data_views_test_data_uid' => 'uid',
  );

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
        'table' => 'users_field_data',
        'field' => 'uid',
        'relationship' => 'uid',
      ),
    ));

    $fields = $view->displayHandlers->get('default')->getOption('fields');
    $view->displayHandlers->get('default')->overrideOption('fields', $fields + array(
      'uid' => array(
        'id' => 'uid',
        'table' => 'users_field_data',
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

  /**
   * Tests rendering of a view with a relationship.
   */
  public function testRelationshipRender() {
    $author1 = $this->createUser();
    db_query("UPDATE {views_test_data} SET uid = :uid WHERE id = 1", [':uid' => $author1->id()]);
    $author2 = $this->createUser();
    db_query("UPDATE {views_test_data} SET uid = :uid WHERE id = 2", [':uid' => $author2->id()]);
    // Set uid to non-existing author uid for row 3.
    db_query("UPDATE {views_test_data} SET uid = :uid WHERE id = 3", [':uid' => $author2->id() + 123]);

    $view = Views::getView('test_view');
    // Add a relationship for authors.
    $view->getDisplay()->overrideOption('relationships', [
      'uid' => [
        'id' => 'uid',
        'table' => 'views_test_data',
        'field' => 'uid',
      ],
    ]);
    // Add fields for {views_test_data}.id and author name.
    $view->getDisplay()->overrideOption('fields', [
      'id' => [
        'id' => 'id',
        'table' => 'views_test_data',
        'field' => 'id',
      ],
      'author' => [
        'id' => 'author',
        'table' => 'users_field_data',
        'field' => 'name',
        'relationship' => 'uid',
      ],
    ]);

    // Render the view.
    $output = $view->preview();
    $html = $this->container->get('renderer')->renderRoot($output);
    $this->setRawContent($html);

    // Check that the output contains correct values.
    $xpath = '//div[@class="views-row" and div[@class="views-field views-field-id"]=:id and div[@class="views-field views-field-author"]=:author]';
    $this->assertEqual(1, count($this->xpath($xpath, [':id' => 1, ':author' => $author1->getUsername()])));
    $this->assertEqual(1, count($this->xpath($xpath, [':id' => 2, ':author' => $author2->getUsername()])));
    $this->assertEqual(1, count($this->xpath($xpath, [':id' => 3, ':author' => ''])));
  }

}
