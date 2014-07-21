<?php

/**
 * @file
 * Contains \Drupal\locale\Tests\LocaleTranslationTest.
 */

namespace Drupal\locale\Tests;

use Drupal\locale\LocaleTranslation;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\locale\LocaleTranslation
 * @group locale
 */
class LocaleTranslationTest extends UnitTestCase {

  /**
   * A mocked storage to use when instantiating LocaleTranslation objects.
   *
   * @var \PHPUnit_Framework_MockObject_MockObject
   */
  protected $storage;

  /**
   * A mocked language manager built from LanguageManagerInterface.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $languageManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    $this->storage = $this->getMock('Drupal\locale\StringStorageInterface');
    $this->cache = $this->getMock('Drupal\Core\Cache\CacheBackendInterface');
    $this->lock = $this->getMock('Drupal\Core\Lock\LockBackendInterface');
    $this->languageManager = $this->getMock('Drupal\Core\Language\LanguageManagerInterface');
  }

  /**
   * Tests for \Drupal\locale\LocaleTranslation::destruct().
   */
  public function testDestruct() {
    $translation = new LocaleTranslation($this->storage, $this->cache, $this->lock, $this->getConfigFactoryStub(), $this->languageManager);
    // Prove that destruction works without errors when translations are empty.
    $this->assertAttributeEmpty('translations', $translation);
    $translation->destruct();
  }

}
