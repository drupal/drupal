<?php

namespace Drupal\Tests\field\Kernel;

use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Entity\Entity\EntityFormMode;
use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\Core\Entity\Entity\EntityViewMode;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;

/**
 * Ensure display modes are updated when fields are created.
 *
 * @group field
 */
class DisplayModeUpdateTest extends FieldKernelTestBase {

  /**
   * The default view display name.
   *
   * @var string
   */
  protected $defaultViewDisplayName;

  /**
   * The default form display name.
   *
   * @var string
   */
  protected $defaultFormDisplayName;

  /**
   * The alternate view display name.
   *
   * @var string
   */
  protected $foobarViewDisplayName;

  /**
   * The alternate form display name.
   *
   * @var string
   */
  protected $foobarFormDisplayName;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create 'default' view-display.
    $default_view_display = EntityViewDisplay::create([
      'targetEntityType' => 'entity_test',
      'bundle' => 'entity_test',
      'mode' => 'default',
      'status' => TRUE,
    ]);
    $default_view_display->save();
    $this->defaultViewDisplayName = $default_view_display->getConfigDependencyName();

    // Create 'default' form-display.
    $default_form_display = EntityFormDisplay::create([
      'targetEntityType' => 'entity_test',
      'bundle' => 'entity_test',
      'mode' => 'default',
      'status' => TRUE,
    ]);
    $default_form_display->save();
    $this->defaultFormDisplayName = $default_form_display->getConfigDependencyName();

    // Create a view-mode 'foobar', create view-display that uses it.
    EntityViewMode::create([
      'id' => 'entity_test.foobar',
      'targetEntityType' => 'entity_test',
      'status' => TRUE,
      'enabled' => TRUE,
    ])->save();
    $foobar_view_display = EntityViewDisplay::create([
      'targetEntityType' => 'entity_test',
      'bundle' => 'entity_test',
      'mode' => 'foobar',
      'status' => TRUE,
    ]);
    $foobar_view_display->save();
    $this->foobarViewDisplayName = $foobar_view_display->getConfigDependencyName();

    // Create a new form-mode 'foobar', create form-display that uses it.
    EntityFormMode::create([
      'id' => 'entity_test.foobar',
      'targetEntityType' => 'entity_test',
      'status' => TRUE,
      'enabled' => TRUE,
    ])->save();
    $foobar_form_display = EntityFormDisplay::create([
      'targetEntityType' => 'entity_test',
      'bundle' => 'entity_test',
      'mode' => 'foobar',
      'status' => TRUE,
    ]);
    $foobar_form_display->save();
    $this->foobarFormDisplayName = $foobar_form_display->getConfigDependencyName();
  }

  /**
   * Ensure display modes are updated when fields are created.
   */
  public function testDisplayModeUpdateAfterFieldCreation() {

    // Sanity test: field has not been created yet, so should not exist in display.
    $this->assertArrayNotHasKey('field_test', \Drupal::config($this->defaultViewDisplayName)->get('hidden'));
    $this->assertArrayNotHasKey('field_test', \Drupal::config($this->defaultFormDisplayName)->get('hidden'));
    $this->assertArrayNotHasKey('field_test', \Drupal::config($this->foobarViewDisplayName)->get('hidden'));
    $this->assertArrayNotHasKey('field_test', \Drupal::config($this->foobarFormDisplayName)->get('hidden'));

    $field_storage = FieldStorageConfig::create([
      'field_name' => 'field_test',
      'entity_type' => 'entity_test',
      'type' => 'test_field',
      'cardinality' => 1,
    ]);
    $field_storage->save();

    FieldConfig::create([
      'field_storage' => $field_storage,
      'bundle' => 'entity_test',
    ])->save();

    // Ensure field is added to display modes.
    $this->assertArrayHasKey('field_test', \Drupal::config($this->defaultViewDisplayName)->get('hidden'));
    $this->assertArrayHasKey('field_test', \Drupal::config($this->defaultFormDisplayName)->get('hidden'));
    $this->assertArrayHasKey('field_test', \Drupal::config($this->foobarViewDisplayName)->get('hidden'));
    $this->assertArrayHasKey('field_test', \Drupal::config($this->foobarFormDisplayName)->get('hidden'));

  }

}
