<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\Config\ConfigTest.
 */

namespace Drupal\Tests\Core\Config;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Tests\UnitTestCase;
use Drupal\Core\Config\Config;
use Drupal\Component\Utility\SafeMarkup;

/**
 * Tests the Config.
 *
 * @coversDefaultClass \Drupal\Core\Config\Config
 *
 * @group Config
 *
 * @see \Drupal\Core\Config\Config
 */
class ConfigTest extends UnitTestCase {

  /**
   * Config.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $config;

  /**
   * Storage.
   *
   * @var \Drupal\Core\Config\StorageInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $storage;

  /**
   * Event Dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $eventDispatcher;

  /**
   * Typed Config.
   *
   * @var \Drupal\Core\Config\TypedConfigManagerInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $typedConfig;

  /**
   * The mocked cache tags invalidator.
   *
   * @var \Drupal\Core\Cache\CacheTagsInvalidatorInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $cacheTagsInvalidator;

  public function setUp() {
    $this->storage = $this->getMock('Drupal\Core\Config\StorageInterface');
    $this->eventDispatcher = $this->getMock('Symfony\Component\EventDispatcher\EventDispatcherInterface');
    $this->typedConfig = $this->getMock('\Drupal\Core\Config\TypedConfigManagerInterface');
    $this->config = new Config('config.test', $this->storage, $this->eventDispatcher, $this->typedConfig);
    $this->cacheTagsInvalidator = $this->getMock('Drupal\Core\Cache\CacheTagsInvalidatorInterface');

    $container = new ContainerBuilder();
    $container->set('cache_tags.invalidator', $this->cacheTagsInvalidator);
    \Drupal::setContainer($container);
  }

  /**
   * @covers ::setName
   * @dataProvider setNameProvider
   */
  public function testSetName($name) {
    // Set the name.
    $this->config->setName($name);

    // Check that the name has been set correctly.
    $this->assertEquals($name, $this->config->getName());

    // Check that the name validates.
    // Should throw \Drupal\Core\Config\ConfigNameException if invalid.
    $this->config->validateName($name);
  }

  /**
   * Provides config names to test.
   *
   * @see \Drupal\Tests\Core\Config\ConfigTest::testSetName()
   */
  public function setNameProvider() {
    return array(
      // Valid name with dot.
      array(
        'test.name',
      ),
      // Maximum length.
      array(
        'test.' . str_repeat('a', Config::MAX_NAME_LENGTH - 5),
      ),
    );
  }

  /**
   * @covers ::isNew
   */
  public function testIsNew() {
    // Config should be new by default.
    $this->assertTrue($this->config->isNew());

    // Config is no longer new once saved.
    $this->config->save();
    $this->assertFalse($this->config->isNew());
  }

  /**
   * @covers ::setData
   * @dataProvider nestedDataProvider
   */
  public function testSetData($data) {
    $this->config->setData($data);
    $this->assertEquals($data, $this->config->getRawData());
    $this->assertConfigDataEquals($data);
  }

  /**
   * @covers ::save
   * @dataProvider nestedDataProvider
   */
  public function testSaveNew($data) {
    $this->cacheTagsInvalidator->expects($this->never())
      ->method('invalidateTags');

    // Set initial data.
    $this->config->setData($data);

    // Check that original data has not been set yet.
    foreach ($data as $key => $value) {
      $this->assertNull($this->config->getOriginal($key, FALSE));
    }

    // Save so that the original data is set.
    $config = $this->config->save();

    // Check that returned $config is instance of Config.
    $this->assertInstanceOf('\Drupal\Core\Config\Config', $config);

    // Check that the original data it saved.
    $this->assertOriginalConfigDataEquals($data, TRUE);
  }

  /**
   * @covers ::save
   * @dataProvider nestedDataProvider
   */
  public function testSaveExisting($data) {
    $this->cacheTagsInvalidator->expects($this->once())
      ->method('invalidateTags')
      ->with(['config:config.test']);

    // Set initial data.
    $this->config->setData($data);
    $this->config->save();

    // Update.
    $new_data = $data;
    $new_data['a']['d'] = 2;
    $this->config->setData($new_data);
    $this->config->save();
    $this->assertOriginalConfigDataEquals($new_data, TRUE);
  }

