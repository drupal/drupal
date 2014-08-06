<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\Path\AliasManagerTest.
 */

namespace Drupal\Tests\Core\Path;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Language\Language;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Path\AliasManager;
use Drupal\Core\Url;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;

/**
 * @coversDefaultClass \Drupal\Core\Path\AliasManager
 * @group Path
 */
class AliasManagerTest extends UnitTestCase {

  /**
   * The alias manager.
   *
   * @var \Drupal\Core\Path\AliasManager
   */
  protected $aliasManager;

  /**
   * Alias storage.
   *
   * @var \Drupal\Core\Path\AliasStorageInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $aliasStorage;

  /**
   * Alias whitelist.
   *
   * @var \Drupal\Core\Path\AliasWhitelistInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $aliasWhitelist;

  /**
   * Language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $languageManager;

  /**
   * Cache backend.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface|\PHPUnit_Framework_MockObject_MockObject
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
  protected function setUp() {
    parent::setUp();

    $this->aliasStorage = $this->getMock('Drupal\Core\Path\AliasStorageInterface');
    $this->aliasWhitelist = $this->getMock('Drupal\Core\Path\AliasWhitelistInterface');
    $this->languageManager = $this->getMock('Drupal\Core\Language\LanguageManagerInterface');
    $this->cache = $this->getMock('Drupal\Core\Cache\CacheBackendInterface');

    $this->aliasManager = new AliasManager($this->aliasStorage, $this->aliasWhitelist, $this->languageManager, $this->cache);

  }

  /**
   * Tests the getPathByAlias method for an alias that have no matching path.
   *
   * @covers ::getPathByAlias()
   */
  public function testGetPathByAliasNoMatch() {
    $alias = $this->randomMachineName();

    $language = new Language(array('id' => 'en'));

    $this->languageManager->expects($this->any())
      ->method('getCurrentLanguage')
      ->with(LanguageInterface::TYPE_URL)
      ->will($this->returnValue($language));

    $this->aliasStorage->expects($this->once())
      ->method('lookupPathSource')
      ->with($alias, $language->getId())
      ->will($this->returnValue(NULL));

    $this->assertEquals($alias, $this->aliasManager->getPathByAlias($alias));
    // Call it twice to test the static cache.
    $this->assertEquals($alias, $this->aliasManager->getPathByAlias($alias));
  }

  /**
   * Tests the getPathByAlias method for an alias that have a matching path.
   *
   * @covers ::getPathByAlias()
   */
  public function testGetPathByAliasNatch() {
    $alias = $this->randomMachineName();
    $path = $this->randomMachineName();

    $language = $this->setUpCurrentLanguage();

    $this->aliasStorage->expects($this->once())
      ->method('lookupPathSource')
      ->with($alias, $language->getId())
      ->will($this->returnValue($path));

    $this->assertEquals($path, $this->aliasManager->getPathByAlias($alias));
    // Call it twice to test the static cache.
    $this->assertEquals($path, $this->aliasManager->getPathByAlias($alias));
  }

  /**
   * Tests the getPathByAlias method when a langcode is passed explicitly.
   *
   * @covers ::getPathByAlias()
   */
  public function testGetPathByAliasLangcode() {
    $alias = $this->randomMachineName();
    $path = $this->randomMachineName();

    $this->languageManager->expects($this->never())
      ->method('getCurrentLanguage');

    $this->aliasStorage->expects($this->once())
      ->method('lookupPathSource')
      ->with($alias, 'de')
      ->will($this->returnValue($path));

    $this->assertEquals($path, $this->aliasManager->getPathByAlias($alias, 'de'));
    // Call it twice to test the static cache.
    $this->assertEquals($path, $this->aliasManager->getPathByAlias($alias, 'de'));
  }


  /**
   * Tests the getAliasByPath method for a path that is not in the whitelist.
   *
   * @covers ::getAliasByPath()
   */
  public function testGetAliasByPathWhitelist() {
    $path_part1 = $this->randomMachineName();
    $path_part2 = $this->randomMachineName();
    $path = $path_part1 . '/' . $path_part2;

    $this->setUpCurrentLanguage();

    $this->aliasWhitelist->expects($this->any())
      ->method('get')
      ->with($path_part1)
      ->will($this->returnValue(FALSE));

    // The whitelist returns FALSE for that path part, so the storage should
    // never be called.
    $this->aliasStorage->expects($this->never())
      ->method('lookupPathAlias');

    $this->assertEquals($path, $this->aliasManager->getAliasByPath($path));
  }

