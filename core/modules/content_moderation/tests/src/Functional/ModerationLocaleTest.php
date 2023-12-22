<?php

namespace Drupal\Tests\content_moderation\Functional;

use Drupal\node\NodeInterface;

/**
 * Test content_moderation functionality with localization and translation.
 *
 * @group content_moderation
 * @group #slow
 */
class ModerationLocaleTest extends ModerationStateTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'node',
    'content_moderation',
    'locale',
    'content_translation',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->drupalLogin($this->rootUser);

    // Enable moderation on Article node type.
    $this->createContentTypeFromUi('Article', 'article', TRUE);

    // Add French and Italian languages.
    foreach (['fr', 'it'] as $langcode) {
      $edit = [
        'predefined_langcode' => $langcode,
      ];
      $this->drupalGet('admin/config/regional/language/add');
      $this->submitForm($edit, 'Add language');
    }

    // Enable content translation on articles.
    $this->drupalGet('admin/config/regional/content-language');
    $edit = [
      'entity_types[node]' => TRUE,
      'settings[node][article][translatable]' => TRUE,
      'settings[node][article][settings][language][language_alterable]' => TRUE,
    ];
    $this->submitForm($edit, 'Save configuration');

    // Adding languages requires a container rebuild in the test running
    // environment so that multilingual services are used.
    $this->rebuildContainer();
  }

  /**
   * Tests article translations can be moderated separately.
   */
  public function testTranslateModeratedContent() {
    // Create a published article in English.
    $edit = [
      'title[0][value]' => 'Published English node',
      'langcode[0][value]' => 'en',
      'moderation_state[0][state]' => 'published',
    ];
    $this->drupalGet('node/add/article');
    $this->submitForm($edit, 'Save');
    $this->assertSession()->pageTextContains('Article Published English node has been created.');
    $english_node = $this->drupalGetNodeByTitle('Published English node');

    // Add a French translation.
    $this->drupalGet('node/' . $english_node->id() . '/translations');
    $this->clickLink('Add');
    $edit = [
      'title[0][value]' => 'French node Draft',
      'moderation_state[0][state]' => 'draft',
    ];
    $this->submitForm($edit, 'Save (this translation)');
    // Here the error has occurred "The website encountered an unexpected error.
    // Try again later."
    // If the translation has got lost.
    $this->assertSession()->pageTextContains('Article French node Draft has been updated.');

    // Create an article in English.
    $edit = [
      'title[0][value]' => 'English node',
      'langcode[0][value]' => 'en',
      'moderation_state[0][state]' => 'draft',
    ];
    $this->drupalGet('node/add/article');
    $this->submitForm($edit, 'Save');
    $this->assertSession()->pageTextContains('Article English node has been created.');
    $english_node = $this->drupalGetNodeByTitle('English node');

    // Add a French translation.
    $this->drupalGet('node/' . $english_node->id() . '/translations');
    $this->clickLink('Add');
    $edit = [
      'title[0][value]' => 'French node',
      'moderation_state[0][state]' => 'draft',
    ];
    $this->submitForm($edit, 'Save (this translation)');
    $this->assertSession()->pageTextContains('Article French node has been updated.');
    $english_node = $this->drupalGetNodeByTitle('English node', TRUE);

    // Publish the English article and check that the translation stays
    // unpublished.
    $this->drupalGet('node/' . $english_node->id() . '/edit');
    $this->submitForm(['moderation_state[0][state]' => 'published'], 'Save (this translation)');
    $this->assertSession()->pageTextContains('Article English node has been updated.');
    $english_node = $this->drupalGetNodeByTitle('English node', TRUE);
    $french_node = $english_node->getTranslation('fr');
    $this->assertEquals('French node', $french_node->label());

    $this->assertEquals('published', $english_node->moderation_state->value);
    $this->assertTrue($english_node->isPublished());
    $this->assertEquals('draft', $french_node->moderation_state->value);
    $this->assertFalse($french_node->isPublished());

    // Create another article with its translation. This time we will publish
    // the translation first.
    $edit = [
      'title[0][value]' => 'Another node',
      'moderation_state[0][state]' => 'draft',
    ];
    $this->drupalGet('node/add/article');
    $this->submitForm($edit, 'Save');
    $this->assertSession()->pageTextContains('Article Another node has been created.');
    $english_node = $this->drupalGetNodeByTitle('Another node');

    // Add a French translation.
    $this->drupalGet('node/' . $english_node->id() . '/translations');
    $this->clickLink('Add');
    $edit = [
      'title[0][value]' => 'Translated node',
      'moderation_state[0][state]' => 'draft',
    ];
    $this->submitForm($edit, 'Save (this translation)');
    $this->assertSession()->pageTextContains('Article Translated node has been updated.');
    $english_node = $this->drupalGetNodeByTitle('Another node', TRUE);

    // Publish the translation and check that the source language version stays
    // unpublished.
    $this->drupalGet('fr/node/' . $english_node->id() . '/edit');
    $this->submitForm(['moderation_state[0][state]' => 'published'], 'Save (this translation)');
    $this->assertSession()->pageTextContains('Article Translated node has been updated.');
    $english_node = $this->drupalGetNodeByTitle('Another node', TRUE);
    $french_node = $english_node->getTranslation('fr');
    $this->assertEquals('published', $french_node->moderation_state->value);
    $this->assertTrue($french_node->isPublished());
    $this->assertEquals('draft', $english_node->moderation_state->value);
    $this->assertFalse($english_node->isPublished());

    // Now check that we can create a new draft of the translation.
    $edit = [
      'title[0][value]' => 'New draft of translated node',
      'moderation_state[0][state]' => 'draft',
    ];
    $this->drupalGet('fr/node/' . $english_node->id() . '/edit');
    $this->submitForm($edit, 'Save (this translation)');
    $this->assertSession()->pageTextContains('Article New draft of translated node has been updated.');
    $english_node = $this->drupalGetNodeByTitle('Another node', TRUE);
    $french_node = $english_node->getTranslation('fr');
    $this->assertEquals('published', $french_node->moderation_state->value);
    $this->assertTrue($french_node->isPublished());
    $this->assertEquals('Translated node', $french_node->getTitle(), 'The default revision of the published translation remains the same.');

    // Publish the French article before testing the archive transition.
    $this->drupalGet('fr/node/' . $english_node->id() . '/edit');
    $this->submitForm(['moderation_state[0][state]' => 'published'], 'Save (this translation)');
    $this->assertSession()->pageTextContains('Article New draft of translated node has been updated.');
    $english_node = $this->drupalGetNodeByTitle('Another node', TRUE);
    $french_node = $english_node->getTranslation('fr');
    $this->assertEquals('published', $french_node->moderation_state->value);
    $this->assertTrue($french_node->isPublished());
    $this->assertEquals('New draft of translated node', $french_node->getTitle(), 'The draft has replaced the published revision.');

    // Publish the English article before testing the archive transition.
    $this->drupalGet('node/' . $english_node->id() . '/edit');
    $this->submitForm([
      'moderation_state[0][state]' => 'published',
    ], 'Save (this translation)');
    $this->assertSession()->pageTextContains('Article Another node has been updated.');
    $english_node = $this->drupalGetNodeByTitle('Another node', TRUE);
    $this->assertEquals('published', $english_node->moderation_state->value);

    // Archive the node and its translation.
    $this->drupalGet('node/' . $english_node->id() . '/edit');
    $this->submitForm([
      'moderation_state[0][state]' => 'archived',
    ], 'Save (this translation)');
    $this->assertSession()->pageTextContains('Article Another node has been updated.');
    $this->drupalGet('fr/node/' . $english_node->id() . '/edit');
    $this->submitForm([
      'moderation_state[0][state]' => 'archived',
    ], 'Save (this translation)');
    $this->assertSession()->pageTextContains('Article New draft of translated node has been updated.');
    $english_node = $this->drupalGetNodeByTitle('Another node', TRUE);
    $french_node = $english_node->getTranslation('fr');
    $this->assertEquals('archived', $english_node->moderation_state->value);
    $this->assertFalse($english_node->isPublished());
    $this->assertEquals('archived', $french_node->moderation_state->value);
    $this->assertFalse($french_node->isPublished());
  }

  /**
   * Tests that individual translations can be moderated independently.
   */
  public function testLanguageIndependentContentModeration() {
    // Create a published article in English (revision 1).
    $this->drupalGet('node/add/article');
    $node = $this->submitNodeForm('Test 1.1 EN', 'published');
    $this->assertNotLatestVersionPage($node);

    $edit_path = $node->toUrl('edit-form');
    $translate_path = $node->toUrl('drupal:content-translation-overview');

    // Create a new English draft (revision 2).
    $this->drupalGet($edit_path);
    $this->submitNodeForm('Test 1.2 EN', 'draft', TRUE);
    $this->assertLatestVersionPage($node);

    // Add a French translation draft (revision 3).
    $this->drupalGet($translate_path);
    $this->clickLink('Add');
    $this->submitNodeForm('Test 1.3 FR', 'draft');
    $fr_node = $this->loadTranslation($node, 'fr');
    $this->assertLatestVersionPage($fr_node);
    $this->assertModerationForm($node);

    // Add an Italian translation draft (revision 4).
    $this->drupalGet($translate_path);
    $this->clickLink('Add');
    $this->submitNodeForm('Test 1.4 IT', 'draft');
    $it_node = $this->loadTranslation($node, 'it');
    $this->assertLatestVersionPage($it_node);
    $this->assertModerationForm($node);
    $this->assertModerationForm($fr_node);

    // Publish the English draft (revision 5).
    $this->drupalGet($edit_path);
    $this->submitNodeForm('Test 1.5 EN', 'published', TRUE);
    $this->assertNotLatestVersionPage($node);
    $this->assertModerationForm($fr_node);
    $this->assertModerationForm($it_node);

    // Publish the Italian draft (revision 6).
    $this->drupalGet($translate_path);
    $this->clickLink('Edit', 3);
    $this->submitNodeForm('Test 1.6 IT', 'published');
    $this->assertNotLatestVersionPage($it_node);
    $this->assertNoModerationForm($node);
    $this->assertModerationForm($fr_node);

    // Publish the French draft (revision 7).
    $this->drupalGet($translate_path);
    $this->clickLink('Edit', 2);
    $this->submitNodeForm('Test 1.7 FR', 'published');
    $this->assertNotLatestVersionPage($fr_node);
    $this->assertNoModerationForm($node);
    $this->assertNoModerationForm($it_node);

    // Create an Italian draft (revision 8).
    $this->drupalGet($translate_path);
    $this->clickLink('Edit', 3);
    $this->submitNodeForm('Test 1.8 IT', 'draft');
    $this->assertLatestVersionPage($it_node);
    $this->assertNoModerationForm($node);
    $this->assertNoModerationForm($fr_node);

    // Create a French draft (revision 9).
    $this->drupalGet($translate_path);
    $this->clickLink('Edit', 2);
    $this->submitNodeForm('Test 1.9 FR', 'draft');
    $this->assertLatestVersionPage($fr_node);
    $this->assertNoModerationForm($node);
    $this->assertModerationForm($it_node);

    // Create an English draft (revision 10).
    $this->drupalGet($edit_path);
    $this->submitNodeForm('Test 1.10 EN', 'draft');
    $this->assertLatestVersionPage($node);
    $this->assertModerationForm($fr_node);
    $this->assertModerationForm($it_node);

    // Now start from a draft article in English (revision 1).
    $this->drupalGet('node/add/article');
    $node2 = $this->submitNodeForm('Test 2.1 EN', 'draft', TRUE);
    $this->assertNotLatestVersionPage($node2, TRUE);

    $edit_path = $node2->toUrl('edit-form');
    $translate_path = $node2->toUrl('drupal:content-translation-overview');

    // Add a French translation (revision 2).
    $this->drupalGet($translate_path);
    $this->clickLink('Add');
    $this->submitNodeForm('Test 2.2 FR', 'draft');
    $fr_node2 = $this->loadTranslation($node2, 'fr');
    $this->assertNotLatestVersionPage($fr_node2, TRUE);
    $this->assertModerationForm($node2, FALSE);

    // Add an Italian translation (revision 3).
    $this->drupalGet($translate_path);
    $this->clickLink('Add');
    $this->submitNodeForm('Test 2.3 IT', 'draft');
    $it_node2 = $this->loadTranslation($node2, 'it');
    $this->assertNotLatestVersionPage($it_node2, TRUE);
    $this->assertModerationForm($node2, FALSE);
    $this->assertModerationForm($fr_node2, FALSE);

    // Publish the English draft (revision 4).
    $this->drupalGet($edit_path);
    $this->submitNodeForm('Test 2.4 EN', 'published', TRUE);
    $this->assertNotLatestVersionPage($node2);
    $this->assertModerationForm($fr_node2, FALSE);
    $this->assertModerationForm($it_node2, FALSE);

    // Publish the Italian draft (revision 5).
    $this->drupalGet($translate_path);
    $this->clickLink('Edit', 3);
    $this->submitNodeForm('Test 2.5 IT', 'published');
    $this->assertNotLatestVersionPage($it_node2);
    $this->assertNoModerationForm($node2);
    $this->assertModerationForm($fr_node2, FALSE);

    // Publish the French draft (revision 6).
    $this->drupalGet($translate_path);
    $this->clickLink('Edit', 2);
    $this->submitNodeForm('Test 2.6 FR', 'published');
    $this->assertNotLatestVersionPage($fr_node2);
    $this->assertNoModerationForm($node2);
    $this->assertNoModerationForm($it_node2);

    // Now that all revision translations are published, verify that the
    // moderation form is never displayed on revision pages.
    /** @var \Drupal\node\NodeStorageInterface $storage */
    $storage = $this->container->get('entity_type.manager')->getStorage('node');
    foreach (range(11, 16) as $revision_id) {
      /** @var \Drupal\node\NodeInterface $revision */
      $revision = $storage->loadRevision($revision_id);
      foreach ($revision->getTranslationLanguages() as $langcode => $language) {
        if ($revision->isRevisionTranslationAffected()) {
          $this->drupalGet($revision->toUrl('revision'));
          $this->assertFalse($this->hasModerationForm(), 'Moderation form is not displayed correctly for revision ' . $revision_id);
          break;
        }
      }
    }

    // Create an Italian draft (revision 7).
    $this->drupalGet($translate_path);
    $this->clickLink('Edit', 3);
    $this->submitNodeForm('Test 2.7 IT', 'draft');
    $this->assertLatestVersionPage($it_node2);
    $this->assertNoModerationForm($node2);
    $this->assertNoModerationForm($fr_node2);

    // Create a French draft (revision 8).
    $this->drupalGet($translate_path);
    $this->clickLink('Edit', 2);
    $this->submitNodeForm('Test 2.8 FR', 'draft');
    $this->assertLatestVersionPage($fr_node2);
    $this->assertNoModerationForm($node2);
    $this->assertModerationForm($it_node2);

    // Create an English draft (revision 9).
    $this->drupalGet($edit_path);
    $this->submitNodeForm('Test 2.9 EN', 'draft', TRUE);
    $this->assertLatestVersionPage($node2);
    $this->assertModerationForm($fr_node2);
    $this->assertModerationForm($it_node2);

    // Now publish a draft in another language first and verify that the
    // moderation form is not displayed on the English node view page.
    $this->drupalGet('node/add/article');
    $node3 = $this->submitNodeForm('Test 3.1 EN', 'published');
    $this->assertNotLatestVersionPage($node3);

    $edit_path = $node3->toUrl('edit-form');
    $translate_path = $node3->toUrl('drupal:content-translation-overview');

    // Create an English draft (revision 2).
    $this->drupalGet($edit_path);
    $this->submitNodeForm('Test 3.2 EN', 'draft', TRUE);
    $this->assertLatestVersionPage($node3);

    // Add a French translation (revision 3).
    $this->drupalGet($translate_path);
    $this->clickLink('Add');
    $this->submitNodeForm('Test 3.3 FR', 'draft');
    $fr_node3 = $this->loadTranslation($node3, 'fr');
    $this->assertLatestVersionPage($fr_node3);
    $this->assertModerationForm($node3);

    // Publish the French draft (revision 4).
    $this->drupalGet($translate_path);
    $this->clickLink('Edit', 2);
    $this->submitNodeForm('Test 3.4 FR', 'published');
    $this->assertNotLatestVersionPage($fr_node3);
    $this->assertModerationForm($node3);
  }

  /**
   * Checks that new translation values are populated properly.
   */
  public function testNewTranslationSourceValues() {
    // Create a published article in Italian (revision 1).
    $this->drupalGet('node/add/article');
    $node = $this->submitNodeForm('Test 1.1 IT', 'published', TRUE, 'it');
    $this->assertNotLatestVersionPage($node);

    // Create a new draft (revision 2).
    $this->drupalGet($node->toUrl('edit-form'));
    $this->submitNodeForm('Test 1.2 IT', 'draft', TRUE);
    $this->assertLatestVersionPage($node);

    // Create an English draft (revision 3) and verify that the Italian draft
    // values are used as source values.
    $url = $node->toUrl('drupal:content-translation-add');
    $url->setRouteParameter('source', 'it');
    $url->setRouteParameter('target', 'en');
    $this->drupalGet($url);
    $this->assertSession()->pageTextContains('Test 1.2 IT');
    $this->submitNodeForm('Test 1.3 EN', 'draft');
    $this->assertLatestVersionPage($node);

    // Create a French draft (without saving) and verify that the Italian draft
    // values are used as source values.
    $url->setRouteParameter('target', 'fr');
    $this->drupalGet($url);
    $this->assertSession()->pageTextContains('Test 1.2 IT');

    // Now switch source language and verify that the English draft values are
    // used as source values.
    $url->setRouteParameter('source', 'en');
    $this->drupalGet($url);
    $this->assertSession()->pageTextContains('Test 1.3 EN');
  }

  /**
   * Submits the node form at the current URL with the specified values.
   *
   * @param string $title
   *   The node title.
   * @param string $moderation_state
   *   The moderation state.
   * @param bool $default_translation
   *   (optional) Whether we are editing the default translation.
   * @param string|null $langcode
   *   (optional) The node language. Defaults to English.
   *
   * @return \Drupal\node\NodeInterface|null
   *   A node object if a new one is being created, NULL otherwise.
   */
  protected function submitNodeForm($title, $moderation_state, $default_translation = FALSE, $langcode = 'en') {
    $is_new = str_contains($this->getSession()->getCurrentUrl(), '/node/add/');
    $edit = [
      'title[0][value]' => $title,
      'moderation_state[0][state]' => $moderation_state,
    ];
    if ($is_new) {
      $default_translation = TRUE;
      $edit['langcode[0][value]'] = $langcode;
    }
    $submit = $default_translation ? 'Save' : 'Save (this translation)';
    $this->submitForm($edit, $submit);
    $message = $is_new ? "Article $title has been created." : "Article $title has been updated.";
    $this->assertSession()->pageTextContains($message);
    return $is_new ? $this->drupalGetNodeByTitle($title) : NULL;
  }

  /**
   * Loads the node translation for the specified language.
   *
   * @param \Drupal\node\NodeInterface $node
   *   A node object.
   * @param string $langcode
   *   The translation language code.
   *
   * @return \Drupal\node\NodeInterface
   *   The node translation object.
   */
  protected function loadTranslation(NodeInterface $node, $langcode) {
    /** @var \Drupal\node\NodeStorageInterface $storage */
    $storage = $this->container->get('entity_type.manager')->getStorage('node');
    // Explicitly invalidate the cache for that node, as the call below is
    // statically cached.
    $storage->resetCache([$node->id()]);
    /** @var \Drupal\node\NodeInterface $node */
    $node = $storage->loadRevision($storage->getLatestRevisionId($node->id()));
    return $node->getTranslation($langcode);
  }

  /**
   * Asserts that this is the "latest version" page for the specified node.
   *
   * @param \Drupal\node\NodeInterface $node
   *   A node object.
   *
   * @internal
   */
  public function assertLatestVersionPage(NodeInterface $node): void {
    $this->assertEquals($node->toUrl('latest-version')->setAbsolute()->toString(), $this->getSession()->getCurrentUrl());
    $this->assertModerationForm($node);
  }

  /**
   * Asserts that this is not the "latest version" page for the specified node.
   *
   * @param \Drupal\node\NodeInterface $node
   *   A node object.
   * @param bool $moderation_form
   *   (optional) Whether the page should contain the moderation form. Defaults
   *   to FALSE.
   *
   * @internal
   */
  public function assertNotLatestVersionPage(NodeInterface $node, bool $moderation_form = FALSE): void {
    $this->assertNotEquals($node->toUrl('latest-version')->setAbsolute()->toString(), $this->getSession()->getCurrentUrl());
    if ($moderation_form) {
      $this->assertModerationForm($node, FALSE);
    }
    else {
      $this->assertNoModerationForm($node);
    }
  }

  /**
   * Asserts that the moderation form is displayed for the specified node.
   *
   * @param \Drupal\node\NodeInterface $node
   *   A node object.
   * @param bool $latest_tab
   *   (optional) Whether the node form is expected to be displayed on the
   *   latest version page or on the node view page. Defaults to the former.
   *
   * @internal
   */
  public function assertModerationForm(NodeInterface $node, bool $latest_tab = TRUE): void {
    $this->drupalGet($node->toUrl());
    $this->assertEquals(!$latest_tab, $this->hasModerationForm());
    $this->drupalGet($node->toUrl('latest-version'));
    $this->assertEquals($latest_tab, $this->hasModerationForm());
  }

  /**
   * Asserts that the moderation form is not displayed for the specified node.
   *
   * @param \Drupal\node\NodeInterface $node
   *   A node object.
   *
   * @internal
   */
  public function assertNoModerationForm(NodeInterface $node): void {
    $this->drupalGet($node->toUrl());
    $this->assertFalse($this->hasModerationForm());
    $this->drupalGet($node->toUrl('latest-version'));
    $this->assertEquals(403, $this->getSession()->getStatusCode());
  }

  /**
   * Checks whether the page contains the moderation form.
   *
   * @return bool
   *   TRUE if the moderation form could be find in the page, FALSE otherwise.
   */
  public function hasModerationForm() {
    return (bool) $this->xpath('//ul[@class="entity-moderation-form"]');
  }

}
