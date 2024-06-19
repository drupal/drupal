<?php

declare(strict_types=1);

namespace Drupal\Tests\user\Kernel\Views;

use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\node\Traits\NodeCreationTrait;
use Drupal\Tests\user\Traits\UserCreationTrait;
use Drupal\views\Tests\ViewResultAssertionTrait;
use Drupal\views\Tests\ViewTestData;
use Drupal\views\Views;

/**
 * Tests the representative node relationship for users.
 *
 * @group user
 */
class RelationshipRepresentativeNodeTest extends KernelTestBase {

  use NodeCreationTrait;
  use UserCreationTrait;
  use ViewResultAssertionTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'filter',
    'node',
    'system',
    'user',
    'user_test_views',
    'views',
  ];

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = ['test_groupwise_user'];

  /**
   * Tests the relationship.
   */
  public function testRelationship(): void {
    $this->installEntitySchema('user');
    $this->installEntitySchema('node');
    $this->installConfig(['filter']);
    ViewTestData::createTestViews(static::class, ['user_test_views']);

    $users[] = $this->createUser([], NULL, FALSE, ['uid' => 2]);
    $users[] = $this->createUser([], NULL, FALSE, ['uid' => 1]);
    $nodes[] = $this->createNode(['uid' => $users[0]->id()]);
    $nodes[] = $this->createNode(['uid' => $users[1]->id()]);

    $view = Views::getView('test_groupwise_user');
    $view->preview();
    $map = ['node_field_data_users_field_data_nid' => 'nid', 'uid' => 'uid'];
    $expected_result = [
      [
        'uid' => $users[1]->id(),
        'nid' => $nodes[1]->id(),
      ],
      [
        'uid' => $users[0]->id(),
        'nid' => $nodes[0]->id(),
      ],
    ];
    $this->assertIdenticalResultset($view, $expected_result, $map);
  }

}