  /**
   * Tests the getAliasByPath method for a path that has no matching alias.
   *
   * @covers ::getAliasByPath()
   */
  public function testGetAliasByPathNoMatch() {
    $path_part1 = $this->randomMachineName();
    $path_part2 = $this->randomMachineName();
    $path = $path_part1 . '/' . $path_part2;

    $language = $this->setUpCurrentLanguage();

    $this->aliasManager->setCacheKey($this->path);

    $this->aliasWhitelist->expects($this->any())
      ->method('get')
      ->with($path_part1)
      ->will($this->returnValue(TRUE));

    $this->aliasStorage->expects($this->once())
      ->method('lookupPathAlias')
      ->with($path, $language->getId())
      ->will($this->returnValue(NULL));

    $this->assertEquals($path, $this->aliasManager->getAliasByPath($path));
    // Call it twice to test the static cache.
    $this->assertEquals($path, $this->aliasManager->getAliasByPath($path));

    // This needs to write out the cache.
    $this->cache->expects($this->once())
      ->method('set')
      ->with($this->cacheKey, array($language->getId() => array($path)), REQUEST_TIME + (60 * 60 * 24));

    $this->aliasManager->writeCache();
  }

  /**
   * Tests the getAliasByPath method for a path that has a matching alias.
   *
   * @covers ::getAliasByPath()
   * @covers ::writeCache()
   */
  public function testGetAliasByPathMatch() {
    $path_part1 = $this->randomMachineName();
    $path_part2 = $this->randomMachineName();
    $path = $path_part1 . '/' . $path_part2;
    $alias = $this->randomMachineName();

    $language = $this->setUpCurrentLanguage();

    $this->aliasManager->setCacheKey($this->path);

    $this->aliasWhitelist->expects($this->any())
      ->method('get')
      ->with($path_part1)
      ->will($this->returnValue(TRUE));

    $this->aliasStorage->expects($this->once())
      ->method('lookupPathAlias')
      ->with($path, $language->getId())
      ->will($this->returnValue($alias));

    $this->assertEquals($alias, $this->aliasManager->getAliasByPath($path));
    // Call it twice to test the static cache.
    $this->assertEquals($alias, $this->aliasManager->getAliasByPath($path));

    // This needs to write out the cache.
    $this->cache->expects($this->once())
      ->method('set')
      ->with($this->cacheKey, array($language->getId() => array($path)), REQUEST_TIME + (60 * 60 * 24));

    $this->aliasManager->writeCache();
  }

  /**
   * Tests the getAliasByPath method for a path that is preloaded.
   *
   * @covers ::getAliasByPath()
   * @covers ::writeCache()
   */
  public function testGetAliasByPathCachedMatch() {
    $path_part1 = $this->randomMachineName();
    $path_part2 = $this->randomMachineName();
    $path = $path_part1 . '/' . $path_part2;
    $alias = $this->randomMachineName();

    $language = $this->setUpCurrentLanguage();

    $cached_paths = array($language->getId() => array($path));
    $this->cache->expects($this->once())
      ->method('get')
      ->with($this->cacheKey)
      ->will($this->returnValue((object) array('data' => $cached_paths)));

    // Simulate a request so that the preloaded paths are fetched.
    $this->aliasManager->setCacheKey($this->path);

    $this->aliasWhitelist->expects($this->any())
      ->method('get')
      ->with($path_part1)
      ->will($this->returnValue(TRUE));

    $this->aliasStorage->expects($this->once())
      ->method('preloadPathAlias')
      ->with($cached_paths[$language->getId()], $language->getId())
      ->will($this->returnValue(array($path => $alias)));

    // LookupPathAlias should not be called.
    $this->aliasStorage->expects($this->never())
      ->method('lookupPathAlias');

    $this->assertEquals($alias, $this->aliasManager->getAliasByPath($path));
    // Call it twice to test the static cache.
    $this->assertEquals($alias, $this->aliasManager->getAliasByPath($path));

    // This must not write to the cache again.
    $this->cache->expects($this->never())
      ->method('set');
    $this->aliasManager->writeCache();
  }

