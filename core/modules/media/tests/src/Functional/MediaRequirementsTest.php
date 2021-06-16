<?php

namespace Drupal\Tests\media\Functional;

/**
 * Tests the Media module's requirements checks.
 *
 * @group media
 */
class MediaRequirementsTest extends MediaFunctionalTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests that the requirements check can handle a missing source field.
   */
  public function testMissingSourceFieldDefinition() {
    $media_type = $this->createMediaType('test');
    /** @var \Drupal\field\FieldConfigInterface $field_definition */
    $field_definition = $media_type->getSource()
      ->getSourceFieldDefinition($media_type);
    /** @var \Drupal\field\FieldStorageConfigInterface $field_storage_definition */
    $field_storage_definition = $field_definition->getFieldStorageDefinition();
    $field_definition->delete();
    $field_storage_definition->delete();

    $this->drupalLogin($this->rootUser);
    $this->drupalGet('/admin/reports/status');
    $this->assertSession()->statusCodeEquals(200);
  }

}
