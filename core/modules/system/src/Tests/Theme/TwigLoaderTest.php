<?php

namespace Drupal\system\Tests\Theme;

use Drupal\simpletest\WebTestBase;

/**
 * Tests adding Twig loaders.
 *
 * @group Theme
 */
class TwigLoaderTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['twig_loader_test'];

  /**
   * Tests adding an additional twig loader to the loader chain.
   */
  public function testTwigLoaderAddition() {
    $environment = \Drupal::service('twig');

    $template = $environment->loadTemplate('kittens');
    $this->assertEqual($template->render(array()), 'kittens', 'Passing "kittens" to the custom Twig loader returns "kittens".');

    $template = $environment->loadTemplate('meow');
    $this->assertEqual($template->render(array()), 'cats', 'Passing something other than "kittens" to the custom Twig loader returns "cats".');
  }

}
