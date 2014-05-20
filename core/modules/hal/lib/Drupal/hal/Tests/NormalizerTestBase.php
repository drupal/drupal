<?php

/**
 * @file
 * Contains \Drupal\hal\Tests\NormalizerTestBase.
 */

namespace Drupal\hal\Tests;

use Drupal\Core\Cache\MemoryBackend;
use Drupal\Core\Language\Language;
use Drupal\hal\Encoder\JsonEncoder;
use Drupal\hal\Normalizer\ContentEntityNormalizer;
use Drupal\hal\Normalizer\EntityReferenceItemNormalizer;
use Drupal\hal\Normalizer\FieldItemNormalizer;
use Drupal\hal\Normalizer\FieldNormalizer;
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
  function setUp() {
    parent::setUp();
    $this->installSchema('system', array('url_alias', 'router'));
    $this->installSchema('user', array('users'));
    $this->installSchema('entity_test', array('entity_test'));
    $this->installConfig(array('field', 'language'));

    // Add English as a language.
    $english = new Language(array(
      'id' => 'en',
      'name' => 'English',
    ));
    language_save($english);
    // Add German as a language.
    $german = new Language(array(
      'id' => 'de',
      'name' => 'Deutsch',
      'weight' => -1,
    ));
    language_save($german);

    // Create the test text field.
    entity_create('field_config', array(
      'name' => 'field_test_text',
      'entity_type' => 'entity_test',
      'type' => 'text',
      'translatable' => FALSE,
    ))->save();
    entity_create('field_instance_config', array(
      'entity_type' => 'entity_test',
      'field_name' => 'field_test_text',
      'bundle' => 'entity_test',
    ))->save();

    // Create the test translatable field.
    entity_create('field_config', array(
      'name' => 'field_test_translatable_text',
      'entity_type' => 'entity_test',
      'type' => 'text',
      'translatable' => TRUE,
    ))->save();
    entity_create('field_instance_config', array(
      'entity_type' => 'entity_test',
      'field_name' => 'field_test_translatable_text',
      'bundle' => 'entity_test',
    ))->save();

    // Create the test entity reference field.
    entity_create('field_config', array(
      'name' => 'field_test_entity_reference',
      'entity_type' => 'entity_test',
      'type' => 'entity_reference',
      'translatable' => TRUE,
      'settings' => array(
        'target_type' => 'entity_test',
      ),
    ))->save();
    entity_create('field_instance_config', array(
      'entity_type' => 'entity_test',
      'field_name' => 'field_test_entity_reference',
      'bundle' => 'entity_test',
    ))->save();

    $link_manager = new LinkManager(new TypeLinkManager(new MemoryBackend('default')), new RelationLinkManager(new MemoryBackend('default'), \Drupal::entityManager()));

    $chain_resolver = new ChainEntityResolver(array(new UuidResolver(), new TargetIdResolver()));

    // Set up the mock serializer.
    $normalizers = array(
      new ContentEntityNormalizer($link_manager, \Drupal::entityManager(), \Drupal::moduleHandler()),
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
