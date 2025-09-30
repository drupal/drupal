<?php

declare(strict_types=1);

namespace Drupal\Tests\path_alias\Unit;

use Drupal\Component\Datetime\Time;
use Drupal\Core\Language\Language;
use Drupal\Core\Language\LanguageInterface;
use Drupal\path_alias\AliasManager;
use Drupal\path_alias\AliasRepositoryInterface;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests Drupal\path_alias\AliasManager.
 */
#[CoversClass(AliasManager::class)]
#[Group('path_alias')]
class AliasManagerTest extends UnitTestCase {

  /**
   * The alias manager.
   *
   * @var \Drupal\path_alias\AliasManager
   */
  protected $aliasManager;

  /**
   * Alias repository.
   *
   * @var \Drupal\path_alias\AliasRepositoryInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $aliasRepository;

  /**
   * Alias prefix list.
   *
   * @var \Drupal\path_alias\AliasPrefixListInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $aliasPrefixList;

  /**
   * Language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $languageManager;

  /**
   * Cache backend.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $cache;

  /**
   * The internal cache key used by the alias manager.
   *
   * @var string
   */
  protected $cacheKey = 'preload-paths:key';

  /**
   * The cache key passed to the alias manager.
   *
   * @var string
   */
  protected $path = 'key';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->aliasRepository = $this->createMock(AliasRepositoryInterface::class);
    $this->aliasPrefixList = $this->createMock('Drupal\path_alias\AliasPrefixListInterface');
    $this->languageManager = $this->createMock('Drupal\Core\Language\LanguageManagerInterface');
    $this->cache = $this->createMock('Drupal\Core\Cache\CacheBackendInterface');

