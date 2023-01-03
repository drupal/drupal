<?php

namespace Drupal\KernelTests\Core\Render\Element;

use Drupal\KernelTests\KernelTestBase;

/**
 * @group Render
 */
class DeprecatedElementTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['element_info_test'];

  /**
   * Tests that render elements can trigger deprecations in their constructor.
   */
  public function testBuildInfo() {
    $info_manager = $this->container->get('plugin.manager.element_info');
    $this->assertSame([
      '#type' => 'deprecated',
      '#defaults_loaded' => TRUE,
    ], $info_manager->getInfo('deprecated'));

    // Ensure the constructor is triggering a deprecation error.
    $previous_error_handler = set_error_handler(function ($severity, $message, $file, $line) use (&$previous_error_handler) {
      // Convert deprecation error into a catchable exception.
      if ($severity === E_USER_DEPRECATED) {
        throw new \ErrorException($message, 0, $severity, $file, $line);
      }
      if ($previous_error_handler) {
        return $previous_error_handler($severity, $message, $file, $line);
      }
    });

    try {
      $info_manager->createInstance('deprecated');
      $this->fail('No deprecation error triggered.');
    }
    catch (\ErrorException $e) {
      $this->assertSame('Drupal\element_info_test\Element\Deprecated is deprecated in drupal:10.1.0 and is removed from drupal:11.0.0. See https://www.drupal.org/node/3068104', $e->getMessage());
    }
    restore_error_handler();
  }

}