  /**
   * Tests the getAliasByPath cache when a different language is requested.
   *
   * @covers ::getAliasByPath()
   * @covers ::writeCache()
   */
  public function testGetAliasByPathCachedMissLanguage() {
    $path_part1 = $this->randomMachineName();
    $path_part2 = $this->randomMachineName();
    $path = $path_part1 . '/' . $path_part2;
    $alias = $this->randomMachineName();

    $language = $this->setUpCurrentLanguage();
    $cached_language = new Language(array('id' => 'de'));

    $cached_paths = array($cached_language->getId() => array($path));
    $this->cache->expects($this->once())
      ->method('get')
      ->with($this->cacheKey)
      ->will($this->returnValue((object) array('data' => $cached_paths)));

    // Simulate a request so that the preloaded paths are fetched.
    $this->aliasManager->setCacheKey($this->path);

    $this->aliasWhitelist->expects($this->any())
      ->method('get')
      ->with($path_part1)
      ->will($this->returnValue(TRUE));

    // The requested language is different than the cached, so this will
    // need to load.
    $this->aliasStorage->expects($this->never())
      ->method('preloadPathAlias');
    $this->aliasStorage->expects($this->once())
      ->method('lookupPathAlias')
      ->with($path, $language->getId())
      ->will($this->returnValue($alias));

    $this->assertEquals($alias, $this->aliasManager->getAliasByPath($path));
    // Call it twice to test the static cache.
    $this->assertEquals($alias, $this->aliasManager->getAliasByPath($path));

    // This needs to write out the cache.
    $expected_new_cache = array(
      $cached_language->getId() => array($path),
      $language->getId() => array($path),
    );
    $this->cache->expects($this->once())
      ->method('set')
      ->with($this->cacheKey, $expected_new_cache, REQUEST_TIME + (60 * 60 * 24));
    $this->aliasManager->writeCache();
  }

  /**
   * Tests the getAliasByPath cache with a preloaded path without alias.
   *
   * @covers ::getAliasByPath()
   * @covers ::writeCache()
   */
  public function testGetAliasByPathCachedMissNoAlias() {
    $path_part1 = $this->randomMachineName();
    $path_part2 = $this->randomMachineName();
    $path = $path_part1 . '/' . $path_part2;
    $cached_path = $this->randomMachineName();
    $cached_alias = $this->randomMachineName();

    $language = $this->setUpCurrentLanguage();

    $cached_paths = array($language->getId() => array($cached_path, $path));
    $this->cache->expects($this->once())
      ->method('get')
      ->with($this->cacheKey)
      ->will($this->returnValue((object) array('data' => $cached_paths)));

    // Simulate a request so that the preloaded paths are fetched.
    $this->aliasManager->setCacheKey($this->path);

    $this->aliasWhitelist->expects($this->any())
      ->method('get')
      ->with($path_part1)
      ->will($this->returnValue(TRUE));

    $this->aliasStorage->expects($this->once())
      ->method('preloadPathAlias')
      ->with($cached_paths[$language->getId()], $language->getId())
      ->will($this->returnValue(array($cached_path => $cached_alias)));

    // LookupPathAlias() should not be called.
    $this->aliasStorage->expects($this->never())
      ->method('lookupPathAlias');

    $this->assertEquals($path, $this->aliasManager->getAliasByPath($path));
    // Call it twice to test the static cache.
    $this->assertEquals($path, $this->aliasManager->getAliasByPath($path));

    // This must not write to the cache again.
    $this->cache->expects($this->never())
      ->method('set');
    $this->aliasManager->writeCache();
  }

