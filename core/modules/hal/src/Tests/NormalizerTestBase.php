<?php

/**
 * @file
 * Contains \Drupal\hal\Tests\NormalizerTestBase.
 */

namespace Drupal\hal\Tests;

use Drupal\Core\Cache\MemoryBackend;
use Drupal\hal\Encoder\JsonEncoder;
use Drupal\hal\Normalizer\ContentEntityNormalizer;
use Drupal\hal\Normalizer\EntityReferenceItemNormalizer;
use Drupal\hal\Normalizer\FieldItemNormalizer;
use Drupal\hal\Normalizer\FieldNormalizer;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\rest\LinkManager\LinkManager;
use Drupal\rest\LinkManager\RelationLinkManager;
use Drupal\rest\LinkManager\TypeLinkManager;
use Drupal\serialization\EntityResolver\ChainEntityResolver;
use Drupal\serialization\EntityResolver\TargetIdResolver;
use Drupal\serialization\EntityResolver\UuidResolver;
use Drupal\simpletest\DrupalUnitTestBase;
use Symfony\Component\Serializer\Serializer;

/**
 * Test the HAL normalizer.
 */
abstract class NormalizerTestBase extends DrupalUnitTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('entity', 'entity_test', 'entity_reference', 'field', 'hal', 'language', 'rest', 'serialization', 'system', 'text', 'user', 'filter');

  /**
   * The mock serializer.
   *
   * @var \Symfony\Component\Serializer\Serializer
   */
  protected $serializer;

  /**
   * The format being tested.
   *
   * @var string
   */
  protected $format = 'hal_json';

  /**
   * The class name of the test class.
   *
   * @var string
   */
  protected $entityClass = 'Drupal\entity_test\Entity\EntityTest';

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installSchema('system', array('url_alias', 'router'));
    $this->installEntitySchema('user');
    $this->installEntitySchema('entity_test');
    // If the concrete test sub-class installs node.module, ensure that the node
    // entity schema is created before the field configurations are installed,
    // because the node entity tables need to be created before the body field
    // storage tables. This prevents trying to create the body field tables
    // twice.
    $class = get_class($this);
    while ($class) {
      if (property_exists($class, 'modules')) {
        // Only check the modules, if the $modules property was not inherited.
        $rp = new \ReflectionProperty($class, 'modules');
        if ($rp->class == $class) {
          if (in_array('node', $class::$modules, TRUE)) {
            $this->installEntitySchema('node');
            break;
          }
        }
      }
      $class = get_parent_class($class);
    }
    $this->installConfig(array('field', 'language'));

    // Add German as a language.
    ConfigurableLanguage::create(array(
      'id' => 'de',
      'label' => 'Deutsch',
      'weight' => -1,
    ))->save();

    // Create the test text field.
    entity_create('field_storage_config', array(
      'field_name' => 'field_test_text',
      'entity_type' => 'entity_test',
      'type' => 'text',
    ))->save();
    entity_create('field_config', array(
      'entity_type' => 'entity_test',
      'field_name' => 'field_test_text',
      'bundle' => 'entity_test',
      'translatable' => FALSE,
    ))->save();

    // Create the test translatable field.
    entity_create('field_storage_config', array(
      'field_name' => 'field_test_translatable_text',
      'entity_type' => 'entity_test',
      'type' => 'text',
    ))->save();
    entity_create('field_config', array(
      'entity_type' => 'entity_test',
      'field_name' => 'field_test_translatable_text',
      'bundle' => 'entity_test',
      'translatable' => TRUE,
    ))->save();

    // Create the test entity reference field.
    entity_create('field_storage_config', array(
      'field_name' => 'field_test_entity_reference',
      'entity_type' => 'entity_test',
      'type' => 'entity_reference',
      'settings' => array(
        'target_type' => 'entity_test',
      ),
    ))->save();
    entity_create('field_config', array(
      'entity_type' => 'entity_test',
      'field_name' => 'field_test_entity_reference',
      'bundle' => 'entity_test',
      'translatable' => TRUE,
    ))->save();

    $entity_manager = \Drupal::entityManager();
    $link_manager = new LinkManager(new TypeLinkManager(new MemoryBackend('default')), new RelationLinkManager(new MemoryBackend('default'), $entity_manager));

    $chain_resolver = new ChainEntityResolver(array(new UuidResolver($entity_manager), new TargetIdResolver()));

    // Set up the mock serializer.
    $normalizers = array(
      new ContentEntityNormalizer($link_manager, $entity_manager, \Drupal::moduleHandler()),
      new EntityReferenceItemNormalizer($link_manager, $chain_resolver),
      new FieldItemNormalizer(),
      new FieldNormalizer(),
    );

    $encoders = array(
      new JsonEncoder(),
    );
    $this->serializer = new Serializer($normalizers, $encoders);
  }

}
