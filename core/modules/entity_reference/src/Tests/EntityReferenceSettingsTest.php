<?php

/**
 * @file
 * Contains \Drupal\entity_reference\Tests\EntityReferenceSettingsTest.
 */

namespace Drupal\entity_reference\Tests;

use Drupal\Component\Utility\Unicode;
use Drupal\field\Entity\FieldConfig;
use Drupal\node\Entity\NodeType;
use Drupal\simpletest\KernelTestBase;
use Drupal\taxonomy\Entity\Vocabulary;

/**
 * Tests entity reference field settings.
 *
 * @group entity_reference
 */
class EntityReferenceSettingsTest extends KernelTestBase {

  use EntityReferenceTestTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = ['node', 'taxonomy', 'field', 'user', 'text', 'entity_reference', 'entity_test'];

  /**
   * Testing node type.
   *
   * @var \Drupal\node\Entity\NodeType
   */
  protected $nodeType;

  /**
   * Testing vocabulary.
   *
   * @var \Drupal\taxonomy\Entity\Vocabulary
   */
  protected $vocabulary;

  /**
   * An entity bundle that is not stored as a configuration entity.
   *
   * @var string
   */
  protected $customBundle;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setup();

    $this->installEntitySchema('node');
    $this->installEntitySchema('taxonomy_term');
    $this->installEntitySchema('entity_test');

    $this->nodeType = NodeType::create([
      'type' => Unicode::strtolower($this->randomMachineName()),
      'name' => $this->randomString(),
    ]);
    $this->nodeType->save();

    $this->vocabulary = Vocabulary::create([
      'vid' => Unicode::strtolower($this->randomMachineName()),
      'name' => $this->randomString(),
    ]);
    $this->vocabulary->save();

    // Create a custom bundle.
    $this->customBundle = 'test_bundle_' . Unicode::strtolower($this->randomMachineName());
    entity_test_create_bundle($this->customBundle, NULL, 'entity_test');
  }

  /**
   * Tests that config bundle deletions are mirrored in field config settings.
   */
  public function testConfigTargetBundleDeletion() {
    // Attach an entity reference field to $this->nodeType.
    $name = Unicode::strtolower($this->randomMachineName());
    $label = $this->randomString();
    $vid = $this->vocabulary->id();
    $handler_settings = ['target_bundles' => [$vid => $vid]];
    $this->createEntityReferenceField('node', $this->nodeType->id(), $name, $label, 'taxonomy_term', 'default', $handler_settings);

    // Check that the 'target_bundle' setting contains the vocabulary.
    $field_config = FieldConfig::loadByName('node', $this->nodeType->id(), $name);
    $actual_handler_settings = $field_config->getSetting('handler_settings');
    $this->assertEqual($handler_settings, $actual_handler_settings);

    // Delete the vocabulary.
    $this->vocabulary->delete();

    // Check that the deleted vocabulary is no longer present in the
    // 'target_bundles' field setting.
    $field_config = FieldConfig::loadByName('node', $this->nodeType->id(), $name);
    $handler_settings = $field_config->getSetting('handler_settings');
    $this->assertTrue(empty($handler_settings['target_bundles']));
  }

  /**
   * Tests that deletions of custom bundles are mirrored in field settings.
   */
  public function testCustomTargetBundleDeletion() {
    // Attach an entity reference field to $this->nodeType.
    $name = Unicode::strtolower($this->randomMachineName());
    $label = $this->randomString();
    $handler_settings = ['target_bundles' => [$this->customBundle => $this->customBundle]];
    $this->createEntityReferenceField('node', $this->nodeType->id(), $name, $label, 'entity_test', 'default', $handler_settings);

    // Check that the 'target_bundle' setting contains the custom bundle.
    $field_config = FieldConfig::loadByName('node', $this->nodeType->id(), $name);
    $actual_handler_settings = $field_config->getSetting('handler_settings');
    $this->assertEqual($handler_settings, $actual_handler_settings);

    // Delete the custom bundle.
    entity_test_delete_bundle($this->customBundle, 'entity_test');

    // Check that the deleted bundle is no longer present in the
    // 'target_bundles' field setting.
    $field_config = FieldConfig::loadByName('node', $this->nodeType->id(), $name);
    $handler_settings = $field_config->getSetting('handler_settings');
    $this->assertTrue(empty($handler_settings['target_bundles']));
  }

}
