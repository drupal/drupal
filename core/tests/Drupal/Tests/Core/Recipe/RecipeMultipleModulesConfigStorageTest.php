<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Recipe;

use Drupal\Core\Config\StorageInterface;
use Drupal\Core\Extension\Extension;
use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\Core\Recipe\RecipeMultipleModulesConfigStorage;
use Drupal\Tests\UnitTestCase;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\TestWith;
use Symfony\Component\Yaml\Yaml;

/**
 * Tests RecipeMultipleModulesConfigStorage.
 */
#[Group('Recipe')]
#[CoversClass(RecipeMultipleModulesConfigStorage::class)]
class RecipeMultipleModulesConfigStorageTest extends UnitTestCase {

  /**
   * The mocked module extension list.
   */
  protected ModuleExtensionList $extensionList;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    vfsStream::setup('root', NULL, [
      'modules' => [
        'system' => [
          'config' => [
            'install' => [
              'system.site.yml' => Yaml::dump(['name' => 'Site A']),
              'node.settings.yml' => Yaml::dump(['use_admin_theme' => TRUE]),
            ],
          ],
        ],
        'system_test' => [
          'config' => [
            'install' => [
              'system_test.settings.yml' => Yaml::dump(['verbose' => TRUE]),
            ],
          ],
        ],
        'user' => [
          'config' => [
            'install' => [
              'system.site.yml' => Yaml::dump(['name' => 'Site B']),
              'user.settings.yml' => Yaml::dump(['register' => 'visitors']),
            ],
          ],
        ],
      ],
    ]);

    $systemExtension = $this->createStub(Extension::class);
    $systemExtension->method('getPath')->willReturn('vfs://root/modules/system');

    $systemTestExtension = $this->createStub(Extension::class);
    $systemTestExtension->method('getPath')->willReturn('vfs://root/modules/system_test');

    $userExtension = $this->createStub(Extension::class);
    $userExtension->method('getPath')->willReturn('vfs://root/modules/user');

