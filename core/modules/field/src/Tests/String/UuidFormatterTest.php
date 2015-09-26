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

    // Verify default render.
    $render_array = $uuid_field->view([]);
    $this->assertIdentical($render_array[0]['#context']['value'], $entity->uuid(), 'The rendered UUID matches the entity UUID.');
    $this->assertTrue(strpos($this->render($render_array), $entity->uuid()), 'The rendered UUID found.');

    // Verify customized render.
    $render_array = $uuid_field->view(['settings' => ['link_to_entity' => TRUE]]);
    $this->assertIdentical($render_array[0]['#type'], 'link');
    $this->assertIdentical($render_array[0]['#title']['#context']['value'], $entity->uuid());
    $this->assertIdentical($render_array[0]['#url']->toString(), $entity->url());
    $rendered = $this->render($render_array);
    $this->assertTrue(strpos($rendered, $entity->uuid()), 'The rendered UUID found.');
    $this->assertTrue(strpos($rendered, $entity->url()), 'The rendered entity URL found.');
  }

}
