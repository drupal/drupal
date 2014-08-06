<?php

/**
 * @file
 * Contains \Drupal\hal\Tests\NormalizeTest.
 */

namespace Drupal\hal\Tests;

/**
 * Tests that entities can be normalized in HAL.
 *
 * @group hal
 */
class NormalizeTest extends NormalizerTestBase {

  /**
   * {@inheritdoc}
   */
  function setUp() {
    parent::setUp();

    \Drupal::service('router.builder')->rebuild();
  }

  /**
   * Tests the normalize function.
   */
  public function testNormalize() {
    $target_entity_de = entity_create('entity_test', (array('langcode' => 'de', 'field_test_entity_reference' => NULL)));
    $target_entity_de->save();
    $target_entity_en = entity_create('entity_test', (array('langcode' => 'en', 'field_test_entity_reference' => NULL)));
    $target_entity_en->save();

    // Create a German entity.
    $values = array(
      'langcode' => 'de',
      'name' => $this->randomMachineName(),
      'user_id' => 1,
      'field_test_text' => array(
        'value' => $this->randomMachineName(),
        'format' => 'full_html',
      ),
      'field_test_entity_reference' => array(
        'target_id' => $target_entity_de->id(),
      ),
    );
    // Array of translated values.
    $translation_values = array(
      'name' => $this->randomMachineName(),
      'field_test_entity_reference' => array(
        'target_id' => $target_entity_en->id(),
      )
    );

    $entity = entity_create('entity_test', $values);
    $entity->save();
    // Add an English value for name and entity reference properties.
    $entity->getTranslation('en')->set('name', array(0 => array('value' => $translation_values['name'])));
    $entity->getTranslation('en')->set('field_test_entity_reference', array(0 => $translation_values['field_test_entity_reference']));
    $entity->save();

    $type_uri = url('rest/type/entity_test/entity_test', array('absolute' => TRUE));
    $relation_uri = url('rest/relation/entity_test/entity_test/field_test_entity_reference', array('absolute' => TRUE));

    $expected_array = array(
      '_links' => array(
        'curies' => array(
          array(
            'href' => '/relations',
            'name' => 'site',
            'templated' => true,
          ),
        ),
        'self' => array(
          'href' => $this->getEntityUri($entity),
        ),
        'type' => array(
          'href' => $type_uri,
        ),
        $relation_uri => array(
          array(
            'href' => $this->getEntityUri($target_entity_de),
            'lang' => 'de',
          ),
          array(
            'href' => $this->getEntityUri($target_entity_en),
            'lang' => 'en',
          ),
        ),
      ),
      '_embedded' => array(
        $relation_uri => array(
          array(
            '_links' => array(
              'self' => array(
                'href' => $this->getEntityUri($target_entity_de),
              ),
              'type' => array(
                'href' => $type_uri,
              ),
            ),
            'uuid' => array(
              array(
                'value' => $target_entity_de->uuid(),
              ),
            ),
            'lang' => 'de',
          ),
          array(
            '_links' => array(
              'self' => array(
                'href' => $this->getEntityUri($target_entity_en),
              ),
              'type' => array(
                'href' => $type_uri,
              ),
            ),
            'uuid' => array(
              array(
                'value' => $target_entity_en->uuid(),
              ),
            ),
            'lang' => 'en',
          ),
        ),
      ),
      'uuid' => array(
        array(
          'value' => $entity->uuid(),
        ),
      ),
      'langcode' => array(
        array(
          'value' => 'de',
        ),
      ),
      'name' => array(
        array(
          'value' => $values['name'],
          'lang' => 'de',
        ),
        array(
          'value' => $translation_values['name'],
          'lang' => 'en',
        ),
      ),
      'field_test_text' => array(
        array(
          'value' => $values['field_test_text']['value'],
          'format' => $values['field_test_text']['format'],
        ),
      ),
    );

    $normalized = $this->serializer->normalize($entity, $this->format);
    $this->assertEqual($normalized['_links']['self'], $expected_array['_links']['self'], 'self link placed correctly.');
    // @todo Test curies.
    // @todo Test type.
    $this->assertFalse(isset($normalized['id']), 'Internal id is not exposed.');
    $this->assertEqual($normalized['uuid'], $expected_array['uuid'], 'Non-translatable fields is normalized.');
    $this->assertEqual($normalized['name'], $expected_array['name'], 'Translatable field with multiple language values is normalized.');
    $this->assertEqual($normalized['field_test_text'], $expected_array['field_test_text'], 'Field with properties is normalized.');
    $this->assertEqual($normalized['_embedded'][$relation_uri], $expected_array['_embedded'][$relation_uri], 'Entity reference field is normalized.');
    $this->assertEqual($normalized['_links'][$relation_uri], $expected_array['_links'][$relation_uri], 'Links are added for entity reference field.');
  }

  /**
   * Constructs the entity URI.
   *
   * @param $entity
   *   The entity.
   *
   * @return string
   *   The entity URI.
   */
  protected function getEntityUri($entity) {
    return $entity->url('canonical', array('absolute' => TRUE));
  }

}
