<?php

declare(strict_types=1);

namespace Drupal\Tests\user\Kernel\Views;

use Drupal\views\Views;
use Drupal\Core\Session\AnonymousUserSession;

/**
 * Tests the current user filter handler.
 *
 * @group user
 * @see \Drupal\user\Plugin\views\filter\Current
 */
class HandlerFilterCurrentUserTest extends UserKernelTestBase {

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = ['test_filter_current_user'];

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxy
   */
  protected $currentUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp($import_test_views = TRUE): void {
    parent::setUp();
    $this->currentUser = $this->container->get('current_user');
    $this->setupPermissionTestData();
  }

  /**
   * Tests the current user filter handler with anonymous user.
   */
  public function testFilterCurrentUserAsAnonymous(): void {
    $column_map = ['uid' => 'uid'];
    $this->currentUser->setAccount(new AnonymousUserSession());

    $view = Views::getView('test_filter_current_user');
    $view->initHandlers();
    $view->filter['uid_current']->value = 0;
    $this->executeView($view);
    $expected[] = ['uid' => 1];
    $expected[] = ['uid' => 2];
    $expected[] = ['uid' => 3];
    $expected[] = ['uid' => 4];
    $this->assertIdenticalResultset($view, $expected, $column_map, 'Anonymous account can view all accounts when current filter is FALSE.');
    $view->destroy();

    $view = Views::getView('test_filter_current_user');
    $view->initHandlers();
    $view->filter['uid_current']->value = 1;
    $this->executeView($view);
    $expected = [];
    $this->assertIdenticalResultset($view, $expected, $column_map, 'Anonymous account can view zero accounts when current filter is TRUE.');
    $view->destroy();
  }

  /**
   * Tests the current user filter handler with logged-in user.
   */
  public function testFilterCurrentUserAsUser(): void {
    $column_map = ['uid' => 'uid'];
    $user = reset($this->users);
    $this->currentUser->setAccount($user);

    $view = Views::getView('test_filter_current_user');
    $view->initHandlers();
    $view->filter['uid_current']->value = 0;
    $this->executeView($view);
    $expected = [];
    $expected[] = ['uid' => 2];
    $expected[] = ['uid' => 3];
    $expected[] = ['uid' => 4];
    $this->assertIdenticalResultset($view, $expected, $column_map, 'User can view all users except itself when current filter is FALSE.');
    $view->destroy();

    $view = Views::getView('test_filter_current_user');
    $view->initHandlers();
    $view->filter['uid_current']->value = 1;
    $this->executeView($view);
    $expected = [];
    $expected[] = ['uid' => 1];
    $this->assertIdenticalResultset($view, $expected, $column_map, 'User can only view itself when current filter is TRUE.');
    $view->destroy();
  }

}
