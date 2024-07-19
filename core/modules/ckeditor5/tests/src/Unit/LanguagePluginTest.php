<?php

declare(strict_types=1);

namespace Drupal\Tests\ckeditor5\Unit;

use Drupal\ckeditor5\Plugin\CKEditor5Plugin\Language;
use Drupal\ckeditor5\Plugin\CKEditor5PluginDefinition;
use Drupal\Core\Language\Language as LanguageLanguage;
use Drupal\Core\Language\LanguageManager;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Routing\RouteProviderInterface;
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
  public static function providerGetDynamicPluginConfig(): array {
    $united_nations_expected_output = [
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
        $united_nations_expected_output,
      ],
      'site_configured' => [
        ['language_list' => 'site_configured'],
        [
          'language' => [
            'textPartLanguage' => [
              [
                'title' => 'Arabic',
                'languageCode' => 'ar',
                'textDirection' => 'rtl',
              ],
              [
                'title' => 'German',
                'languageCode' => 'de',
              ],
            ],
          ],
        ],
      ],
      'all' => [
        ['language_list' => 'all'],
        [
          'language' => [
            'textPartLanguage' => static::buildExpectedDynamicConfig(LanguageManager::getStandardLanguageList()),
          ],
        ],
      ],
      'default configuration' => [
        [],
        $united_nations_expected_output,
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
  protected static function buildExpectedDynamicConfig(array $language_list): array {
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
    $route_provider = $this->prophesize(RouteProviderInterface::class);
    $language_manager = $this->prophesize(LanguageManagerInterface::class);
    $language_manager->getLanguages()->willReturn([
      new LanguageLanguage([
        'id' => 'de',
        'name' => 'German',
      ]),
      new LanguageLanguage([
        'id' => 'ar',
        'name' => 'Arabic',
        'direction' => 'rtl',
      ]),
    ]);
    $plugin = new Language($configuration, 'ckeditor5_language', new CKEditor5PluginDefinition(['id' => 'IRRELEVANT-FOR-A-UNIT-TEST']), $language_manager->reveal(), $route_provider->reveal());
    $dynamic_config = $plugin->getDynamicPluginConfig([], $this->prophesize(EditorInterface::class)
      ->reveal());
    $this->assertSame($expected_dynamic_config, $dynamic_config);
  }

}
