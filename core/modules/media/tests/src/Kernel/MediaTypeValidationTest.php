<?php

declare(strict_types=1);

namespace Drupal\Tests\media\Kernel;

use Drupal\KernelTests\Core\Config\ConfigEntityValidationTestBase;
use Drupal\Tests\media\Traits\MediaTypeCreationTrait;

/**
 * Tests validation of media_type entities.
 *
 * @group media
 * @group #slow
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
    $valid_values['source'] = 'image';
    parent::testImmutableProperties($valid_values);
  }

  /**
   * Tests that the media source plugin's existence is validated.
   */
  public function testMediaSourceIsValidated(): void {
    // The `source` property is immutable, so we need to clone the entity in
    // order to cleanly change its immutable properties.
    $this->entity = $this->entity->createDuplicate()
      // The `id` property is thrown out by createDuplicate().
      ->set('id', 'test')
      // We need to clear the current source configuration, or we will get
      // validation errors because the old configuration is not supported by the
      // new source.
      ->set('source_configuration', [])
      ->set('source', 'invalid');

    $this->assertValidationErrors([
      'source' => "The 'invalid' plugin does not exist.",
    ]);
  }

}
