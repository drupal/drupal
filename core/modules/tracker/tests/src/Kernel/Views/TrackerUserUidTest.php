<?php

namespace Drupal\Tests\tracker\Kernel\Views;

use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\node\Traits\NodeCreationTrait;
use Drupal\Tests\user\Traits\UserCreationTrait;
use Drupal\views\Tests\ViewResultAssertionTrait;
use Drupal\views\Tests\ViewTestData;
use Drupal\views\Views;

/**
 * Tests the tracker user uid handlers.
 *
 * @group tracker
 * @group legacy
 */
class TrackerUserUidTest extends KernelTestBase {

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
    'tracker',
    'tracker_test_views',
    'user',
    'views',
  ];

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = ['test_tracker_user_uid'];

  /**
   * Tests the user uid filter and argument.
   */
  public function testUserUid() {
    $this->installSchema('system', ['sequences']);
    $this->installConfig(['filter']);
    $this->installEntitySchema('user');
    $this->installEntitySchema('node');
    $this->installSchema('tracker', ['tracker_node', 'tracker_user']);

    ViewTestData::createTestViews(static::class, ['tracker_test_views']);
    $node = $this->createNode();

    $map = [
      'nid' => 'nid',
      'title' => 'title',
    ];

    $expected = [
      [
        'nid' => $node->id(),
        'title' => $node->label(),
      ],
    ];

    $view = Views::getView('test_tracker_user_uid');
    $view->preview();

    // We should have no results as the filter is set for uid 0.
    $this->assertIdenticalResultSet($view, [], $map);
    $view->destroy();

    // Change the filter value to our user.
    $view->initHandlers();
    $view->filter['uid_touch_tracker']->value = $node->getOwnerId();
    $view->preview();

    // We should have one result as the filter is set for the created user.
    $this->assertIdenticalResultSet($view, $expected, $map);
    $view->destroy();

    // Remove the filter now, so only the argument will affect the query.
    $view->removeHandler('default', 'filter', 'uid_touch_tracker');

    // Test the incorrect argument UID.
    $view->initHandlers();
    $view->preview(NULL, [rand()]);
    $this->assertIdenticalResultSet($view, [], $map);
    $view->destroy();

    // Test the correct argument UID.
    $view->initHandlers();
    $view->preview(NULL, [$node->getOwnerId()]);
    $this->assertIdenticalResultSet($view, $expected, $map);
  }

}
