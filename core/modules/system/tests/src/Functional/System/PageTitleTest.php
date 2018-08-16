<?php

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
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['node', 'test_page_test', 'form_test', 'block'];

  protected $contentUser;
  protected $savedTitle;

  /**
   * Implement setUp().
   */
  protected function setUp() {
    parent::setUp();

    $this->drupalCreateContentType(['type' => 'page', 'name' => 'Basic page']);

    $this->drupalPlaceBlock('page_title_block');

    $this->contentUser = $this->drupalCreateUser(['create page content', 'access content', 'administer themes', 'administer site configuration', 'link to any page']);
    $this->drupalLogin($this->contentUser);
  }

  /**
   * Tests the handling of HTML in node titles.
   */
  public function testTitleTags() {
    $title = "string with <em>HTML</em>";
    // Generate node content.
    $edit = [
      'title[0][value]' => '!SimpleTest! ' . $title . $this->randomMachineName(20),
      'body[0][value]' => '!SimpleTest! test body' . $this->randomMachineName(200),
    ];
    // Create the node with HTML in the title.
    $this->drupalPostForm('node/add/page', $edit, t('Save'));

    $node = $this->drupalGetNodeByTitle($edit['title[0][value]']);
    $this->assertNotNull($node, 'Node created and found in database');
    $this->assertText(Html::escape($edit['title[0][value]']), 'Check to make sure tags in the node title are converted.');
    $this->drupalGet("node/" . $node->id());
    $this->assertText(Html::escape($edit['title[0][value]']), 'Check to make sure tags in the node title are converted.');
  }

  /**
   * Test if the title of the site is XSS proof.
   */
  public function testTitleXSS() {
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
    $this->drupalPostForm('admin/config/system/site-information', $edit, t('Save configuration'));

    // Place branding block with site name and slogan into header region.
    $this->drupalPlaceBlock('system_branding_block', ['region' => 'header']);

    // Load frontpage.
    $this->drupalGet('');

    // Test the title.
    $this->assertNoRaw($title, 'Check for the lack of the unfiltered version of the title.');
    // Add </title> to make sure we're checking the title tag, rather than the
    // first 'heading' on the page.
    $this->assertRaw($title_filtered . '</title>', 'Check for the filtered version of the title in a <title> tag.');

    // Test the slogan.
    $this->assertNoRaw($slogan, 'Check for the unfiltered version of the slogan.');
    $this->assertRaw($slogan_filtered, 'Check for the filtered version of the slogan.');
  }

  /**
   * Tests the page title of render arrays.
   *
   * @see \Drupal\test_page_test\Controller\Test
   */
  public function testRoutingTitle() {
    // Test the '#title' render array attribute.
    $this->drupalGet('test-render-title');

    $this->assertTitle('Foo | Drupal');
    $result = $this->xpath('//h1[@class="page-title"]');
    $this->assertEqual('Foo', $result[0]->getText());

    // Test forms
    $this->drupalGet('form-test/object-builder');

    $this->assertTitle('Test dynamic title | Drupal');
    $result = $this->xpath('//h1[@class="page-title"]');
    $this->assertEqual('Test dynamic title', $result[0]->getText());

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

    $this->assertTitle('Static title translated | Drupal');
    $result = $this->xpath('//h1[@class="page-title"]');
    $this->assertEqual('Static title translated', $result[0]->getText());

    // Test the dynamic '_title_callback' route option.
    $this->drupalGet('test-page-dynamic-title');

    $this->assertTitle('Dynamic title | Drupal');
    $result = $this->xpath('//h1[@class="page-title"]');
    $this->assertEqual('Dynamic title', $result[0]->getText());

    // Ensure that titles are cacheable and are escaped normally if the
    // controller does not escape them.
    $this->drupalGet('test-page-cached-controller');
    $this->assertTitle('Cached title | Drupal');
    $this->assertRaw(Html::escape('<span>Cached title</span>') . '</h1>');
    $this->drupalGet('test-page-cached-controller');
    $this->assertTitle('Cached title | Drupal');
    $this->assertRaw(Html::escape('<span>Cached title</span>') . '</h1>');
  }

}
