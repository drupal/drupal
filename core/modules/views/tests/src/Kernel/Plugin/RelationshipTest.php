<?php

namespace Drupal\Tests\views\Kernel\Plugin;

use Drupal\Core\Database\Database;
use Drupal\Tests\user\Traits\UserCreationTrait;
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
  public static $testViews = ['test_view'];

  /**
   * Maps between the key in the expected result and the query result.
   *
   * @var array
   */
  protected $columnMap = [
    'views_test_data_name' => 'name',
    'users_field_data_views_test_data_uid' => 'uid',
  ];

  /**
   * Tests the query result of a view with a relationship.
   */
  public function testRelationshipQuery() {
    $connection = Database::getConnection();
    // Set the first entry to have the admin as author.
    $connection->update('views_test_data')
      ->fields([
        'uid' => 1,
      ])
      ->condition('id', 1)
      ->execute();
    $connection->update('views_test_data')
      ->fields([
        'uid' => 2,
      ])
      ->condition('id', 1, '<>')
      ->execute();

    $view = Views::getView('test_view');
    $view->setDisplay();

    $view->displayHandlers->get('default')->overrideOption('relationships', [
      'uid' => [
        'id' => 'uid',
        'table' => 'views_test_data',
        'field' => 'uid',
      ],
    ]);

    $view->displayHandlers->get('default')->overrideOption('filters', [
      'uid' => [
        'id' => 'uid',
        'table' => 'users_field_data',
        'field' => 'uid',
        'relationship' => 'uid',
      ],
    ]);

    $fields = $view->displayHandlers->get('default')->getOption('fields');
    $view->displayHandlers->get('default')->overrideOption('fields', $fields + [
      'uid' => [
        'id' => 'uid',
        'table' => 'users_field_data',
        'field' => 'uid',
        'relationship' => 'uid',
      ],
    ]);

    $view->initHandlers();

    // Check for all beatles created by admin.
    $view->filter['uid']->value = [1];
    $this->executeView($view);

    $expected_result = [
      [
        'name' => 'John',
        'uid' => 1,
      ],
    ];
    $this->assertIdenticalResultset($view, $expected_result, $this->columnMap);
    $view->destroy();

    // Check for all beatles created by another user, which so doesn't exist.
    $view->initHandlers();
    $view->filter['uid']->value = [3];
    $this->executeView($view);
    $expected_result = [];
    $this->assertIdenticalResultset($view, $expected_result, $this->columnMap);
    $view->destroy();

    // Set the relationship to required, so only results authored by the admin
    // should return.
    $view->initHandlers();
    $view->relationship['uid']->options['required'] = TRUE;
    $this->executeView($view);

    $expected_result = [
      [
        'name' => 'John',
        'uid' => 1,
      ],
    ];
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
    $connection = Database::getConnection();
    $author1 = $this->createUser();
    $connection->update('views_test_data')
      ->fields([
        'uid' => $author1->id(),
      ])
      ->condition('id', 1)
      ->execute();
    $author2 = $this->createUser();
    $connection->update('views_test_data')
      ->fields([
        'uid' => $author2->id(),
      ])
      ->condition('id', 2)
      ->execute();
    // Set uid to non-existing author uid for row 3.
    $connection->update('views_test_data')
      ->fields([
        'uid' => $author2->id() + 123,
      ])
      ->condition('id', 3)
      ->execute();

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
    $this->assertCount(1, $this->xpath($xpath, [':id' => 1, ':author' => $author1->getAccountName()]));
    $this->assertCount(1, $this->xpath($xpath, [':id' => 2, ':author' => $author2->getAccountName()]));
    $this->assertCount(1, $this->xpath($xpath, [':id' => 3, ':author' => '']));
  }

}
