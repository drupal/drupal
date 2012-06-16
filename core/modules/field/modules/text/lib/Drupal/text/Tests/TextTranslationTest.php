<?php

/**
 * @file
 * Definition of Drupal\text\TextTranslationTest.
 */

namespace Drupal\text\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Tests text field translation.
 */
class TextTranslationTest extends WebTestBase {
  protected $profile = 'standard';

  public static function getInfo() {
    return array(
      'name' => 'Text translation',
      'description' => 'Check if the text field is correctly prepared for translation.',
      'group' => 'Field types',
    );
  }

  function setUp() {
    parent::setUp('translation');

    $full_html_format = filter_format_load('full_html');
    $this->format = $full_html_format->format;
    $this->admin = $this->drupalCreateUser(array(
      'administer languages',
      'administer content types',
      'access administration pages',
      'bypass node access',
      filter_permission_name($full_html_format),
    ));
    $this->translator = $this->drupalCreateUser(array('create article content', 'edit own article content', 'translate content'));

    // Enable an additional language.
    $this->drupalLogin($this->admin);
    $edit = array('langcode' => 'fr');
    $this->drupalPost('admin/config/regional/language/add', $edit, t('Add language'));

    // Set "Article" content type to use multilingual support with translation.
    $edit = array('node_type_language_hidden' => FALSE, 'node_type_language_translation_enabled' => TRUE);
    $this->drupalPost('admin/structure/types/manage/article', $edit, t('Save content type'));
    $this->assertRaw(t('The content type %type has been updated.', array('%type' => 'Article')), t('Article content type has been updated.'));
  }

  /**
   * Test that a plaintext textfield widget is correctly populated.
   */
  function testTextField() {
    // Disable text processing for body.
    $edit = array('instance[settings][text_processing]' => 0);
    $this->drupalPost('admin/structure/types/manage/article/fields/body', $edit, t('Save settings'));

    // Login as translator.
    $this->drupalLogin($this->translator);

    // Create content.
    $langcode = LANGUAGE_NOT_SPECIFIED;
    $body = $this->randomName();
    $edit = array(
      'title' => $this->randomName(),
      'langcode' => 'en',
      "body[$langcode][0][value]" => $body,
    );

    // Translate the article in french.
    $this->drupalPost('node/add/article', $edit, t('Save'));
    $node = $this->drupalGetNodeByTitle($edit['title']);
    $this->drupalGet("node/$node->nid/translate");
    $this->clickLink(t('add translation'));
    $this->assertFieldByXPath("//textarea[@name='body[$langcode][0][value]']", $body, t('The textfield widget is populated.'));
  }

  /**
   * Check that user that does not have access the field format cannot see the
   * source value when creating a translation.
   */
  function testTextFieldFormatted() {
    // Make node body multiple.
    $edit = array('field[cardinality]' => -1);
    $this->drupalPost('admin/structure/types/manage/article/fields/body', $edit, t('Save settings'));
    $this->drupalGet('node/add/article');
    $this->assertFieldByXPath("//input[@name='body_add_more']", t('Add another item'), t('Body field cardinality set to multiple.'));

    $body = array(
      $this->randomName(),
      $this->randomName(),
    );

    // Create an article with the first body input format set to "Full HTML".
    $title = $this->randomName();
    $edit = array(
      'title' => $title,
      'langcode' => 'en',
    );
    $this->drupalPost('node/add/article', $edit, t('Save'));

    // Populate the body field: the first item gets the "Full HTML" input
    // format, the second one "Filtered HTML".
    $formats = array('full_html', 'filtered_html');
    $langcode = LANGUAGE_NOT_SPECIFIED;
    foreach ($body as $delta => $value) {
      $edit = array(
        "body[$langcode][$delta][value]" => $value,
        "body[$langcode][$delta][format]" => array_shift($formats),
      );
      $this->drupalPost('node/1/edit', $edit, t('Save'));
      $this->assertText($body[$delta], t('The body field with delta @delta has been saved.', array('@delta' => $delta)));
    }

    // Login as translator.
    $this->drupalLogin($this->translator);

    // Translate the article in french.
    $node = $this->drupalGetNodeByTitle($title);
    $this->drupalGet("node/$node->nid/translate");
    $this->clickLink(t('add translation'));
    $this->assertNoText($body[0], t('The body field with delta @delta is hidden.', array('@delta' => 0)));
    $this->assertText($body[1], t('The body field with delta @delta is shown.', array('@delta' => 1)));
  }
}
