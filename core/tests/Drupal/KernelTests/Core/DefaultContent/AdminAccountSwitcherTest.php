<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\DefaultContent;

use Drupal\Core\Access\AccessException;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\DefaultContent\AdminAccountSwitcher;
use Drupal\Core\Session\AccountSwitcherInterface;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\user\Traits\UserCreationTrait;

/**
 * @covers \Drupal\Core\DefaultContent\AdminAccountSwitcher
 * @group DefaultContent
 */
class AdminAccountSwitcherTest extends KernelTestBase {

  use UserCreationTrait;

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
   * {@inheritdoc}
   */
  public function register(ContainerBuilder $container): void {
    parent::register($container);
    $container->getDefinition(AdminAccountSwitcher::class)->setPublic(TRUE);
  }

  /**
   * Tests switching to a user with an administrative role.
   */
  public function testSwitchToAdministrator(): void {
    /** @var \Drupal\Core\Session\AccountInterface $account */
    $account = $this->createUser(admin: TRUE);

    $this->assertSame($account->id(), $this->container->get(AdminAccountSwitcher::class)->switchToAdministrator()->id());
    $this->assertSame($account->id(), $this->container->get('current_user')->id());
  }

  /**
   * Tests that there is an error if there are no administrative users.
   */
  public function testNoAdministratorsExist(): void {
    /** @var \Drupal\Core\Session\AccountInterface $account */
    $account = $this->createUser();
    $this->assertSame(1, (int) $account->id());

    $this->expectException(AccessException::class);
    $this->expectExceptionMessage("There are no user accounts with administrative roles.");
    $this->container->get(AdminAccountSwitcher::class)->switchToAdministrator();
  }

  /**
   * Tests switching to user 1 when the superuser access policy is enabled.
   */
  public function testSuperUser(): void {
    /** @var \Drupal\Core\Session\AccountInterface $account */
    $account = $this->createUser();
    $this->assertSame(1, (int) $account->id());

    $switcher = new AdminAccountSwitcher(
      $this->container->get(AccountSwitcherInterface::class),
      $this->container->get(EntityTypeManagerInterface::class),
      TRUE,
    );
    $this->assertSame(1, (int) $switcher->switchToAdministrator()->id());
  }

  public function testSwitchToAndSwitchBack(): void {
    $this->assertTrue($this->container->get('current_user')->isAnonymous());

    /** @var \Drupal\Core\Session\AccountInterface $account */
    $account = $this->createUser();
    $switcher = $this->container->get(AdminAccountSwitcher::class);
    $this->assertSame($switcher, $switcher->switchTo($account));
    $this->assertSame($account->id(), $this->container->get('current_user')->id());

    $this->assertSame($switcher, $switcher->switchBack());
    $this->assertTrue($this->container->get('current_user')->isAnonymous());
  }

}
