<?php

declare(strict_types=1);

namespace Drupal\Tests\system\Kernel\Module;

use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\user\Traits\UserCreationTrait;
use Symfony\Component\HttpFoundation\Request;

/**
 * Tests PrepareModulesEntityUninstallForm.
 *
 * @group Module
 */
class PrepareModulesEntityUninstallFormTest extends KernelTestBase {

  use UserCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['user', 'system'];

  /**
   * Tests PrepareModulesEntityUninstallForm::formTitle.
   */
  public function testModuleEntityUninstallTitle(): void {
    $this->setUpCurrentUser(permissions: ['administer modules']);
    /** @var \Drupal\Core\Controller\TitleResolverInterface $title_resolver */
    $title_resolver = \Drupal::service('title_resolver');
    \Drupal::service('router.builder')->rebuild();
    $request = Request::create('/admin/modules/uninstall/entity/user');
    // Simulate matching.
    $request->attributes->set('entity_type_id', 'user');
    $route = \Drupal::service('router.route_provider')->getRouteByName('system.prepare_modules_entity_uninstall');
    $title = (string) $title_resolver->getTitle($request, $route);
    $this->assertEquals('Are you sure you want to delete all users?', $title);

    $not_an_entity_type = $this->randomMachineName();
    $request = Request::create('/admin/modules/uninstall/entity/' . $not_an_entity_type);
    // Simulate matching.
    $request->attributes->set('entity_type_id', $not_an_entity_type);
    $this->expectException(PluginNotFoundException::class);
    (string) $title_resolver->getTitle($request, $route);
  }

}
