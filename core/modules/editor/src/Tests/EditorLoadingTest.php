<?php

/**
 * @file
 * Contains \Drupal\editor\Tests\EditorLoadingTest.
 */

namespace Drupal\editor\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Tests loading of text editors.
 *
 * @group editor
 */
class EditorLoadingTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('filter', 'editor', 'editor_test', 'node');

  /**
   * An untrusted user, with access to the 'plain_text' format.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $untrustedUser;

  /**
   * A normal user with additional access to the 'filtered_html' format.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $normalUser;

  /**
   * A privileged user with additional access to the 'full_html' format.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $privilegedUser;

  protected function setUp() {
    parent::setUp();

    // Add text formats.
    $filtered_html_format = entity_create('filter_format', array(
      'format' => 'filtered_html',
      'name' => 'Filtered HTML',
      'weight' => 0,
      'filters' => array(),
    ));
    $filtered_html_format->save();
    $full_html_format = entity_create('filter_format', array(
      'format' => 'full_html',
      'name' => 'Full HTML',
      'weight' => 1,
      'filters' => array(),
    ));
    $full_html_format->save();

    // Create node type.
    $this->drupalCreateContentType(array(
      'type' => 'article',
      'name' => 'Article',
    ));

    // Create 3 users, each with access to different text formats.
    $this->untrustedUser = $this->drupalCreateUser(array('create article content', 'edit any article content'));
    $this->normalUser = $this->drupalCreateUser(array('create article content', 'edit any article content', 'use text format filtered_html'));
    $this->privilegedUser = $this->drupalCreateUser(array('create article content', 'edit any article content', 'use text format filtered_html', 'use text format full_html'));
  }

  /**
   * Tests loading of text editors.
   */
  public function testLoading() {
    // Only associate a text editor with the "Full HTML" text format.
    $editor = entity_create('editor', array(
      'format' => 'full_html',
      'editor' => 'unicorn',
      'image_upload' => array(
        'status' => FALSE,
        'scheme' => file_default_scheme(),
        'directory' => 'inline-images',
        'max_size' => '',
        'max_dimensions' => array('width' => '', 'height' => ''),
      )
    ));
    $editor->save();

    // The normal user:
    // - has access to 2 text formats;
    // - doesn't have access to the full_html text format, so: no text editor.
    $this->drupalLogin($this->normalUser);
    $this->drupalGet('node/add/article');
    list( , $editor_settings_present, $editor_js_present, $body, $format_selector) = $this->getThingsToCheck();
    $this->assertFalse($editor_settings_present, 'No Text Editor module settings.');
    $this->assertFalse($editor_js_present, 'No Text Editor JavaScript.');
    $this->assertTrue(count($body) === 1, 'A body field exists.');
    $this->assertTrue(count($format_selector) === 0, 'No text format selector exists on the page because the user only has access to a single format.');
    $this->drupalLogout($this->normalUser);

    // The privileged user:
    // - has access to 2 text formats (and the fallback format);
    // - does have access to the full_html text format, so: Unicorn text editor.
    $this->drupalLogin($this->privilegedUser);
    $this->drupalGet('node/add/article');
    list($settings, $editor_settings_present, $editor_js_present, $body, $format_selector) = $this->getThingsToCheck();
    $expected = array('formats' => array('full_html' => array(
      'format' => 'full_html',
      'editor' => 'unicorn',
      'editorSettings' => array('ponyModeEnabled' => TRUE),
      'editorSupportsContentFiltering' => TRUE,
      'isXssSafe' => FALSE,
    )));
    $this->assertTrue($editor_settings_present, "Text Editor module's JavaScript settings are on the page.");
    $this->assertIdentical($expected, $settings['editor'], "Text Editor module's JavaScript settings on the page are correct.");
    $this->assertTrue($editor_js_present, 'Text Editor JavaScript is present.');
    $this->assertTrue(count($body) === 1, 'A body field exists.');
    $this->assertTrue(count($format_selector) === 1, 'A single text format selector exists on the page.');
    $specific_format_selector = $this->xpath('//select[contains(@class, "filter-list") and contains(@class, "editor") and @data-editor-for="edit-body-0-value"]');
    $this->assertTrue(count($specific_format_selector) === 1, 'A single text format selector exists on the page and has the "editor" class and a "data-editor-for" attribute with the correct value.');

    // Load the editor image dialog form and make sure it does not fatal.
    $this->drupalGet('editor/dialog/image/full_html');
    $this->assertResponse(200);

    $this->drupalLogout($this->privilegedUser);

    // Also associate a text editor with the "Plain Text" text format.
    $editor = entity_create('editor', array(
      'format' => 'plain_text',
      'editor' => 'unicorn',
    ));
    $editor->save();

    // The untrusted user:
    // - has access to 1 text format (plain_text);
    // - has access to the plain_text text format, so: Unicorn text editor.
    $this->drupalLogin($this->untrustedUser);
    $this->drupalGet('node/add/article');
    list($settings, $editor_settings_present, $editor_js_present, $body, $format_selector) = $this->getThingsToCheck();
    $expected = array('formats' => array('plain_text' => array(
      'format' => 'plain_text',
      'editor' => 'unicorn',
      'editorSettings' => array('ponyModeEnabled' => TRUE),
      'editorSupportsContentFiltering' => TRUE,
      'isXssSafe' => FALSE,
    )));
    $this->assertTrue($editor_settings_present, "Text Editor module's JavaScript settings are on the page.");
    $this->assertIdentical($expected, $settings['editor'], "Text Editor module's JavaScript settings on the page are correct.");
    $this->assertTrue($editor_js_present, 'Text Editor JavaScript is present.');
    $this->assertTrue(count($body) === 1, 'A body field exists.');
    $this->assertTrue(count($format_selector) === 0, 'No text format selector exists on the page.');
    $hidden_input = $this->xpath('//input[@type="hidden" and @value="plain_text" and contains(@class, "editor") and @data-editor-for="edit-body-0-value"]');
    $this->assertTrue(count($hidden_input) === 1, 'A single text format hidden input exists on the page and has the "editor" class and a "data-editor-for" attribute with the correct value.');

    // Create an "article" node that users the full_html text format, then try
    // to let the untrusted user edit it.
    $this->drupalCreateNode(array(
      'type' => 'article',
      'body' => array(
        array('value' => $this->randomMachineName(32), 'format' => 'full_html')
      ),
    ));

    // The untrusted user tries to edit content that is written in a text format
    // that (s)he is not allowed to use. The editor is still loaded. CKEditor,
    // for example, supports being loaded in a disabled state.
    $this->drupalGet('node/1/edit');
    list( , $editor_settings_present, $editor_js_present, $body, $format_selector) = $this->getThingsToCheck();
    $this->assertTrue($editor_settings_present, 'Text Editor module settings.');
    $this->assertTrue($editor_js_present, 'Text Editor JavaScript.');
    $this->assertTrue(count($body) === 1, 'A body field exists.');
    $this->assertFieldByXPath('//textarea[@id="edit-body-0-value" and @disabled="disabled"]', t('This field has been disabled because you do not have sufficient permissions to edit it.'), 'Text format access denied message found.');
    $this->assertTrue(count($format_selector) === 0, 'No text format selector exists on the page.');
    $hidden_input = $this->xpath('//input[@type="hidden" and contains(@class, "editor")]');
    $this->assertTrue(count($hidden_input) === 0, 'A single text format hidden input does not exist on the page.');
  }

  protected function getThingsToCheck() {
    $settings = $this->getDrupalSettings();
    return array(
      // JavaScript settings.
      $settings,
      // Editor.module's JS settings present.
      isset($settings['editor']),
      // Editor.module's JS present.
      strpos($this->getRawContent(), drupal_get_path('module', 'editor') . '/js/editor.js') !== FALSE,
      // Body field.
      $this->xpath('//textarea[@id="edit-body-0-value"]'),
      // Format selector.
      $this->xpath('//select[contains(@class, "filter-list")]'),
    );
  }

}
