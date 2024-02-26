<?php

namespace Drupal\Tests\system\Functional\Entity;

use Drupal\Core\Cache\Cache;
use Drupal\Core\State\StateInterface;
use Drupal\entity_test\Entity\EntityTestComputedField;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests that entities with computed fields work correctly.
 *
 * @group Entity
 */
class EntityComputedFieldTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['entity_test'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'olivero';

  /**
   * The state service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected StateInterface $state;

  protected function setUp(): void {
    parent::setUp();

    $this->state = $this->container->get('state');
  }

  /**
   * Tests that formatters bubble the cacheable metadata of computed fields.
   */
  public function testFormatterComputedFieldCacheableMetadata() {
    $this->drupalLogin($this->drupalCreateUser(['administer entity_test content']));

    $entity = EntityTestComputedField::create([
      'name' => 'Test entity with a cacheable, computed field',
    ]);
    $entity->save();

    $this->state->set('entity_test_computed_integer_value', 2024);
    $this->drupalGet($entity->toUrl('canonical')->toString());
    $field_item_selector = '.field--name-computed-test-cacheable-integer-field .field__item';
    $this->assertSession()->elementTextEquals('css', $field_item_selector, 2024);
    $this->assertSession()->responseHeaderContains('X-Drupal-Cache-Contexts', 'url.query_args:computed_test_cacheable_integer_field');
    $this->assertSession()->responseHeaderContains('X-Drupal-Cache-Tags', 'field:computed_test_cacheable_integer_field');
    $this->assertSession()->responseHeaderEquals('X-Drupal-Cache-Max-Age', "31536000");

    $this->state->set('entity_test_computed_integer_value', 2025);
    $this->drupalGet($entity->toUrl('canonical')->toString());
    $this->assertSession()->elementTextEquals('css', $field_item_selector, 2024);
    $this->assertSession()->responseHeaderContains('X-Drupal-Cache-Contexts', 'url.query_args:computed_test_cacheable_integer_field');
    $this->assertSession()->responseHeaderContains('X-Drupal-Cache-Tags', 'field:computed_test_cacheable_integer_field');
    $this->assertSession()->responseHeaderEquals('X-Drupal-Cache-Max-Age', "31536000");

    Cache::invalidateTags(['field:computed_test_cacheable_integer_field']);
    $this->drupalGet($entity->toUrl('canonical')->toString());
    $this->assertSession()->elementTextEquals('css', $field_item_selector, 2025);
    $this->assertSession()->responseHeaderContains('X-Drupal-Cache-Contexts', 'url.query_args:computed_test_cacheable_integer_field');
    $this->assertSession()->responseHeaderContains('X-Drupal-Cache-Tags', 'field:computed_test_cacheable_integer_field');
    $this->assertSession()->responseHeaderEquals('X-Drupal-Cache-Max-Age', "31536000");
  }

}
