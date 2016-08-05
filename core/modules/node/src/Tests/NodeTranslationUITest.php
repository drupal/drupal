<?php

namespace Drupal\node\Tests;

use Drupal\Core\Entity\EntityInterface;
use Drupal\content_translation\Tests\ContentTranslationUITestBase;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Url;
use Drupal\node\Entity\Node;
use Drupal\language\Entity\ConfigurableLanguage;

/**
 * Tests the Node Translation UI.
 *
 * @group node
 */
class NodeTranslationUITest extends ContentTranslationUITestBase {

  /**
   * {inheritdoc}
   */
  protected $defaultCacheContexts = [
    'languages:language_interface',
    'session',
    'theme',
    'route',
    'timezone',
    'url.path.parent',
    'url.query_args:_wrapper_format',
    'user'
  ];

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('block', 'language', 'content_translation', 'node', 'datetime', 'field_ui', 'help');

  /**
   * The profile to install as a basis for testing.
   *
   * @var string
   */
  protected $profile = 'standard';

  protected function setUp() {
    $this->entityTypeId = 'node';
    $this->bundle = 'article';
    parent::setUp();

    // Ensure the help message is shown even with prefixed paths.
    $this->drupalPlaceBlock('help_block', array('region' => 'content'));

    // Display the language selector.
    $this->drupalLogin($this->administrator);
    $edit = array('language_configuration[language_alterable]' => TRUE);
    $this->drupalPostForm('admin/structure/types/manage/article', $edit, t('Save content type'));
    $this->drupalLogin($this->translator);
  }

  /**
   * Tests the basic translation UI.
   */
  public function testTranslationUI() {
    parent::testTranslationUI();
    $this->doUninstallTest();
  }

  /**
   * Tests changing the published status on a node without fields.
   */
  public function testPublishedStatusNoFields() {
    // Test changing the published status of an article without fields.
    $this->drupalLogin($this->administrator);
    // Delete all fields.
    $this->drupalGet('admin/structure/types/manage/article/fields');
    $this->drupalPostForm('admin/structure/types/manage/article/fields/node.article.' . $this->fieldName . '/delete', array(), t('Delete'));
    $this->drupalPostForm('admin/structure/types/manage/article/fields/node.article.field_tags/delete', array(), t('Delete'));
    $this->drupalPostForm('admin/structure/types/manage/article/fields/node.article.field_image/delete', array(), t('Delete'));

    // Add a node.
    $default_langcode = $this->langcodes[0];
    $values[$default_langcode] = array('title' => array(array('value' => $this->randomMachineName())));
    $entity_id = $this->createEntity($values[$default_langcode], $default_langcode);
    $storage = $this->container->get('entity_type.manager')
      ->getStorage($this->entityTypeId);
    $storage->resetCache([$this->entityId]);
    $entity = $storage->load($this->entityId);

    // Add a content translation.
    $langcode = 'fr';
    $language = ConfigurableLanguage::load($langcode);
    $values[$langcode] = array('title' => array(array('value' => $this->randomMachineName())));

    $entity_type_id = $entity->getEntityTypeId();
    $add_url = Url::fromRoute("entity.$entity_type_id.content_translation_add", [
      $entity->getEntityTypeId() => $entity->id(),
      'source' => $default_langcode,
      'target' => $langcode
    ], array('language' => $language));
    $this->drupalPostForm($add_url, $this->getEditValues($values, $langcode), t('Save and unpublish (this translation)'));

    $storage->resetCache([$this->entityId]);
    $entity = $storage->load($this->entityId);
    $translation = $entity->getTranslation($langcode);
    // Make sure we unpublished the node correctly.
    $this->assertFalse($this->manager->getTranslationMetadata($translation)->isPublished(), 'The translation has been correctly unpublished.');
  }

