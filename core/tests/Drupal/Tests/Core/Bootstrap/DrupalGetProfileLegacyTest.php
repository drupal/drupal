<?php

namespace Drupal\Tests\Core\Bootstrap;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Tests\UnitTestCase;

/**
 * Tests drupal_get_profile().
 *
 * @group Bootstrap
 * @group legacy
 * @see drupal_get_profile()
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class DrupalGetProfileLegacyTest extends UnitTestCase {

  /**
   * Config storage profile.
   *
   * @var string
   */
  protected $bootstrapConfigStorageProfile;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    include $this->root . '/core/includes/bootstrap.inc';
  }

  /**
   * Tests drupal_get_profile() deprecation.
   *
   * @expectedDeprecation drupal_get_profile() is deprecated in drupal:8.3.0 and is removed from drupal:9.0.0. Use the install_profile container parameter or \Drupal::installProfile() instead. If you are accessing the value before it is written to configuration during the installer use the $install_state global. If you need to access the value before container is available you can use BootstrapConfigStorageFactory to load the value directly from configuration. See https://www.drupal.org/node/2538996
   * @dataProvider providerDrupalGetProfileInstallState
   */
  public function testDrupalGetProfileLegacyInstallState($expected, array $install_state_array = NULL, $container_parameter = FALSE) {
    // Set up global for install state.
    global $install_state;
    $install_state = $install_state_array;

    // Set up the container.
    $container = new ContainerBuilder();
    $container->setParameter('install_profile', $container_parameter);
    \Drupal::setContainer($container);

    // Do test.
    $this->assertEquals($expected, drupal_get_profile());
  }

  /**
   * Data provider for testDrupalGetProfileInstallState().
   *
   * @return array
   *   Test data.
   *
   * @see testDrupalGetProfileInstallState
   */
  public function providerDrupalGetProfileInstallState() {
    $tests = [];
    $tests['install_state_with_profile'] = [
      'test_profile', [
        'parameters' => [
          'profile' => 'test_profile',
        ],
      ],
    ];
    $tests['install_state_with_no_profile_overriding_container_profile'] = [
      NULL,
      [
        'parameters' => [],
      ],
      'test_profile',
    ];
    $tests['no_install_state_with_container_profile'] = [
      'container_profile',
      NULL,
      'container_profile',
    ];

    return $tests;
  }

}
