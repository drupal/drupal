<?php

namespace Drupal\Tests\locale\Unit;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Lock\LockBackendInterface;
use Drupal\locale\LocaleTranslation;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * @coversDefaultClass \Drupal\locale\LocaleTranslation
 * @group locale
 */
class LocaleTranslationTest extends UnitTestCase {

  /**
   * A mocked storage to use when instantiating LocaleTranslation objects.
   *
   * @var \PHPUnit\Framework\MockObject\MockObject
   */
  protected $storage;

  /**
   * A mocked lock to use when instantiating LocaleTranslation objects.
   *
   * @var \Drupal\Core\Lock\LockBackendInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected LockBackendInterface $lock;

  /**
   * A mocked cache to use when instantiating LocaleTranslation objects.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected CacheBackendInterface $cache;

  /**
   * A mocked language manager built from LanguageManagerInterface.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $languageManager;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->storage = $this->createMock('Drupal\locale\StringStorageInterface');
    $this->cache = $this->createMock('Drupal\Core\Cache\CacheBackendInterface');
    $this->lock = $this->createMock('Drupal\Core\Lock\LockBackendInterface');
    $this->languageManager = $this->createMock('Drupal\Core\Language\LanguageManagerInterface');
    $this->requestStack = new RequestStack();
  }

  /**
   * Tests for \Drupal\locale\LocaleTranslation::destruct().
   */
  public function testDestruct() {
    $translation = new LocaleTranslation($this->storage, $this->cache, $this->lock, $this->getConfigFactoryStub(), $this->languageManager, $this->requestStack);
    // Prove that destruction works without errors when translations are empty.
    $this->assertNull($translation->destruct());
  }

}
