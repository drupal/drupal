<?php

/**
 * @file
 * Contains Drupal\views\Tests\Language\LanguageTestBase.
 */

namespace Drupal\views\Tests\Language;

use Drupal\views\Tests\ViewUnitTestBase;
use Drupal\Core\Language\Language;

/**
 * Defines the base class for all Language handler tests.
 */
abstract class LanguageTestBase extends ViewUnitTestBase {

  protected function setUp() {
    parent::setUp();

    $this->enableModules(array('system', 'language'));

    // Create another language beside English.
    $language = new Language(array('langcode' => 'xx-lolspeak', 'name' => 'Lolspeak'));
    language_save($language);
  }

  /**
   * Overrides \Drupal\views\Tests\ViewTestBase::schemaDefinition().
   */
  protected function schemaDefinition() {
    $schema = parent::schemaDefinition();
    $schema['views_test_data']['fields']['langcode'] = array(
      'description' => 'The {language}.langcode of this beatle.',
      'type' => 'varchar',
      'length' => 12,
      'default' => '',
    );

    return $schema;
  }

  /**
   * Overrides \Drupal\views\Tests\ViewTestBase::schemaDefinition().
   */
  protected function viewsData() {
    $data = parent::viewsData();
    $data['views_test_data']['langcode'] = array(
      'title' => t('Langcode'),
      'help' => t('Langcode'),
      'field' => array(
        'id' => 'language',
      ),
      'argument' => array(
        'id' => 'language',
      ),
      'filter' => array(
        'id' => 'language',
      ),
    );

    return $data;
  }

  /**
   * Overrides \Drupal\views\Tests\ViewTestBase::dataSet().
   */
  protected function dataSet() {
    $data = parent::dataSet();
    $data[0]['langcode'] = 'en';
    $data[1]['langcode'] = 'xx-lolspeak';
    $data[2]['langcode'] = '';
    $data[3]['langcode'] = '';
    $data[4]['langcode'] = '';

    return $data;
  }

}
