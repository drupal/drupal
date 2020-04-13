<?php

namespace Drupal\Tests\block\Functional;

use Drupal\block_content\Entity\BlockContent;
use Drupal\block_content\Entity\BlockContentType;
use Drupal\Core\Url;
use Drupal\system\Entity\Menu;
use Drupal\Tests\BrowserTestBase;
use Drupal\views\Entity\View;

/**
 * Tests that the block module properly escapes block descriptions.
 *
 * @group block
 */
class BlockXssTest extends BrowserTestBase {

  /**
   * Modules to install.
   *
   * @var array
   */
  protected static $modules = ['block', 'block_content', 'menu_ui', 'views'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'classy';

  /**
   * Tests that nothing is escaped other than the blocks explicitly tested.
   */
  public function testNoUnexpectedEscaping() {
    $this->drupalLogin($this->drupalCreateUser(['administer blocks', 'access administration pages']));
    $this->drupalGet(Url::fromRoute('block.admin_display'));
    $this->clickLink('Place block');
    $this->assertNoEscaped('<');
  }

  /**
   * Tests XSS in title.
   */
  public function testXssInTitle() {
    $this->container->get('module_installer')->install(['block_test']);
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
    $this->container->get('module_installer')->install(['block_test']);
    $this->drupalPlaceBlock('test_xss_title');
    $this->drupalLogin($this->drupalCreateUser(['administer blocks', 'access administration pages']));
    $this->drupalGet(Url::fromRoute('block.admin_display'));
    $this->clickLink('Place block');
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

    $this->drupalGet(Url::fromRoute('block.admin_display'));
    $this->clickLink('Place block');
    $this->assertNoRaw('&amp;lt;', 'The page does not have double escaped HTML tags.');
  }

  /**
   * Tests XSS coming from View block labels.
   */
  protected function doViewTest() {
    // Create a View without a custom label for its block Display. The
    // admin_label of the block then becomes just the View's label.
    $view = View::create([
      'id' => $this->randomMachineName(),
      'label' => '<script>alert("view1");</script>',
    ]);
    $view->addDisplay('block');
    $view->save();

    // Create a View with a custom label for its block Display. The
    // admin_label of the block then becomes the View's label combined with
    // the Display's label.
    $view = View::create([
      'id' => $this->randomMachineName(),
      'label' => '<script>alert("view2");</script>',
    ]);
    $view->addDisplay('block', 'Fish & chips');
    $view->save();

    $this->drupalGet(Url::fromRoute('block.admin_display'));
    $this->clickLink('Place block');

    // \Drupal\views\Plugin\Derivative\ViewsBlock::getDerivativeDefinitions()
    // has a different code path for an admin label based only on the View
    // label versus one based on both the View label and the Display label.
    // Ensure that this test is covering both code paths by asserting the
    // absence of a ":" for the first View and the presence of a ":" for the
    // second one. Note that the second assertion is redundant with the one
    // further down which also checks for the Display label, but is included
    // here for clarity.
    $this->assertNoEscaped('<script>alert("view1");</script>:');
    $this->assertEscaped('<script>alert("view2");</script>:');

    // Assert that the blocks have their admin labels escaped and
    // don't appear anywhere unescaped.
    $this->assertEscaped('<script>alert("view1");</script>');
    $this->assertNoRaw('<script>alert("view1");</script>');
    $this->assertEscaped('<script>alert("view2");</script>: Fish & chips');
    $this->assertNoRaw('<script>alert("view2");</script>');
    $this->assertNoRaw('Fish & chips');

    // Assert the Display label doesn't appear anywhere double escaped.
    $this->assertNoRaw('Fish & chips');
    $this->assertNoRaw('Fish &amp;amp; chips');
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
    $this->clickLink('Place block');

    $this->assertEscaped('<script>alert("menu");</script>');
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
    $this->clickLink('Place block');

    $this->assertEscaped('<script>alert("block_content");</script>');
    $this->assertNoRaw('<script>alert("block_content");</script>');
  }

}
