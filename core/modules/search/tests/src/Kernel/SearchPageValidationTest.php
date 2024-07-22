<?php

declare(strict_types=1);

namespace Drupal\Tests\search\Kernel;

use Drupal\KernelTests\Core\Config\ConfigEntityValidationTestBase;
use Drupal\search\Entity\SearchPage;
use Drupal\search\Plugin\Derivative\SearchLocalTask;
use Drupal\search\SearchPageRepository;

/**
 * Tests validation of search_page entities.
 *
 * @group search
 * @group #slow
 */
class SearchPageValidationTest extends ConfigEntityValidationTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['search', 'user'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entity = SearchPage::create([
      'id' => 'test',
      'label' => 'Test',
      'plugin' => 'user_search',
    ]);
    $this->entity->save();
  }

  /**
   * Tests that the search plugin ID is validated.
   */
  public function testInvalidPluginId(): void {
    $this->entity->set('plugin', 'non_existent');
    $this->assertValidationErrors([
      'plugin' => "The 'non_existent' plugin does not exist.",
    ]);
  }

  /**
   * Test that the base route stored in definition is correct.
   */
  public function testBaseRouteIsValid(): void {
    $search_page_repository = new SearchPageRepository(\Drupal::configFactory(), \Drupal::entityTypeManager());
    $search_local_task = new SearchLocalTask($search_page_repository);
    $definitions = $search_local_task->getDerivativeDefinitions([]);
    $route_provider = \Drupal::service('router.route_provider');
    $base_route = $route_provider->getRouteByName($definitions['test']['base_route']);
    $this->assertSame($base_route, $route_provider->getRouteByName('search.view'));
  }

}
