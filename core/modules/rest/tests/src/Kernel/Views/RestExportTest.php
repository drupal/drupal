<?php

declare(strict_types=1);

namespace Drupal\Tests\rest\Kernel\Views;

use Drupal\rest\Plugin\views\display\RestExport;
use Drupal\Tests\views\Kernel\ViewsKernelTestBase;
use Drupal\views\Entity\View;
use Drupal\views\Tests\ViewTestData;

/**
 * Tests the REST export view display plugin.
 *
 * @coversDefaultClass \Drupal\rest\Plugin\views\display\RestExport
 *
 * @group rest
 */
class RestExportTest extends ViewsKernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $testViews = ['test_serializer_display_entity'];

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'rest_test_views',
    'serialization',
    'rest',
    'entity_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp($import_test_views = TRUE): void {
    parent::setUp($import_test_views);

    ViewTestData::createTestViews(static::class, ['rest_test_views']);
    $this->installEntitySchema('entity_test');
  }

  /**
   * @covers ::buildResponse
   */
  public function testBuildResponse(): void {
    /** @var \Drupal\views\Entity\View $view */
    $view = View::load('test_serializer_display_entity');
    $display = &$view->getDisplay('rest_export_1');

    $display['display_options']['defaults']['style'] = FALSE;
    $display['display_options']['style']['type'] = 'serializer';
    $display['display_options']['style']['options']['formats'] = ['json', 'xml'];
    $view->save();

    // No custom header should be set yet.
    $response = RestExport::buildResponse('test_serializer_display_entity', 'rest_export_1', []);
    $this->assertEmpty($response->headers->get('Custom-Header'));

    // Clear render cache.
    /** @var \Drupal\Core\Cache\MemoryBackend $render_cache */
    $render_cache = $this->container->get('cache_factory')->get('render');
    $render_cache->deleteAll();

    // A custom header should now be added.
    // @see rest_test_views_views_post_execute()
    $header = $this->randomString();
    $this->container->get('state')->set('rest_test_views_set_header', $header);
    $response = RestExport::buildResponse('test_serializer_display_entity', 'rest_export_1', []);
    $this->assertEquals($header, $response->headers->get('Custom-Header'));
  }

}
