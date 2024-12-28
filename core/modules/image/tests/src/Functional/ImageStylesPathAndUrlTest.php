<?php

declare(strict_types=1);

namespace Drupal\Tests\image\Functional;

use Drupal\Core\File\FileExists;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\StreamWrapper\StreamWrapperManager;
use Drupal\image\Entity\ImageStyle;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\TestFileCreationTrait;

/**
 * Tests the functions for generating paths and URLs for image styles.
 *
 * @group image
 */
class ImageStylesPathAndUrlTest extends BrowserTestBase {

  use TestFileCreationTrait {
    getTestFiles as drupalGetTestFiles;
    compareFiles as drupalCompareFiles;
  }

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['image', 'image_module_test', 'language'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * The image style.
   *
   * @var \Drupal\image\ImageStyleInterface
   */
  protected $style;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->style = ImageStyle::create([
      'name' => 'style_foo',
      'label' => $this->randomString(),
    ]);
    $this->style->save();

    // Create a new language.
    ConfigurableLanguage::createFromLangcode('fr')->save();
  }

  /**
   * Tests \Drupal\image\ImageStyleInterface::buildUri().
   */
  public function testImageStylePath(): void {
    $scheme = 'public';
    $actual = $this->style->buildUri("$scheme://foo/bar.gif");
    $expected = "$scheme://styles/" . $this->style->id() . "/$scheme/foo/bar.gif";
    $this->assertEquals($expected, $actual, 'Got the path for a file URI.');

    $actual = $this->style->buildUri('foo/bar.gif');
    $expected = "$scheme://styles/" . $this->style->id() . "/$scheme/foo/bar.gif";
    $this->assertEquals($expected, $actual, 'Got the path for a relative file path.');
  }

  /**
   * Tests an image style URL using the "public://" scheme.
   */
  public function testImageStyleUrlAndPathPublic(): void {
    $this->doImageStyleUrlAndPathTests('public');
  }

  /**
   * Tests an image style URL using the "private://" scheme.
   */
  public function testImageStyleUrlAndPathPrivate(): void {
    $this->doImageStyleUrlAndPathTests('private');
  }

  /**
   * Tests an image style URL with the "public://" scheme and unclean URLs.
   */
  public function testImageStyleUrlAndPathPublicUnclean(): void {
    $this->doImageStyleUrlAndPathTests('public', FALSE);
  }

  /**
   * Tests an image style URL with the "private://" schema and unclean URLs.
   */
  public function testImageStyleUrlAndPathPrivateUnclean(): void {
    $this->doImageStyleUrlAndPathTests('private', FALSE);
  }

  /**
   * Tests an image style URL with the "public://" schema and language prefix.
   */
  public function testImageStyleUrlAndPathPublicLanguage(): void {
    $this->doImageStyleUrlAndPathTests('public', TRUE, TRUE, 'fr');
  }

  /**
   * Tests an image style URL with the "private://" schema and language prefix.
   */
  public function testImageStyleUrlAndPathPrivateLanguage(): void {
    $this->doImageStyleUrlAndPathTests('private', TRUE, TRUE, 'fr');
  }

  /**
   * Tests an image style URL with a file URL that has an extra slash in it.
   */
  public function testImageStyleUrlExtraSlash(): void {
    $this->doImageStyleUrlAndPathTests('public', TRUE, TRUE);
  }

  /**
   * Test an image style URL with a private file that also gets converted.
   */
  public function testImageStylePrivateWithConversion(): void {
    // Add the "convert" image style effect to our style.
    $this->style->addImageEffect([
      'uuid' => '',
      'id' => 'image_convert',
      'weight' => 1,
      'data' => [
        'extension' => 'jpeg',
      ],
    ]);
    $this->doImageStyleUrlAndPathTests('private');
  }

  /**
   * Tests that an invalid source image returns a 404.
   */
  public function testImageStyleUrlForMissingSourceImage(): void {
    $non_existent_uri = 'public://foo.png';
    $generated_url = $this->style->buildUrl($non_existent_uri);
    $this->drupalGet($generated_url);
    $this->assertSession()->statusCodeEquals(404);
  }

