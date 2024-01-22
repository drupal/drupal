<?php

namespace Drupal\Tests\system\Kernel\Theme;

use Drupal\KernelTests\KernelTestBase;

/**
 * Tests adding Twig loaders.
 *
 * @group Theme
 */
class TwigLoaderTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['twig_loader_test'];

  /**
   * Tests adding an additional twig loader to the loader chain.
   */
  public function testTwigLoaderAddition(): void {
    $environment = \Drupal::service('twig');

    $template = $environment->load('kittens');
    $this->assertEquals('kittens', $template->render([]), 'Passing "kittens" to the custom Twig loader returns "kittens".');

    $template = $environment->load('meow');
    $this->assertEquals('cats', $template->render([]), 'Passing something other than "kittens" to the custom Twig loader returns "cats".');
  }

}
