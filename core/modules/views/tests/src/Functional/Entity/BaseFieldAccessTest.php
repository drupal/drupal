<?php

namespace Drupal\Tests\views\Functional\Entity;

use Drupal\entity_test\Entity\EntityTest;
use Drupal\Tests\views\Functional\ViewTestBase;

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
  public static $testViews = ['test_entity_test_protected_access'];

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'views', 'views_test_config', 'entity_test', 'node', 'views_entity_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp($import_test_views = TRUE, $modules = ['views_test_config', 'comment_test_views']): void {
    parent::setUp($import_test_views, $modules);

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
   * Tests access to protected base fields.
   */
  public function testProtectedField() {
    $this->drupalGet('test-entity-protected-access');
    $this->assertSession()->pageTextContains('ok to see this one');
    $this->assertSession()->pageTextNotContains('no access value');
  }

}