  /**
   * @covers ::setModuleOverride
   * @covers ::setSettingsOverride
   * @covers ::getOriginal
   * @dataProvider overrideDataProvider
   */
  public function testOverrideData($data, $module_data, $setting_data) {
    // Set initial data.
    $this->config->setData($data);

    // Check original data was set correctly.
    $this->assertConfigDataEquals($data);

    // Save so that the original data is stored.
    $this->config->save();

    // Set module override data and check value before and after save.
    $this->config->setModuleOverride($module_data);
    $this->assertConfigDataEquals($module_data);
    $this->config->save();
    $this->assertConfigDataEquals($module_data);

    // Set setting override data and check value before and after save.
    $this->config->setSettingsOverride($setting_data);
    $this->assertConfigDataEquals($setting_data);
    $this->config->save();
    $this->assertConfigDataEquals($setting_data);

    // Set module overrides again to ensure override order is correct.
    $this->config->setModuleOverride($module_data);

    // Setting data should be overriding module data.
    $this->assertConfigDataEquals($setting_data);
    $this->config->save();
    $this->assertConfigDataEquals($setting_data);

    // Check original data has not changed.
    $this->assertOriginalConfigDataEquals($data, FALSE);

    // Check setting overrides are returned with $apply_overrides = TRUE.
    $this->assertOriginalConfigDataEquals($setting_data, TRUE);

    // Check that $apply_overrides defaults to TRUE.
    foreach ($setting_data as $key => $value) {
      $config_value = $this->config->getOriginal($key);
      $this->assertEquals($value, $config_value);
    }
  }

  /**
   * @covers ::set
   * @dataProvider nestedDataProvider
   */
  public function testSetValue($data) {
    foreach ($data as $key => $value) {
      $this->config->set($key, $value);
    }
    $this->assertConfigDataEquals($data);
  }

  /**
   * @covers ::set
   * @expectedException \Drupal\Core\Config\ConfigValueException
   */
  public function testSetValidation() {
    $this->config->set('testData', array('dot.key' => 1));
  }

  /**
   * @covers ::set
   * @expectedException PHPUnit_Framework_Error_Warning
   */
  public function testSetIllegalOffsetValue() {
    // Set a single value.
    $this->config->set('testData', 1);

    // Attempt to treat the single value as a nested item.
    $this->config->set('testData.illegalOffset', 1);
  }

  /**
   * @covers ::initWithData
   * @dataProvider nestedDataProvider
   */
  public function testInitWithData($data) {
    $config = $this->config->initWithData($data);

    // Should return the Config object.
    $this->assertInstanceOf('\Drupal\Core\Config\Config', $config);

    // Check config is not new.
    $this->assertEquals(FALSE, $this->config->isNew());

    // Check that data value was set correctly.
    $this->assertConfigDataEquals($data);

    // Check that original data was set.
    $this->assertOriginalConfigDataEquals($data, TRUE);

    // Check without applying overrides.
    $this->assertOriginalConfigDataEquals($data, FALSE);
  }

  /**
   * @covers ::clear
   * @dataProvider simpleDataProvider
   */
  public function testClear($data) {
    foreach ($data as $key => $value) {
      // Check that values are cleared.
      $this->config->set($key, $value);
      $this->assertEquals($value, $this->config->get($key));
      $this->config->clear($key);
      $this->assertNull($this->config->get($key));
    }
  }

  /**
   * @covers ::clear
   * @dataProvider nestedDataProvider
   */
  public function testNestedClear($data) {
    foreach ($data as $key => $value) {
      // Check that values are cleared.
      $this->config->set($key, $value);
      // Check each nested value.
      foreach ($value as $nested_key => $nested_value) {
        $full_nested_key = $key . '.' . $nested_key;
        $this->assertEquals($nested_value, $this->config->get($full_nested_key));
        $this->config->clear($full_nested_key);
        $this->assertNull($this->config->get($full_nested_key));
      }
    }
  }

  /**
   * @covers ::delete
   * @dataProvider overrideDataProvider
   */
  public function testDelete($data, $module_data) {
    $this->cacheTagsInvalidator->expects($this->once())
      ->method('invalidateTags')
      ->with(['config:config.test']);

    // Set initial data.
    foreach ($data as $key => $value) {
      $this->config->set($key, $value);
    }
    // Set overrides.
    $this->config->setModuleOverride($module_data);

    // Save.
    $this->config->save();

    // Check that original data is still correct.
    $this->assertOriginalConfigDataEquals($data, FALSE);

    // Check overrides have been set.
    $this->assertConfigDataEquals($module_data);
    $this->assertOriginalConfigDataEquals($module_data, TRUE);

    // Check that config is new.
    $this->assertFalse($this->config->isNew());

    // Delete.
    $this->config->delete();

    // Check object properties have been reset.
    $this->assertTrue($this->config->isNew());
    foreach ($data as $key => $value) {
      $this->assertEmpty($this->config->getOriginal($key, FALSE));
    }

    // Check that overrides have persisted.
    foreach ($module_data as $key => $value) {
      $this->assertConfigDataEquals($module_data);
      $this->assertOriginalConfigDataEquals($module_data, TRUE);
    }
  }

