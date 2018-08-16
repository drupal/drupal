<?php

namespace Drupal\Tests\field\Kernel\EntityReference;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Logger\RfcLogLevel;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Tests\EntityReference\EntityReferenceTestTrait;
use Drupal\node\Entity\NodeType;
use Drupal\KernelTests\KernelTestBase;
use Drupal\taxonomy\Entity\Vocabulary;
use Symfony\Component\Debug\BufferingLogger;

/**
 * Tests entity reference field settings.
 *
 * @group field
 */
class EntityReferenceSettingsTest extends KernelTestBase {

  use EntityReferenceTestTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = ['node', 'taxonomy', 'field', 'user', 'text', 'entity_reference', 'entity_test', 'system'];

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
   * The service name for a logger implementation that collects anything logged.
   *
   * @var string
   */
  protected $testLogServiceName = 'entity_reference_settings_test.logger';

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setup();

    $this->installEntitySchema('node');
    $this->installEntitySchema('taxonomy_term');
    $this->installEntitySchema('entity_test');

    $this->nodeType = NodeType::create([
      'type' => mb_strtolower($this->randomMachineName()),
      'name' => $this->randomString(),
    ]);
    $this->nodeType->save();

    // Create a custom bundle.
    $this->customBundle = 'test_bundle_' . mb_strtolower($this->randomMachineName());
    entity_test_create_bundle($this->customBundle, NULL, 'entity_test');

    // Prepare the logger for collecting the expected critical error.
    $this->container->get($this->testLogServiceName)->cleanLogs();
  }

  /**
   * Tests that config bundle deletions are mirrored in field config settings.
   */
  public function testConfigTargetBundleDeletion() {
    // Create two vocabularies.
    /** @var \Drupal\taxonomy\Entity\Vocabulary[] $vocabularies */
    $vocabularies = [];
    for ($i = 0; $i < 2; $i++) {
      $vid = mb_strtolower($this->randomMachineName());
      $vocabularies[$i] = Vocabulary::create([
        'name' => $this->randomString(),
        'vid' => $vid,
      ]);
      $vocabularies[$i]->save();
    }
    // Attach an entity reference field to $this->nodeType.
    $name = mb_strtolower($this->randomMachineName());
    $label = $this->randomString();
    $handler_settings = [
      'target_bundles' => [
        $vocabularies[0]->id() => $vocabularies[0]->id(),
        $vocabularies[1]->id() => $vocabularies[1]->id(),
      ],
    ];
    $this->createEntityReferenceField('node', $this->nodeType->id(), $name, $label, 'taxonomy_term', 'default', $handler_settings);

    // Check that the 'target_bundle' setting contains the vocabulary.
    $field_config = FieldConfig::loadByName('node', $this->nodeType->id(), $name);
    $actual_handler_settings = $field_config->getSetting('handler_settings');
    $this->assertEqual($handler_settings, $actual_handler_settings);

    // Delete the vocabulary.
    $vocabularies[0]->delete();
    // Ensure that noting is logged.
    $this->assertEmpty($this->container->get($this->testLogServiceName)->cleanLogs());

    // Check that the deleted vocabulary is no longer present in the
    // 'target_bundles' field setting.
    $field_config = FieldConfig::loadByName('node', $this->nodeType->id(), $name);
    $handler_settings = $field_config->getSetting('handler_settings');
    $this->assertEquals([$vocabularies[1]->id() => $vocabularies[1]->id()], $handler_settings['target_bundles']);

    // Delete the other vocabulary.
    $vocabularies[1]->delete();
    // Ensure that field_field_config_presave() logs the expected critical
    // error.
    $log_message = $this->container->get($this->testLogServiceName)->cleanLogs()[0];
    $this->assertEquals(RfcLogLevel::CRITICAL, $log_message[0]);
    $this->assertEquals('The %field_name entity reference field (entity_type: %entity_type, bundle: %bundle) no longer has any valid bundle it can reference. The field is not working correctly anymore and has to be adjusted.', $log_message[1]);
    $this->assertEquals($field_config->getName(), $log_message[2]['%field_name']);
    $this->assertEquals('node', $log_message[2]['%entity_type']);
    $this->assertEquals($this->nodeType->id(), $log_message[2]['%bundle']);

    // Check that the deleted bundle is no longer present in the
    // 'target_bundles' field setting.
    $field_config = FieldConfig::loadByName('node', $this->nodeType->id(), $name);
    $handler_settings = $field_config->getSetting('handler_settings');
    $this->assertEquals([], $handler_settings['target_bundles']);
  }

  /**
   * Tests that deletions of custom bundles are mirrored in field settings.
   */
  public function testCustomTargetBundleDeletion() {
    // Attach an entity reference field to $this->nodeType.
    $name = mb_strtolower($this->randomMachineName());
    $label = $this->randomString();
    $handler_settings = ['target_bundles' => [$this->customBundle => $this->customBundle]];
    $this->createEntityReferenceField('node', $this->nodeType->id(), $name, $label, 'entity_test', 'default', $handler_settings);

    // Check that the 'target_bundle' setting contains the custom bundle.
    $field_config = FieldConfig::loadByName('node', $this->nodeType->id(), $name);
    $actual_handler_settings = $field_config->getSetting('handler_settings');
    $this->assertEqual($handler_settings, $actual_handler_settings);

    // Delete the custom bundle.
    entity_test_delete_bundle($this->customBundle, 'entity_test');

    // Ensure that field_field_config_presave() logs the expected critical
    // error.
    $log_message = $this->container->get($this->testLogServiceName)->cleanLogs()[0];
    $this->assertEquals(RfcLogLevel::CRITICAL, $log_message[0]);
    $this->assertEquals('The %field_name entity reference field (entity_type: %entity_type, bundle: %bundle) no longer has any valid bundle it can reference. The field is not working correctly anymore and has to be adjusted.', $log_message[1]);
    $this->assertEquals($field_config->getName(), $log_message[2]['%field_name']);
    $this->assertEquals('node', $log_message[2]['%entity_type']);
    $this->assertEquals($this->nodeType->id(), $log_message[2]['%bundle']);

    // Check that the deleted bundle is no longer present in the
    // 'target_bundles' field setting.
    $field_config = FieldConfig::loadByName('node', $this->nodeType->id(), $name);
    $handler_settings = $field_config->getSetting('handler_settings');
    $this->assertTrue(empty($handler_settings['target_bundles']));
  }

  /**
   * {@inheritdoc}
   */
  public function register(ContainerBuilder $container) {
    parent::register($container);
    $container
      ->register($this->testLogServiceName, BufferingLogger::class)
      ->addTag('logger');
  }

}
