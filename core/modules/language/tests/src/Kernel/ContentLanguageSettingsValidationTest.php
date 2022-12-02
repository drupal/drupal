<?php

namespace Drupal\Tests\language\Kernel;

use Drupal\KernelTests\Core\Config\ConfigEntityValidationTestBase;
use Drupal\language\Entity\ContentLanguageSettings;

/**
 * Tests validation of content_language_settings entities.
 *
 * @group language
 */
class ContentLanguageSettingsValidationTest extends ConfigEntityValidationTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['language', 'user'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entity = ContentLanguageSettings::create([
      'target_entity_type_id' => 'user',
      'target_bundle' => 'user',
    ]);
    $this->entity->save();
  }

}
