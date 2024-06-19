<?php

declare(strict_types=1);

namespace Drupal\Tests\media\Functional;

/**
 * Tests the Media module's requirements checks.
 *
 * @group media
 */
class MediaRequirementsTest extends MediaFunctionalTestBase {

  /**
   * {@inheritdoc}
   *
   * @todo Remove and fix test to not rely on super user.
   * @see https://www.drupal.org/project/drupal/issues/3437620
   */
  protected bool $usesSuperUserAccessPolicy = TRUE;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests that the requirements check can handle a missing source field.
   */
  public function testMissingSourceFieldDefinition(): void {
    $media_type = $this->createMediaType('test');
    /** @var \Drupal\field\FieldConfigInterface $field_definition */
    $field_definition = $media_type->getSource()
      ->getSourceFieldDefinition($media_type);
    /** @var \Drupal\field\FieldStorageConfigInterface $field_storage_definition */
    $field_storage_definition = $field_definition->getFieldStorageDefinition();
    $field_definition->delete();
    $field_storage_definition->delete();
    $valid_media_type = $this->createMediaType('test');

    $this->drupalLogin($this->rootUser);
    $this->drupalGet('/admin/reports/status');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains("The source field definition for the {$media_type->label()} media type is missing.");
    $this->assertSession()->pageTextNotContains("The source field definition for the {$valid_media_type->label()} media type is missing.");
  }

}
