<?php

declare(strict_types=1);

namespace Drupal\Tests\node\Functional;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Url;
use Drupal\Tests\content_translation\Functional\ContentTranslationUITestBase;
use Drupal\Tests\language\Traits\LanguageTestTrait;
use Drupal\comment\Tests\CommentTestTrait;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\node\Entity\Node;
use Drupal\user\Entity\Role;
use Drupal\user\RoleInterface;

/**
 * Tests the Node Translation UI.
 *
 * @group node
 */
class NodeTranslationUITest extends ContentTranslationUITestBase {

  use LanguageTestTrait;
  use CommentTestTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected $defaultCacheContexts = [
    'theme',
    'timezone',
    'url.query_args:_wrapper_format',
    'url.site',
    'user.permissions',
  ];

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'block',
    'language',
    'content_translation',
    'node',
    'field_ui',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    $this->entityTypeId = 'node';
    $this->bundle = 'article';
    parent::setUp();

    // Create the bundle.
    $this->drupalCreateContentType(['type' => 'article', 'title' => 'Article']);
    $this->doSetup();

    // Ensure the help message is shown even with prefixed paths.
    $this->drupalPlaceBlock('help_block', ['region' => 'content']);

    // Display the language selector.
    static::enableBundleTranslation('node', 'article');
    $this->drupalLogin($this->translator);
  }

  /**
   * Tests the basic translation UI.
   */
  public function testTranslationUI(): void {
    parent::testTranslationUI();
    $this->doUninstallTest();
  }

  /**
   * Tests changing the published status on a node without fields.
   */
  public function testPublishedStatusNoFields(): void {
    // Test changing the published status of an article without fields.
    $this->drupalLogin($this->administrator);
    // Delete all fields.
    $this->drupalGet('admin/structure/types/manage/article/fields');
    $this->drupalGet('admin/structure/types/manage/article/fields/node.article.' . $this->fieldName . '/delete');
    $this->submitForm([], 'Delete');

    // Add a node.
    $default_langcode = $this->langcodes[0];
    $values[$default_langcode] = ['title' => [['value' => $this->randomMachineName()]]];
    $this->entityId = $this->createEntity($values[$default_langcode], $default_langcode);
    $storage = $this->container->get('entity_type.manager')
      ->getStorage($this->entityTypeId);
    $entity = $storage->load($this->entityId);

    // Add a content translation.
    $langcode = 'fr';
    $language = ConfigurableLanguage::load($langcode);
    $values[$langcode] = ['title' => [['value' => $this->randomMachineName()]]];

    $entity_type_id = $entity->getEntityTypeId();
    $add_url = Url::fromRoute("entity.$entity_type_id.content_translation_add", [
      $entity->getEntityTypeId() => $entity->id(),
      'source' => $default_langcode,
      'target' => $langcode,
    ], ['language' => $language]);
    $edit = $this->getEditValues($values, $langcode);
    $edit['status[value]'] = FALSE;
    $this->drupalGet($add_url);
    $this->submitForm($edit, 'Save (this translation)');

    $entity = $storage->load($this->entityId);
    $translation = $entity->getTranslation($langcode);
    // Make sure we unpublished the node correctly.
    $this->assertFalse($this->manager->getTranslationMetadata($translation)->isPublished(), 'The translation has been correctly unpublished.');
  }

  /**
   * {@inheritdoc}
   */
  protected function getTranslatorPermissions(): array {
    return array_merge(parent::getTranslatorPermissions(), ['administer nodes', "edit any $this->bundle content"]);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditorPermissions(): array {
    return ['administer nodes', 'create article content'];
  }

  /**
   * {@inheritdoc}
   */
  protected function getAdministratorPermissions(): array {
    return array_merge(parent::getAdministratorPermissions(), ['access administration pages', 'administer content types', 'administer node fields', 'access content overview', 'bypass node access', 'administer languages', 'administer themes', 'view the administration theme']);
  }

  /**
   * {@inheritdoc}
   */
  protected function getNewEntityValues($langcode) {
    return ['title' => [['value' => $this->randomMachineName()]]] + parent::getNewEntityValues($langcode);
  }

  /**
   * {@inheritdoc}
   */
  protected function doTestPublishedStatus(): void {
    $storage = $this->container->get('entity_type.manager')
      ->getStorage($this->entityTypeId);
    $entity = $storage->load($this->entityId);
    $languages = $this->container->get('language_manager')->getLanguages();

    $statuses = [
      TRUE,
      FALSE,
    ];

    foreach ($statuses as $index => $value) {
      // (Un)publish the node translations and check that the translation
      // statuses are (un)published accordingly.
      foreach ($this->langcodes as $langcode) {
        $options = ['language' => $languages[$langcode]];
        $url = $entity->toUrl('edit-form', $options);
        $this->drupalGet($url, $options);
        $this->submitForm([
          'status[value]' => $value,
        ], 'Save' . $this->getFormSubmitSuffix($entity, $langcode));
      }
      $entity = $storage->load($this->entityId);
      foreach ($this->langcodes as $langcode) {
        // The node is created as unpublished thus we switch to the published
        // status first.
        $status = !$index;
        $translation = $entity->getTranslation($langcode);
        $this->assertEquals($status, $this->manager->getTranslationMetadata($translation)->isPublished(), 'The translation has been correctly unpublished.');
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function doTestAuthoringInfo(): void {
    $storage = $this->container->get('entity_type.manager')
      ->getStorage($this->entityTypeId);
    $entity = $storage->load($this->entityId);
    $languages = $this->container->get('language_manager')->getLanguages();
    $values = [];

    // Post different base field information for each translation.
    foreach ($this->langcodes as $langcode) {
      $user = $this->drupalCreateUser();
      $values[$langcode] = [
        'uid' => $user->id(),
        'created' => \Drupal::time()->getRequestTime() - mt_rand(0, 1000),
        'sticky' => (bool) mt_rand(0, 1),
        'promote' => (bool) mt_rand(0, 1),
      ];
      /** @var \Drupal\Core\Datetime\DateFormatterInterface $date_formatter */
      $date_formatter = $this->container->get('date.formatter');
      $edit = [
        'uid[0][target_id]' => $user->getAccountName(),
        'created[0][value][date]' => $date_formatter->format($values[$langcode]['created'], 'custom', 'Y-m-d'),
        'created[0][value][time]' => $date_formatter->format($values[$langcode]['created'], 'custom', 'H:i:s'),
        'sticky[value]' => $values[$langcode]['sticky'],
        'promote[value]' => $values[$langcode]['promote'],
      ];
      $options = ['language' => $languages[$langcode]];
      $url = $entity->toUrl('edit-form', $options);
      $this->drupalGet($url, $options);
      $this->submitForm($edit, $this->getFormSubmitAction($entity, $langcode));
    }

    $entity = $storage->load($this->entityId);
    foreach ($this->langcodes as $langcode) {
      $translation = $entity->getTranslation($langcode);
      $metadata = $this->manager->getTranslationMetadata($translation);
      $this->assertEquals($values[$langcode]['uid'], $metadata->getAuthor()->id(), 'Translation author correctly stored.');
      $this->assertEquals($values[$langcode]['created'], $metadata->getCreatedTime(), 'Translation date correctly stored.');
      $this->assertEquals($values[$langcode]['sticky'], $translation->isSticky(), 'Sticky of Translation correctly stored.');
      $this->assertEquals($values[$langcode]['promote'], $translation->isPromoted(), 'Promoted of Translation correctly stored.');
    }
  }

  /**
   * Tests that translation page inherits admin status of edit page.
   */
  public function testTranslationLinkTheme(): void {
    $this->drupalLogin($this->administrator);
    $article = $this->drupalCreateNode(['type' => 'article', 'langcode' => $this->langcodes[0]]);

    // Set up the default admin theme and use it for node editing.
    $this->container->get('theme_installer')->install(['claro']);
    $this->config('system.theme')->set('admin', 'claro')->save();

    // Verify that translation uses the admin theme if edit is admin.
    $this->drupalGet('node/' . $article->id() . '/translations');
    $this->assertSession()->responseContains('core/themes/claro/css/base/elements.css');

    // Turn off admin theme for editing, assert inheritance to translations.
    $this->config('node.settings')->set('use_admin_theme', FALSE)->save();
    // Changing node.settings:use_admin_theme requires a route rebuild.
    $this->container->get('router.builder')->rebuild();

    // Verify that translation uses the frontend theme if edit is frontend.
    $this->drupalGet('node/' . $article->id() . '/translations');
    $this->assertSession()->responseNotContains('core/themes/claro/css/base/elements.css');

    // Assert presence of translation page itself (vs. DisabledBundle below).
    $this->assertSession()->statusCodeEquals(200);
  }

  /**
   * Tests that no metadata is stored for a disabled bundle.
   */
  public function testDisabledBundle(): void {
    // Create a bundle that does not have translation enabled.
    $disabledBundle = $this->randomMachineName();
    $this->drupalCreateContentType(['type' => $disabledBundle, 'name' => $disabledBundle]);

    // Create a node for each bundle.
    $node = $this->drupalCreateNode([
      'type' => $this->bundle,
      'langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED,
    ]);

    // Make sure that nothing was inserted into the {content_translation} table.
    $nids = \Drupal::entityQueryAggregate('node')
      ->aggregate('nid', 'COUNT')
      ->accessCheck(FALSE)
      ->condition('type', $this->bundle)
      ->conditionAggregate('nid', 'COUNT', 2, '>=')
      ->groupBy('nid')
      ->execute();
    $this->assertCount(0, $nids);

    // Ensure the translation tab is not accessible.
    $this->drupalGet('node/' . $node->id() . '/translations');
    $this->assertSession()->statusCodeEquals(403);
  }

  /**
   * Tests that translations are rendered properly.
   */
  public function testTranslationRendering(): void {
    // Add a comment field to the article content type.
    \Drupal::service('module_installer')->install(['comment']);
    $this->addDefaultCommentField('node', 'article');

    // Add 'post comments' permission to the authenticated role.
    $role = Role::load(RoleInterface::AUTHENTICATED_ID);
    $role->grantPermission('post comments')->save();

    $default_langcode = $this->langcodes[0];
    $values[$default_langcode] = $this->getNewEntityValues($default_langcode);
    $this->entityId = $this->createEntity($values[$default_langcode], $default_langcode);
    $node = \Drupal::entityTypeManager()->getStorage($this->entityTypeId)->load($this->entityId);
    $node->setPromoted(TRUE);

    // Create translations.
    foreach (array_diff($this->langcodes, [$default_langcode]) as $langcode) {
      $values[$langcode] = $this->getNewEntityValues($langcode);
      $translation = $node->addTranslation($langcode, $values[$langcode]);
      // Publish and promote the translation to frontpage.
      $translation->setPromoted(TRUE);
      $translation->setPublished();
    }
    $node->save();

    // Test that the frontpage view displays the correct translations.
    \Drupal::service('module_installer')->install(['views'], TRUE);
    $this->rebuildContainer();
    $this->doTestTranslations('node', $values);

    // Enable the translation language renderer.
    $view = \Drupal::entityTypeManager()->getStorage('view')->load('frontpage');
    $display = &$view->getDisplay('default');
    $display['display_options']['rendering_language'] = '***LANGUAGE_entity_translation***';
    $view->save();

    // Need to check from the beginning, including the base_path, in the URL
    // since the pattern for the default language might be a substring of
    // the strings for other languages.
    $base_path = base_path();

    // Check the frontpage for 'Read more' links to each translation.
    // See also assertTaxonomyPage() in NodeAccessBaseTableTest.
    $node_href = 'node/' . $node->id();
    foreach ($this->langcodes as $langcode) {
      $this->drupalGet('node', ['language' => \Drupal::languageManager()->getLanguage($langcode)]);
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
        if (preg_match($pattern, $link->getAttribute('href'), $matches) == TRUE) {
          $num_match_found++;
        }
      }
      $this->assertSame(1, $num_match_found, 'There is 1 Read more link, ' . $expected_href . ', for the ' . $langcode . ' translation of a node on the frontpage. (Found ' . $num_match_found . '.)');
    }

    // Check the frontpage for 'Add new comment' links that include the
    // language.
    $comment_form_href = 'node/' . $node->id() . '#comment-form';
    foreach ($this->langcodes as $langcode) {
      $this->drupalGet('node', ['language' => \Drupal::languageManager()->getLanguage($langcode)]);
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
        if (preg_match($pattern, $link->getAttribute('href'), $matches) == TRUE) {
          $num_match_found++;
        }
      }
      $this->assertSame(1, $num_match_found, 'There is 1 Add new comment link, ' . $expected_href . ', for the ' . $langcode . ' translation of a node on the frontpage. (Found ' . $num_match_found . '.)');
    }

    // Test that the node page displays the correct translations.
    $this->doTestTranslations('node/' . $node->id(), $values);

    // Test that the node page has the correct alternate hreflang links.
    $this->doTestAlternateHreflangLinks($node);
  }

  /**
   * Tests that the given path displays the correct translation values.
   *
   * @param string $path
   *   The path to be tested.
   * @param array $values
   *   The translation values to be found.
   */
  protected function doTestTranslations($path, array $values): void {
    $languages = $this->container->get('language_manager')->getLanguages();
    foreach ($this->langcodes as $langcode) {
      $this->drupalGet($path, ['language' => $languages[$langcode]]);
      $this->assertSession()->pageTextContains($values[$langcode]['title'][0]['value']);
    }
  }

  /**
   * Tests that the given path provides the correct alternate hreflang links.
   *
   * @param \Drupal\node\Entity\Node $node
   *   The node to be tested.
   */
  protected function doTestAlternateHreflangLinks(Node $node): void {
    $url = $node->toUrl();
    $languages = $this->container->get('language_manager')->getLanguages();
    $url->setAbsolute();
    $urls = [];
    $translations = [];
    foreach ($this->langcodes as $langcode) {
      $language_url = clone $url;
      $urls[$langcode] = $language_url->setOption('language', $languages[$langcode]);
      $translations[$langcode] = $node->getTranslation($langcode);
    }
    foreach ($this->langcodes as $langcode) {
      // Skip unpublished translations.
      if ($translations[$langcode]->isPublished()) {
        $this->drupalGet($urls[$langcode]);
        foreach ($urls as $alternate_langcode => $language_url) {
          // Retrieve desired link elements from the HTML head.
          $xpath = $this->assertSession()->buildXPathQuery('head/link[@rel = "alternate" and @href = :href and @hreflang = :hreflang]', [
            ':href' => $language_url->toString(),
            ':hreflang' => $alternate_langcode,
          ]);
          if ($translations[$alternate_langcode]->isPublished()) {
            // Verify that the node translation has the correct alternate
            // hreflang link for the alternate langcode.
            $this->assertSession()->elementExists('xpath', $xpath);
          }
          else {
            // Verify that the node translation does not have an alternate
            // hreflang link for the alternate langcode.
            $this->assertSession()->elementNotExists('xpath', $xpath);
          }
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function getFormSubmitSuffix(EntityInterface $entity, $langcode): string {
    if (!$entity->isNew() && $entity->isTranslatable()) {
      $translations = $entity->getTranslationLanguages();
      if ((count($translations) > 1 || !isset($translations[$langcode])) && ($field = $entity->getFieldDefinition('status'))) {
        return ' ' . ($field->isTranslatable() ? '(this translation)' : '(all translations)');
      }
    }
    return '';
  }

  /**
   * Tests uninstalling content_translation.
   */
  protected function doUninstallTest(): void {
    // Delete all the nodes so there is no data.
    $nodes = Node::loadMultiple();
    foreach ($nodes as $node) {
      $node->delete();
    }
    $language_count = count(\Drupal::configFactory()->listAll('language.content_settings.'));
    \Drupal::service('module_installer')->uninstall(['content_translation']);
    $this->rebuildContainer();
    $this->assertCount($language_count, \Drupal::configFactory()->listAll('language.content_settings.'), 'Languages have been fixed rather than deleted during content_translation uninstall.');
  }

  /**
   * {@inheritdoc}
   */
  protected function doTestTranslationEdit(): void {
    $storage = $this->container->get('entity_type.manager')
      ->getStorage($this->entityTypeId);
    $entity = $storage->load($this->entityId);
    $languages = $this->container->get('language_manager')->getLanguages();
    $type_name = node_get_type_label($entity);

    foreach ($this->langcodes as $langcode) {
      // We only want to test the title for non-english translations.
      if ($langcode != 'en') {
        $options = ['language' => $languages[$langcode]];
        $url = $entity->toUrl('edit-form', $options);
        $this->drupalGet($url);
        $this->assertSession()->pageTextContains("Edit {$type_name} {$entity->getTranslation($langcode)->label()} [{$languages[$langcode]->getName()} translation]");
      }
    }
  }

  /**
   * Tests that revision translations are rendered properly.
   */
  public function testRevisionTranslationRendering(): void {
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
    $this->assertNotSame($original_revision_url, $original_revision->toUrl()->toString());
    $this->drupalGet($original_revision_url);
    $this->assertSession()->statusCodeEquals(200);

    // Contents should be in English, of correct revision.
    $this->assertSession()->pageTextContains('First rev en title');
    $this->assertSession()->pageTextNotContains('First rev fr title');

    // Get a French view.
    $url_fr = $original_revision->getTranslation('fr')->toUrl('revision')->toString();

    // Should have different URL from English.
    $this->assertNotSame($url_fr, $original_revision->toUrl()->toString());
    $this->assertNotSame($url_fr, $original_revision_url);
    $this->drupalGet($url_fr);
    $this->assertSession()->statusCodeEquals(200);

    // Contents should be in French, of correct revision.
    $this->assertSession()->pageTextContains('First rev fr title');
    $this->assertSession()->pageTextNotContains('First rev en title');
  }

  /**
   * Tests title is not escaped (but XSS-filtered) for details form element.
   */
  public function testDetailsTitleIsNotEscaped(): void {
    // Create an image field.
    \Drupal::service('module_installer')->install(['image']);
    FieldStorageConfig::create([
      'entity_type' => 'node',
      'field_name' => 'field_image',
      'type' => 'image',
    ])->save();
    FieldConfig::create([
      'entity_type' => 'node',
      'field_name' => 'field_image',
      'bundle' => 'article',
      'translatable' => TRUE,
    ])->save();

    // Make the image field a multi-value field in order to display a
    // details form element.
    $fieldStorage = FieldStorageConfig::loadByName('node', 'field_image');
    $fieldStorage->setCardinality(2)->save();

    // Enable the display of the image field.
    EntityFormDisplay::load('node.article.default')
      ->setComponent('field_image', ['region' => 'content'])->save();

    // Make the image field non-translatable.
    static::setFieldTranslatable('node', 'article', 'field_image', FALSE);

    // Create a node.
    $nid = $this->createEntity(['title' => 'Node with multi-value image field en title'], 'en');

    // Add a French translation and assert the title markup is not escaped.
    $this->drupalGet("node/$nid/translations/add/en/fr");
    $markup = 'Image <span class="translation-entity-all-languages">(all languages)</span>';
    $this->assertSession()->assertNoEscaped($markup);
    $this->assertSession()->responseContains($markup);
  }

  /**
   * Test that when content is language neutral, it uses interface language.
   *
   * When language neutral content is displayed on interface language, it should
   * consider the interface language for creating the content link.
   */
  public function testUrlPrefixOnLanguageNeutralContent(): void {
    $this->drupalLogin($this->administrator);
    $neutral_langcodes = [
      LanguageInterface::LANGCODE_NOT_SPECIFIED,
    ];
    foreach ($neutral_langcodes as $langcode) {
      $article = $this->drupalCreateNode(['type' => 'article', 'langcode' => $langcode]);
      $this->drupalGet("{$this->langcodes[1]}/admin/content");
      $this->assertSession()->linkByHrefExists("{$this->langcodes[1]}/node/{$article->id()}");

      $this->drupalGet("{$this->langcodes[2]}/admin/content");
      $this->assertSession()->linkByHrefExists("{$this->langcodes[2]}/node/{$article->id()}");
    }
  }

  /**
   * Test deletion of translated content from search and index rebuild.
   */
  public function testSearchIndexRebuildOnTranslationDeletion(): void {
    \Drupal::service('module_installer')->install(['search']);
    $admin_user = $this->drupalCreateUser([
      'administer site configuration',
      'access administration pages',
      'administer content types',
      'delete content translations',
      'administer content translation',
      'translate any entity',
      'administer search',
      'search content',
      'delete any article content',
    ]);
    $this->drupalLogin($admin_user);

    // Create a node.
    $node = $this->drupalCreateNode([
      'type' => $this->bundle,
    ]);

    // Add a French translation.
    $translation = $node->addTranslation('fr');
    $translation->title = 'First rev fr title';
    $translation->setNewRevision(FALSE);
    $translation->save();

    // Check if 1 page is listed for indexing.
    $this->drupalGet('admin/config/search/pages');
    $this->assertSession()->pageTextContains('There is 1 item left to index.');

    // Run cron.
    $this->drupalGet('admin/config/system/cron');
    $this->getSession()->getPage()->pressButton('Run cron');

    // Assert no items are left for indexing.
    $this->drupalGet('admin/config/search/pages');
    $this->assertSession()->pageTextContains('There are 0 items left to index.');

    // Search for French content.
    $this->drupalGet('search/node', ['query' => ['keys' => urlencode('First rev fr title')]]);
    $this->assertSession()->pageTextContains('First rev fr title');

    // Delete translation.
    $this->drupalGet('fr/node/' . $node->id() . '/delete');
    $this->getSession()->getPage()->pressButton('Delete French translation');

    // Run cron.
    $this->drupalGet('admin/config/system/cron');
    $this->getSession()->getPage()->pressButton('Run cron');

    // Assert no items are left for indexing.
    $this->drupalGet('admin/config/search/pages');
    $this->assertSession()->pageTextContains('There are 0 items left to index.');

    // Search for French content.
    $this->drupalGet('search/node', ['query' => ['keys' => urlencode('First rev fr title')]]);
    $this->assertSession()->pageTextNotContains('First rev fr title');
  }

  /**
   * Tests redirection after saving translation.
   */
  public function testRedirect(): void {
    $this->drupalLogin($this->administrator);

    $article = $this->drupalCreateNode(['type' => 'article', 'langcode' => $this->langcodes[0]]);

    $edit = [
      'title[0][value]' => 'English node title',
    ];
    $this->drupalGet('node/' . $article->id() . '/edit');
    $this->submitForm($edit, 'Save');

    $this->assertSession()->pageTextContains('English node title');
    $this->assertEquals($this->baseUrl . '/node/' . $article->id(), $this->getSession()->getCurrentUrl());

    $this->drupalGet('node/' . $article->id() . '/translations/add/' . $this->langcodes[0] . '/' . $this->langcodes[1]);
    $edit = [
      'title[0][value]' => 'Italian node title',
    ];
    $this->submitForm($edit, 'Save (this translation)');

    $this->assertSession()->pageTextContains('Italian node title');
    $this->assertEquals($this->baseUrl . '/' . $this->langcodes[1] . '/node/' . $article->id(), $this->getSession()->getCurrentUrl());
  }

}
