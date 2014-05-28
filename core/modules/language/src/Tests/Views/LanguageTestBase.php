<?php

/**
 * @file
 * Contains \Drupal\language\Tests\Views\LanguageTestBase.
 */

namespace Drupal\language\Tests\Views;

use Drupal\views\Tests\ViewUnitTestBase;
use Drupal\Core\Language\Language;

/**
 * Defines the base class for all Language handler tests.
 */
abstract class LanguageTestBase extends ViewUnitTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('system', 'language');

  protected function setUp() {
    parent::setUp();
    $this->installConfig(array('language'));

    // Create English and another language beside English.
    $language = new Language(array('id' => 'en'));
    language_save($language);
    $language = new Language(array('id' => 'xx-lolspeak', 'name' => 'Lolspeak'));
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
