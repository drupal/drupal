<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Recipe;

use Drupal\Core\Config\Action\ConfigActionException;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Extension\ModuleInstallerInterface;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\node\Traits\ContentTypeCreationTrait;
use Drupal\Tests\user\Traits\UserCreationTrait;
use Drupal\user\Entity\Role;

/**
 * @covers \Drupal\Core\Config\Action\Plugin\ConfigAction\EntityClone
 * @group Recipe
 */
class EntityCloneConfigActionTest extends KernelTestBase {

  use ContentTypeCreationTrait;
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
    $this->installConfig('user');

    $this->createRole(['access user profiles'], 'test');
  }

  /**
   * Tests error if original entity does not exist.
   */
  public function testErrorIfOriginalDoesNotExist(): void {
    $this->expectException(ConfigActionException::class);
    $this->expectExceptionMessage("Cannot clone 'user.role.nope' because it does not exist.");
    $this->container->get('plugin.manager.config_action')
      ->applyAction('cloneAs', 'user.role.nope', 'user.role.yep');
  }

  /**
   * Tests successful clone.
   */
  public function testSuccessfulClone(): void {
    $this->container->get('plugin.manager.config_action')
      ->applyAction('cloneAs', 'user.role.test', 'cloned');

    $clone = Role::load('cloned');
    $this->assertInstanceOf(Role::class, $clone);
    $this->assertTrue($clone->hasPermission('access user profiles'));
  }

  /**
   * Tests no error is thrown when an entity with the same ID already exists.
   */
  public function testNoErrorWithExistingEntity(): void {
    $this->createRole(['administer site configuration'], 'cloned');

    $this->container->get('plugin.manager.config_action')
      ->applyAction('cloneAs', 'user.role.test', 'cloned');

    $clone = Role::load('cloned');
    $this->assertInstanceOf(Role::class, $clone);
    $this->assertTrue($clone->hasPermission('administer site configuration'));
    $this->assertFalse($clone->hasPermission('access user profiles'));
  }

  /**
   * Tests that the action can be configured to fail if the clone exists.
   */
  public function testFailIfEntityExists(): void {
    $this->container->get('plugin.manager.config_action')
      ->applyAction('cloneAs', 'user.role.test', [
        'id' => 'cloned',
        'fail_if_exists' => TRUE,
      ]);

    $this->expectException(ConfigActionException::class);
    $this->expectExceptionMessage('Entity user.role.cloned exists');
    $this->container->get('plugin.manager.config_action')
      ->applyAction('cloneAs', 'user.role.test', [
        'id' => 'cloned',
        'fail_if_exists' => TRUE,
      ]);
  }

  /**
   * Tests wildcard support, which allows positional tokens in the clone's ID.
   */
  public function testCloneWithWildcards(): void {
    $this->container->get(ModuleInstallerInterface::class)->install(['node']);
    $this->createContentType(['type' => 'alpha']);
    $this->createContentType(['type' => 'beta']);

    /** @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface $display_repository */
    $display_repository = $this->container->get(EntityDisplayRepositoryInterface::class);
    // Create the default view displays for each node type.
    $display_repository->getViewDisplay('node', 'alpha')->save();
    $display_repository->getViewDisplay('node', 'beta')->save();

    // Ensure the `rss` displays don't exist yet.
    $this->assertTrue($display_repository->getViewDisplay('node', 'alpha', 'rss')->isNew());
    $this->assertTrue($display_repository->getViewDisplay('node', 'beta', 'rss')->isNew());
    // Use the action to clone the default view displays to the `rss` view mode.
    /** @var \Drupal\Core\Config\Action\ConfigActionManager $manager */
    $manager = $this->container->get('plugin.manager.config_action');
    $manager->applyAction('cloneAs', 'core.entity_view_display.node.*.default', 'node.%.rss');
    $this->assertFalse($display_repository->getViewDisplay('node', 'alpha', 'rss')->isNew());
    $this->assertFalse($display_repository->getViewDisplay('node', 'beta', 'rss')->isNew());
  }

}
