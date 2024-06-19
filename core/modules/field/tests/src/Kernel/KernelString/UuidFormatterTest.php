<?php

declare(strict_types=1);

namespace Drupal\Tests\field\Kernel\KernelString;

use Drupal\entity_test\Entity\EntityTest;
use Drupal\KernelTests\KernelTestBase;

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
  protected static $modules = ['field', 'entity_test', 'system', 'user'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installConfig(['system', 'field']);
    $this->installEntitySchema('entity_test');
  }

  /**
   * Tests string formatter output.
   */
  public function testUuidStringFormatter(): void {
    $entity = EntityTest::create([]);
    $entity->save();

    $uuid_field = $entity->get('uuid');

    // Verify default render.
    $render_array = $uuid_field->view([]);
    $this->assertSame($entity->uuid(), $render_array[0]['#context']['value'], 'The rendered UUID matches the entity UUID.');
    $this->assertStringContainsString($entity->uuid(), $this->render($render_array), 'The rendered UUID found.');

    // Verify customized render.
    $render_array = $uuid_field->view(['settings' => ['link_to_entity' => TRUE]]);
    $this->assertSame('link', $render_array[0]['#type']);
    $this->assertSame($entity->uuid(), $render_array[0]['#title']['#context']['value']);
    $this->assertSame($entity->toUrl()->toString(), $render_array[0]['#url']->toString());
    $rendered = $this->render($render_array);
    $this->assertStringContainsString($entity->uuid(), $rendered, 'The rendered UUID found.');
    $this->assertStringContainsString($entity->toUrl()->toString(), $rendered, 'The rendered entity URL found.');
  }

}