    $this->extensionList = $this->createStub(ModuleExtensionList::class);
    $this->extensionList->method('get')->willReturnMap([
      ['system', $systemExtension],
      ['system_test', $systemTestExtension],
      ['user', $userExtension],
    ]);
  }

  /**
   * Tests exists() returns TRUE when config is in any directory.
   */
  public function testExists(): void {
    $storage = RecipeMultipleModulesConfigStorage::createFromModuleList(['system', 'user'], $this->extensionList);

    // Config in System module only but does not begin with 'system.'.
    $this->assertFalse($storage->exists('node.settings'));
    // Config in User module.
    $this->assertTrue($storage->exists('user.settings'));
    // Config in both directories.
    $this->assertTrue($storage->exists('system.site'));
    // Config that does not exist anywhere.
    $this->assertFalse($storage->exists('nonexistent.config'));
  }

  /**
   * Tests read() returns from the first directory that has the config.
   */
  public function testRead(): void {
    $storage = RecipeMultipleModulesConfigStorage::createFromModuleList(['system', 'user'], $this->extensionList);

    // Config only in System module.
    $this->assertSame(['name' => 'Site A'], $storage->read('system.site'));
    // Config only in User module.
    $this->assertSame(['register' => 'visitors'], $storage->read('user.settings'));
    // Non-existent config returns FALSE.
    $this->assertFalse($storage->read('nonexistent.config'));
  }

  /**
   * Tests read() safety: only read from the correct storage.
   */
  public function testReadSafety(): void {
    $storage = RecipeMultipleModulesConfigStorage::createFromModuleList(['user', 'system'], $this->extensionList);

    // The System module's version should be read.
    $this->assertSame(['name' => 'Site A'], $storage->read('system.site'));

    $storage = RecipeMultipleModulesConfigStorage::createFromModuleList(['system', 'user'], $this->extensionList);

    // The System module's version should be read, regardless of the order of
    // the modules in the list.
    $this->assertSame(['name' => 'Site A'], $storage->read('system.site'));

    $storage = RecipeMultipleModulesConfigStorage::createFromModuleList(['user'], $this->extensionList);

    // The User module's version should never be read.
    $this->assertFalse($storage->read('system.site'));
  }

  /**
   * Tests that modules with similar name prefixes are correctly isolated.
   *
   * The 'system' and 'system_test' modules share the string prefix "system"
   * but must be treated as entirely separate modules. Configuration is routed
   * by the part of the config name before the first dot, so 'system.site'
   * belongs to the 'system' module and 'system_test.settings' belongs to the
   * 'system_test' module.
   */
  public function testSimilarModuleNameIsolation(): void {
    $storage = RecipeMultipleModulesConfigStorage::createFromModuleList(['system', 'system_test', 'user'], $this->extensionList);

    // Each module's config is read from its own storage.
    $this->assertSame(['name' => 'Site A'], $storage->read('system.site'));
    $this->assertSame(['verbose' => TRUE], $storage->read('system_test.settings'));
    $this->assertSame(['register' => 'visitors'], $storage->read('user.settings'));

    // exists() correctly distinguishes between the two modules.
    $this->assertTrue($storage->exists('system.site'));
    $this->assertTrue($storage->exists('system_test.settings'));
    $this->assertFalse($storage->exists('system_test.nonexistent'));
    $this->assertFalse($storage->exists('system.nonexistent'));

    // listAll() with a dot-terminated prefix only returns config from the
    // matching module — 'system.' must not include 'system_test.' config.
    $this->assertSame(['system.site'], $storage->listAll('system.'));
    $this->assertSame(['system_test.settings'], $storage->listAll('system_test.'));

    // listAll() without a trailing dot filters by string prefix. 'system'
    // matches both 'system.site' and 'system_test.settings'.
    $result = $storage->listAll('system');
    $this->assertContains('system.site', $result);
    $this->assertContains('system_test.settings', $result);
    $this->assertNotContains('user.settings', $result);

    // listAll() with no prefix returns all config sorted.
    $this->assertSame([
      'system.site',
      'system_test.settings',
      'user.settings',
    ], $storage->listAll());

    // readMultiple() routes each name to the correct module.
    $result = $storage->readMultiple(['system.site', 'system_test.settings']);
    $this->assertSame(['name' => 'Site A'], $result['system.site']);
    $this->assertSame(['verbose' => TRUE], $result['system_test.settings']);

    // Without system_test in the module list, its config is inaccessible.
    $storage = RecipeMultipleModulesConfigStorage::createFromModuleList(['system', 'user'], $this->extensionList);
    $this->assertFalse($storage->exists('system_test.settings'));
    $this->assertFalse($storage->read('system_test.settings'));
    $this->assertSame([], $storage->listAll('system_test.'));
  }

  /**
   * Tests readMultiple() reads from across all directories.
   */
  public function testReadMultiple(): void {
    $storage = RecipeMultipleModulesConfigStorage::createFromModuleList(['system', 'user'], $this->extensionList);

    $result = $storage->readMultiple(['system.site', 'user.settings', 'nonexistent.config']);
    $this->assertCount(2, $result);
    $this->assertSame(['name' => 'Site A'], $result['system.site']);
    $this->assertSame(['register' => 'visitors'], $result['user.settings']);
    $this->assertArrayNotHasKey('nonexistent.config', $result);
  }

  /**
   * Tests readMultiple() with an empty names array.
   */
  public function testReadMultipleEmpty(): void {
    $storage = RecipeMultipleModulesConfigStorage::createFromModuleList(['system'], $this->extensionList);
    $this->assertSame([], $storage->readMultiple([]));
  }

  /**
   * Tests listAll() merges results from all directories.
   */
  public function testListAll(): void {
    $storage = RecipeMultipleModulesConfigStorage::createFromModuleList(['system', 'user'], $this->extensionList);

    $this->assertSame([
      'system.site',
      'user.settings',
    ], $storage->listAll());
  }

  /**
   * Tests listAll() with a prefix filter.
   */
  public function testListAllWithPrefix(): void {
    $storage = RecipeMultipleModulesConfigStorage::createFromModuleList(['system', 'user'], $this->extensionList);

    $this->assertSame([], $storage->listAll('node.'));
    $this->assertSame(['system.site'], $storage->listAll('system.'));
    $this->assertSame([], $storage->listAll('nonexistent.'));
    // Prefix not ending in a dot that matches items.
    $this->assertSame(['system.site'], $storage->listAll('system'));
    // Prefix not ending in a dot that matches nothing.
    $this->assertSame([], $storage->listAll('node'));
  }

  /**
   * Tests that write operations throw BadMethodCallException.
   *
   * @param string $method
   *   The method to call.
   * @param mixed ...$args
   *   The arguments to pass.
   */
  #[TestWith(['write', 'name', []])]
  #[TestWith(['delete', 'name'])]
  #[TestWith(['rename', 'old', 'new'])]
  #[TestWith(['deleteAll'])]
  public function testUnsupportedMethods(string $method, mixed ...$args): void {
    $storage = RecipeMultipleModulesConfigStorage::createFromModuleList(['system'], $this->extensionList);
    $this->expectException(\BadMethodCallException::class);
    $storage->{$method}(...$args);
  }

  /**
   * Tests encode() delegates to underlying FileStorage.
   */
  public function testEncode(): void {
    $storage = RecipeMultipleModulesConfigStorage::createFromModuleList(['system'], $this->extensionList);
    $data = ['key' => 'value'];
    $encoded = $storage->encode($data);
    $this->assertIsString($encoded);
    $this->assertSame($data, Yaml::parse($encoded));
  }

  /**
   * Tests decode().
   */
  public function testDecode(): void {
    $storage = RecipeMultipleModulesConfigStorage::createFromModuleList(['system'], $this->extensionList);
    $yaml = Yaml::dump(['key' => 'value']);
    $this->assertSame(['key' => 'value'], $storage->decode($yaml));
  }

  /**
   * Tests getCollectionName() returns the default collection.
   */
  public function testGetCollectionNameDefault(): void {
    $storage = RecipeMultipleModulesConfigStorage::createFromModuleList(['system'], $this->extensionList);
    $this->assertSame(StorageInterface::DEFAULT_COLLECTION, $storage->getCollectionName());
  }

  /**
   * Tests createCollection() returns a new instance with the given collection.
   */
  public function testCreateCollection(): void {
    $storage = RecipeMultipleModulesConfigStorage::createFromModuleList(['system', 'user'], $this->extensionList);
    $collection = $storage->createCollection('test_collection');

    $this->assertInstanceOf(RecipeMultipleModulesConfigStorage::class, $collection);
    $this->assertSame('test_collection', $collection->getCollectionName());
    // Original storage retains its collection name.
    $this->assertSame(StorageInterface::DEFAULT_COLLECTION, $storage->getCollectionName());
  }

  /**
   * Tests createCollection() reads from collection subdirectories.
   */
  public function testCreateCollectionReadsFromSubdirectories(): void {
    // Add a collection subdirectory to the system module.
    vfsStream::create([
      'modules' => [
        'system' => [
          'config' => [
            'install' => [
              'system.image.yml' => Yaml::dump(['toolkit' => 'gd']),
              'language' => [
                'fr' => [
                  'system.site.yml' => Yaml::dump(['name' => 'Site FR']),
                ],
              ],
            ],
          ],
        ],
      ],
    ]);

    $storage = RecipeMultipleModulesConfigStorage::createFromModuleList(['system', 'user'], $this->extensionList);
    $frStorage = $storage->createCollection('language.fr');

    $this->assertSame('language.fr', $frStorage->getCollectionName());
    $this->assertTrue($frStorage->exists('system.site'));
    $this->assertSame(['name' => 'Site FR'], $frStorage->read('system.site'));
    // The default collection config should not be visible.
    $this->assertFalse($frStorage->exists('system.image'));
    $this->assertTrue($storage->exists('system.image'));
  }

  /**
   * Tests getAllCollectionNames() merges and deduplicates from all directories.
   */
  public function testGetAllCollectionNames(): void {
    // Add collection subdirectories.
    vfsStream::create([
      'modules' => [
        'system' => [
          'config' => [
            'install' => [
              'language' => [
                'fr' => [
                  'system.site.yml' => Yaml::dump([]),
                ],
              ],
            ],
          ],
        ],
        'user' => [
          'config' => [
            'install' => [
              'language' => [
                'fr' => [
                  'user.settings.yml' => Yaml::dump([]),
                ],
                'de' => [
                  'user.settings.yml' => Yaml::dump([]),
                ],
              ],
            ],
          ],
        ],
      ],
    ]);

    $storage = RecipeMultipleModulesConfigStorage::createFromModuleList(['system', 'user'], $this->extensionList);
    $collections = $storage->getAllCollectionNames();

    $this->assertContains('language.fr', $collections);
    $this->assertContains('language.de', $collections);
    // Duplicates should be removed.
    $this->assertCount(2, $collections);
  }

  /**
   * Tests getAllCollectionNames() returns empty when no collections exist.
   */
  public function testGetAllCollectionNamesEmpty(): void {
    $storage = RecipeMultipleModulesConfigStorage::createFromModuleList(['system'], $this->extensionList);
    $this->assertSame([], $storage->getAllCollectionNames());
  }

  /**
   * Tests createFromModuleList() throws when given an empty module list.
   */
  public function testCreateFromModuleListEmpty(): void {
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('At least one module must be provided.');
    RecipeMultipleModulesConfigStorage::createFromModuleList([], $this->extensionList);
  }

}
