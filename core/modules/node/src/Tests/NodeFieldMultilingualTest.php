<?php

/**
 * @file
 * Contains \Drupal\node\Tests\NodeFieldMultilingualTest.
 */

namespace Drupal\node\Tests;

use Drupal\field\Entity\FieldStorageConfig;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\language\Plugin\LanguageNegotiation\LanguageNegotiationUrl;
use Drupal\simpletest\WebTestBase;
use Drupal\Core\Language\LanguageInterface;

/**
 * Tests multilingual support for fields.
 *
 * @group node
 */
class NodeFieldMultilingualTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('node', 'language');

  protected function setUp() {
    parent::setUp();

    // Create Basic page node type.
    $this->drupalCreateContentType(array('type' => 'page', 'name' => 'Basic page'));

    // Setup users.
    $admin_user = $this->drupalCreateUser(array('administer languages', 'administer content types', 'access administration pages', 'create page content', 'edit own page content'));
    $this->drupalLogin($admin_user);

    // Add a new language.
    ConfigurableLanguage::createFromLangcode('it')->save();

    // Enable URL language detection and selection.
    $edit = array('language_interface[enabled][language-url]' => '1');
    $this->drupalPostForm('admin/config/regional/language/detection', $edit, t('Save settings'));

    // Set "Basic page" content type to use multilingual support.
    $edit = array(
      'language_configuration[language_show]' => TRUE,
    );
    $this->drupalPostForm('admin/structure/types/manage/page', $edit, t('Save content type'));
    $this->assertRaw(t('The content type %type has been updated.', array('%type' => 'Basic page')), 'Basic page content type has been updated.');

    // Make node body translatable.
    $field_storage = FieldStorageConfig::loadByName('node', 'body');
    $field_storage->translatable = TRUE;
    $field_storage->save();
  }

  /**
   * Tests whether field languages are correctly set through the node form.
   */
  function testMultilingualNodeForm() {
    // Create "Basic page" content.
    $langcode = language_get_default_langcode('node', 'page');
    $title_key = 'title[0][value]';
    $title_value = $this->randomMachineName(8);
    $body_key = 'body[0][value]';
    $body_value = $this->randomMachineName(16);

    // Create node to edit.
    $edit = array();
    $edit[$title_key] = $title_value;
    $edit[$body_key] = $body_value;
    $this->drupalPostForm('node/add/page', $edit, t('Save'));

    // Check that the node exists in the database.
    $node = $this->drupalGetNodeByTitle($edit[$title_key]);
    $this->assertTrue($node, 'Node found in database.');
    $this->assertTrue($node->language()->getId() == $langcode && $node->body->value == $body_value, 'Field language correctly set.');

    // Change node language.
    $langcode = 'it';
    $this->drupalGet("node/{$node->id()}/edit");
    $edit = array(
      $title_key => $this->randomMachineName(8),
      'langcode' => $langcode,
    );
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $node = $this->drupalGetNodeByTitle($edit[$title_key], TRUE);
    $this->assertTrue($node, 'Node found in database.');
    $this->assertTrue($node->language()->getId() == $langcode && $node->body->value == $body_value, 'Field language correctly changed.');

    // Enable content language URL detection.
    $this->container->get('language_negotiator')->saveConfiguration(LanguageInterface::TYPE_CONTENT, array(LanguageNegotiationUrl::METHOD_ID => 0));

    // Test multilingual field language fallback logic.
    $this->drupalGet("it/node/{$node->id()}");
    $this->assertRaw($body_value, 'Body correctly displayed using Italian as requested language');

    $this->drupalGet("node/{$node->id()}");
    $this->assertRaw($body_value, 'Body correctly displayed using English as requested language');
  }

  /*
   * Tests multilingual field display settings.
   */
  function testMultilingualDisplaySettings() {
    // Create "Basic page" content.
    $title_key = 'title[0][value]';
    $title_value = $this->randomMachineName(8);
    $body_key = 'body[0][value]';
    $body_value = $this->randomMachineName(16);

    // Create node to edit.
    $edit = array();
    $edit[$title_key] = $title_value;
    $edit[$body_key] = $body_value;
    $this->drupalPostForm('node/add/page', $edit, t('Save'));

    // Check that the node exists in the database.
    $node = $this->drupalGetNodeByTitle($edit[$title_key]);
    $this->assertTrue($node, 'Node found in database.');

    // Check if node body is showed.
    $this->drupalGet('node/' . $node->id());
     $body = $this->xpath('//article[contains(concat(" ", normalize-space(@class), " "), :node-class)]//div[contains(concat(" ", normalize-space(@class), " "), :content-class)]/descendant::p', array(
      ':node-class' => ' node ',
      ':content-class' => 'node__content',
    ));
    $this->assertEqual(current($body), $node->body->value, 'Node body found.');
  }

}
