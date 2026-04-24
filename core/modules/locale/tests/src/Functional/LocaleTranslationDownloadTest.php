<?php

declare(strict_types=1);

namespace Drupal\Tests\locale\Functional;

use Drupal\Core\StreamWrapper\PublicStream;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\locale\File\LocaleFile;
use Drupal\locale\File\LocaleFileManager;
use Drupal\locale\LocaleSource;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests locale translation download.
 */
#[Group('locale')]
#[RunTestsInSeparateProcesses]
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
    $settings['settings']['locale_translation_path'] = (object) [
      'value' => $this->translationsStream->url(),
      'required' => TRUE,
    ];
    $this->writeSettings($settings);
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

    $filepath = PublicStream::basePath() . '/remote/all/contrib_module_one/contrib_module_one-8.x-1.1.de._po';
    $filename = basename($filepath);
    $url = \Drupal::service('url_generator')->generateFromRoute('<front>', [], ['absolute' => TRUE]);
    $uri = $url . $filepath;

    $hash = hash_file(LocaleSource::LOCAL_FILE_HASH_ALGO, $uri);
    $source_file = new LocaleFile($filename, $uri, $hash);
    $result = \Drupal::service(LocaleFileManager::class)->downloadTranslationSource($source_file, 'translations://');

    $this->assertEquals('translations://contrib_module_one-8.x-1.1.de._po', $result->uri);
    $this->assertFileDoesNotExist('translations://contrib_module_one-8.x-1.1.de_0._po');
    $this->assertFileExists('translations://contrib_module_one-8.x-1.1.de._po');
    $this->assertStringNotContainsString('__old_content__', file_get_contents('translations://contrib_module_one-8.x-1.1.de._po'));
  }

}
