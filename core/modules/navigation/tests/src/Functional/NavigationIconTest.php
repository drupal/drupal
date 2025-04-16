<?php

declare(strict_types=1);

namespace Drupal\Tests\navigation\Functional;

use Drupal\Core\Menu\MenuLinkManagerInterface;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests Navigation Icon behavior.
 *
 * @group navigation
 */
class NavigationIconTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['navigation', 'navigation_test', 'test_page_test'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->drupalLogin($this->createUser([
      'access navigation',
    ]));
  }

  /**
   * Tests the behavior of custom icons.
   */
  public function testNavigationIcon(): void {
    $this->drupalGet('/test-page');
    $this->assertSession()->elementAttributeContains('css', 'a.toolbar-button--icon--star svg', 'width', '25');
    $this->assertSession()->elementAttributeContains('css', 'a.toolbar-button--icon--star svg', 'class', 'toolbar-button__icon foo');
    $this->assertSession()->elementAttributeContains('css', 'a.toolbar-button--icon--pencil svg', 'width', '20');
    $this->assertSession()->elementAttributeContains('css', 'a.toolbar-button--icon--pencil svg', 'class', 'toolbar-button__icon');
    $this->assertSession()->elementAttributeContains('css', 'a.toolbar-button--icon--navigation-media svg', 'width', '20');
    $this->assertSession()->elementAttributeContains('css', 'a.toolbar-button--icon--navigation-media svg', 'class', 'toolbar-button__icon');
    $this->assertSession()->elementExists('css', 'a.toolbar-button--icon--navigation-test-navigation__no-icon');
    $this->assertSession()->elementNotExists('css', 'a.toolbar-button--icon--navigation-test-navigation__no-icon svg');

    // Rebuild menu with alterations and reload the page to check them.
    \Drupal::keyValue('navigation_test')->set('menu_links_discovered_alter', 1);
    \Drupal::service(MenuLinkManagerInterface::class)->rebuild();

    $this->drupalGet('/test-page');
    $this->assertSession()->elementAttributeContains('css', 'a.toolbar-button--icon--star svg', 'width', '25');
    $this->assertSession()->elementAttributeContains('css', 'a.toolbar-button--icon--star svg', 'class', 'toolbar-button__icon foo');
    $this->assertSession()->elementNotExists('css', 'a.toolbar-button--icon--pencil svg');
    $this->assertSession()->elementAttributeContains('css', 'a.toolbar-button--icon--navigation-media svg', 'width', '20');
    $this->assertSession()->elementAttributeContains('css', 'a.toolbar-button--icon--navigation-media svg', 'class', 'toolbar-button__icon');
    $this->assertSession()->elementAttributeContains('css', 'a.toolbar-button--icon--radioactive svg', 'width', '20');
    $this->assertSession()->elementAttributeContains('css', 'a.toolbar-button--icon--radioactive svg', 'class', 'toolbar-button__icon');
  }

}
