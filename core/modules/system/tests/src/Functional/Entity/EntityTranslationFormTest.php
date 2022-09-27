<?php

namespace Drupal\Tests\system\Functional\Entity;

use Drupal\Core\Language\LanguageInterface;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\node\Entity\Node;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests entity translation form.
 *
 * @group Entity
 */
class EntityTranslationFormTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['entity_test', 'language', 'node'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  protected $langcodes;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    // Enable translations for the test entity type.
    \Drupal::state()->set('entity_test.translation', TRUE);

    // Create test languages.
    $this->langcodes = [];
    for ($i = 0; $i < 2; ++$i) {
      $language = ConfigurableLanguage::create([
        'id' => 'l' . $i,
        'label' => $this->randomString(),
      ]);
      $this->langcodes[$i] = $language->id();
      $language->save();
    }
  }

  /**
   * Tests entity form language.
   */
  public function testEntityFormLanguage() {
    $this->drupalCreateContentType(['type' => 'page', 'name' => 'Basic page']);

    $web_user = $this->drupalCreateUser([
      'create page content',
      'edit own page content',
      'administer content types',
    ]);
    $this->drupalLogin($web_user);

    // Create a node with language LanguageInterface::LANGCODE_NOT_SPECIFIED.
    $edit = [];
    $edit['title[0][value]'] = $this->randomMachineName(8);
    $edit['body[0][value]'] = $this->randomMachineName(16);
    $this->drupalGet('node/add/page');
    $form_langcode = \Drupal::state()->get('entity_test.form_langcode');
    $this->submitForm($edit, 'Save');

    $node = $this->drupalGetNodeByTitle($edit['title[0][value]']);

    $this->assertSame($form_langcode, $node->language()->getId(), 'Form language is the same as the entity language.');

    // Edit the node and test the form language.
    $this->drupalGet($this->langcodes[0] . '/node/' . $node->id() . '/edit');
    $form_langcode = \Drupal::state()->get('entity_test.form_langcode');
    $this->assertSame($form_langcode, $node->language()->getId(), 'Form language is the same as the entity language.');

    // Explicitly set form langcode.
    $langcode = $this->langcodes[0];
    $form_state_additions['langcode'] = $langcode;
    \Drupal::service('entity.form_builder')->getForm($node, 'default', $form_state_additions);
    $form_langcode = \Drupal::state()->get('entity_test.form_langcode');
    $this->assertSame($form_langcode, $langcode, 'Form language is the same as the language parameter.');

    // Enable language selector.
    $this->drupalGet('admin/structure/types/manage/page');
    $edit = ['language_configuration[language_alterable]' => TRUE, 'language_configuration[langcode]' => LanguageInterface::LANGCODE_NOT_SPECIFIED];
    $this->drupalGet('admin/structure/types/manage/page');
    $this->submitForm($edit, 'Save content type');
    $this->assertSession()->pageTextContains("The content type Basic page has been updated.");

    // Create a node with language.
    $edit = [];
    $langcode = $this->langcodes[0];
    $edit['title[0][value]'] = $this->randomMachineName(8);
    $edit['body[0][value]'] = $this->randomMachineName(16);
    $edit['langcode[0][value]'] = $langcode;
    $this->drupalGet('node/add/page');
    $this->submitForm($edit, 'Save');
    $this->assertSession()->pageTextContains('Basic page ' . $edit['title[0][value]'] . ' has been created.');

    // Verify that the creation message contains a link to a node.
    $this->assertSession()->elementExists('xpath', '//div[@data-drupal-messages]//a[contains(@href, "node/")]');

    // Check to make sure the node was created.
    $node = $this->drupalGetNodeByTitle($edit['title[0][value]']);
    $this->assertInstanceOf(Node::class, $node);

    // Make body translatable.
    $field_storage = FieldStorageConfig::loadByName('node', 'body');
    $field_storage->setTranslatable(TRUE);
    $field_storage->save();
    $field_storage = FieldStorageConfig::loadByName('node', 'body');
    $this->assertTrue($field_storage->isTranslatable(), 'Field body is translatable.');

    // Create a body translation and check the form language.
    $langcode2 = $this->langcodes[1];
    $translation = $node->addTranslation($langcode2);
    $translation->title->value = $this->randomString();
    $translation->body->value = $this->randomMachineName(16);
    $translation->setOwnerId($web_user->id());
    $node->save();
    $this->drupalGet($langcode2 . '/node/' . $node->id() . '/edit');
    $form_langcode = \Drupal::state()->get('entity_test.form_langcode');
    $this->assertSame($form_langcode, $langcode2, "Node edit form language is $langcode2.");
  }

}
