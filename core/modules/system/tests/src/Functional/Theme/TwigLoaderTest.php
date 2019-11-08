<?php

namespace Drupal\Tests\system\Functional\Theme;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests adding Twig loaders.
 *
 * @group Theme
 */
class TwigLoaderTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['twig_loader_test'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests adding an additional twig loader to the loader chain.
   */
  public function testTwigLoaderAddition() {
    $environment = \Drupal::service('twig');

    $template = $environment->loadTemplate('kittens');
    $this->assertEqual($template->render([]), 'kittens', 'Passing "kittens" to the custom Twig loader returns "kittens".');

    $template = $environment->loadTemplate('meow');
    $this->assertEqual($template->render([]), 'cats', 'Passing something other than "kittens" to the custom Twig loader returns "cats".');
  }

}
