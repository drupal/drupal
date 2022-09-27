<?php

namespace Drupal\Tests\hal\Functional\rest\Views;

use Drupal\Core\Cache\Cache;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\Tests\system\Functional\Cache\AssertPageCacheContextsAndTagsTrait;
use Drupal\Tests\views\Functional\ViewTestBase;
use Drupal\views\Views;

/**
 * Tests the serializer style plugin.
 *
 * @group hal
 * @group legacy
 * @see \Drupal\rest\Plugin\views\display\RestExport
 * @see \Drupal\rest\Plugin\views\style\Serializer
 * @see \Drupal\rest\Plugin\views\row\DataEntityRow
 * @see \Drupal\rest\Plugin\views\row\DataFieldRow
 */
class StyleSerializerTest extends ViewTestBase {

  use AssertPageCacheContextsAndTagsTrait;

  /**
   * Modules to install.
   *
   * @var array
   */
  protected static $modules = [
    'entity_test',
    'hal',
    'hal_test_views',
    'node',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = ['test_serializer_display_entity'];

  /**
   * A user with administrative privileges to look at test entity and configure views.
   */
  protected $adminUser;

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * {@inheritdoc}
   */
  protected function setUp($import_test_views = TRUE, $modules = ['hal_test_views']): void {
    parent::setUp($import_test_views, $modules);

    $this->adminUser = $this->drupalCreateUser([
      'administer entity_test content',
      'access user profiles',
      'view test entity',
    ]);

    // Save some entity_test entities.
    for ($i = 1; $i <= 10; $i++) {
      EntityTest::create(['name' => 'test_' . $i, 'user_id' => $this->adminUser->id()])->save();
    }

    $this->enableViewsTestModule();
  }

  /**
   * Checks the behavior of the Serializer callback paths and row plugins.
   */
  public function testSerializerResponses() {
    // Test the entity rows.
    $view = Views::getView('test_serializer_display_entity');
    $view->initDisplay();
    $this->executeView($view);

    // Get the serializer service.
    $serializer = $this->container->get('serializer');

    $entities = [];
    foreach ($view->result as $row) {
      $entities[] = $row->_entity;
    }

    $expected_cache_tags = $view->getCacheTags();
    $expected_cache_tags[] = 'entity_test_list';
    /** @var \Drupal\Core\Entity\EntityInterface $entity */
    foreach ($entities as $entity) {
      $expected_cache_tags = Cache::mergeTags($expected_cache_tags, $entity->getCacheTags());
    }
    $expected = $serializer->serialize($entities, 'hal_json');
    $actual_json = $this->drupalGet('test/serialize/entity', ['query' => ['_format' => 'hal_json']]);
    $this->assertSame($expected, $actual_json, 'The expected HAL output was found.');
    $this->assertCacheTags($expected_cache_tags);
  }

}
