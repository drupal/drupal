<?php

/**
 * @file
 * Contains \Drupal\system\Tests\System\PageTitleTest.
 */

namespace Drupal\system\Tests\System;

use Drupal\Component\Utility\SafeMarkup;
use Drupal\Component\Utility\Xss;
use Drupal\simpletest\WebTestBase;

/**
 * Tests HTML output escaping of page title, site name, and slogan.
 *
 * @group system
 */
class PageTitleTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('node', 'test_page_test', 'form_test');

  protected $contentUser;
  protected $savedTitle;

  /**
   * Implement setUp().
   */
  protected function setUp() {
    parent::setUp();

    $this->drupalCreateContentType(array('type' => 'page', 'name' => 'Basic page'));

    $this->contentUser = $this->drupalCreateUser(array('create page content', 'access content', 'administer themes', 'administer site configuration', 'link to any page'));
    $this->drupalLogin($this->contentUser);
  }

  /**
   * Tests the handling of HTML in node titles.
   */
  function testTitleTags() {
    $title = "string with <em>HTML</em>";
    // Generate node content.
    $edit = array(
      'title[0][value]' => '!SimpleTest! ' . $title . $this->randomMachineName(20),
      'body[0][value]' => '!SimpleTest! test body' . $this->randomMachineName(200),
    );
    // Create the node with HTML in the title.
    $this->drupalPostForm('node/add/page', $edit, t('Save'));

    $node = $this->drupalGetNodeByTitle($edit['title[0][value]']);
    $this->assertNotNull($node, 'Node created and found in database');
    $this->drupalGet("node/" . $node->id());
    $this->assertText(SafeMarkup::checkPlain($edit['title[0][value]']), 'Check to make sure tags in the node title are converted.');
  }

  /**
   * Test if the title of the site is XSS proof.
   */
  function testTitleXSS() {
    // Set some title with JavaScript and HTML chars to escape.
    $title = '</title><script type="text/javascript">alert("Title XSS!");</script> & < > " \' ';
    $title_filtered = SafeMarkup::checkPlain($title);

    $slogan = '<script type="text/javascript">alert("Slogan XSS!");</script>';
    $slogan_filtered = Xss::filterAdmin($slogan);

    // Activate needed appearance settings.
    $edit = array(
      'toggle_name'           => TRUE,
      'toggle_slogan'         => TRUE,
    );
    $this->drupalPostForm('admin/appearance/settings', $edit, t('Save configuration'));

    // Set title and slogan.
    $edit = array(
      'site_name'    => $title,
      'site_slogan'  => $slogan,
    );
    $this->drupalPostForm('admin/config/system/site-information', $edit, t('Save configuration'));

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
    $result = $this->xpath('//h1');
    $this->assertEqual('Foo', (string) $result[0]);

    // Test forms
    $this->drupalGet('form-test/object-builder');

    $this->assertTitle('Test dynamic title | Drupal');
    $result = $this->xpath('//h1');
    $this->assertEqual('Test dynamic title', (string) $result[0]);

    // Set some custom translated strings.
    $this->addCustomTranslations('en', array('' => array(
      'Static title' => 'Static title translated'
    )));
    $this->writeCustomTranslations();

    // Ensure that the title got translated.
    $this->drupalGet('test-page-static-title');

    $this->assertTitle('Static title translated | Drupal');
    $result = $this->xpath('//h1');
    $this->assertEqual('Static title translated', (string) $result[0]);

    // Test the dynamic '_title_callback' route option.
    $this->drupalGet('test-page-dynamic-title');

    $this->assertTitle('Dynamic title | Drupal');
    $result = $this->xpath('//h1');
    $this->assertEqual('Dynamic title', (string) $result[0]);
  }

}
