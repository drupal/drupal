<?php

declare(strict_types=1);

namespace Drupal\Tests\ckeditor5\Kernel;

use Drupal\editor\Entity\Editor;
use Drupal\filter\Entity\FilterFormat;
use Drupal\KernelTests\KernelTestBase;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\TestTools\Random;
use Symfony\Component\Yaml\Yaml;

/**
 * Tests language resolving for CKEditor 5.
 *
 * @group ckeditor5
 * @internal
 */
class LanguageTest extends KernelTestBase {

  /**
   * The CKEditor 5 plugin.
   *
   * @var \Drupal\ckeditor5\Plugin\Editor\CKEditor5
   */
  protected $ckeditor5;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'ckeditor5',
    'editor',
    'filter',
    'language',
    'locale',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->ckeditor5 = $this->container->get('plugin.manager.editor')->createInstance('ckeditor5');

    FilterFormat::create(
      Yaml::parseFile('core/profiles/standard/config/install/filter.format.basic_html.yml')
    )->save();
    Editor::create([
      'format' => 'basic_html',
      'editor' => 'ckeditor5',
      'image_upload' => [
        'status' => FALSE,
      ],
    ])->save();

    $this->installConfig(['language']);
  }

  /**
   * Ensure that languages are resolved correctly.
   *
   * @param string $drupal_langcode
   *   The language code in Drupal.
   * @param string $cke5_langcode
   *   The language code in CKEditor 5.
   * @param bool $is_missing_mapping
   *   Whether this mapping is expected to be missing from language.mappings.
   *
   * @dataProvider provider
   */
  public function test(string $drupal_langcode, string $cke5_langcode, bool $is_missing_mapping = FALSE): void {
    $editor = Editor::load('basic_html');

    ConfigurableLanguage::createFromLangcode($drupal_langcode)->save();
    $this->config('system.site')->set('default_langcode', $drupal_langcode)->save();

    if ($is_missing_mapping) {
      // CKEditor 5's UI language falls back to English, until the language
      // mapping is expanded.
      $settings = $this->ckeditor5->getJSSettings($editor);
      $this->assertSame('en', $settings['language']['ui']);

      // Expand the language mapping.
      $config = $this->config('language.mappings');
      $mapping = $config->get('map');
      $mapping += [$cke5_langcode => $drupal_langcode];
      $config->set('map', $mapping)->save();
    }

    $settings = $this->ckeditor5->getJSSettings($editor);
    $this->assertSame($cke5_langcode, $settings['language']['ui']);
  }

  /**
   * Provides a list of language code pairs.
   *
   * @return string[][]
   */
  public static function provider(): array {
    $random_langcode = Random::machineName();
    return [
      'Language code transformed from browser mappings' => [
        'drupal_langcode' => 'pt-pt',
        'cke5_langcode' => 'pt',
      ],
      'Language code transformed from browser mappings 2' => [
        'drupal_langcode' => 'zh-hans',
        'cke5_langcode' => 'zh-cn',
      ],
      'Language code both in Drupal and CKEditor' => [
        'drupal_langcode' => 'fi',
        'cke5_langcode' => 'fi',
      ],
      'Language code not in Drupal but in CKEditor 5 requires new language.mappings entry' => [
        'drupal_langcode' => $random_langcode,
        'cke5_langcode' => 'de-ch',
        'is_missing_mapping' => TRUE,
      ],
    ];
  }

}
