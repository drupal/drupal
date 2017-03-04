<?php

namespace Drupal\Tests\system\Unit\Installer;

use Drupal\Core\StringTranslation\Translator\FileTranslation;
use Drupal\Tests\UnitTestCase;

/**
 * Tests for installer language support.
 *
 * @group Installer
 */
class InstallTranslationFilePatternTest extends UnitTestCase {

  /**
   * @var \Drupal\Core\StringTranslation\Translator\FileTranslation
   */
  protected $fileTranslation;

  /**
   * @var \ReflectionMethod
   */
  protected $filePatternMethod;

  /**
   * {@inheritdoc}
   */
  protected function setup() {
    parent::setUp();
    $this->fileTranslation = new FileTranslation('filename');
    $method = new \ReflectionMethod('\Drupal\Core\StringTranslation\Translator\FileTranslation', 'getTranslationFilesPattern');
    $method->setAccessible(TRUE);
    $this->filePatternMethod = $method;
  }

  /**
   * @dataProvider providerValidTranslationFiles
   */
  public function testFilesPatternValid($langcode, $filename) {
    $pattern = $this->filePatternMethod->invoke($this->fileTranslation, $langcode);
    $this->assertNotEmpty(preg_match($pattern, $filename));
  }

  /**
   * @return array
   */
  public function providerValidTranslationFiles() {
    return [
      ['hu', 'drupal-8.0.0-alpha1.hu.po'],
      ['ta', 'drupal-8.10.10-beta12.ta.po'],
      ['hi', 'drupal-8.0.0.hi.po'],
    ];
  }

  /**
   * @dataProvider providerInvalidTranslationFiles
   */
  public function testFilesPatternInvalid($langcode, $filename) {
    $pattern = $this->filePatternMethod->invoke($this->fileTranslation, $langcode);
    $this->assertEmpty(preg_match($pattern, $filename));
  }

  /**
   * @return array
   */
  public function providerInvalidTranslationFiles() {
    return [
      ['hu', 'drupal-alpha1-*-hu.po'],
      ['ta', 'drupal-beta12.ta'],
      ['hi', 'drupal-hi.po'],
      ['de', 'drupal-dummy-de.po'],
      ['hu', 'drupal-10.0.1.alpha1-hu.po'],
    ];
  }

}
