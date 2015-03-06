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
  public static $modules = ['block', 'block_content', 'menu_ui', 'views'];

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
    $this->clickLink('<script>alert("view");</script>');
    $this->assertRaw('&lt;script&gt;alert(&quot;view&quot;);&lt;/script&gt;');
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
    $this->clickLink('<script>alert("menu");</script>');
    $this->assertRaw('&lt;script&gt;alert(&quot;menu&quot;);&lt;/script&gt;');
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
    $this->clickLink('<script>alert("block_content");</script>');
    $this->assertRaw('&lt;script&gt;alert(&quot;block_content&quot;);&lt;/script&gt;');
    $this->assertNoRaw('<script>alert("block_content");</script>');
  }

}
