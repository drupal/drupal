<?php

declare(strict_types=1);

namespace Drupal\Tests\language\Kernel\Views;

use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\Tests\views\Kernel\ViewsKernelTestBase;

/**
 * Defines the base class for all Language handler tests.
 */
abstract class LanguageTestBase extends ViewsKernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['system', 'language'];

  /**
   * {@inheritdoc}
   */
  protected function setUp($import_test_views = TRUE): void {
    parent::setUp();
    $this->installConfig(['language']);

    // Create another language beside English.
    ConfigurableLanguage::create(['id' => 'xx-lolspeak', 'label' => 'Lolspeak'])->save();
  }

  /**
   * {@inheritdoc}
   */
  protected function schemaDefinition() {
    $schema = parent::schemaDefinition();
    $schema['views_test_data']['fields']['langcode'] = [
      'description' => 'The {language}.langcode of this beatle.',
      'type' => 'varchar',
      'length' => 12,
      'default' => '',
    ];

    return $schema;
  }

  /**
   * {@inheritdoc}
   */
  protected function viewsData() {
    $data = parent::viewsData();
    $data['views_test_data']['langcode'] = [
      'title' => t('Langcode'),
      'help' => t('Langcode'),
      'field' => [
        'id' => 'language',
      ],
      'argument' => [
        'id' => 'language',
      ],
      'filter' => [
        'id' => 'language',
      ],
    ];

    return $data;
  }

  /**
   * {@inheritdoc}
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