  /**
   * @covers ::merge
   * @dataProvider mergeDataProvider
   */
  public function testMerge($data, $data_to_merge, $merged_data) {
    // Set initial data.
    $this->config->setData($data);

    // Data to merge.
    $this->config->merge($data_to_merge);

    // Check that data has merged correctly.
    $this->assertEquals($merged_data, $this->config->getRawData());
  }

  /**
   * Provides data to test merges.
   *
   * @see \Drupal\Tests\Core\Config\ConfigTest::testMerge()
   */
  public function mergeDataProvider() {
    return array(
      array(
        // Data.
        array('a' => 1, 'b' => 2, 'c' => array('d' => 3)),
        // Data to merge.
        array('a' => 2, 'e' => 4, 'c' => array('f' => 5)),
        // Data merged.
        array('a' => 2, 'b' => 2, 'c' => array('d' => 3, 'f' => 5), 'e' => 4),
      ),
    );
  }

  /**
   * @covers ::validateName
   * @expectedException \Drupal\Core\Config\ConfigNameException
   * @dataProvider validateNameProvider
   */
  public function testValidateNameException($name, $exception_message) {
    $this->setExpectedException('\Drupal\Core\Config\ConfigNameException', $exception_message);
    $this->config->validateName($name);
  }

  /**
   * @covers ::getCacheTags
   */
  public function testGetCacheTags() {
    $this->assertSame(['config:' . $this->config->getName()], $this->config->getCacheTags());
  }

  /**
   * Provides data to test name validation.
   *
   * @see \Drupal\Tests\Core\Config\ConfigTest::testValidateNameException()
   */
  public function validateNameProvider() {
    $return = array(
      // Name missing namespace (dot).
      array(
        'MissingNamespace',
        SafeMarkup::format('Missing namespace in Config object name MissingNamespace.', array(
          '@name' => 'MissingNamespace',
        )),
      ),
      // Exceeds length (max length plus an extra dot).
      array(
        str_repeat('a', Config::MAX_NAME_LENGTH) . ".",
        SafeMarkup::format('Config object name @name exceeds maximum allowed length of @length characters.', array(
          '@name' => str_repeat('a', Config::MAX_NAME_LENGTH) . ".",
          '@length' => Config::MAX_NAME_LENGTH,
        )),
      ),
    );
    // Name must not contain : ? * < > " ' / \
    foreach (array(':', '?', '*', '<', '>', '"', "'", '/', '\\') as $char) {
      $name = 'name.' . $char;
      $return[] = array(
        $name,
        SafeMarkup::format('Invalid character in Config object name @name.', array(
          '@name' => $name,
        )),
      );
    }
    return $return;
  }

  /**
   * Provides override data.
   *
   * @see \Drupal\Tests\Core\Config\ConfigTest::testOverrideData()
   * @see \Drupal\Tests\Core\Config\ConfigTest::testDelete()
   */
  public function overrideDataProvider() {
    return array(
      array(
        // Original data.
        array(
          'a' => 'originalValue',
        ),
        // Module overrides.
        array(
          'a' => 'moduleValue',
        ),
        // Setting overrides.
        array(
          'a' => 'settingValue',
        ),
      ),
    );
  }

  /**
   * Provides simple test data.
   *
   * @see \Drupal\Tests\Core\Config\ConfigTest::testClear()
   */
  public function simpleDataProvider() {
    return array(
      array(
        array(
          'a' => '1',
          'b' => '2',
          'c' => '3',
        ),
      ),
    );
  }

  /**
   * Provides nested test data.
   *
   * @see \Drupal\Tests\Core\Config\ConfigTest::testSetData()
   * @see \Drupal\Tests\Core\Config\ConfigTest::testSave()
   * @see \Drupal\Tests\Core\Config\ConfigTest::testSetValue()
   * @see \Drupal\Tests\Core\Config\ConfigTest::testInitWithData()
   * @see \Drupal\Tests\Core\Config\ConfigTest::testNestedClear()
   */
  public function nestedDataProvider() {
    return array(
      array(
        array(
          'a' => array(
            'd' => 1,
          ),
          'b' => array(
            'e' => 2,
          ),
          'c' => array(
            'f' => 3,
          ),
        ),
      ),
    );
  }

  /**
   * Asserts all config data equals $data provided.
   *
   * @param array $data
   *   Config data to be checked.
   */
  public function assertConfigDataEquals($data) {
    foreach ($data as $key => $value) {
      $this->assertEquals($value, $this->config->get($key));
    }
  }

  /**
   * Asserts all original config data equals $data provided.
   *
   * @param array $data
   *   Config data to be checked.
   * @param bool $apply_overrides
   *   Apply any overrides to the original data.
   */
  public function assertOriginalConfigDataEquals($data, $apply_overrides) {
    foreach ($data as $key => $value) {
      $config_value = $this->config->getOriginal($key, $apply_overrides);
      $this->assertEquals($value, $config_value);
    }
  }

}
