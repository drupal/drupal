<?php

namespace Drupal\Tests\user\Kernel\ContextProvider;

use Drupal\Core\Session\AccountInterface;
use Drupal\KernelTests\KernelTestBase;
use Drupal\user\Entity\User;

/**
 * @coversDefaultClass \Drupal\user\ContextProvider\CurrentUserContext
 *
 * @group user
 */
class CurrentUserContextTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['system', 'user'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('user');
  }

  /**
   * @covers ::getAvailableContexts
   */
  public function testGetAvailableContexts() {
    $context_repository = $this->container->get('context.repository');

    // Test an authenticated account.
    $authenticated = User::create([
      'name' => $this->randomMachineName(),
    ]);
    $authenticated->save();
    $authenticated = User::load($authenticated->id());
    $this->container->get('current_user')->setAccount($authenticated);

    $contexts = $context_repository->getAvailableContexts();
    $this->assertArrayHasKey('@user.current_user_context:current_user', $contexts);
    $this->assertSame('entity:user', $contexts['@user.current_user_context:current_user']->getContextDefinition()->getDataType());
    $this->assertTrue($contexts['@user.current_user_context:current_user']->hasContextValue());
    $this->assertNotNull($contexts['@user.current_user_context:current_user']->getContextValue());

    // Test an anonymous account.
    $anonymous = $this->prophesize(AccountInterface::class);
    $anonymous->id()->willReturn(0);
    $this->container->get('current_user')->setAccount($anonymous->reveal());

    $contexts = $context_repository->getAvailableContexts();
    $this->assertArrayHasKey('@user.current_user_context:current_user', $contexts);
    $this->assertSame('entity:user', $contexts['@user.current_user_context:current_user']->getContextDefinition()->getDataType());
    $this->assertFalse($contexts['@user.current_user_context:current_user']->hasContextValue());
  }

}