  /**
   * {@inheritdoc}
   */
  protected function getTranslatorPermissions() {
    return array_merge(parent::getTranslatorPermissions(), array('administer nodes', "edit any $this->bundle content"));
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditorPermissions() {
    return array('administer nodes', 'create article content');
  }

  /**
   * {@inheritdoc}
   */
  protected function getAdministratorPermissions() {
    return array_merge(parent::getAdministratorPermissions(), array('access administration pages', 'administer content types', 'administer node fields', 'access content overview', 'bypass node access', 'administer languages', 'administer themes', 'view the administration theme'));
  }

  /**
   * {@inheritdoc}
   */
  protected function getNewEntityValues($langcode) {
    return array('title' => array(array('value' => $this->randomMachineName()))) + parent::getNewEntityValues($langcode);
  }

  /**
   * {@inheritdoc}
   */
  protected function getFormSubmitAction(EntityInterface $entity, $langcode) {
    if ($entity->getTranslation($langcode)->isPublished()) {
      return t('Save and keep published') . $this->getFormSubmitSuffix($entity, $langcode);
    }
    else {
      return t('Save and keep unpublished') . $this->getFormSubmitSuffix($entity, $langcode);
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function doTestPublishedStatus() {
    $storage = $this->container->get('entity_type.manager')
      ->getStorage($this->entityTypeId);
    $storage->resetCache([$this->entityId]);
    $entity = $storage->load($this->entityId);
    $languages = $this->container->get('language_manager')->getLanguages();

    $actions = array(
      t('Save and keep published'),
      t('Save and unpublish'),
    );

    foreach ($actions as $index => $action) {
      // (Un)publish the node translations and check that the translation
      // statuses are (un)published accordingly.
      foreach ($this->langcodes as $langcode) {
        $options = array('language' => $languages[$langcode]);
        $url = $entity->urlInfo('edit-form', $options);
        $this->drupalPostForm($url, array(), $action . $this->getFormSubmitSuffix($entity, $langcode), $options);
      }
      $storage->resetCache([$this->entityId]);
      $entity = $storage->load($this->entityId);
      foreach ($this->langcodes as $langcode) {
        // The node is created as unpublished thus we switch to the published
        // status first.
        $status = !$index;
        $translation = $entity->getTranslation($langcode);
        $this->assertEqual($status, $this->manager->getTranslationMetadata($translation)->isPublished(), 'The translation has been correctly unpublished.');
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function doTestAuthoringInfo() {
    $storage = $this->container->get('entity_type.manager')
      ->getStorage($this->entityTypeId);
    $storage->resetCache([$this->entityId]);
    $entity = $storage->load($this->entityId);
    $languages = $this->container->get('language_manager')->getLanguages();
    $values = array();

    // Post different base field information for each translation.
    foreach ($this->langcodes as $langcode) {
      $user = $this->drupalCreateUser();
      $values[$langcode] = array(
        'uid' => $user->id(),
        'created' => REQUEST_TIME - mt_rand(0, 1000),
        'sticky' => (bool) mt_rand(0, 1),
        'promote' => (bool) mt_rand(0, 1),
      );
      $edit = array(
        'uid[0][target_id]' => $user->getUsername(),
        'created[0][value][date]' => format_date($values[$langcode]['created'], 'custom', 'Y-m-d'),
        'created[0][value][time]' => format_date($values[$langcode]['created'], 'custom', 'H:i:s'),
        'sticky[value]' => $values[$langcode]['sticky'],
        'promote[value]' => $values[$langcode]['promote'],
      );
      $options = array('language' => $languages[$langcode]);
      $url = $entity->urlInfo('edit-form', $options);
      $this->drupalPostForm($url, $edit, $this->getFormSubmitAction($entity, $langcode), $options);
    }

    $storage->resetCache([$this->entityId]);
    $entity = $storage->load($this->entityId);
    foreach ($this->langcodes as $langcode) {
      $translation = $entity->getTranslation($langcode);
      $metadata = $this->manager->getTranslationMetadata($translation);
      $this->assertEqual($metadata->getAuthor()->id(), $values[$langcode]['uid'], 'Translation author correctly stored.');
      $this->assertEqual($metadata->getCreatedTime(), $values[$langcode]['created'], 'Translation date correctly stored.');
      $this->assertEqual($translation->isSticky(), $values[$langcode]['sticky'], 'Sticky of Translation correctly stored.');
      $this->assertEqual($translation->isPromoted(), $values[$langcode]['promote'], 'Promoted of Translation correctly stored.');
    }
  }

  /**
   * Tests that translation page inherits admin status of edit page.
   */
  public function testTranslationLinkTheme() {
    $this->drupalLogin($this->administrator);
    $article = $this->drupalCreateNode(array('type' => 'article', 'langcode' => $this->langcodes[0]));

    // Set up Seven as the admin theme and use it for node editing.
    $this->container->get('theme_handler')->install(array('seven'));
    $edit = array();
    $edit['admin_theme'] = 'seven';
    $edit['use_admin_theme'] = TRUE;
    $this->drupalPostForm('admin/appearance', $edit, t('Save configuration'));
    $this->drupalGet('node/' . $article->id() . '/translations');
    $this->assertRaw('core/themes/seven/css/base/elements.css', 'Translation uses admin theme if edit is admin.');

    // Turn off admin theme for editing, assert inheritance to translations.
    $edit['use_admin_theme'] = FALSE;
    $this->drupalPostForm('admin/appearance', $edit, t('Save configuration'));
    $this->drupalGet('node/' . $article->id() . '/translations');
    $this->assertNoRaw('core/themes/seven/css/base/elements.css', 'Translation uses frontend theme if edit is frontend.');

    // Assert presence of translation page itself (vs. DisabledBundle below).
    $this->assertResponse(200);
  }

  /**
   * Tests that no metadata is stored for a disabled bundle.
   */
  public function testDisabledBundle() {
    // Create a bundle that does not have translation enabled.
    $disabledBundle = $this->randomMachineName();
    $this->drupalCreateContentType(array('type' => $disabledBundle, 'name' => $disabledBundle));

    // Create a node for each bundle.
    $node = $this->drupalCreateNode(array(
      'type' => $this->bundle,
      'langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED,
    ));

    // Make sure that nothing was inserted into the {content_translation} table.
    $rows = db_query('SELECT nid, count(nid) AS count FROM {node_field_data} WHERE type <> :type GROUP BY nid HAVING count(nid) >= 2', array(':type' => $this->bundle))->fetchAll();
    $this->assertEqual(0, count($rows));

    // Ensure the translation tab is not accessible.
    $this->drupalGet('node/' . $node->id() . '/translations');
    $this->assertResponse(403);
  }

  /**
   * Tests that translations are rendered properly.
   */
  public function testTranslationRendering() {
    $default_langcode = $this->langcodes[0];
    $values[$default_langcode] = $this->getNewEntityValues($default_langcode);
    $this->entityId = $this->createEntity($values[$default_langcode], $default_langcode);
    $node = \Drupal::entityManager()->getStorage($this->entityTypeId)->load($this->entityId);
    $node->setPromoted(TRUE);

    // Create translations.
    foreach (array_diff($this->langcodes, array($default_langcode)) as $langcode) {
      $values[$langcode] = $this->getNewEntityValues($langcode);
      $translation = $node->addTranslation($langcode, $values[$langcode]);
      // Publish and promote the translation to frontpage.
      $translation->setPromoted(TRUE);
      $translation->setPublished(TRUE);
    }
    $node->save();

    // Test that the frontpage view displays the correct translations.
    \Drupal::service('module_installer')->install(array('views'), TRUE);
    $this->rebuildContainer();
    $this->doTestTranslations('node', $values);

    // Enable the translation language renderer.
    $view = \Drupal::entityManager()->getStorage('view')->load('frontpage');
    $display = &$view->getDisplay('default');
    $display['display_options']['rendering_language'] = '***LANGUAGE_entity_translation***';
    $view->save();

    // Need to check from the beginning, including the base_path, in the url
    // since the pattern for the default language might be a substring of
    // the strings for other languages.
    $base_path = base_path();

    // Check the frontpage for 'Read more' links to each translation.
    // See also assertTaxonomyPage() in NodeAccessBaseTableTest.
    $node_href = 'node/' . $node->id();
    foreach ($this->langcodes as $langcode) {
      $this->drupalGet('node', array('language' => \Drupal::languageManager()->getLanguage($langcode)));
      $num_match_found = 0;
      if ($langcode == 'en') {
        // Site default language does not have langcode prefix in the URL.
        $expected_href = $base_path . $node_href;
      }
      else {
        $expected_href = $base_path . $langcode . '/' . $node_href;
      }
      $pattern = '|^' . $expected_href . '$|';
      foreach ($this->xpath("//a[text()='Read more']") as $link) {
        if (preg_match($pattern, (string) $link['href'], $matches) == TRUE) {
          $num_match_found++;
        }
      }
      $this->assertTrue($num_match_found == 1, 'There is 1 Read more link, ' . $expected_href . ', for the ' . $langcode . ' translation of a node on the frontpage. (Found ' . $num_match_found . '.)');
    }

    // Check the frontpage for 'Add new comment' links that include the
    // language.
    $comment_form_href = 'node/' . $node->id() . '#comment-form';
    foreach ($this->langcodes as $langcode) {
      $this->drupalGet('node', array('language' => \Drupal::languageManager()->getLanguage($langcode)));
      $num_match_found = 0;
      if ($langcode == 'en') {
        // Site default language does not have langcode prefix in the URL.
        $expected_href = $base_path . $comment_form_href;
      }
      else {
        $expected_href = $base_path . $langcode . '/' . $comment_form_href;
      }
      $pattern = '|^' . $expected_href . '$|';
      foreach ($this->xpath("//a[text()='Add new comment']") as $link) {
        if (preg_match($pattern, (string) $link['href'], $matches) == TRUE) {
          $num_match_found++;
        }
      }
      $this->assertTrue($num_match_found == 1, 'There is 1 Add new comment link, ' . $expected_href . ', for the ' . $langcode . ' translation of a node on the frontpage. (Found ' . $num_match_found . '.)');
    }

    // Test that the node page displays the correct translations.
    $this->doTestTranslations('node/' . $node->id(), $values);

    // Test that the node page has the correct alternate hreflang links.
    $this->doTestAlternateHreflangLinks($node->urlInfo());
  }

  /**
   * Tests that the given path displays the correct translation values.
   *
   * @param string $path
   *   The path to be tested.
   * @param array $values
   *   The translation values to be found.
   */
  protected function doTestTranslations($path, array $values) {
    $languages = $this->container->get('language_manager')->getLanguages();
    foreach ($this->langcodes as $langcode) {
      $this->drupalGet($path, array('language' => $languages[$langcode]));
      $this->assertText($values[$langcode]['title'][0]['value'], format_string('The %langcode node translation is correctly displayed.', array('%langcode' => $langcode)));
    }
  }

  /**
   * Tests that the given path provides the correct alternate hreflang links.
   *
   * @param \Drupal\Core\Url $url
   *   The path to be tested.
   */
  protected function doTestAlternateHreflangLinks(Url $url) {
    $languages = $this->container->get('language_manager')->getLanguages();
    $url->setAbsolute();
    $urls = [];
    foreach ($this->langcodes as $langcode) {
      $language_url = clone $url;
      $urls[$langcode] = $language_url->setOption('language', $languages[$langcode]);
    }
    foreach ($this->langcodes as $langcode) {
      $this->drupalGet($urls[$langcode]);
      foreach ($urls as $alternate_langcode => $language_url) {
        // Retrieve desired link elements from the HTML head.
        $links = $this->xpath('head/link[@rel = "alternate" and @href = :href and @hreflang = :hreflang]',
          array(':href' => $language_url->toString(), ':hreflang' => $alternate_langcode));
        $this->assert(isset($links[0]), format_string('The %langcode node translation has the correct alternate hreflang link for %alternate_langcode: %link.', array('%langcode' => $langcode, '%alternate_langcode' => $alternate_langcode, '%link' => $url->toString())));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function getFormSubmitSuffix(EntityInterface $entity, $langcode) {
    if (!$entity->isNew() && $entity->isTranslatable()) {
      $translations = $entity->getTranslationLanguages();
      if ((count($translations) > 1 || !isset($translations[$langcode])) && ($field = $entity->getFieldDefinition('status'))) {
        return ' ' . ($field->isTranslatable() ? t('(this translation)') : t('(all translations)'));
      }
    }
    return '';
  }

  /**
   * Tests uninstalling content_translation.
   */
  protected function doUninstallTest() {
    // Delete all the nodes so there is no data.
    $nodes = Node::loadMultiple();
    foreach ($nodes as $node) {
      $node->delete();
    }
    $language_count = count(\Drupal::configFactory()->listAll('language.content_settings.'));
    \Drupal::service('module_installer')->uninstall(['content_translation']);
    $this->rebuildContainer();
    $this->assertEqual($language_count, count(\Drupal::configFactory()->listAll('language.content_settings.')), 'Languages have been fixed rather than deleted during content_translation uninstall.');
  }

  /**
   * {@inheritdoc}
   */
  protected function doTestTranslationEdit() {
    $storage = $this->container->get('entity_type.manager')
      ->getStorage($this->entityTypeId);
    $storage->resetCache([$this->entityId]);
    $entity = $storage->load($this->entityId);
    $languages = $this->container->get('language_manager')->getLanguages();
    $type_name = node_get_type_label($entity);

    foreach ($this->langcodes as $langcode) {
      // We only want to test the title for non-english translations.
      if ($langcode != 'en') {
        $options = array('language' => $languages[$langcode]);
        $url = $entity->urlInfo('edit-form', $options);
        $this->drupalGet($url);

        $title = t('<em>Edit @type</em> @title [%language translation]', array(
          '@type' => $type_name,
          '@title' => $entity->getTranslation($langcode)->label(),
          '%language' => $languages[$langcode]->getName(),
        ));
        $this->assertRaw($title);
      }
    }
  }

  /**
   * Tests that revision translations are rendered properly.
   */
  public function testRevisionTranslationRendering() {
    $storage = \Drupal::entityTypeManager()->getStorage('node');

    // Create a node.
    $nid = $this->createEntity(['title' => 'First rev en title'], 'en');
    $node = $storage->load($nid);
    $original_revision_id = $node->getRevisionId();

    // Add a French translation.
    $translation = $node->addTranslation('fr');
    $translation->title = 'First rev fr title';
    $translation->setNewRevision(FALSE);
    $translation->save();

    // Create a new revision.
    $node->title = 'Second rev en title';
    $node->setNewRevision(TRUE);
    $node->save();

    // Get an English view of this revision.
    $original_revision = $storage->loadRevision($original_revision_id);
    $original_revision_url = $original_revision->toUrl('revision')->toString();

    // Should be different from regular node URL.
    $this->assertNotIdentical($original_revision_url, $original_revision->toUrl()->toString());
    $this->drupalGet($original_revision_url);
    $this->assertResponse(200);

    // Contents should be in English, of correct revision.
    $this->assertText('First rev en title');
    $this->assertNoText('First rev fr title');

    // Get a French view.
    $url_fr = $original_revision->getTranslation('fr')->toUrl('revision')->toString();

    // Should have different URL from English.
    $this->assertNotIdentical($url_fr, $original_revision->toUrl()->toString());
    $this->assertNotIdentical($url_fr, $original_revision_url);
    $this->drupalGet($url_fr);
    $this->assertResponse(200);

    // Contents should be in French, of correct revision.
    $this->assertText('First rev fr title');
    $this->assertNoText('First rev en title');
  }

}
