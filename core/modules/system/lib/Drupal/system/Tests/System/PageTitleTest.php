<?php

/**
 * @file
 * Contains \Drupal\system\Tests\System\PageTitleTest.
 */

namespace Drupal\system\Tests\System;

use Drupal\Component\Utility\String;
use Drupal\Core\Language\Language;
use Drupal\Core\Utility\Title;
use Drupal\simpletest\WebTestBase;

class PageTitleTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('node', 'test_page_test', 'form_test');

  protected $content_user;
  protected $saved_title;

  /**
   * Implement getInfo().
   */
  public static function getInfo() {
    return array(
      'name' => 'Page titles',
      'description' => 'Tests correct handling or conversion by drupal_set_title() and drupal_get_title() and checks the correct escaping of site name and slogan.',
      'group' => 'System'
    );
  }

  /**
   * Implement setUp().
   */
  function setUp() {
    parent::setUp();

    $this->drupalCreateContentType(array('type' => 'page', 'name' => 'Basic page'));

    $this->content_user = $this->drupalCreateUser(array('create page content', 'access content', 'administer themes', 'administer site configuration'));
    $this->drupalLogin($this->content_user);
    $this->saved_title = drupal_get_title();
  }

  /**
   * Reset page title.
   */
  function tearDown() {
    // Restore the page title.
    drupal_set_title($this->saved_title, PASS_THROUGH);

    parent::tearDown();
  }

  /**
   * Tests the handling of HTML by drupal_set_title() and drupal_get_title()
   */
  function testTitleTags() {
    $title = "string with <em>HTML</em>";
    // drupal_set_title's $filter is Title::CHECK_PLAIN by default, so the title should be
    // returned with check_plain().
    drupal_set_title($title, Title::CHECK_PLAIN);
    $this->assertTrue(strpos(drupal_get_title(), '<em>') === FALSE, 'Tags in title converted to entities when $output is Title::CHECK_PLAIN.');
    // drupal_set_title's $filter is passed as PASS_THROUGH, so the title should be
    // returned with HTML.
    drupal_set_title($title, PASS_THROUGH);
    $this->assertTrue(strpos(drupal_get_title(), '<em>') !== FALSE, 'Tags in title are not converted to entities when $output is PASS_THROUGH.');
    // Generate node content.
    $langcode = Language::LANGCODE_NOT_SPECIFIED;
    $edit = array(
      "title" => '!SimpleTest! ' . $title . $this->randomName(20),
      "body[$langcode][0][value]" => '!SimpleTest! test body' . $this->randomName(200),
    );
    // Create the node with HTML in the title.
    $this->drupalPostForm('node/add/page', $edit, t('Save'));

    $node = $this->drupalGetNodeByTitle($edit["title"]);
    $this->assertNotNull($node, 'Node created and found in database');
    $this->drupalGet("node/" . $node->id());
    $this->assertText(check_plain($edit["title"]), 'Check to make sure tags in the node title are converted.');
  }
  /**
   * Test if the title of the site is XSS proof.
   */
  function testTitleXSS() {
    // Set some title with JavaScript and HTML chars to escape.
    $title = '</title><script type="text/javascript">alert("Title XSS!");</script> & < > " \' ';
    $title_filtered = check_plain($title);

    $slogan = '<script type="text/javascript">alert("Slogan XSS!");</script>';
    $slogan_filtered = filter_xss_admin($slogan);

    // Activate needed appearance settings.
    $edit = array(
      'toggle_name'           => TRUE,
      'toggle_slogan'         => TRUE,
      'toggle_main_menu'      => TRUE,
      'toggle_secondary_menu' => TRUE,
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
    $this->assertNoRaw($title, 'Check for the unfiltered version of the title.');
    // Adding </title> so we do not test the escaped version from drupal_set_title().
    $this->assertRaw($title_filtered . '</title>', 'Check for the filtered version of the title.');

    // Test the slogan.
    $this->assertNoRaw($slogan, 'Check for the unfiltered version of the slogan.');
    $this->assertRaw($slogan_filtered, 'Check for the filtered version of the slogan.');
  }

  /**
   * Tests the page title of render arrays.
   *
   * @see \Drupal\test_page_test\Controller\Test::renderTitle()
   */
  public function testRenderTitle() {
    $this->drupalGet('test-render-title');

    $this->assertTitle('Foo | Drupal');
    $result = $this->xpath('//h1');
    $this->assertEqual('Foo', (string) $result[0]);

    // Test a controller using _controller instead of _content.
    $this->drupalGet('test-render-title-controller');

    $this->assertTitle('Foo | Drupal');
    $result = $this->xpath('//h1');
    $this->assertEqual('Foo', (string) $result[0]);

    // Test forms
    $this->drupalGet('form-test/object-builder');

    $this->assertTitle('Test dynamic title | Drupal');
    $result = $this->xpath('//h1');
    $this->assertEqual('Test dynamic title', (string) $result[0]);
  }

}
