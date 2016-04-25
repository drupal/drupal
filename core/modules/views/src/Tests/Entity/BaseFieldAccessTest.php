<?php

namespace Drupal\views\Tests\Entity;

use Drupal\entity_test\Entity\EntityTest;
use Drupal\views\Tests\ViewTestBase;
use Drupal\views\Tests\ViewTestData;

/**
 * Tests views base field access.
 *
 * @group views
 */
class BaseFieldAccessTest extends ViewTestBase {

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = array('test_entity_test_protected_access');

  /**
   * Modules to enable
   *
   * @var array
   */
  public static $modules = [
    'views', 'views_test_config', 'entity_test', 'node', 'views_entity_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    /** @var \Drupal\Core\Entity\EntityDefinitionUpdateManager $update_manager */
    $update_manager = $this->container->get('entity.definition_update_manager');
    \Drupal::entityManager()->clearCachedDefinitions();
    $update_manager->applyUpdates();
    ViewTestData::createTestViews(get_class($this), array('comment_test_views'));
    \Drupal::state()->set('entity_test.views_data', [
      'entity_test' => [
        'test_text_access' => [
          'field' => [
            'id' => 'standard',
          ],
        ],
      ],
    ]);
    $entity_1 = EntityTest::create([
      'test_text_access' => 'no access value',
    ]);
    $entity_1->save();
    $entity_2 = EntityTest::create([
      'test_text_access' => 'ok to see this one',
    ]);
    $entity_2->save();
    $this->drupalLogin($this->drupalCreateUser(['access content']));
  }

  /**
   * Test access to protected base fields.
   */
  public function testProtectedField() {
    $this->drupalGet('test-entity-protected-access');
    $this->assertText('ok to see this one');
    $this->assertNoText('no access value');
  }

}
