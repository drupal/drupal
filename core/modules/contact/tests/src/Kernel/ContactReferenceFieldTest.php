<?php

namespace Drupal\Tests\contact\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\field\Traits\EntityReferenceTestTrait;
use Drupal\Tests\node\Traits\ContentTypeCreationTrait;

/**
 * Tests entity reference fields that target contact messages.
 *
 * @group contact
 */
class ContactReferenceFieldTest extends KernelTestBase {

  use ContentTypeCreationTrait;
  use EntityReferenceTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'contact',
    'field',
    'node',
    'system',
    'text',
    'user',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installConfig('node');
  }

  /**
   * Tests creating an entity reference field targeting contact messages.
   */
  public function testCreateContactMessageReferenceField(): void {
    $node_type = $this->createContentType()->id();

    $this->expectException('\Drupal\Core\Field\FieldException');
    $this->expectExceptionMessage('Entity type "contact_message" has no ID key and cannot be targeted by entity reference field "field_messages"');
    $this->createEntityReferenceField('node', $node_type, 'field_messages', 'Messages', 'contact_message');
  }

}
