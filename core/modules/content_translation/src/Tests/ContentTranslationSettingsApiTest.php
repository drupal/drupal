<?php

/**
 * @file
 * Contains \Drupal\content_translation\Tests\ContentTranslationSettingsApiTest.
 */

namespace Drupal\content_translation\Tests;

use Drupal\simpletest\KernelTestBase;

/**
 * Tests the content translation settings API.
 *
 * @group content_translation
 */
class ContentTranslationSettingsApiTest extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('language', 'content_translation', 'user', 'entity_test');

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installEntitySchema('entity_test_mul');
  }

  /**
   * Tests that enabling translation via the API triggers schema updates.
   */
  function testSettingsApi() {
    $this->container->get('content_translation.manager')->setEnabled('entity_test_mul', 'entity_test_mul', TRUE);
    $result =
      db_field_exists('entity_test_mul_property_data', 'content_translation_source') &&
      db_field_exists('entity_test_mul_property_data', 'content_translation_outdated');
    $this->assertTrue($result, 'Schema updates correctly performed.');
  }

}
