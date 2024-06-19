<?php

declare(strict_types=1);

namespace Drupal\Tests\user\Kernel\Views;

use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\user\Traits\UserCreationTrait;
use Drupal\user\Entity\User;
use Drupal\views\Tests\ViewTestData;
use Drupal\views\Views;

/**
 * Tests the handler of the user: uid Argument.
 *
 * @group user
 */
class HandlerArgumentUserUidTest extends KernelTestBase {

  use UserCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'user_test_views',
    'views',
  ];

  /**
   * Test views.
   *
   * @var string[]
   */
  public static $testViews = ['test_user_uid_argument'];

  /**
   * Tests the generated title of a user: uid argument.
   */
  public function testArgumentTitle(): void {
    $this->installEntitySchema('user');
    $this->installConfig(['user']);
    User::create(['uid' => 0, 'name' => ''])->save();
    ViewTestData::createTestViews(static::class, ['user_test_views']);

    $view = Views::getView('test_user_uid_argument');

    // Tests an invalid user uid.
    $view->preview(NULL, [rand(1000, 10000)]);
    $this->assertEmpty($view->getTitle());
    $view->destroy();

    // Tests a valid user.
    $account = $this->createUser();
    $view->preview(NULL, [$account->id()]);
    $this->assertEquals($account->label(), $view->getTitle());
    $view->destroy();

    // Tests the anonymous user.
    $anonymous = $this->config('user.settings')->get('anonymous');
    $view->preview(NULL, [0]);
    $this->assertEquals($anonymous, $view->getTitle());
    $view->destroy();

    $view->getDisplay()->getHandler('argument', 'uid')->options['break_phrase'] = TRUE;
    $view->preview(NULL, [$account->id() . ',0']);
    $this->assertEquals($account->label() . ', ' . $anonymous, $view->getTitle());
    $view->destroy();

    $view->getDisplay()->getHandler('argument', 'uid')->options['break_phrase'] = TRUE;
    $view->preview(NULL, ['0,' . $account->id()]);
    $this->assertEquals($anonymous . ', ' . $account->label(), $view->getTitle());
    $view->destroy();
  }

}
