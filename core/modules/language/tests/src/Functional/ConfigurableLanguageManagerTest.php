<?php

namespace Drupal\Tests\language\Functional;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\language\Entity\ContentLanguageSettings;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests Language Negotiation.
 *
 * Uses different negotiators for content and interface.
 *
 * @group language
 */
class ConfigurableLanguageManagerTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'language',
    'content_translation',
    'node',
    'locale',
    'block',
    'system',
    'user',
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

    /** @var \Drupal\user\UserInterface $user */
    $user = $this->createUser([], '', TRUE);
    $this->drupalLogin($user);
    ConfigurableLanguage::createFromLangcode('es')->save();

    // Create a page node type and make it translatable.
    NodeType::create([
      'type' => 'page',
      'name' => t('Page'),
    ])->save();

    $config = ContentLanguageSettings::loadByEntityTypeBundle('node', 'page');
    $config->setDefaultLangcode('en')
      ->setLanguageAlterable(TRUE)
      ->save();

    // Create a Node with title 'English' and translate it to Spanish.
    $node = Node::create([
      'type' => 'page',
      'title' => 'English',
    ]);
    $node->save();
    $node->addTranslation('es', ['title' => 'Español']);
    $node->save();

    // Enable both language_interface and language_content language negotiation.
    \Drupal::getContainer()->get('language_negotiator')->updateConfiguration([
      'language_interface',
      'language_content',
    ]);

    // Set the preferred language of the user for admin pages to English.
    $user->set('preferred_admin_langcode', 'en')->save();

    // Make sure node edit pages are administration pages.
    $this->config('node.settings')->set('use_admin_theme', '1')->save();
    $this->container->get('router.builder')->rebuild();

    // Place a Block with a translatable string on the page.
    $this->placeBlock('system_powered_by_block', ['region' => 'content']);

    // Load the Spanish Node page once, to register the translatable string.
    $this->drupalGet('/es/node/1');

    // Translate the Powered by string.
    /** @var \Drupal\locale\StringStorageInterface $string_storage */
    $string_storage = \Drupal::getContainer()->get('locale.storage');
    $source = $string_storage->findString(['source' => 'Powered by <a href=":poweredby">Drupal</a>']);
    $string_storage->createTranslation([
      'lid' => $source->lid,
      'language' => 'es',
      'translation' => 'Funciona con ...',
    ])->save();
    // Invalidate caches so that the new translation will be used.
    Cache::invalidateTags(['rendered', 'locale']);
  }

  /**
   * Test translation with URL and Preferred Admin Language negotiators.
   *
   * The interface language uses the preferred language for admin pages of the
   * user and after that the URL. The Content uses just the URL.
   */
  public function testUrlContentTranslationWithPreferredAdminLanguage() {
    $assert_session = $this->assertSession();
    // Set the interface language to use the preferred administration language
    // and then the URL.
    /** @var \Drupal\language\LanguageNegotiatorInterface $language_negotiator */
    $language_negotiator = \Drupal::getContainer()->get('language_negotiator');
    $language_negotiator->saveConfiguration('language_interface', [
      'language-user-admin' => 1,
      'language-url' => 2,
      'language-selected' => 3,
    ]);
    // Set Content Language Negotiator to use just the URL.
    $language_negotiator->saveConfiguration('language_content', [
      'language-url' => 4,
      'language-selected' => 5,
    ]);

    // See if the full view of the node in english is present and the
    // string in the Powered By Block is in English.
    $this->drupalGet('/node/1');
    $assert_session->pageTextContains('English');
    $assert_session->pageTextContains('Powered by');

    // Load the spanish node page again and see if both the node and the string
    // are translated.
    $this->drupalGet('/es/node/1');
    $assert_session->pageTextContains('Español');
    $assert_session->pageTextContains('Funciona con');
    $assert_session->pageTextNotContains('Powered by');

    // Check if the Powered by string is shown in English on an
    // administration page, and the node content is shown in Spanish.
    $this->drupalGet('/es/node/1/edit');
    $assert_session->pageTextContains('Español');
    $assert_session->pageTextContains('Powered by');
    $assert_session->pageTextNotContains('Funciona con');
  }

  /**
   * Test translation with URL and Session Language Negotiators.
   */
  public function testUrlContentTranslationWithSessionLanguage() {
    $assert_session = $this->assertSession();
    /** @var \Drupal\language\LanguageNegotiatorInterface $language_negotiator */
    $language_negotiator = \Drupal::getContainer()->get('language_negotiator');
    // Set Interface Language Negotiator to Session.
    $language_negotiator->saveConfiguration('language_interface', [
      'language-session' => 1,
      'language-url' => 2,
      'language-selected' => 3,
    ]);

    // Set Content Language Negotiator to URL.
    $language_negotiator->saveConfiguration('language_content', [
      'language-url' => 4,
      'language-selected' => 5,
    ]);

    // See if the full view of the node in english is present and the
    // string in the Powered By Block is in English.
    $this->drupalGet('/node/1');
    $assert_session->pageTextContains('English');
    $assert_session->pageTextContains('Powered by');

    // The language session variable has not been set yet, so
    // The string should be in Spanish.
    $this->drupalGet('/es/node/1');
    $assert_session->pageTextContains('Español');
    $assert_session->pageTextNotContains('Powered by');
    $assert_session->pageTextContains('Funciona con');

    // Set the session language to Spanish but load the English node page.
    $this->drupalGet('/node/1', ['query' => ['language' => 'es']]);
    $assert_session->pageTextContains('English');
    $assert_session->pageTextNotContains('Español');
    $assert_session->pageTextContains('Funciona con');
    $assert_session->pageTextNotContains('Powered by');

    // Set the session language to English but load the node page in Spanish.
    $this->drupalGet('/es/node/1', ['query' => ['language' => 'en']]);
    $assert_session->pageTextNotContains('English');
    $assert_session->pageTextContains('Español');
    $assert_session->pageTextNotContains('Funciona con');
    $assert_session->pageTextContains('Powered by');
  }

  /**
   * Tests translation of the user profile edit form.
   *
   * The user profile edit form is a special case when used with the preferred
   * admin language negotiator because of the recursive way that the negotiator
   * is called.
   */
  public function testUserProfileTranslationWithPreferredAdminLanguage() {
    $assert_session = $this->assertSession();
    // Set the interface language to use the preferred administration language.
    /** @var \Drupal\language\LanguageNegotiatorInterface $language_negotiator */
    $language_negotiator = \Drupal::getContainer()->get('language_negotiator');
    $language_negotiator->saveConfiguration('language_interface', [
      'language-user-admin' => 1,
      'language-selected' => 2,
    ]);

    // Create a field on the user entity.
    $field_name = mb_strtolower($this->randomMachineName());
    $label = mb_strtolower($this->randomMachineName());
    $field_label_en = "English $label";
    $field_label_es = "Español $label";

    $field_storage = FieldStorageConfig::create([
      'field_name' => $field_name,
      'entity_type' => 'user',
      'type' => 'string',
    ]);
    $field_storage->save();

    $instance = FieldConfig::create([
      'field_storage' => $field_storage,
      'bundle' => 'user',
      'label' => $field_label_en,
    ]);
    $instance->save();

    // Add a Spanish translation.
    \Drupal::languageManager()
      ->getLanguageConfigOverride('es', "field.field.user.user.$field_name")
      ->set('label', $field_label_es)
      ->save();

    // Add the new field to the edit form.
    EntityFormDisplay::create([
      'targetEntityType' => 'user',
      'bundle' => 'user',
      'mode' => 'default',
      'status' => TRUE,
    ])
      ->setComponent($field_name, [
        'type' => 'string_textfield',
      ])
      ->save();

    $user_id = \Drupal::currentUser()->id();
    $this->drupalGet("/user/$user_id/edit");
    // Admin language choice is "No preference" so we should get the default.
    $assert_session->pageTextContains($field_label_en);
    $assert_session->pageTextNotContains($field_label_es);

    // Set admin language to Spanish.
    $this->drupalPostForm(NULL, ['edit-preferred-admin-langcode' => 'es'], 'edit-submit');
    $assert_session->pageTextContains($field_label_es);
    $assert_session->pageTextNotContains($field_label_en);

    // Set admin language to English.
    $this->drupalPostForm(NULL, ['edit-preferred-admin-langcode' => 'en'], 'edit-submit');
    $assert_session->pageTextContains($field_label_en);
    $assert_session->pageTextNotContains($field_label_es);
  }

}
