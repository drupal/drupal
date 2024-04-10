<?php

declare(strict_types=1);

namespace Drupal\Tests\views\Kernel\Entity;

use Drupal\entity_test\Entity\EntityTest;
use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;
use Drupal\views\Tests\ViewTestData;

/**
 * Tests the ViewSelection EntityReferenceSelection plugin.
 *
 * @group views
 */
class ViewSelectionEntityReferenceTest extends EntityKernelTestBase {

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = ['test_display_entity_reference'];

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['views', 'views_test_config'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    ViewTestData::createTestViews(static::class, ['views_test_config']);
  }

  /**
   * Tests the ViewSelection plugin.
   */
  public function testSelectionPlugin(): void {
    for ($i = 1; $i <= 5; $i++) {
      $entity = EntityTest::create([
        'name' => 'Test entity ' . $i,
      ]);
      $entity->save();
    }

    $selection_options = [
      'target_type' => 'entity_test',
      'handler' => 'views',
      'view' => [
        'view_name' => 'test_display_entity_reference',
        'display_name' => 'entity_reference_1',
      ],
    ];
    $handler = $this->container->get('plugin.manager.entity_reference_selection')->getInstance($selection_options);

    $state = \Drupal::state();
    $this->assertNull($state->get('views_test_config.views_post_render_called'));
    $state->set('views_test_config.views_post_render_cache_tag', TRUE);
    $result = $handler->getReferenceableEntities();
    $this->assertCount(5, $result['entity_test']);
    $this->assertTrue($state->get('views_test_config.views_post_render_called'));
  }

}