  /**
   * Tests the getAliasByPath cache with an unpreloaded path without alias.
   *
   * @covers ::getAliasByPath()
   * @covers ::writeCache()
   */
  public function testGetAliasByPathUncachedMissNoAlias() {
    $path_part1 = $this->randomMachineName();
    $path_part2 = $this->randomMachineName();
    $path = $path_part1 . '/' . $path_part2;
    $cached_path = $this->randomMachineName();
    $cached_alias = $this->randomMachineName();

    $language = $this->setUpCurrentLanguage();

    $cached_paths = array($language->getId() => array($cached_path));
    $this->cache->expects($this->once())
      ->method('get')
      ->with($this->cacheKey)
      ->will($this->returnValue((object) array('data' => $cached_paths)));

    // Simulate a request so that the preloaded paths are fetched.
    $this->aliasManager->setCacheKey($this->path);

    $this->aliasWhitelist->expects($this->any())
      ->method('get')
      ->with($path_part1)
      ->will($this->returnValue(TRUE));

    $this->aliasStorage->expects($this->once())
      ->method('preloadPathAlias')
      ->with($cached_paths[$language->getId()], $language->getId())
      ->will($this->returnValue(array($cached_path => $cached_alias)));

    $this->aliasStorage->expects($this->once())
      ->method('lookupPathAlias')
      ->with($path, $language->getId())
      ->will($this->returnValue(NULL));

    $this->assertEquals($path, $this->aliasManager->getAliasByPath($path));
    // Call it twice to test the static cache.
    $this->assertEquals($path, $this->aliasManager->getAliasByPath($path));

    // This needs to write out the cache.
    $expected_new_cache = array(
      $language->getId() => array($cached_path, $path),
    );
    $this->cache->expects($this->once())
      ->method('set')
      ->with($this->cacheKey, $expected_new_cache, REQUEST_TIME + (60 * 60 * 24));
    $this->aliasManager->writeCache();
  }

  /**
   * Tests the getAliasByPath cache with an unpreloaded path with alias.
   *
   * @covers ::getAliasByPath()
   * @covers ::writeCache()
   */
  public function testGetAliasByPathUncachedMissWithAlias() {
    $path_part1 = $this->randomMachineName();
    $path_part2 = $this->randomMachineName();
    $path = $path_part1 . '/' . $path_part2;
    $cached_path = $this->randomMachineName();
    $cached_no_alias_path = $this->randomMachineName();
    $cached_alias = $this->randomMachineName();
    $new_alias = $this->randomMachineName();

    $language = $this->setUpCurrentLanguage();

    $cached_paths = array($language->getId() => array($cached_path, $cached_no_alias_path));
    $this->cache->expects($this->once())
      ->method('get')
      ->with($this->cacheKey)
      ->will($this->returnValue((object) array('data' => $cached_paths)));

    // Simulate a request so that the preloaded paths are fetched.
    $this->aliasManager->setCacheKey($this->path);

    $this->aliasWhitelist->expects($this->any())
      ->method('get')
      ->with($path_part1)
      ->will($this->returnValue(TRUE));

    $this->aliasStorage->expects($this->once())
      ->method('preloadPathAlias')
      ->with($cached_paths[$language->getId()], $language->getId())
      ->will($this->returnValue(array($cached_path => $cached_alias)));

    $this->aliasStorage->expects($this->once())
      ->method('lookupPathAlias')
      ->with($path, $language->getId())
      ->will($this->returnValue($new_alias));

    $this->assertEquals($new_alias, $this->aliasManager->getAliasByPath($path));
    // Call it twice to test the static cache.
    $this->assertEquals($new_alias, $this->aliasManager->getAliasByPath($path));

    // This needs to write out the cache.
    $expected_new_cache = array(
      $language->getId() => array($cached_path, $path, $cached_no_alias_path),
    );
    $this->cache->expects($this->once())
      ->method('set')
      ->with($this->cacheKey, $expected_new_cache, REQUEST_TIME + (60 * 60 * 24));
    $this->aliasManager->writeCache();
  }

  /**
   * Sets up the current language.
   *
   * @return \Drupal\Core\Language\LanguageInterface
   *   The current language object.
   */
  protected function setUpCurrentLanguage() {
    $language = new Language(array('id' => 'en'));

    $this->languageManager->expects($this->any())
      ->method('getCurrentLanguage')
      ->with(LanguageInterface::TYPE_URL)
      ->will($this->returnValue($language));

    return $language;
  }

}
