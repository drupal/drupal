<?php

namespace Drupal\Tests\Core\Site;

use Drupal\Core\Site\Settings;
use Drupal\Tests\Traits\ExpectDeprecationTrait;
use Drupal\Tests\UnitTestCase;
use org\bovigo\vfs\vfsStream;

/**
 * @coversDefaultClass \Drupal\Core\Site\Settings
 * @group Site
 */
class SettingsTest extends UnitTestCase {

  use ExpectDeprecationTrait;

  /**
   * Simple settings array to test against.
   *
   * @var array
   */
  protected $config = [];

  /**
   * The class under test.
   *
   * @var \Drupal\Core\Site\Settings
   */
  protected $settings;

  /**
   * @covers ::__construct
   */
  protected function setUp() {
    $this->config = [
      'one' => '1',
      'two' => '2',
      'hash_salt' => $this->randomMachineName(),
    ];
    $this->settings = new Settings($this->config);
  }

  /**
   * @covers ::get
   */
  public function testGet() {
    // Test stored settings.
    $this->assertEquals($this->config['one'], Settings::get('one'), 'The correct setting was not returned.');
    $this->assertEquals($this->config['two'], Settings::get('two'), 'The correct setting was not returned.');

    // Test setting that isn't stored with default.
    $this->assertEquals('3', Settings::get('three', '3'), 'Default value for a setting not properly returned.');
    $this->assertNull(Settings::get('four'), 'Non-null value returned for a setting that should not exist.');
  }

  /**
   * @covers ::getAll
   */
  public function testGetAll() {
    $this->assertEquals($this->config, Settings::getAll());
  }

  /**
   * @covers ::getInstance
   */
  public function testGetInstance() {
    $singleton = $this->settings->getInstance();
    $this->assertEquals($singleton, $this->settings);
  }

  /**
   * Tests Settings::getHashSalt();
   *
   * @covers ::getHashSalt
   */
  public function testGetHashSalt() {
    $this->assertSame($this->config['hash_salt'], $this->settings->getHashSalt());
  }

  /**
   * Tests Settings::getHashSalt() with no hash salt value.
   *
   * @covers ::getHashSalt
   *
   * @dataProvider providerTestGetHashSaltEmpty
   */
  public function testGetHashSaltEmpty(array $config) {
    // Re-create settings with no 'hash_salt' key.
    $settings = new Settings($config);
    $this->expectException(\RuntimeException::class);
    $settings->getHashSalt();
  }

  /**
   * Data provider for testGetHashSaltEmpty.
   *
   * @return array
   */
  public function providerTestGetHashSaltEmpty() {
    return [
      [[]],
      [['hash_salt' => '']],
      [['hash_salt' => NULL]],
    ];
  }

  /**
   * Ensures settings cannot be serialized.
   *
   * @covers ::__sleep
   */
  public function testSerialize() {
    $this->expectException(\LogicException::class);
    serialize(new Settings([]));
  }

  /**
   * Tests Settings::getApcuPrefix().
   *
   * @covers ::getApcuPrefix
   */
  public function testGetApcuPrefix() {
    $settings = new Settings([
      'hash_salt' => 123,
      'apcu_ensure_unique_prefix' => TRUE,
    ]);
    $this->assertNotEquals($settings::getApcuPrefix('cache_test', '/test/a'), $settings::getApcuPrefix('cache_test', '/test/b'));

    $settings = new Settings([
      'hash_salt' => 123,
      'apcu_ensure_unique_prefix' => FALSE,
    ]);
    $this->assertNotEquals($settings::getApcuPrefix('cache_test', '/test/a'), $settings::getApcuPrefix('cache_test', '/test/b'));
  }

  /**
   * Tests that an exception is thrown when settings are not initialized yet.
   *
   * @covers ::getInstance
   */
  public function testGetInstanceReflection() {
    $settings = new Settings([]);

    $class = new \ReflectionClass(Settings::class);
    $instace_property = $class->getProperty("instance");
    $instace_property->setAccessible(TRUE);
    $instace_property->setValue(NULL);

    $this->expectException(\BadMethodCallException::class);
    $settings->getInstance();
  }

  /**
   * @runInSeparateProcess
   * @group legacy
   * @covers ::__construct
   * @dataProvider configDirectoriesBcLayerProvider
   */
  public function testConfigDirectoriesBcLayer($settings_file_content, $directory, $expect_deprecation) {
    global $config_directories;
    $class_loader = NULL;

    $vfs_root = vfsStream::setup('root');
    $sites_directory = vfsStream::newDirectory('sites')->at($vfs_root);
    vfsStream::newFile('settings.php')
      ->at($sites_directory)
      ->setContent($settings_file_content);

    if ($expect_deprecation) {
      $this->addExpectedDeprecationMessage('$config_directories[\'sync\'] has moved to $settings[\'config_sync_directory\']. See https://www.drupal.org/node/3018145.');
    }

    Settings::initialize(vfsStream::url('root'), 'sites', $class_loader);
    $this->assertSame($directory, Settings::get('config_sync_directory'));
    $this->assertSame($directory, $config_directories['sync']);
  }

  /**
   * Data provider for self::testConfigDirectoriesBcLayer().
   */
  public function configDirectoriesBcLayerProvider() {
    $no_config_directories = <<<'EOD'
<?php
$settings['config_sync_directory'] = 'foo';
EOD;

    $only_config_directories = <<<'EOD'
<?php
$config_directories['sync'] = 'bar';
EOD;

    $both = <<<'EOD'
<?php
$settings['config_sync_directory'] = 'foo';
$config_directories['sync'] = 'bar';
EOD;

    return [
      'Only $settings[\'config_sync_directory\']' => [
        $no_config_directories,
        'foo',
        FALSE,
      ],
      'Only $config_directories' => [$only_config_directories, 'bar', TRUE],
      'Both' => [$both, 'foo', FALSE],
    ];
  }

  /**
   * @runInSeparateProcess
   * @group legacy
   */
  public function testConfigDirectoriesBcLayerEmpty() {
    global $config_directories;
    $class_loader = NULL;

    $vfs_root = vfsStream::setup('root');
    $sites_directory = vfsStream::newDirectory('sites')->at($vfs_root);
    vfsStream::newFile('settings.php')->at($sites_directory)->setContent(<<<'EOD'
<?php
$settings = [];
EOD
    );

    Settings::initialize(vfsStream::url('root'), 'sites', $class_loader);
    $this->assertNull(Settings::get('config_sync_directory'));
    $this->assertNull($config_directories);
  }

  /**
   * @runInSeparateProcess
   * @group legacy
   */
  public function testConfigDirectoriesBcLayerMultiple() {
    global $config_directories;
    $class_loader = NULL;

    $vfs_root = vfsStream::setup('root');
    $sites_directory = vfsStream::newDirectory('sites')->at($vfs_root);
    vfsStream::newFile('settings.php')->at($sites_directory)->setContent(<<<'EOD'
<?php
$settings['config_sync_directory'] = 'foo';
$config_directories['sync'] = 'bar';
$config_directories['custom'] = 'custom';
EOD
    );

    Settings::initialize(vfsStream::url('root'), 'sites', $class_loader);
    $this->assertSame('foo', Settings::get('config_sync_directory'));
    $this->assertSame('foo', $config_directories['sync']);
    $this->assertSame('custom', $config_directories['custom']);
  }

}
