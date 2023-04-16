<?php

namespace Drupal\Tests\locale\Unit;

use Drupal\Component\Gettext\PoItem;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\locale\LocaleLookup;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * @coversDefaultClass \Drupal\locale\LocaleLookup
 * @group locale
 */
class LocaleLookupTest extends UnitTestCase {

  /**
   * A mocked storage to use when instantiating LocaleTranslation objects.
   *
   * @var \Drupal\locale\StringStorageInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $storage;

  /**
   * A mocked cache object.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $cache;

  /**
   * A mocked lock object.
   *
   * @var \Drupal\Core\Lock\LockBackendInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $lock;

  /**
   * A mocked user object built from AccountInterface.
   *
   * @var \Drupal\Core\Session\AccountInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $user;

  /**
   * A mocked config factory built with UnitTestCase::getConfigFactoryStub().
   *
   * @var \Drupal\Core\Config\ConfigFactory|\PHPUnit\Framework\MockObject\MockBuilder
   */
  protected $configFactory;

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
    $this->lock->expects($this->never())
      ->method($this->anything());

    $this->user = $this->createMock('Drupal\Core\Session\AccountInterface');
    $this->user->expects($this->any())
      ->method('getRoles')
      ->willReturn(['anonymous']);

    $this->configFactory = $this->getConfigFactoryStub(['locale.settings' => ['cache_strings' => FALSE]]);

    $this->languageManager = $this->createMock('Drupal\Core\Language\LanguageManagerInterface');
    $this->requestStack = new RequestStack();

