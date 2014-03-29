<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\Config\Entity\ConfigEntityTypeTest.
 */

namespace Drupal\Tests\Core\Config\Entity;

use Drupal\Tests\UnitTestCase;
use Drupal\Core\Config\Entity\ConfigEntityType;
use Drupal\Component\Utility\String;

/**
 * @coversDefaultClass \Drupal\Core\Config\Entity\ConfigEntityType
 *
 * @group Drupal
 * @group Config
 */
class ConfigEntityTypeTest extends UnitTestCase {

  /**
   * {@inheritdoc}
   */
  public static function getInfo() {
    return array(
      'description' => '',
      'name' => '\Drupal\Core\Config\Entity\ConfigEntityType unit test',
      'group' => 'Entity',
    );
  }


  /**
   * Tests that we get an exception when the length of the config prefix that is
   * returned by getConfigPrefix() exceeds the maximum defined prefix length.
   *
   * @dataProvider providerPrefixLengthExceeds
   * @covers ::getConfigPrefix()
   */
  public function testConfigPrefixLengthExceeds($entity_data, $exception, $message) {
    $config_entity = new ConfigEntityType($entity_data);
    $this->setExpectedException($exception, $message);
    $this->assertEmpty($config_entity->getConfigPrefix());
  }

  /**
   * Provides arguments to instantiate a ConfigEntityType with a configuration
   * entity prefix that exceeds the maximum character length.
   *
   * @return array
   */
  public function providerPrefixLengthExceeds() {
    $test_parameters = array();
    $message_text = 'The configuration file name prefix @config_prefix exceeds the maximum character limit of @max_char.';

    // A provider length of 24 and id length of 59 (+1 for the .) results
    // in a config length of 84, which is too long.
    $entity_data = array(
      'provider' => $this->randomName(24),
      'id' => $this->randomName(59),
    );
    $test_parameters[] = array(
      $entity_data,
      '\Drupal\Core\Config\ConfigPrefixLengthException',
      String::format($message_text, array(
        '@config_prefix' => $entity_data['provider'] . '.' . $entity_data['id'],
        '@max_char' => ConfigEntityType::PREFIX_LENGTH,
      )),
    );

    // A provider length of 24 and config_prefix length of 59 (+1 for the .)
    // results in a config length of 84, which is too long.
    $entity_data = array(
      'provider' => $this->randomName(24),
      'config_prefix' => $this->randomName(59),
    );
    $test_parameters[] = array(
      $entity_data,
      '\Drupal\Core\Config\ConfigPrefixLengthException',
      String::format($message_text, array(
        '@config_prefix' => $entity_data['provider'] . '.' . $entity_data['config_prefix'],
        '@max_char' => ConfigEntityType::PREFIX_LENGTH,
      )),
    );

    return $test_parameters;
  }

  /**
   * Tests that a valid config prefix returned by getConfigPrefix()
   * does not throw an exception and is formatted as expected.
   *
   * @dataProvider providerPrefixLengthValid
   * @covers ::getConfigPrefix()
   */
  public function testConfigPrefixLengthValid($entity_data) {
    $config_entity = new ConfigEntityType($entity_data);
    if (isset($entity_data['config_prefix'])) {
      $expected_prefix = $entity_data['provider'] . '.' . $entity_data['config_prefix'];
    } else {
      $expected_prefix = $entity_data['provider'] . '.' . $entity_data['id'];
    }
    $this->assertEquals($expected_prefix, $config_entity->getConfigPrefix());
  }

  /**
   * Provides arguments to instantiate a ConfigEntityType with a configuration
   * entity prefix that does not exceed the maximum character length.
   *
   * @return array
   */
  public function providerPrefixLengthValid() {
    $test_parameters = array();

    // A provider length of 24 and config_prefix length of 58 (+1 for the .)
    // results in a config length of 83, which is right at the limit.
    $test_parameters[] = array(array(
      'provider' => $this->randomName(24),
      'config_prefix' => $this->randomName(58),
    ));

    // A provider length of 24 and id length of 58 (+1 for the .) results in a
    // config length of 83, which is right at the limit.
    $test_parameters[] = array(array(
      'provider' => $this->randomName(24),
      'id' => $this->randomName(58),
    ));

    return $test_parameters;
  }

}
