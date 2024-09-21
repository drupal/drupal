<?php

declare(strict_types=1);

namespace Drupal\Tests\language\Kernel;

use Drupal\entity_test\Entity\EntityTestBundle;
use Drupal\KernelTests\Core\Config\ConfigEntityValidationTestBase;
use Drupal\language\Entity\ContentLanguageSettings;
use Drupal\Tests\node\Traits\ContentTypeCreationTrait;

/**
 * Tests validation of content_language_settings entities.
 *
 * @group language
 */
class ContentLanguageSettingsValidationTest extends ConfigEntityValidationTestBase {

  use ContentTypeCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'entity_test',
    'field',
    'language',
    'node',
    'text',
    'user',
  ];

  /**
   * {@inheritdoc}
   */
  protected bool $hasLabel = FALSE;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installConfig('node');

    $this->createContentType(['type' => 'alpha']);
    $this->createContentType(['type' => 'bravo']);

    EntityTestBundle::create(['id' => 'alpha'])->save();
    EntityTestBundle::create(['id' => 'bravo'])->save();

    $this->entity = ContentLanguageSettings::create([
      'target_entity_type_id' => 'node',
      'target_bundle' => 'alpha',
    ]);
    $this->entity->save();
  }

  /**
   * Tests that the target bundle of the language content settings is checked.
   */
  public function testTargetBundleMustExist(): void {
    $this->entity->set('target_bundle', 'superhero');
    $this->assertValidationErrors([
      '' => "The 'target_bundle' property cannot be changed.",
      'target_bundle' => "The 'superhero' bundle does not exist on the 'node' entity type.",
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function testImmutableProperties(array $valid_values = []): void {
    parent::testImmutableProperties([
      'target_entity_type_id' => 'entity_test_with_bundle',
      'target_bundle' => 'bravo',
    ]);
  }

}