    $container = new ContainerBuilder();
    $container->set('current_user', $this->user);
    \Drupal::setContainer($container);
  }

  /**
   * Tests locale lookups without fallback.
   *
   * @covers ::resolveCacheMiss
   */
  public function testResolveCacheMissWithoutFallback() {
    $args = [
      'language' => 'en',
      'source' => 'test',
      'context' => 'irrelevant',
    ];

    $result = (object) [
      'translation' => 'test',
    ];

    $this->cache->expects($this->once())
      ->method('get')
      ->with('locale:en:irrelevant:anonymous', FALSE);

    $this->storage->expects($this->once())
      ->method('findTranslation')
      ->with($this->equalTo($args))
      ->willReturn($result);

    $locale_lookup = $this->getMockBuilder('Drupal\locale\LocaleLookup')
      ->setConstructorArgs(['en', 'irrelevant', $this->storage, $this->cache, $this->lock, $this->configFactory, $this->languageManager, $this->requestStack])
      ->onlyMethods(['persist'])
      ->getMock();
    $locale_lookup->expects($this->never())
      ->method('persist');
    $this->assertSame('test', $locale_lookup->get('test'));
  }

  /**
   * Tests locale lookups with fallback.
   *
   * Note that context is irrelevant here. It is not used but it is required.
   *
   * @covers ::resolveCacheMiss
   *
   * @dataProvider resolveCacheMissWithFallbackProvider
   */
  public function testResolveCacheMissWithFallback($langcode, $string, $context, $expected) {
    // These are fake words!
    // cSpell:disable
    $translations = [
      'en' => [
        'test' => 'test',
        'fake' => 'fake',
        'missing pl' => 'missing pl',
        'missing cs' => 'missing cs',
        'missing both' => 'missing both',
      ],
      'pl' => [
        'test' => 'test po polsku',
        'fake' => 'ściema',
        'missing cs' => 'zaginiony czech',
      ],
      'cs' => [
        'test' => 'test v české',
        'fake' => 'falešný',
        'missing pl' => 'chybějící pl',
      ],
    ];
    // cSpell:enable
    $this->storage->expects($this->any())
      ->method('findTranslation')
      ->willReturnCallback(function ($argument) use ($translations) {
        if (isset($translations[$argument['language']][$argument['source']])) {
          return (object) ['translation' => $translations[$argument['language']][$argument['source']]];
        }

        return TRUE;
      });

    $this->languageManager->expects($this->any())
      ->method('getFallbackCandidates')
      ->willReturnCallback(function (array $context = []) {
        switch ($context['langcode']) {
          case 'pl':
            return ['cs', 'en'];

          case 'cs':
            return ['en'];

          default:
            return [];
        }
      });

    $this->cache->expects($this->once())
      ->method('get')
      ->with('locale:' . $langcode . ':' . $context . ':anonymous', FALSE);

    $locale_lookup = new LocaleLookup($langcode, $context, $this->storage, $this->cache, $this->lock, $this->configFactory, $this->languageManager, $this->requestStack);
    $this->assertSame($expected, $locale_lookup->get($string));
  }

  /**
   * Provides test data for testResolveCacheMissWithFallback().
   */
  public function resolveCacheMissWithFallbackProvider() {
    // cSpell:disable
    return [
      ['cs', 'test', 'irrelevant', 'test v české'],
      ['cs', 'fake', 'irrelevant', 'falešný'],
      ['cs', 'missing pl', 'irrelevant', 'chybějící pl'],
      ['cs', 'missing cs', 'irrelevant', 'missing cs'],
      ['cs', 'missing both', 'irrelevant', 'missing both'],

      // Testing PL with fallback to cs, en.
      ['pl', 'test', 'irrelevant', 'test po polsku'],
      ['pl', 'fake', 'irrelevant', 'ściema'],
      ['pl', 'missing pl', 'irrelevant', 'chybějící pl'],
      ['pl', 'missing cs', 'irrelevant', 'zaginiony czech'],
      ['pl', 'missing both', 'irrelevant', 'missing both'],
    ];
    // cSpell:enable
  }

  /**
   * Tests locale lookups with persistent tracking.
   *
   * @covers ::resolveCacheMiss
   */
  public function testResolveCacheMissWithPersist() {
    $args = [
      'language' => 'en',
      'source' => 'test',
      'context' => 'irrelevant',
    ];

    $result = (object) [
      'translation' => 'test',
    ];

    $this->storage->expects($this->once())
      ->method('findTranslation')
      ->with($this->equalTo($args))
      ->willReturn($result);

    $this->configFactory = $this->getConfigFactoryStub(['locale.settings' => ['cache_strings' => TRUE]]);
    $locale_lookup = $this->getMockBuilder('Drupal\locale\LocaleLookup')
      ->setConstructorArgs(['en', 'irrelevant', $this->storage, $this->cache, $this->lock, $this->configFactory, $this->languageManager, $this->requestStack])
      ->onlyMethods(['persist'])
      ->getMock();
    $locale_lookup->expects($this->once())
      ->method('persist');

    $this->assertSame('test', $locale_lookup->get('test'));
  }

  /**
   * Tests locale lookups without a found translation.
   *
   * @covers ::resolveCacheMiss
   */
  public function testResolveCacheMissNoTranslation() {
    $string = $this->createMock('Drupal\locale\StringInterface');
    $string->expects($this->once())
      ->method('addLocation')
      ->will($this->returnSelf());
    $this->storage->expects($this->once())
      ->method('findTranslation')
      ->willReturn(NULL);
    $this->storage->expects($this->once())
      ->method('createString')
      ->willReturn($string);

    $request = Request::create('/test');
    $this->requestStack->push($request);

    $locale_lookup = $this->getMockBuilder('Drupal\locale\LocaleLookup')
      ->setConstructorArgs(['en', 'irrelevant', $this->storage, $this->cache, $this->lock, $this->configFactory, $this->languageManager, $this->requestStack])
      ->onlyMethods(['persist'])
      ->getMock();
    $locale_lookup->expects($this->never())
      ->method('persist');

    $this->assertTrue($locale_lookup->get('test'));
  }

  /**
   * Tests locale lookups with old plural style of translations.
   *
   * @param array $translations
   *   The source with translations.
   * @param string $langcode
   *   The language code of translation string.
   * @param string $string
   *   The string for translation.
   * @param bool $is_fix
   *   The flag about expected fix translation.
   *
   * @covers ::resolveCacheMiss
   * @dataProvider providerFixOldPluralTranslationProvider
   */
  public function testFixOldPluralStyleTranslations($translations, $langcode, $string, $is_fix) {
    $this->storage->expects($this->any())
      ->method('findTranslation')
      ->willReturnCallback(function ($argument) use ($translations) {
        if (isset($translations[$argument['language']][$argument['source']])) {
          return (object) ['translation' => $translations[$argument['language']][$argument['source']]];
        }

        return TRUE;
      });
    $this->languageManager->expects($this->any())
      ->method('getFallbackCandidates')
      ->willReturnCallback(function (array $context = []) {
        switch ($context['langcode']) {
          case 'by':
            return ['ru'];
        }
      });
    $this->cache->expects($this->once())
      ->method('get')
      ->with('locale:' . $langcode . '::anonymous', FALSE);

    $locale_lookup = new LocaleLookup($langcode, '', $this->storage, $this->cache, $this->lock, $this->configFactory, $this->languageManager, $this->requestStack);
    if ($is_fix) {
      $this->assertStringNotContainsString('@count[2]', $locale_lookup->get($string));
    }
    else {
      $this->assertStringContainsString('@count[2]', $locale_lookup->get($string));
    }
  }

  /**
   * Provides test data for testResolveCacheMissWithFallback().
   */
  public function providerFixOldPluralTranslationProvider() {
    $translations = [
      'by' => [
        'word1' => '@count[2] word-by',
        'word2' => implode(PoItem::DELIMITER, ['word-by', '@count[2] word-by']),
      ],
      'ru' => [
        'word3' => '@count[2] word-ru',
        'word4' => implode(PoItem::DELIMITER, ['word-ru', '@count[2] word-ru']),
      ],
    ];
    return [
      'no-plural' => [$translations, 'by', 'word1', FALSE],
      'no-plural from other language' => [$translations, 'by', 'word3', FALSE],
      'plural' => [$translations, 'by', 'word2', TRUE],
      'plural from other language' => [$translations, 'by', 'word4', TRUE],
    ];
  }

  /**
   * @covers ::getCid
   *
   * @dataProvider getCidProvider
   */
  public function testGetCid(array $roles, $expected) {
    $this->user = $this->createMock('Drupal\Core\Session\AccountInterface');
    $this->user->expects($this->any())
      ->method('getRoles')
      ->willReturn($roles);

    $container = new ContainerBuilder();
    $container->set('current_user', $this->user);
    \Drupal::setContainer($container);

    $locale_lookup = $this->getMockBuilder('Drupal\locale\LocaleLookup')
      ->setConstructorArgs(['en', 'irrelevant', $this->storage, $this->cache, $this->lock, $this->configFactory, $this->languageManager, $this->requestStack])
      ->getMock();

    $o = new \ReflectionObject($locale_lookup);
    $method = $o->getMethod('getCid');
    $method->setAccessible(TRUE);
    $cid = $method->invoke($locale_lookup, 'getCid');

    $this->assertEquals($expected, $cid);
  }

  /**
   * Provides test data for testGetCid().
   */
  public function getCidProvider() {
    return [
      [
        ['a'], 'locale:en:irrelevant:a',
      ],
      [
        ['a', 'b'], 'locale:en:irrelevant:a:b',
      ],
      [
        ['b', 'a'], 'locale:en:irrelevant:a:b',
      ],
    ];
  }

}
