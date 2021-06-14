<?php

namespace Drupal\Tests\views\Kernel\Handler;

use Drupal\entity_test\Entity\EntityTest;

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
  }

  public function testEntityTestFields() {
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
