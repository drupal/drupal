<?php

/**
 * @file
 * Contains \Drupal\field\Tests\String\UuidFormatterTest.
 */

namespace Drupal\field\Tests\String;

use Drupal\simpletest\KernelTestBase;
use Drupal\entity_test\Entity\EntityTest;

/**
 * Tests the output of a UUID field.
 *
 * @group field
 */
class UuidFormatterTest extends KernelTestBase {


  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['field', 'entity_test', 'system', 'user'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installConfig(['system', 'field']);
    $this->installSchema('system', 'router');
    \Drupal::service('router.builder')->rebuild();
    $this->installEntitySchema('entity_test');
  }

  /**
   * Tests string formatter output.
   */
  public function testUuidStringFormatter() {
    $entity = EntityTest::create([]);
    $entity->save();

    $uuid_field = $entity->get('uuid');

    $render_array = $uuid_field->view([]);
    $this->assertIdentical($render_array[0]['#markup'], $entity->uuid(), 'The rendered UUID matches the entity UUID.');

    $render_array = $uuid_field->view(['settings' => ['link_to_entity' => TRUE]]);
    $this->assertIdentical($render_array[0]['#type'], 'link');
    $this->assertIdentical($render_array[0]['#title']['#markup'], $entity->uuid());
    $this->assertIdentical($render_array[0]['#url']->toString(), $entity->url());
  }

}
