<?php

declare(strict_types=1);

namespace Drupal\Tests\locale\Unit;

use Drupal\Component\Gettext\PoItem;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Lock\LockBackendInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\locale\LocaleLookup;
use Drupal\locale\StringStorageInterface;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Tests Drupal\locale\LocaleLookup.
 */
#[CoversClass(LocaleLookup::class)]
#[Group('locale')]
class LocaleLookupTest extends UnitTestCase {

  /**
   * A mocked storage to use when instantiating LocaleTranslation objects.
   */
  protected StringStorageInterface&MockObject $storage;

  /**
   * A mocked lock object.
   */
  protected LockBackendInterface&MockObject $lock;

  /**
   * A mocked user object built from AccountInterface.
   *
   * @var \Drupal\Core\Session\AccountInterface|\PHPUnit\Framework\MockObject\Stub
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
   * @var \Drupal\Core\Language\LanguageManagerInterface|\PHPUnit\Framework\MockObject\Stub
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
    $this->lock = $this->createMock('Drupal\Core\Lock\LockBackendInterface');
    $this->lock->expects($this->never())
      ->method($this->anything());

    $this->user = $this->createStub(AccountInterface::class);
    $this->user
      ->method('getRoles')
      ->willReturn(['anonymous']);

    $this->configFactory = $this->getConfigFactoryStub(['locale.settings' => ['cache_strings' => FALSE]]);

    $this->languageManager = $this->createStub(LanguageManagerInterface::class);
    $this->requestStack = new RequestStack();

    $container = new ContainerBuilder();
    $container->set('current_user', $this->user);
    \Drupal::setContainer($container);
  }

  /**
   * Tests locale lookups without fallback.
   */
  public function testResolveCacheMissWithoutFallback(): void {
    $args = [
      'language' => 'en',
      'source' => 'test',
      'context' => 'irrelevant',
    ];

    $result = (object) [
      'translation' => 'test',
    ];

    $cache = $this->createMock(CacheBackendInterface::class);
    $cache->expects($this->once())
      ->method('get')
      ->with('locale:en:irrelevant:anonymous', FALSE);

    $this->storage->expects($this->once())
      ->method('findTranslation')
      ->with($this->equalTo($args))
      ->willReturn($result);

    $locale_lookup = $this->getMockBuilder('Drupal\locale\LocaleLookup')
      ->setConstructorArgs([
        'en',
        'irrelevant',
        $this->storage,
        $cache,
        $this->lock,
        $this->configFactory,
        $this->languageManager,
        $this->requestStack,
      ])
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
   */
  #[DataProvider('resolveCacheMissWithFallbackProvider')]
  public function testResolveCacheMissWithFallback(string $langcode, string $string, string $context, string $expected): void {
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
    $this->storage->expects($this->atLeastOnce())
      ->method('findTranslation')
      ->willReturnCallback(function (array $argument) use ($translations) {
        if (isset($translations[$argument['language']][$argument['source']])) {
          return (object) ['translation' => $translations[$argument['language']][$argument['source']]];
        }

        return TRUE;
      });

    $this->languageManager
      ->method('getFallbackCandidates')
      ->willReturnCallback(function (array $context = []): array {
        switch ($context['langcode']) {
          case 'pl':
            return ['cs', 'en'];

          case 'cs':
            return ['en'];

          default:
            return [];
        }
      });

    $cache = $this->createMock(CacheBackendInterface::class);
    $cache->expects($this->once())
      ->method('get')
      ->with('locale:' . $langcode . ':' . $context . ':anonymous', FALSE);

    $locale_lookup = new LocaleLookup($langcode, $context, $this->storage, $cache, $this->lock, $this->configFactory, $this->languageManager, $this->requestStack);
    $this->assertSame($expected, $locale_lookup->get($string));
  }

  /**
   * Provides test data for testResolveCacheMissWithFallback().
   */
  public static function resolveCacheMissWithFallbackProvider(): array {
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
   */
  public function testResolveCacheMissWithPersist(): void {
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
      ->setConstructorArgs([
        'en',
        'irrelevant',
        $this->storage,
        $this->createStub(CacheBackendInterface::class),
        $this->lock,
        $this->configFactory,
        $this->languageManager,
        $this->requestStack,
      ])
      ->onlyMethods(['persist'])
      ->getMock();
    $locale_lookup->expects($this->once())
      ->method('persist');

    $this->assertSame('test', $locale_lookup->get('test'));
  }

  /**
   * Tests locale lookups without a found translation.
   */
  public function testResolveCacheMissNoTranslation(): void {
    $string = $this->createMock('Drupal\locale\StringInterface');
    $string->expects($this->once())
      ->method('addLocation')
      ->willReturnSelf();

    $this->storage->expects($this->once())
      ->method('findTranslation')
      ->willReturn(NULL);
    $this->storage->expects($this->once())
      ->method('createString')
      ->willReturn($string);

    $request = Request::create('/test');
    $this->requestStack->push($request);

    $locale_lookup = $this->getMockBuilder('Drupal\locale\LocaleLookup')
      ->setConstructorArgs([
        'en',
        'irrelevant',
        $this->storage,
        $this->createStub(CacheBackendInterface::class),
        $this->lock,
        $this->configFactory,
        $this->languageManager,
        $this->requestStack,
      ])
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
   * @legacy-covers ::resolveCacheMiss
   */
  #[DataProvider('providerFixOldPluralTranslationProvider')]
  public function testFixOldPluralStyleTranslations(array $translations, string $langcode, string $string, bool $is_fix): void {
    $this->storage->expects($this->atLeastOnce())
      ->method('findTranslation')
      ->willReturnCallback(function (array $argument) use ($translations) {
        if (isset($translations[$argument['language']][$argument['source']])) {
          return (object) ['translation' => $translations[$argument['language']][$argument['source']]];
        }

        return TRUE;
      });
    $this->languageManager
      ->method('getFallbackCandidates')
      ->willReturnCallback(function (array $context = []) {
        switch ($context['langcode']) {
          case 'by':
            return ['ru'];
        }
      });
    $cache = $this->createMock(CacheBackendInterface::class);
    $cache->expects($this->once())
      ->method('get')
      ->with('locale:' . $langcode . '::anonymous', FALSE);

    $locale_lookup = new LocaleLookup($langcode, '', $this->storage, $cache, $this->lock, $this->configFactory, $this->languageManager, $this->requestStack);
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
  public static function providerFixOldPluralTranslationProvider(): array {
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
   * Tests get cid.
   */
  #[DataProvider('getCidProvider')]
  public function testGetCid(array $roles, string $expected): void {
    $this->storage->expects($this->never())
      ->method('findTranslation');

    $this->user = $this->createStub(AccountInterface::class);
    $this->user
      ->method('getRoles')
      ->willReturn($roles);

    $container = new ContainerBuilder();
    $container->set('current_user', $this->user);
    \Drupal::setContainer($container);

    $locale_lookup = new LocaleLookup(
      'en',
      'irrelevant',
      $this->storage,
      $this->createStub(CacheBackendInterface::class),
      $this->lock,
      $this->configFactory,
      $this->languageManager,
      $this->requestStack,
    );

    $o = new \ReflectionObject($locale_lookup);
    $method = $o->getMethod('getCid');
    $cid = $method->invoke($locale_lookup, 'getCid');

    $this->assertEquals($expected, $cid);
  }

  /**
   * Provides test data for testGetCid().
   */
  public static function getCidProvider(): array {
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
