<?php

namespace Drupal\Tests\views\Kernel\Plugin;

use Drupal\Tests\user\Traits\UserCreationTrait;
use Drupal\views\Views;

/**
 * Tests the base relationship handler.
 *
 * @group views
 * @see \Drupal\views\Plugin\views\relationship\RelationshipPluginBase
 */
class RelationshipJoinInTest extends RelationshipJoinTestBase {

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
   * Tests the query result of a view with a relationship with an IN condition.
   */
  public function testRelationshipInQuery() {
    // Update the first two Beatles to be authored by Kristiaan.
    $account_k = $this->createUser([], 'Kristiaan');
    db_query("UPDATE {views_test_data} SET uid = :uid WHERE id IN (1,2)", [':uid' => $account_k->id()]);

    // Update the other two Beatles to be authored by Django.
    $account_d = $this->createUser([], 'Django');
    db_query("UPDATE {views_test_data} SET uid = :uid WHERE id IN (3,4)", [':uid' => $account_d->id()]);

    // Update Meredith to be authored by Silvie.
    $account_s = $this->createUser([], 'Silvie');
    db_query("UPDATE {views_test_data} SET uid = :uid WHERE id = 5", [':uid' => $account_s->id()]);

    $view = Views::getView('test_view');
    $view->setDisplay();

    $view->displayHandlers->get('default')->overrideOption('relationships', [
      'uid' => [
        'id' => 'uid',
        'table' => 'views_test_data',
        'field' => 'uid',
        'required' => TRUE,
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

    // Check for all beatles created by Kristiaan.
    $view->initHandlers();
    $view->filter['uid']->value = [$account_k->id()];
    $this->executeView($view);

    $expected_result = [
      ['name' => 'John', 'uid' => $account_k->id()],
      ['name' => 'George', 'uid' => $account_k->id()],
    ];
    $this->assertIdenticalResultset($view, $expected_result, $this->columnMap);
    $view->destroy();

    // Check for all beatles created by Django. This should not return anything
    // as the 'extra' option on the join prohibits relating to any authors but
    // Kristiaan or Silvie.
    $view->initHandlers();
    $view->filter['uid']->value = [$account_d->id()];
    $this->executeView($view);

    $expected_result = [];
    $this->assertIdenticalResultset($view, $expected_result, $this->columnMap);
    $view->destroy();

    // Check for all people created by anyone.
    $view->initHandlers();
    $this->executeView($view);
    $expected_result = [
      ['name' => 'John', 'uid' => $account_k->id()],
      ['name' => 'George', 'uid' => $account_k->id()],
      ['name' => 'Meredith', 'uid' => $account_s->id()],
    ];
    $this->assertIdenticalResultset($view, $expected_result, $this->columnMap);
    $view->destroy();
  }

  /**
   * Adds an IN condition for the user name.
   */
  protected function viewsData() {
    $data = parent::viewsData();
    // Only relate if the author's name is Kristiaan or Silvie.
    $data['views_test_data']['uid']['relationship']['extra'][] = [
      'field' => 'name',
      'value' => ['Kristiaan', 'Silvie'],
    ];
    return $data;
  }

}
