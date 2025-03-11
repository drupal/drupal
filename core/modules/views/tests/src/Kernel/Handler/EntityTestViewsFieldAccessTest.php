<?php

declare(strict_types=1);

namespace Drupal\Tests\views\Kernel\Handler;

use Drupal\entity_test\Entity\EntityTest;
use Drupal\language\Entity\ConfigurableLanguage;

/**
 * Tests base field access in Views for the entity_test entity.
 *
 * @group entity_test
 */
class EntityTestViewsFieldAccessTest extends FieldFieldAccessTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['entity_test'];

  /**
   * {@inheritdoc}
   */
  protected function setUp($import_test_views = TRUE): void {
    parent::setUp($import_test_views);

    $this->installEntitySchema('entity_test');
    // Make the site multilingual to have a working language field handler.
    ConfigurableLanguage::create(['id' => 'es', 'title' => 'Spanish title', 'label' => 'Spanish label'])->save();
  }

  /**
   * Tests field access permissions for the 'entity_test' entity in Views.
   */
  public function testEntityTestFields(): void {
    $entity_test = EntityTest::create([
      'name' => 'test entity name',
    ]);
    $entity_test->save();

    // @todo Expand the test coverage in https://www.drupal.org/node/2464635

    $this->assertFieldAccess('entity_test', 'id', $entity_test->id());
    $this->assertFieldAccess('entity_test', 'langcode', $entity_test->language()->getName());
    $this->assertFieldAccess('entity_test', 'name', $entity_test->getName());
  }

}
