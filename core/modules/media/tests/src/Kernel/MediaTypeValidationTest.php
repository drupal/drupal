<?php

namespace Drupal\Tests\media\Kernel;

use Drupal\KernelTests\Core\Config\ConfigEntityValidationTestBase;
use Drupal\Tests\media\Traits\MediaTypeCreationTrait;

/**
 * Tests validation of media_type entities.
 *
 * @group media
 */
class MediaTypeValidationTest extends ConfigEntityValidationTestBase {

  use MediaTypeCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['field', 'media', 'media_test_source'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->entity = $this->createMediaType('test', ['id' => 'test_media']);
  }

  /**
   * {@inheritdoc}
   */
  public function testImmutableProperties(array $valid_values = []): void {
    // If we don't clear the previous settings here, we will get unrelated
    // validation errors (in addition to the one we're expecting), because the
    // settings from the *old* source won't match the config schema for the
    // settings of the *new* source.
    $this->entity->set('source_configuration', []);
    parent::testImmutableProperties($valid_values);
  }

}
