<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Entity;

use Drupal\Core\Entity\Display\EntityFormDisplayInterface;
use Drupal\Core\Entity\Entity\EntityFormMode;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\entity_test\Entity\EntityTestBundle;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\KernelTests\Core\Config\ConfigEntityValidationTestBase;
use Drupal\Tests\node\Traits\ContentTypeCreationTrait;

/**
 * Tests validation of entity_form_display entities.
 *
 * @group Entity
 * @group Validation
 */
class EntityFormDisplayValidationTest extends ConfigEntityValidationTestBase {

  use ContentTypeCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected bool $hasLabel = FALSE;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['entity_test', 'field', 'node', 'text', 'user'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installConfig('node');
    $this->createContentType(['type' => 'one']);
    $this->createContentType(['type' => 'two']);

    EntityTestBundle::create(['id' => 'one'])->save();
    EntityTestBundle::create(['id' => 'two'])->save();

    EntityFormMode::create([
      'id' => 'node.test',
      'label' => 'Test',
      'targetEntityType' => 'node',
    ])->save();

    $this->entity = $this->container->get(EntityDisplayRepositoryInterface::class)
      ->getFormDisplay('node', 'one', 'test');
    $this->entity->save();
  }

  /**
   * Tests validation of entity form display component's widget settings.
   */
  public function testMultilineTextFieldWidgetPlaceholder(): void {
    // First, create a field for which widget settings exist.
    $text_field_storage_config = FieldStorageConfig::create([
      'type' => 'text_with_summary',
      'field_name' => 'novel',
      'entity_type' => 'user',
    ]);
    $text_field_storage_config->save();

    $text_field_config = FieldConfig::create([
      'field_storage' => $text_field_storage_config,
      'bundle' => 'user',
      'dependencies' => [
        'config' => [
          $text_field_storage_config->getConfigDependencyName(),
        ],
      ],
    ]);
    $text_field_config->save();

    // Then, configure a form display widget for this field.
    assert($this->entity instanceof EntityFormDisplayInterface);
    $this->entity->setComponent('novel', [
      'type' => 'text_textarea_with_summary',
      'region' => 'content',
      'settings' => [
        'rows' => 9,
        'summary_rows' => 3,
        'placeholder' => "Multi\nLine",
        'show_summary' => FALSE,
      ],
      'third_party_settings' => [],
    ]);

    $this->assertValidationErrors([]);
  }

  /**
   * Tests that the target bundle of the entity form display is checked.
   */
  public function testTargetBundleMustExist(): void {
    $this->entity->set('bundle', 'superhero');
    $this->assertValidationErrors([
      '' => "The 'bundle' property cannot be changed.",
      'bundle' => "The 'superhero' bundle does not exist on the 'node' entity type.",
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function testImmutableProperties(array $valid_values = []): void {
    parent::testImmutableProperties([
      'id' => 'entity_test_with_bundle.two.default',
      'targetEntityType' => 'entity_test_with_bundle',
      'bundle' => 'two',
    ]);
  }

}
