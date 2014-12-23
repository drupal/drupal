<?php

/**
 * @file
 * Definition of Drupal\system\Tests\Entity\EntityTranslationFormTest.
 */

namespace Drupal\system\Tests\Entity;

use Drupal\Core\Language\LanguageInterface;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\simpletest\WebTestBase;

/**
 * Tests entity translation form.
 *
 * @group Entity
 */
class EntityTranslationFormTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('entity_test', 'language', 'node');

  protected $langcodes;

  protected function setUp() {
    parent::setUp();
    // Enable translations for the test entity type.
    \Drupal::state()->set('entity_test.translation', TRUE);

    // Create test languages.
    $this->langcodes = array();
    for ($i = 0; $i < 2; ++$i) {
      $language = ConfigurableLanguage::create(array(
        'id' => 'l' . $i,
        'label' => $this->randomString(),
      ));
      $this->langcodes[$i] = $language->id();
      $language->save();
    }
  }

  /**
   * Tests entity form language.
   */
  function testEntityFormLanguage() {
    $this->drupalCreateContentType(array('type' => 'page', 'name' => 'Basic page'));

    $web_user = $this->drupalCreateUser(array('create page content', 'edit own page content', 'administer content types'));
    $this->drupalLogin($web_user);

    // Create a node with language LanguageInterface::LANGCODE_NOT_SPECIFIED.
    $edit = array();
    $edit['title[0][value]'] = $this->randomMachineName(8);
    $edit['body[0][value]'] = $this->randomMachineName(16);
    $this->drupalGet('node/add/page');
    $form_langcode = \Drupal::state()->get('entity_test.form_langcode');
    $this->drupalPostForm(NULL, $edit, t('Save'));

    $node = $this->drupalGetNodeByTitle($edit['title[0][value]']);

    $this->assertTrue($node->language()->getId() == $form_langcode, 'Form language is the same as the entity language.');

    // Edit the node and test the form language.
    $this->drupalGet($this->langcodes[0] . '/node/' . $node->id() . '/edit');
    $form_langcode = \Drupal::state()->get('entity_test.form_langcode');
    $this->assertTrue($node->language()->getId() == $form_langcode, 'Form language is the same as the entity language.');

    // Explicitly set form langcode.
    $langcode = $this->langcodes[0];
    $form_state_additions['langcode'] = $langcode;
    \Drupal::service('entity.form_builder')->getForm($node, 'default', $form_state_additions);
    $form_langcode = \Drupal::state()->get('entity_test.form_langcode');
    $this->assertTrue($langcode == $form_langcode, 'Form language is the same as the language parameter.');

    // Enable language selector.
    $this->drupalGet('admin/structure/types/manage/page');
    $edit = array('language_configuration[language_alterable]' => TRUE, 'language_configuration[langcode]' => LanguageInterface::LANGCODE_NOT_SPECIFIED);
    $this->drupalPostForm('admin/structure/types/manage/page', $edit, t('Save content type'));
    $this->assertRaw(t('The content type %type has been updated.', array('%type' => 'Basic page')), 'Basic page content type has been updated.');

    // Create a node with language.
    $edit = array();
    $langcode = $this->langcodes[0];
    $edit['title[0][value]'] = $this->randomMachineName(8);
    $edit['body[0][value]'] = $this->randomMachineName(16);
    $edit['langcode[0][value]'] = $langcode;
    $this->drupalPostForm('node/add/page', $edit, t('Save'));
    $this->assertRaw(t('Basic page %title has been created.', array('%title' => $edit['title[0][value]'])), 'Basic page created.');

    // Check to make sure the node was created.
    $node = $this->drupalGetNodeByTitle($edit['title[0][value]']);
    $this->assertTrue($node, 'Node found in database.');

    // Make body translatable.
    $field_storage = FieldStorageConfig::loadByName('node', 'body');
    $field_storage->translatable = TRUE;
    $field_storage->save();
    $field_storage = FieldStorageConfig::loadByName('node', 'body');
    $this->assertTrue($field_storage->isTranslatable(), 'Field body is translatable.');

    // Create a body translation and check the form language.
    $langcode2 = $this->langcodes[1];
    $node->getTranslation($langcode2)->body->value = $this->randomMachineName(16);
    $node->getTranslation($langcode2)->setOwnerId($web_user->id());
    $node->save();
    $this->drupalGet($langcode2 . '/node/' . $node->id() . '/edit');
    $form_langcode = \Drupal::state()->get('entity_test.form_langcode');
    $this->assertTrue($langcode2 == $form_langcode, "Node edit form language is $langcode2.");
  }
}
