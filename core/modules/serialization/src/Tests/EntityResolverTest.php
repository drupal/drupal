<?php
/**
 * @file
 * Contains \Drupal\serialization\Tests\EntityResolverTest.
 */

namespace Drupal\serialization\Tests;

use Drupal\Core\Url;

/**
 * Tests that entities references can be resolved.
 *
 * @group serialization
 */
class EntityResolverTest extends NormalizerTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('entity_reference', 'hal', 'rest');

  /**
   * The format being tested.
   *
   * @var string
   */
  protected $format = 'hal_json';

  protected function setUp() {
    parent::setUp();

    $this->installSchema('system', 'router');
    \Drupal::service('router.builder')->rebuild();

    // Create the test field storage.
    entity_create('field_storage_config', array(
      'entity_type' => 'entity_test_mulrev',
      'field_name' => 'field_test_entity_reference',
      'type' => 'entity_reference',
      'settings' => array(
        'target_type' => 'entity_test_mulrev',
      ),
    ))->save();

    // Create the test field.
    entity_create('field_config', array(
      'entity_type' => 'entity_test_mulrev',
      'field_name' => 'field_test_entity_reference',
      'bundle' => 'entity_test_mulrev',
    ))->save();
  }

  /**
   * Test that fields referencing UUIDs can be denormalized.
   */
  function testUuidEntityResolver() {
    // Create an entity to get the UUID from.
    $entity = entity_create('entity_test_mulrev', array('type' => 'entity_test_mulrev'));
    $entity->set('name', 'foobar');
    $entity->set('field_test_entity_reference', array(array('target_id' => 1)));
    $entity->save();

    $field_uri = Url::fromUri('base://rest/relation/entity_test_mulrev/entity_test_mulrev/field_test_entity_reference', array('absolute' => TRUE))->toString();

    $data = array(
      '_links' => array(
        'type' => array(
          'href' => Url::fromUri('base://rest/type/entity_test_mulrev/entity_test_mulrev', array('absolute' => TRUE))->toString(),
        ),
        $field_uri => array(
          array(
            'href' => $entity->url(),
          ),
        ),
      ),
      '_embedded' => array(
        $field_uri => array(
          array(
            '_links' => array(
              'self' => $entity->url(),
            ),
            'uuid' => array(
              array(
                'value' => $entity->uuid(),
              ),
            ),
          ),
        ),
      ),
    );

    $denormalized = $this->container->get('serializer')->denormalize($data, 'Drupal\entity_test\Entity\EntityTestMulRev', $this->format);
    $field_value = $denormalized->get('field_test_entity_reference')->getValue();
    $this->assertEqual($field_value[0]['target_id'], 1, 'Entity reference resolved using UUID.');
  }

}
