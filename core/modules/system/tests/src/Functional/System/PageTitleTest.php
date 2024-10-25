<?php

declare(strict_types=1);

namespace Drupal\Tests\system\Functional\System;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Site\Settings;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests HTML output escaping of page title, site name, and slogan.
 *
 * @group system
 */
class PageTitleTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['node', 'test_page_test', 'form_test', 'block'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'starterkit_theme';

  protected $contentUser;

  /**
   * Implement setUp().
   */
  protected function setUp(): void {
    parent::setUp();

    $this->drupalCreateContentType(['type' => 'page', 'name' => 'Basic page']);

    $this->drupalPlaceBlock('page_title_block');

    $this->contentUser = $this->drupalCreateUser([
      'create page content',
      'access content',
      'administer themes',
      'administer site configuration',
      'link to any page',
    ]);
    $this->drupalLogin($this->contentUser);
  }

  /**
   * Tests the handling of HTML in node titles.
   */
  public function testTitleTags(): void {
    $title = "string with <em>HTML</em>";
    // Generate node content.
    $edit = [
      'title[0][value]' => '!Test! ' . $title . $this->randomMachineName(20),
      'body[0][value]' => '!Test! test body' . $this->randomMachineName(200),
    ];
    // Create the node with HTML in the title.
    $this->drupalGet('node/add/page');
    $this->submitForm($edit, 'Save');

    // Make sure tags in the node title are converted.
    $node = $this->drupalGetNodeByTitle($edit['title[0][value]']);
    $this->assertNotNull($node, 'Node created and found in database');
    $this->assertSession()->responseContains(Html::escape($edit['title[0][value]']));
    $this->drupalGet("node/" . $node->id());
    $this->assertSession()->responseContains(Html::escape($edit['title[0][value]']));
  }

  /**
   * Tests if the title of the site is XSS proof.
   */
  public function testTitleXSS(): void {
    // Set some title with JavaScript and HTML chars to escape.
    $title = '</title><script type="text/javascript">alert("Title XSS!");</script> & < > " \' ';
    $title_filtered = Html::escape($title);

    $slogan = '<script type="text/javascript">alert("Slogan XSS!");</script>';
    $slogan_filtered = Xss::filterAdmin($slogan);

    // Set title and slogan.
    $edit = [
      'site_name'    => $title,
      'site_slogan'  => $slogan,
    ];
    $this->drupalGet('admin/config/system/site-information');
    $this->submitForm($edit, 'Save configuration');

    // Place branding block with site name and slogan into header region.
    $this->drupalPlaceBlock('system_branding_block', ['region' => 'header']);

    // Load frontpage.
    $this->drupalGet('');

    // Test the title, checking for the lack of the unfiltered version of the
    // title.
    $this->assertSession()->responseNotContains($title);
    // Add </title> to make sure we're checking the title tag, rather than the
    // first 'heading' on the page.
    $this->assertSession()->responseContains($title_filtered . '</title>');

    // Test the slogan.
    // Check the unfiltered version of the slogan is missing.
    $this->assertSession()->responseNotContains($slogan);
    // Check for the filtered version of the slogan.
    $this->assertSession()->responseContains($slogan_filtered);
  }

  /**
   * Tests the page title of render arrays.
   *
   * @see \Drupal\test_page_test\Controller\Test
   */
  public function testRoutingTitle(): void {
    // Test the '#title' render array attribute.
    $this->drupalGet('test-render-title');

    $this->assertSession()->titleEquals('Foo | Drupal');
    $this->assertSession()->elementTextEquals('xpath', '//h1[@class="page-title"]', 'Foo');

    // Test forms
    $this->drupalGet('form-test/object-builder');

    $this->assertSession()->titleEquals('Test dynamic title | Drupal');
    $this->assertSession()->elementTextEquals('xpath', '//h1[@class="page-title"]', 'Test dynamic title');

    // Set some custom translated strings.
    $settings_key = 'locale_custom_strings_en';

    // Update in-memory settings directly.
    $settings = Settings::getAll();
    $settings[$settings_key] = ['' => ['Static title' => 'Static title translated']];
    new Settings($settings);

    // Rewrites the settings.php.
    $this->writeSettings([
      'settings' => [
        $settings_key => (object) [
          'value' => $settings[$settings_key],
          'required' => TRUE,
        ],
      ],
    ]);

    // Ensure that the title got translated.
    $this->drupalGet('test-page-static-title');

    $this->assertSession()->titleEquals('Static title translated | Drupal');
    $this->assertSession()->elementTextEquals('xpath', '//h1[@class="page-title"]', 'Static title translated');

    // Test the dynamic '_title_callback' route option.
    $this->drupalGet('test-page-dynamic-title');

    $this->assertSession()->titleEquals('Dynamic title | Drupal');
    $this->assertSession()->elementTextEquals('xpath', '//h1[@class="page-title"]', 'Dynamic title');

    // Ensure that titles are cacheable and are escaped normally if the
    // controller does not escape them.
    $this->drupalGet('test-page-cached-controller');
    $this->assertSession()->titleEquals('Cached title | Drupal');
    $this->assertSession()->responseContains(Html::escape('<span>Cached title</span>') . '</h1>');
    $this->drupalGet('test-page-cached-controller');
    $this->assertSession()->titleEquals('Cached title | Drupal');
    $this->assertSession()->responseContains(Html::escape('<span>Cached title</span>') . '</h1>');
  }

}