  /**
   * Tests building an image style URL.
   */
  public function doImageStyleUrlAndPathTests($scheme, $clean_url = TRUE, $extra_slash = FALSE, $langcode = FALSE): void {
    $this->prepareRequestForGenerator($clean_url);

    // Make the default scheme neither "public" nor "private" to verify the
    // functions work for other than the default scheme.
    $this->config('system.file')->set('default_scheme', 'temporary')->save();

    // Create the directories for the styles.
    $directory = $scheme . '://styles/' . $this->style->id();
    $status = \Drupal::service('file_system')->prepareDirectory($directory, FileSystemInterface::CREATE_DIRECTORY);
    $this->assertNotFalse($status, 'Created the directory for the generated images for the test style.');

    // Override the language to build the URL for the correct language.
    if ($langcode) {
      $language_manager = \Drupal::service('language_manager');
      $language = $language_manager->getLanguage($langcode);
      $language_manager->setConfigOverrideLanguage($language);
    }

    // Create a working copy of the file.
    $files = $this->drupalGetTestFiles('image');
    $file = array_shift($files);
    /** @var \Drupal\Core\File\FileSystemInterface $file_system */
    $file_system = \Drupal::service('file_system');
    $original_uri = $file_system->copy($file->uri, $scheme . '://', FileExists::Rename);
    // Let the image_module_test module know about this file, so it can claim
    // ownership in hook_file_download().
    \Drupal::keyValue('image')->set('test_file_download', $original_uri);
    $this->assertNotFalse($original_uri, 'Created the generated image file.');

    // Get the URL of a file that has not been generated and try to create it.
    $generated_uri = $this->style->buildUri($original_uri);
    $this->assertFileDoesNotExist($generated_uri);
    $generate_url = $this->style->buildUrl($original_uri, $clean_url);

    // Make sure that language prefix is never added to the image style URL.
    if ($langcode) {
      $this->assertStringNotContainsString("/$langcode/", $generate_url, 'Langcode was not found in the image style URL.');
    }

    // Ensure that the tests still pass when the file is generated by accessing
    // a poorly constructed (but still valid) file URL that has an extra slash
    // in it.
    if ($extra_slash) {
      $modified_uri = str_replace('://', ':///', $original_uri);
      $this->assertNotEquals($original_uri, $modified_uri, 'An extra slash was added to the generated file URI.');
      $generate_url = $this->style->buildUrl($modified_uri, $clean_url);
    }
    if (!$clean_url) {
      $this->assertStringContainsString('index.php/', $generate_url, 'When using non-clean URLS, the system path contains the script name.');
    }
    // Add some extra chars to the token.
    $this->drupalGet(str_replace(IMAGE_DERIVATIVE_TOKEN . '=', IMAGE_DERIVATIVE_TOKEN . '=Zo', $generate_url));
    $this->assertSession()->statusCodeEquals(404);
    // Change the parameter name so the token is missing.
    $this->drupalGet(str_replace(IMAGE_DERIVATIVE_TOKEN . '=', 'wrong_parameter=', $generate_url));
    $this->assertSession()->statusCodeEquals(404);

    // Check that the generated URL is the same when we pass in a relative path
    // rather than a URI. We need to temporarily switch the default scheme to
    // match the desired scheme before testing this, then switch it back to the
    // "temporary" scheme used throughout this test afterwards.
    $this->config('system.file')->set('default_scheme', $scheme)->save();
    $relative_path = StreamWrapperManager::getTarget($original_uri);
    $generate_url_from_relative_path = $this->style->buildUrl($relative_path, $clean_url);
    $this->assertEquals($generate_url, $generate_url_from_relative_path);
    $this->config('system.file')->set('default_scheme', 'temporary')->save();

    // Fetch the URL that generates the file.
    $this->drupalGet($generate_url);
    $this->assertSession()->statusCodeEquals(200);
    $this->assertFileExists($generated_uri);
    // assertRaw can't be used with string containing non UTF-8 chars.
    $this->assertNotEmpty(file_get_contents($generated_uri), 'URL returns expected file.');
    $image = $this->container->get('image.factory')->get($generated_uri);
    $this->assertSession()->responseHeaderEquals('Content-Type', $image->getMimeType());
    $this->assertSession()->responseHeaderEquals('Content-Length', (string) $image->getFileSize());

    // Check that we did not download the original file.
    $original_image = $this->container->get('image.factory')
      ->get($original_uri);
    $this->assertSession()->responseHeaderNotEquals('Content-Length', (string) $original_image->getFileSize());

    if ($scheme == 'private') {
      $this->assertSession()->responseHeaderEquals('Expires', 'Sun, 19 Nov 1978 05:00:00 GMT');
      // Check that Cache-Control header contains 'no-cache' to prevent caching.
      $this->assertSession()->responseHeaderContains('Cache-Control', 'no-cache');
      $this->assertSession()->responseHeaderEquals('X-Image-Owned-By', 'image_module_test');

      // Make sure that a second request to the already existing derivative
      // works too.
      $this->drupalGet($generate_url);
      $this->assertSession()->statusCodeEquals(200);

      // Check that the second request also returned the generated image.
      $this->assertSession()->responseHeaderEquals('Content-Length', (string) $image->getFileSize());

      // Check that we did not download the original file.
      $this->assertSession()->responseHeaderNotEquals('Content-Length', (string) $original_image->getFileSize());

      // Make sure that access is denied for existing style files if we do not
      // have access.
      \Drupal::keyValue('image')->delete('test_file_download');
      $this->drupalGet($generate_url);
      $this->assertSession()->statusCodeEquals(403);

      // Repeat this with a different file that we do not have access to and
      // make sure that access is denied.
      $file_no_access = array_shift($files);
      $original_uri_no_access = $file_system->copy($file_no_access->uri, $scheme . '://', FileExists::Rename);
      $generated_uri_no_access = $scheme . '://styles/' . $this->style->id() . '/' . $scheme . '/' . $file_system->basename($original_uri_no_access);
      $this->assertFileDoesNotExist($generated_uri_no_access);
      $generate_url_no_access = $this->style->buildUrl($original_uri_no_access);

      $this->drupalGet($generate_url_no_access);
      $this->assertSession()->statusCodeEquals(403);
      // Verify that images are not appended to the response.
      // Currently this test only uses PNG images.
      if (!str_contains($generate_url, '.png')) {
        $this->fail('Confirming that private image styles are not appended require PNG file.');
      }
      else {
        // Check for PNG-Signature
        // (cf. http://www.libpng.org/pub/png/book/chapter08.html#png.ch08.div.2)
        // in the response body.
        $raw = $this->getSession()->getPage()->getContent();
        $this->assertStringNotContainsString(chr(137) . chr(80) . chr(78) . chr(71) . chr(13) . chr(10) . chr(26) . chr(10), $raw);
      }
    }
    else {
      $this->assertSession()->responseHeaderEquals('Expires', 'Sun, 19 Nov 1978 05:00:00 GMT');
      $this->assertSession()->responseHeaderNotContains('Cache-Control', 'no-cache');

      if ($clean_url) {
        // Add some extra chars to the token.
        $this->drupalGet(str_replace(IMAGE_DERIVATIVE_TOKEN . '=', IMAGE_DERIVATIVE_TOKEN . '=Zo', $generate_url));
        $this->assertSession()->statusCodeEquals(200);
      }
    }

    // Allow insecure image derivatives to be created for the remainder of this
    // test.
    $this->config('image.settings')
      ->set('allow_insecure_derivatives', TRUE)
      ->save();

    // Create another working copy of the file.
    $files = $this->drupalGetTestFiles('image');
    $file = array_shift($files);
    $original_uri = $file_system->copy($file->uri, $scheme . '://', FileExists::Rename);
    // Let the image_module_test module know about this file, so it can claim
    // ownership in hook_file_download().
    \Drupal::keyValue('image')->set('test_file_download', $original_uri);

    // Suppress the security token in the URL, then get the URL of a file that
    // has not been created and try to create it. Check that the security token
    // is not present in the URL but that the image is still accessible.
    $this->config('image.settings')->set('suppress_itok_output', TRUE)->save();
    $generated_uri = $this->style->buildUri($original_uri);
    $this->assertFileDoesNotExist($generated_uri);
    $generate_url = $this->style->buildUrl($original_uri, $clean_url);
    $this->assertStringNotContainsString(IMAGE_DERIVATIVE_TOKEN . '=', $generate_url, 'The security token does not appear in the image style URL.');
    $this->drupalGet($generate_url);
    $this->assertSession()->statusCodeEquals(200);

    // Stop suppressing the security token in the URL.
    $this->config('image.settings')->set('suppress_itok_output', FALSE)->save();
    // Ensure allow_insecure_derivatives is enabled.
    $this->assertEquals(TRUE, $this->config('image.settings')->get('allow_insecure_derivatives'));
    // Check that a security token is still required when generating a second
    // image derivative using the first one as a source.
    $nested_url = $this->style->buildUrl($generated_uri, $clean_url);
    $matches_expected_url_format = (boolean) preg_match('/styles\/' . $this->style->id() . '\/' . $scheme . '\/styles\/' . $this->style->id() . '\/' . $scheme . '/', $nested_url);
    $this->assertTrue($matches_expected_url_format, "URL for a derivative of an image style matches expected format.");
    $nested_url_with_wrong_token = str_replace(IMAGE_DERIVATIVE_TOKEN . '=', 'wrong_parameter=', $nested_url);
    $this->drupalGet($nested_url_with_wrong_token);
    $this->assertSession()->statusCodeEquals(404);
    // Check that this restriction cannot be bypassed by adding extra slashes
    // to the URL.
    $this->drupalGet(substr_replace($nested_url_with_wrong_token, '//styles/', strrpos($nested_url_with_wrong_token, '/styles/'), strlen('/styles/')));
    $this->assertSession()->statusCodeEquals(404);
    $this->drupalGet(substr_replace($nested_url_with_wrong_token, '////styles/', strrpos($nested_url_with_wrong_token, '/styles/'), strlen('/styles/')));
    $this->assertSession()->statusCodeEquals(404);
    // Make sure the image can still be generated if a correct token is used.
    $this->drupalGet($nested_url);
    $this->assertSession()->statusCodeEquals(200);

    // Check that requesting a nonexistent image does not create any new
    // directories in the file system.
    $directory = $scheme . '://styles/' . $this->style->id() . '/' . $scheme . '/' . $this->randomMachineName();
    $this->drupalGet(\Drupal::service('file_url_generator')->generateAbsoluteString($directory . '/' . $this->randomString()));
    $this->assertDirectoryDoesNotExist($directory);
  }

}
