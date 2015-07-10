<?php

/**
 * @file
 * Contains \Drupal\block\Tests\BlockXssTest.
 */

namespace Drupal\block\Tests;

use Drupal\block_content\Entity\BlockContent;
use Drupal\block_content\Entity\BlockContentType;
use Drupal\Core\Url;
use Drupal\simpletest\WebTestBase;
use Drupal\system\Entity\Menu;
use Drupal\views\Entity\View;

/**
 * Tests that the block module properly escapes block descriptions.
 *
 * @group block
 */
class BlockXssTest extends WebTestBase {

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = ['block', 'block_content', 'block_test', 'menu_ui', 'views'];

  /**
   * Test XSS in title.
   */
  public function testXssInTitle() {
    $this->drupalPlaceBlock('test_xss_title', ['label' => '<script>alert("XSS label");</script>']);

    \Drupal::state()->set('block_test.content', $this->randomMachineName());
    $this->drupalGet('');
    $this->assertNoRaw('<script>alert("XSS label");</script>', 'The block title was properly sanitized when rendered.');

    $this->drupalLogin($this->drupalCreateUser(['administer blocks', 'access administration pages']));
    $default_theme = $this->config('system.theme')->get('default');
    $this->drupalGet('admin/structure/block/list/' . $default_theme);
    $this->assertNoRaw("<script>alert('XSS subject');</script>", 'The block title was properly sanitized in Block Plugin UI Admin page.');
  }

  /**
   * Tests XSS in category.
   */
  public function testXssInCategory() {
    $this->drupalPlaceBlock('test_xss_title');
    $this->drupalLogin($this->drupalCreateUser(['administer blocks', 'access administration pages']));
    $this->drupalGet(Url::fromRoute('block.admin_display'));
    $this->clickLinkPartialName('Place block');
    $this->assertNoRaw("<script>alert('XSS category');</script>");
  }

  /**
   * Tests various modules that provide blocks for XSS.
   */
  public function testBlockXss() {
    $this->drupalLogin($this->rootUser);

    $this->doViewTest();
    $this->doMenuTest();
    $this->doBlockContentTest();
  }

  /**
   * Tests XSS coming from View block labels.
   */
  protected function doViewTest() {
    $view = View::create([
      'id' => $this->randomMachineName(),
      'label' => '<script>alert("view");</script>',
    ]);
    $view->addDisplay('block');
    $view->save();

    $this->drupalGet(Url::fromRoute('block.admin_display'));
    $this->clickLinkPartialName('Place block');
    // The block admin label is automatically XSS admin filtered.
    $this->assertRaw('alert("view");');
    $this->assertNoRaw('<script>alert("view");</script>');
  }

  /**
   * Tests XSS coming from Menu block labels.
   */
  protected function doMenuTest() {
    Menu::create([
      'id' => $this->randomMachineName(),
      'label' => '<script>alert("menu");</script>',
    ])->save();

    $this->drupalGet(Url::fromRoute('block.admin_display'));
    $this->clickLinkPartialName('Place block');
    // The block admin label is automatically XSS admin filtered.
    $this->assertRaw('alert("menu");');
    $this->assertNoRaw('<script>alert("menu");</script>');
  }

  /**
   * Tests XSS coming from Block Content block info.
   */
  protected function doBlockContentTest() {
    BlockContentType::create([
      'id' => 'basic',
      'label' => 'basic',
      'revision' => TRUE,
    ])->save();
    BlockContent::create([
      'type' => 'basic',
      'info' => '<script>alert("block_content");</script>',
    ])->save();

    $this->drupalGet(Url::fromRoute('block.admin_display'));
    $this->clickLinkPartialName('Place block');
    // The block admin label is automatically XSS admin filtered.
    $this->assertRaw('alert("block_content");');
    $this->assertNoRaw('<script>alert("block_content");</script>');
  }

}
