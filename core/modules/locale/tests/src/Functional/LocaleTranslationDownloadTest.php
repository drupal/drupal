<?php

declare(strict_types=1);

namespace Drupal\Tests\locale\Functional;

use Drupal\Core\StreamWrapper\PublicStream;
use Drupal\language\Entity\ConfigurableLanguage;
use org\bovigo\vfs\vfsStream;

/**
 * Tests locale translation download.
 *
 * @group locale
 */
class LocaleTranslationDownloadTest extends LocaleUpdateBase {

  /**
   * The virtual file stream for storing translations.
   *
   * @var \org\bovigo\vfs\vfsStreamDirectory
   */
  protected $translationsStream;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $moduleHandler = $this->container->get('module_handler');
    $moduleHandler->loadInclude('locale', 'inc', 'locale.batch');
    ConfigurableLanguage::createFromLangcode('de')->save();

    // Let the translations:// stream wrapper point to a virtual file system to
    // make it independent from the test environment.
    $this->translationsStream = vfsStream::setup('translations');
    \Drupal::configFactory()->getEditable('locale.settings')
      ->set('translation.path', $this->translationsStream->url())
      ->save();
  }

  /**
   * Tests translation download from remote sources.
   */
  public function testUpdateImportSourceRemote(): void {

    // Provide remote and 'previously' downloaded translation file.
    $this->setTranslationFiles();
    vfsStream::create([
      'contrib_module_one-8.x-1.1.de._po' => '__old_content__',
    ], $this->translationsStream);

    $url = \Drupal::service('url_generator')->generateFromRoute('<front>', [], ['absolute' => TRUE]);
    $uri = $url . PublicStream::basePath() . '/remote/all/contrib_module_one/contrib_module_one-8.x-1.1.de._po';
    $source_file = (object) [
      'uri' => $uri,
    ];

    $result = locale_translation_download_source($source_file, 'translations://');

    $this->assertEquals('translations://contrib_module_one-8.x-1.1.de._po', $result->uri);
    $this->assertFileDoesNotExist('translations://contrib_module_one-8.x-1.1.de_0._po');
    $this->assertFileExists('translations://contrib_module_one-8.x-1.1.de._po');
    $this->assertStringNotContainsString('__old_content__', file_get_contents('translations://contrib_module_one-8.x-1.1.de._po'));
  }

}
