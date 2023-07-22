<?php

declare(strict_types = 1);

namespace Drupal\Tests\locale\Kernel;

use Drupal\Core\Config\NullStorage;
use Drupal\KernelTests\KernelTestBase;
use Drupal\locale\LocaleDefaultConfigStorage;

/**
 * @group locale
 */
class LocaleDefaultConfigStorageTest extends KernelTestBase {

  protected static $modules = [
    'language',
    'locale',
    'locale_test',
    'locale_test_translate',
  ];

  public function testGetComponentNames(): void {
    $storage = new LocaleDefaultConfigStorage(
      new NullStorage(),
      \Drupal::languageManager(),
      'testing',
    );

    $expected = [
      'locale_test.no_translation',
      'locale_test.translation',
      'locale_test.translation_multiple',
      'locale_test_translate.settings',
      'block.block.test_default_config',
    ];
    $actual = $storage->getComponentNames(
      'module',
      [
        \Drupal::moduleHandler()->getModule('locale_test'),
        \Drupal::moduleHandler()->getModule('locale_test_translate'),
      ],
    );
    $this->assertSame($expected, $actual);
  }

}
