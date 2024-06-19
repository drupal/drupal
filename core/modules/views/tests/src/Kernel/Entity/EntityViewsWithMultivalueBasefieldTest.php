<?php

declare(strict_types=1);

namespace Drupal\Tests\views\Kernel\Entity;

use Drupal\entity_test\Entity\EntityTestMultiValueBasefield;
use Drupal\Tests\views\Kernel\ViewsKernelTestBase;
use Drupal\views\Views;

/**
 * Tests entity views with multivalue base fields.
 *
 * @group views
 */
class EntityViewsWithMultivalueBasefieldTest extends ViewsKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['entity_test'];

  /**
   * {@inheritdoc}
   */
  public static $testViews = ['test_entity_multivalue_basefield'];

  /**
   * {@inheritdoc}
   */
  protected function setUp($import_test_views = TRUE): void {
    parent::setUp($import_test_views);

    $this->installEntitySchema('entity_test_multivalue_basefield');
  }

  /**
   * Tests entity views with multivalue base fields.
   */
  public function testView(): void {
    EntityTestMultiValueBasefield::create([
      'name' => 'test',
    ])->save();
    EntityTestMultiValueBasefield::create([
      'name' => ['test2', 'test3'],
    ])->save();

    $view = Views::getView('test_entity_multivalue_basefield');
    $view->execute();
    $this->assertIdenticalResultset($view, [
      ['name' => ['test']],
      ['name' => ['test2', 'test3']],
    ], ['name' => 'name']);
  }

}
