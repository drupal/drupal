<?php

namespace Drupal\KernelTests\Core\Theme;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Routing\NullMatcherDumper;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests system_list() function.
 *
 * In Drupal 8 the system_list() function only lists themes.
 *
 * @group Extension
 * @group legacy
 */
class SystemListTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['system'];

  /**
   * {@inheritdoc}
   */
  public function register(ContainerBuilder $container) {
    parent::register($container);
    // Some test methods involve ModuleHandler operations, which attempt to
    // rebuild and dump routes.
    $container->register('router.dumper', NullMatcherDumper::class);
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installConfig(['system']);
  }

  /**
   * Tests installing a theme.
   *
   * @expectedDeprecation system_list() is deprecated in Drupal 8.7.0 and will be removed before Drupal 9.0.0. Use \Drupal::service('theme_handler')->listInfo() instead. See https://www.drupal.org/node/2709919
   * @expectedDeprecation system_list_reset() is deprecated in Drupal 8.7.0 and will be removed before Drupal 9.0.0. There is no direct replacement. Call each \Drupal::service('extension.list.TYPE')->reset() as necessary. See https://www.drupal.org/node/2709919.
   */
  public function testSystemList() {
    // Verifies that no themes are listed by default.
    $this->assertEmpty(system_list('theme'));
    $this->assertEmpty(system_list('filepaths'));

    $this->container->get('theme_installer')->install(['test_basetheme']);

    $themes = system_list('theme');
    $this->assertTrue(isset($themes['test_basetheme']));
    $this->assertEqual($themes['test_basetheme']->getName(), 'test_basetheme');
    $filepaths = system_list('filepaths');
    $this->assertEquals('test_basetheme', $filepaths[0]['name']);
    $this->assertEquals('core/modules/system/tests/themes/test_basetheme/test_basetheme.info.yml', $filepaths[0]['filepath']);
    $this->assertCount(1, $filepaths);

    $this->container->get('theme_installer')->uninstall(['test_basetheme']);

    $this->assertEmpty(system_list('theme'));
    $this->assertEmpty(system_list('filepaths'));

    // Call system_list_reset() to test deprecation and ensure it is not broken.
    system_list_reset();
  }

}
