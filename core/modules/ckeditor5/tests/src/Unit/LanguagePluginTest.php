<?php

declare(strict_types=1);

namespace Drupal\Tests\ckeditor5\Unit;

use Drupal\ckeditor5\Plugin\CKEditor5Plugin\Language;
use Drupal\Core\Language\LanguageManager;
use Drupal\editor\EditorInterface;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\ckeditor5\Plugin\CKEditor5Plugin\Language
 * @group ckeditor5
 * @internal
 */
class LanguagePluginTest extends UnitTestCase {

  /**
   * Provides a list of configs to test.
   */
  public function providerGetDynamicPluginConfig(): array {
    $un_expected_output = [
      'language' => [
        'textPartLanguage' => [
          [
            'title' => 'Arabic',
            'languageCode' => 'ar',
            'textDirection' => 'rtl',
          ],
          [
            'title' => 'Chinese, Simplified',
            'languageCode' => 'zh-hans',
          ],
          [
            'title' => 'English',
            'languageCode' => 'en',
          ],
          [
            'title' => 'French',
            'languageCode' => 'fr',
          ],
          [
            'title' => 'Russian',
            'languageCode' => 'ru',
          ],
          [
            'title' => 'Spanish',
            'languageCode' => 'es',
          ],
        ],
      ],
    ];
    return [
      'un' => [
        ['language_list' => 'un'],
        $un_expected_output,
      ],
      'all' => [
        ['language_list' => 'all'],
        [
          'language' => [
            'textPartLanguage' => $this->buildExpectedDynamicConfig(LanguageManager::getStandardLanguageList()),
          ],
        ],
      ],
      'default configuration' => [
        [],
        $un_expected_output,
      ],
    ];
  }

  /**
   * Builds the expected dynamic configuration output given a language list.
   *
   * @param array $language_list
   *   The languages list from the language manager.
   *
   * @return array
   *   The expected output of the dynamic plugin configuration.
   */
  protected static function buildExpectedDynamicConfig(array $language_list) {
    $expected_language_config = [];
    foreach ($language_list as $language_code => $language_list_item) {
      $item = [
        'title' => $language_list_item[0],
        'languageCode' => $language_code,
      ];
      if (isset($language_list_item[2])) {
        $item['textDirection'] = $language_list_item[2];
      }
      $expected_language_config[$item['title']] = $item;
    }
    ksort($expected_language_config);
    return array_values($expected_language_config);
  }

  /**
   * @covers ::getDynamicPluginConfig
   * @dataProvider providerGetDynamicPluginConfig
   */
  public function testGetDynamicPluginConfig(array $configuration, array $expected_dynamic_config): void {
    $plugin = new Language($configuration, 'ckeditor5_language', NULL);
    $dynamic_config = $plugin->getDynamicPluginConfig([], $this->prophesize(EditorInterface::class)
      ->reveal());
    $this->assertSame($expected_dynamic_config, $dynamic_config);
  }

}
