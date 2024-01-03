<?php

declare(strict_types=1);

namespace Drupal\Tests\workspaces\Unit;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Cache\Context\CacheContextsManager;
use Drupal\Tests\UnitTestCase;
use Drupal\workspaces\Access\ActiveWorkspaceCheck;
use Drupal\workspaces\WorkspaceManagerInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Routing\Route;

/**
 * @coversDefaultClass \Drupal\workspaces\Access\ActiveWorkspaceCheck
 *
 * @group workspaces
 * @group Access
 */
class ActiveWorkspaceCheckTest extends UnitTestCase {

  /**
   * The dependency injection container.
   *
   * @var \Symfony\Component\DependencyInjection\ContainerBuilder
   */
  protected $container;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->container = new ContainerBuilder();
    $cache_contexts_manager = $this->prophesize(CacheContextsManager::class);
    $cache_contexts_manager->assertValidTokens()->willReturn(TRUE);
    $cache_contexts_manager->reveal();
    $this->container->set('cache_contexts_manager', $cache_contexts_manager);
    \Drupal::setContainer($this->container);
  }

  /**
   * Provides data for the testAccess method.
   *
   * @return array
   */
  public function providerTestAccess() {
    return [
      [[], FALSE, FALSE],
      [[], TRUE, FALSE],
      [['_has_active_workspace' => 'TRUE'], TRUE, TRUE, ['workspace']],
      [['_has_active_workspace' => 'TRUE'], FALSE, FALSE, ['workspace']],
      [['_has_active_workspace' => 'FALSE'], TRUE, FALSE, ['workspace']],
      [['_has_active_workspace' => 'FALSE'], FALSE, TRUE, ['workspace']],
    ];
  }

  /**
   * @covers ::access
   * @dataProvider providerTestAccess
   */
  public function testAccess($requirements, $has_active_workspace, $access, array $contexts = []) {
    $route = new Route('', [], $requirements);

    $workspace_manager = $this->prophesize(WorkspaceManagerInterface::class);
    $workspace_manager->hasActiveWorkspace()->willReturn($has_active_workspace);
    $access_check = new ActiveWorkspaceCheck($workspace_manager->reveal());

    $access_result = AccessResult::allowedIf($access)->addCacheContexts($contexts);
    $this->assertEquals($access_result, $access_check->access($route));
  }

}
