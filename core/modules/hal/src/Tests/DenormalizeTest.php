<?php

/**
 * @file
 * Contains \Drupal\hal\Tests\DenormalizeTest.
 */

namespace Drupal\hal\Tests;

use Symfony\Component\Serializer\Exception\UnexpectedValueException;

/**
 * Tests that entities can be denormalized from HAL.
 *
 * @group hal
 */
class DenormalizeTest extends NormalizerTestBase {

  /**
   * Tests that the type link relation in incoming data is handled correctly.
   */
  public function testTypeHandling() {
    // Valid type.
    $data_with_valid_type = array(
      '_links' => array(
        'type' => array(
          'href' => _url('rest/type/entity_test/entity_test', array('absolute' => TRUE)),
        ),
      ),
    );
    $denormalized = $this->serializer->denormalize($data_with_valid_type, $this->entityClass, $this->format);
    $this->assertEqual(get_class($denormalized), $this->entityClass, 'Request with valid type results in creation of correct bundle.');

    // Multiple types.
    $data_with_multiple_types = array(
      '_links' => array(
        'type' => array(
          array(
            'href' => _url('rest/types/foo', array('absolute' => TRUE)),
          ),
          array(
            'href' => _url('rest/type/entity_test/entity_test', array('absolute' => TRUE)),
          ),
        ),
      ),
    );
    $denormalized = $this->serializer->denormalize($data_with_multiple_types, $this->entityClass, $this->format);
    $this->assertEqual(get_class($denormalized), $this->entityClass, 'Request with multiple types results in creation of correct bundle.');

    // Invalid type.
    $data_with_invalid_type = array(
      '_links' => array(
        'type' => array(
          'href' => _url('rest/types/foo', array('absolute' => TRUE)),
        ),
      ),
    );
    try {
      $this->serializer->denormalize($data_with_invalid_type, $this->entityClass, $this->format);
      $this->fail('Exception should be thrown when type is invalid.');
    }
    catch (UnexpectedValueException $e) {
      $this->pass('Exception thrown when type is invalid.');
    }

    // No type.
    $data_with_no_type = array(
      '_links' => array(
      ),
    );
    try {
      $this->serializer->denormalize($data_with_no_type, $this->entityClass, $this->format);
      $this->fail('Exception should be thrown when no type is provided.');
    }
    catch (UnexpectedValueException $e) {
      $this->pass('Exception thrown when no type is provided.');
    }
  }

  /**
   * Test that a field set to an empty array is different than an empty field.
   */
  public function testMarkFieldForDeletion() {
    $no_field_data = array(
      '_links' => array(
        'type' => array(
          'href' => _url('rest/type/entity_test/entity_test', array('absolute' => TRUE)),
        ),
      ),
    );
    $no_field_denormalized = $this->serializer->denormalize($no_field_data, $this->entityClass, $this->format);
    $no_field_value = $no_field_denormalized->field_test_text->getValue();

    $empty_field_data = array(
      '_links' => array(
        'type' => array(
          'href' => _url('rest/type/entity_test/entity_test', array('absolute' => TRUE)),
        ),
      ),
      'field_test_text' => array(),
    );
    $empty_field_denormalized = $this->serializer->denormalize($empty_field_data, $this->entityClass, $this->format);
    $empty_field_value = $empty_field_denormalized->field_test_text->getValue();

    $this->assertTrue(!empty($no_field_value) && empty($empty_field_value), 'A field set to an empty array in the data is structured differently than an empty field.');
  }

  /**
   * Test that non-reference fields can be denormalized.
   */
  public function testBasicFieldDenormalization() {
    $data = array(
      '_links' => array(
        'type' => array(
          'href' => _url('rest/type/entity_test/entity_test', array('absolute' => TRUE)),
        ),
      ),
      'uuid' => array(
        array(
          'value' => 'e5c9fb96-3acf-4a8d-9417-23de1b6c3311',
        ),
      ),
      'field_test_text' => array(
        array(
          'value' => $this->randomMachineName(),
          'format' => 'full_html',
        ),
      ),
      'field_test_translatable_text' => array(
        array(
          'value' => $this->randomMachineName(),
          'format' => 'full_html',
        ),
        array(
          'value' => $this->randomMachineName(),
          'format' => 'filtered_html',
        ),
        array(
          'value' => $this->randomMachineName(),
          'format' => 'filtered_html',
          'lang' => 'de',
        ),
        array(
          'value' => $this->randomMachineName(),
          'format' => 'full_html',
          'lang' => 'de',
        ),
      ),
    );

    $expected_value_default = array(
      array (
        'value' => $data['field_test_translatable_text'][0]['value'],
        'format' => 'full_html',
      ),
      array (
        'value' => $data['field_test_translatable_text'][1]['value'],
        'format' => 'filtered_html',
      ),
    );
    $expected_value_de = array(
      array (
        'value' => $data['field_test_translatable_text'][2]['value'],
        'format' => 'filtered_html',
      ),
      array (
        'value' => $data['field_test_translatable_text'][3]['value'],
        'format' => 'full_html',
      ),
    );
    $denormalized = $this->serializer->denormalize($data, $this->entityClass, $this->format);
    $this->assertEqual($data['uuid'], $denormalized->get('uuid')->getValue(), 'A preset value (e.g. UUID) is overridden by incoming data.');
    $this->assertEqual($data['field_test_text'], $denormalized->get('field_test_text')->getValue(), 'A basic text field is denormalized.');
    $this->assertEqual($expected_value_default, $denormalized->get('field_test_translatable_text')->getValue(), 'Values in the default language are properly handled for a translatable field.');
    $this->assertEqual($expected_value_de, $denormalized->getTranslation('de')->get('field_test_translatable_text')->getValue(), 'Values in a translation language are properly handled for a translatable field.');
  }

  /**
   * Verifies that only specified properties get populated in the PATCH context.
   */
  public function testPatchDenormailzation() {
    $data = array(
      '_links' => array(
        'type' => array(
          'href' => _url('rest/type/entity_test/entity_test', array('absolute' => TRUE)),
        ),
      ),
      'field_test_text' => array(
        array(
          'value' => $this->randomMachineName(),
          'format' => 'full_html',
        ),
      ),
    );
    $denormalized = $this->serializer->denormalize($data, $this->entityClass, $this->format, array('request_method' => 'patch'));
    // Check that the one field got populated as expected.
    $this->assertEqual($data['field_test_text'], $denormalized->get('field_test_text')->getValue());
    // Unset that field so that now all fields are NULL.
    $denormalized->set('field_test_text', NULL);
    // Assert that all fields are NULL and not set to default values. Example:
    // the UUID field is NULL and not initialized as usual.
    foreach ($denormalized as $field_name => $field) {
      // The 'langcode' field always has a value.
      if ($field_name != 'langcode') {
        $this->assertFalse(isset($denormalized->$field_name), "$field_name is not set.");
      }
    }
  }
}
