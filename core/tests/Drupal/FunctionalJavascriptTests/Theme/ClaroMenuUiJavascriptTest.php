<?php

namespace Drupal\FunctionalJavascriptTests\Theme;

use Drupal\Tests\menu_ui\FunctionalJavascript\MenuUiJavascriptTest;

/**
 * Runs MenuUiJavascriptTest in Claro.
 *
 * @group claro
 *
 * @see \Drupal\Tests\menu_ui\FunctionalJavascript\MenuUiJavascriptTest;
 */
class ClaroMenuUiJavascriptTest extends MenuUiJavascriptTest {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'shortcut',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->container->get('theme_installer')->install(['claro']);
    $this->config('system.theme')->set('default', 'claro')->save();
  }

  /**
   * Intentionally empty method.
   *
   * Contextual links do not work in admin themes, so this is empty to prevent
   * this test running in the parent class.
   */
  public function testBlockContextualLinks() {
  }

}