    $this->aliasManager = new AliasManager($this->aliasRepository, $this->aliasPrefixList, $this->languageManager, $this->cache, new Time());

  }

  /**
   * Tests the getPathByAlias method for an alias that have no matching path.
   *
   * @legacy-covers ::getPathByAlias
   */
  public function testGetPathByAliasNoMatch(): void {
    $alias = '/' . $this->randomMachineName();

    $language = new Language(['id' => 'en']);

    $this->languageManager->expects($this->any())
      ->method('getCurrentLanguage')
      ->with(LanguageInterface::TYPE_URL)
      ->willReturn($language);

    $this->aliasRepository->expects($this->once())
      ->method('lookupByAlias')
      ->with($alias, $language->getId())
      ->willReturn(NULL);

    $this->assertEquals($alias, $this->aliasManager->getPathByAlias($alias));
    // Call it twice to test the static cache.
    $this->assertEquals($alias, $this->aliasManager->getPathByAlias($alias));
  }

  /**
   * Tests the getPathByAlias method for an alias that have a matching path.
   *
   * @legacy-covers ::getPathByAlias
   */
  public function testGetPathByAliasMatch(): void {
    $alias = $this->randomMachineName();
    $path = $this->randomMachineName();

    $language = $this->setUpCurrentLanguage();

    $this->aliasRepository->expects($this->once())
      ->method('lookupByAlias')
      ->with($alias, $language->getId())
      ->willReturn(['path' => $path]);

    $this->assertEquals($path, $this->aliasManager->getPathByAlias($alias));
    // Call it twice to test the static cache.
    $this->assertEquals($path, $this->aliasManager->getPathByAlias($alias));
  }

  /**
   * Tests the getPathByAlias method when a langcode is passed explicitly.
   *
   * @legacy-covers ::getPathByAlias
   */
  public function testGetPathByAliasLangcode(): void {
    $alias = $this->randomMachineName();
    $path = $this->randomMachineName();

    $this->languageManager->expects($this->never())
      ->method('getCurrentLanguage');

    $this->aliasRepository->expects($this->once())
      ->method('lookupByAlias')
      ->with($alias, 'de')
      ->willReturn(['path' => $path]);

    $this->assertEquals($path, $this->aliasManager->getPathByAlias($alias, 'de'));
    // Call it twice to test the static cache.
    $this->assertEquals($path, $this->aliasManager->getPathByAlias($alias, 'de'));
  }

  /**
   * Tests the getAliasByPath method for a path that is not in the prefix list.
   *
   * @legacy-covers ::getAliasByPath
   */
  public function testGetAliasByPathPrefixList() {
    $path_part1 = $this->randomMachineName();
    $path_part2 = $this->randomMachineName();
    $path = '/' . $path_part1 . '/' . $path_part2;

    $this->setUpCurrentLanguage();

    $this->aliasPrefixList->expects($this->any())
      ->method('get')
      ->with($path_part1)
      ->willReturn(FALSE);

    // The prefix list returns FALSE for that path part, so the storage should
    // never be called.
    $this->aliasRepository->expects($this->never())
      ->method('lookupBySystemPath');

    $this->assertEquals($path, $this->aliasManager->getAliasByPath($path));
  }

  /**
   * Tests the getAliasByPath method for a path that has no matching alias.
   *
   * @legacy-covers ::getAliasByPath
   */
  public function testGetAliasByPathNoMatch(): void {
    $path_part1 = $this->randomMachineName();
    $path_part2 = $this->randomMachineName();
    $path = '/' . $path_part1 . '/' . $path_part2;

    $language = $this->setUpCurrentLanguage();

    $this->aliasPrefixList->expects($this->any())
      ->method('get')
      ->with($path_part1)
      ->willReturn(TRUE);

    $this->aliasRepository->expects($this->once())
      ->method('preloadPathAlias')
      ->with([$path => $path], $language->getId())
      ->willReturn([]);

    $this->assertEquals($path, $this->aliasManager->getAliasByPath($path));
    // Call it twice to test the static cache.
    $this->assertEquals($path, $this->aliasManager->getAliasByPath($path));
  }

  /**
   * Tests the getAliasByPath method exception.
   *
   * @legacy-covers ::getAliasByPath
   */
  public function testGetAliasByPathException(): void {
    $this->expectException(\InvalidArgumentException::class);
    $this->aliasManager->getAliasByPath('no-leading-slash-here');
  }

  /**
   * Tests the getAliasByPath method for a path that has a matching alias.
   *
   * @legacy-covers ::getAliasByPath
   */
  public function testGetAliasByPathMatch(): void {
    $path_part1 = $this->randomMachineName();
    $path_part2 = $this->randomMachineName();
    $path = '/' . $path_part1 . '/' . $path_part2;
    $alias = $this->randomMachineName();

    $language = $this->setUpCurrentLanguage();

    $this->aliasPrefixList->expects($this->any())
      ->method('get')
      ->with($path_part1)
      ->willReturn(TRUE);

    $this->aliasRepository->expects($this->once())
      ->method('preloadPathAlias')
      ->with([$path => $path], $language->getId())
      ->willReturn([$path => $alias]);

    $this->assertEquals($alias, $this->aliasManager->getAliasByPath($path));
    // Call it twice to test the static cache.
    $this->assertEquals($alias, $this->aliasManager->getAliasByPath($path));
  }

  /**
   * Tests the getAliasByPath cache when a different language is requested.
   *
   * @legacy-covers ::getAliasByPath
   */
  public function testGetAliasByPathCachedMissLanguage(): void {
    $path_part1 = $this->randomMachineName();
    $path_part2 = $this->randomMachineName();
    $path = '/' . $path_part1 . '/' . $path_part2;
    $alias = $this->randomMachineName();

    $language = $this->setUpCurrentLanguage();
    // @todo Test no longer tests anything different useful. Call explicitly
    //   with two different language codes?

    $this->aliasPrefixList->expects($this->any())
      ->method('get')
      ->with($path_part1)
      ->willReturn(TRUE);

    // The requested language is different than the cached, so this will
    // need to load.
    $this->aliasRepository->expects($this->once())
      ->method('preloadPathAlias')
      ->with([$path => $path], $language->getId())
      ->willReturn([$path => $alias]);

    $this->assertEquals($alias, $this->aliasManager->getAliasByPath($path));
    // Call it twice to test the static cache.
    $this->assertEquals($alias, $this->aliasManager->getAliasByPath($path));
  }

  /**
   * Tests cache clear.
   *
   * @legacy-covers ::cacheClear
   */
  public function testCacheClear(): void {
    $path = '/path';
    $alias = '/alias';
    $language = $this->setUpCurrentLanguage();
    $this->aliasRepository->expects($this->exactly(2))
      ->method('preloadPathAlias')
      ->with([$path => $path], $language->getId())
      ->willReturn([$path => $alias]);
    $this->aliasPrefixList->expects($this->any())
      ->method('get')
      ->willReturn(TRUE);

    // Populate the lookup map.
    $this->assertEquals($alias, $this->aliasManager->getAliasByPath($path, $language->getId()));

    // Check that the cache is populated.
    $this->aliasRepository->expects($this->never())
      ->method('lookupByAlias');
    $this->assertEquals($path, $this->aliasManager->getPathByAlias($alias, $language->getId()));

    // Clear specific source.
    $this->aliasManager->cacheClear($path);

    // Ensure cache has been cleared (this will be the 2nd call to
    // `lookupPathAlias` if cache is cleared).
    $this->assertEquals($alias, $this->aliasManager->getAliasByPath($path, $language->getId()));

    // Clear non-existent source.
    $this->aliasManager->cacheClear('non-existent');
  }

  /**
   * Sets up the current language.
   *
   * @return \Drupal\Core\Language\LanguageInterface
   *   The current language object.
   */
  protected function setUpCurrentLanguage() {
    $language = new Language(['id' => 'en']);

    $this->languageManager->expects($this->any())
      ->method('getCurrentLanguage')
      ->with(LanguageInterface::TYPE_URL)
      ->willReturn($language);

    return $language;
  }

}
