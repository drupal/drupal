<?php

/**
 * @file
 * Contains \Drupal\KernelTests\Core\Path\AliasStorageTest.
 */

namespace Drupal\KernelTests\Core\Path;

use Drupal\Core\Language\LanguageInterface;
use Drupal\KernelTests\KernelTestBase;

/**
 * @coversDefaultClass \Drupal\Core\Path\AliasStorage
 * @group path
 */
class AliasStorageTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['system'];

  /** @var \Drupal\Core\Path\AliasStorage */
  protected $storage;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installSchema('system', 'url_alias');
    $this->storage = $this->container->get('path.alias_storage');
  }

  /**
   * @covers ::load
   */
  public function testLoad() {
    $this->storage->save('/test-source-Case', '/test-alias-Case');

    $expected = [
      'pid' => 1,
      'alias' => '/test-alias-Case',
      'source' => '/test-source-Case',
      'langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED,
    ];

    $this->assertEquals($expected, $this->storage->load(['alias' => '/test-alias-Case']));
    $this->assertEquals($expected, $this->storage->load(['alias' => '/test-alias-case']));
    $this->assertEquals($expected, $this->storage->load(['source' => '/test-source-Case']));
    $this->assertEquals($expected, $this->storage->load(['source' => '/test-source-case']));
  }

  /**
   * @covers ::lookupPathAlias
   */
  public function testLookupPathAlias() {
    $this->storage->save('/test-source-Case', '/test-alias');

    $this->assertEquals('/test-alias', $this->storage->lookupPathAlias('/test-source-Case', LanguageInterface::LANGCODE_NOT_SPECIFIED));
    $this->assertEquals('/test-alias', $this->storage->lookupPathAlias('/test-source-case', LanguageInterface::LANGCODE_NOT_SPECIFIED));
  }

  /**
   * @covers ::lookupPathSource
   */
  public function testLookupPathSource() {
    $this->storage->save('/test-source', '/test-alias-Case');

    $this->assertEquals('/test-source', $this->storage->lookupPathSource('/test-alias-Case', LanguageInterface::LANGCODE_NOT_SPECIFIED));
    $this->assertEquals('/test-source', $this->storage->lookupPathSource('/test-alias-case', LanguageInterface::LANGCODE_NOT_SPECIFIED));
  }

  /**
   * @covers ::aliasExists
   */
  public function testAliasExists() {
    $this->storage->save('/test-source-Case', '/test-alias-Case');

    $this->assertTrue($this->storage->aliasExists('/test-alias-Case', LanguageInterface::LANGCODE_NOT_SPECIFIED));
    $this->assertTrue($this->storage->aliasExists('/test-alias-case', LanguageInterface::LANGCODE_NOT_SPECIFIED));
  }

}
