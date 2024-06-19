<?php

declare(strict_types=1);

namespace Drupal\Tests\system\Functional\Menu;

use Drupal\menu_link_content\Entity\MenuLinkContent;
use Drupal\Tests\BrowserTestBase;

/**
 * Ensures that menu links don't cause XSS issues.
 *
 * @group Menu
 */
class MenuLinkSecurityTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['menu_link_content', 'block', 'menu_test'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Ensures that a menu link does not cause an XSS issue.
   */
  public function testMenuLink(): void {
    $menu_link_content = MenuLinkContent::create([
      'title' => '<script>alert("Wild animals")</script>',
      'menu_name' => 'tools',
      'link' => ['uri' => 'route:<front>'],
    ]);
    $menu_link_content->save();

    $this->drupalPlaceBlock('system_menu_block:tools');

    $this->drupalGet('<front>');
    $this->assertSession()->responseNotContains('<script>alert("Wild animals")</script>');
    $this->assertSession()->responseNotContains('<script>alert("Even more wild animals")</script>');
    $this->assertSession()->assertEscaped('<script>alert("Wild animals")</script>');
    $this->assertSession()->assertEscaped('<script>alert("Even more wild animals")</script>');
  }

}
