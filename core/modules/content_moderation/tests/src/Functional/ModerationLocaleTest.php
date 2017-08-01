<?php

namespace Drupal\Tests\content_moderation\Functional;

/**
 * Test content_moderation functionality with localization and translation.
 *
 * @group content_moderation
 */
class ModerationLocaleTest extends ModerationStateTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'node',
    'content_moderation',
    'locale',
    'content_translation',
  ];

  /**
   * Tests article translations can be moderated separately.
   */
  public function testTranslateModeratedContent() {
    $this->drupalLogin($this->rootUser);

    // Enable moderation on Article node type.
    $this->createContentTypeFromUi('Article', 'article', TRUE);

    // Add French language.
    $edit = [
      'predefined_langcode' => 'fr',
    ];
    $this->drupalPostForm('admin/config/regional/language/add', $edit, t('Add language'));

    // Enable content translation on articles.
    $this->drupalGet('admin/config/regional/content-language');
    $edit = [
      'entity_types[node]' => TRUE,
      'settings[node][article][translatable]' => TRUE,
      'settings[node][article][settings][language][language_alterable]' => TRUE,
    ];
    $this->drupalPostForm(NULL, $edit, t('Save configuration'));

    // Adding languages requires a container rebuild in the test running
    // environment so that multilingual services are used.
    $this->rebuildContainer();

    // Create a published article in English.
    $edit = [
      'title[0][value]' => 'Published English node',
      'langcode[0][value]' => 'en',
      'moderation_state[0][state]' => 'published',
    ];
    $this->drupalPostForm('node/add/article', $edit, t('Save'));
    $this->assertText(t('Article Published English node has been created.'));
    $english_node = $this->drupalGetNodeByTitle('Published English node');

    // Add a French translation.
    $this->drupalGet('node/' . $english_node->id() . '/translations');
    $this->clickLink(t('Add'));
    $edit = [
      'title[0][value]' => 'French node Draft',
      'moderation_state[0][state]' => 'draft',
    ];
    $this->drupalPostForm(NULL, $edit, t('Save (this translation)'));
    // Here the error has occurred "The website encountered an unexpected error.
    // Please try again later."
    // If the translation has got lost.
    $this->assertText(t('Article French node Draft has been updated.'));

    // Create an article in English.
    $edit = [
      'title[0][value]' => 'English node',
      'langcode[0][value]' => 'en',
      'moderation_state[0][state]' => 'draft',
    ];
    $this->drupalPostForm('node/add/article', $edit, t('Save'));
    $this->assertText(t('Article English node has been created.'));
    $english_node = $this->drupalGetNodeByTitle('English node');

    // Add a French translation.
    $this->drupalGet('node/' . $english_node->id() . '/translations');
    $this->clickLink(t('Add'));
    $edit = [
      'title[0][value]' => 'French node',
      'moderation_state[0][state]' => 'draft',
    ];
    $this->drupalPostForm(NULL, $edit, t('Save (this translation)'));
    $this->assertText(t('Article French node has been updated.'));
    $english_node = $this->drupalGetNodeByTitle('English node', TRUE);

    // Publish the English article and check that the translation stays
    // unpublished.
    $this->drupalPostForm('node/' . $english_node->id() . '/edit', [
      'moderation_state[0][state]' => 'published',
    ], t('Save (this translation)'));
    $this->assertText(t('Article English node has been updated.'));
    $english_node = $this->drupalGetNodeByTitle('English node', TRUE);
    $french_node = $english_node->getTranslation('fr');
    $this->assertEqual('French node', $french_node->label());

    $this->assertEqual($english_node->moderation_state->value, 'published');
    $this->assertTrue($english_node->isPublished());
    $this->assertEqual($french_node->moderation_state->value, 'draft');
    $this->assertFalse($french_node->isPublished());

    // Create another article with its translation. This time we will publish
    // the translation first.
    $edit = [
      'title[0][value]' => 'Another node',
      'moderation_state[0][state]' => 'draft',
    ];
    $this->drupalPostForm('node/add/article', $edit, t('Save'));
    $this->assertText(t('Article Another node has been created.'));
    $english_node = $this->drupalGetNodeByTitle('Another node');

    // Add a French translation.
    $this->drupalGet('node/' . $english_node->id() . '/translations');
    $this->clickLink(t('Add'));
    $edit = [
      'title[0][value]' => 'Translated node',
      'moderation_state[0][state]' => 'draft',
    ];
    $this->drupalPostForm(NULL, $edit, t('Save (this translation)'));
    $this->assertText(t('Article Translated node has been updated.'));
    $english_node = $this->drupalGetNodeByTitle('Another node', TRUE);

    // Publish the translation and check that the source language version stays
    // unpublished.
    $this->drupalPostForm('fr/node/' . $english_node->id() . '/edit', [
      'moderation_state[0][state]' => 'published',
    ], t('Save (this translation)'));
    $this->assertText(t('Article Translated node has been updated.'));
    $english_node = $this->drupalGetNodeByTitle('Another node', TRUE);
    $french_node = $english_node->getTranslation('fr');
    $this->assertEqual($french_node->moderation_state->value, 'published');
    $this->assertTrue($french_node->isPublished());
    $this->assertEqual($english_node->moderation_state->value, 'draft');
    $this->assertFalse($english_node->isPublished());

    // Now check that we can create a new draft of the translation.
    $edit = [
      'title[0][value]' => 'New draft of translated node',
      'moderation_state[0][state]' => 'draft',
    ];
    $this->drupalPostForm('fr/node/' . $english_node->id() . '/edit', $edit, t('Save (this translation)'));
    $this->assertText(t('Article New draft of translated node has been updated.'));
    $english_node = $this->drupalGetNodeByTitle('Another node', TRUE);
    $french_node = $english_node->getTranslation('fr');
    $this->assertEqual($french_node->moderation_state->value, 'published');
    $this->assertTrue($french_node->isPublished());
    $this->assertEqual($french_node->getTitle(), 'Translated node', 'The default revision of the published translation remains the same.');

    // Publish the French article before testing the archive transition.
    $this->drupalPostForm('fr/node/' . $english_node->id() . '/edit', [
      'moderation_state[0][state]' => 'published',
    ], t('Save (this translation)'));
    $this->assertText(t('Article New draft of translated node has been updated.'));
    $english_node = $this->drupalGetNodeByTitle('Another node', TRUE);
    $french_node = $english_node->getTranslation('fr');
    $this->assertEqual($french_node->moderation_state->value, 'published');
    $this->assertTrue($french_node->isPublished());
    $this->assertEqual($french_node->getTitle(), 'New draft of translated node', 'The draft has replaced the published revision.');

    // Publish the English article before testing the archive transition.
    $this->drupalPostForm('node/' . $english_node->id() . '/edit', [
      'moderation_state[0][state]' => 'published',
    ], t('Save (this translation)'));
    $this->assertText(t('Article Another node has been updated.'));
    $english_node = $this->drupalGetNodeByTitle('Another node', TRUE);
    $this->assertEqual($english_node->moderation_state->value, 'published');

    // Archive the node and its translation.
    $this->drupalPostForm('node/' . $english_node->id() . '/edit', [
      'moderation_state[0][state]' => 'archived',
    ], t('Save (this translation)'));
    $this->assertText(t('Article Another node has been updated.'));
    $this->drupalPostForm('fr/node/' . $english_node->id() . '/edit', [
      'moderation_state[0][state]' => 'archived',
    ], t('Save (this translation)'));
    $this->assertText(t('Article New draft of translated node has been updated.'));
    $english_node = $this->drupalGetNodeByTitle('Another node', TRUE);
    $french_node = $english_node->getTranslation('fr');
    $this->assertEqual($english_node->moderation_state->value, 'archived');
    $this->assertFalse($english_node->isPublished());
    $this->assertEqual($french_node->moderation_state->value, 'archived');
    $this->assertFalse($french_node->isPublished());
  }

}
