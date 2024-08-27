<?php

declare(strict_types=1);

namespace Drupal\FunctionalTests\Routing;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\link\LinkItemInterface;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\content_translation\Traits\ContentTranslationTestTrait;

/**
 * Tests that route lookup is cached by the current language.
 *
 * @group routing
 */
class RouteCachingLanguageTest extends BrowserTestBase {

  use ContentTranslationTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'path',
    'node',
    'content_translation',
    'link',
    'block',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * A user with permissions to administer content types.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $webUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->createContentType(['type' => 'page']);

    $this->drupalPlaceBlock('local_tasks_block');
    $this->drupalPlaceBlock('page_title_block');

    $permissions = [
      'access administration pages',
      'administer content translation',
      'administer content types',
      'administer languages',
      'administer url aliases',
      'create content translations',
      'create page content',
      'create url aliases',
      'edit any page content',
      'translate any entity',
    ];
    // Create and log in user.
    $this->webUser = $this->drupalCreateUser($permissions);
    $this->drupalLogin($this->webUser);

    // Enable French language.
    static::createLanguageFromLangcode('fr');

    // Enable translation for page node.
    static::enableContentTranslation('node', 'page');

    // Create a field with settings to validate.
    $field_storage = FieldStorageConfig::create([
      'field_name' => 'field_link',
      'entity_type' => 'node',
      'type' => 'link',
    ]);
    $field_storage->save();
    $field = FieldConfig::create([
      'field_storage' => $field_storage,
      'bundle' => 'page',
      'settings' => [
        'title' => DRUPAL_OPTIONAL,
        'link_type' => LinkItemInterface::LINK_GENERIC,
      ],
    ]);
    $field->save();

    \Drupal::service('entity_display.repository')->getFormDisplay('node', 'page', 'default')
      ->setComponent('field_link', [
        'type' => 'link_default',
      ])
      ->save();
    \Drupal::service('entity_display.repository')->getViewDisplay('node', 'page', 'full')
      ->setComponent('field_link', [
        'type' => 'link',
      ])
      ->save();

    // Enable URL language detection and selection and set a prefix for both
    // languages.
    \Drupal::configFactory()->getEditable('language.types')
      ->set('negotiation.language_interface.enabled.language_url', 1)
      ->save();
    \Drupal::configFactory()->getEditable('language.negotiation')
      ->set('url.prefixes.en', 'en')
      ->save();

    // Reset the cache after changing the negotiation settings as that changes
    // how links are built.
    $this->resetAll();

    $definitions = \Drupal::service('entity_field.manager')->getFieldDefinitions('node', 'page');
    $this->assertTrue($definitions['path']->isTranslatable(), 'Node path is translatable.');
    $this->assertTrue($definitions['body']->isTranslatable(), 'Node body is translatable.');
  }

  /**
   * Creates content with a link field pointing to an alias of another language.
   *
   * @dataProvider providerLanguage
   */
  public function testLinkTranslationWithAlias($source_langcode): void {
    $source_url_options = [
      'language' => ConfigurableLanguage::load($source_langcode),
    ];

    // Create a target node in the source language that is the link target.
    $edit = [
      'langcode[0][value]' => $source_langcode,
      'title[0][value]' => 'Target page',
      'path[0][alias]' => '/target-page',
    ];
    $this->drupalGet('node/add/page', $source_url_options);
    $this->submitForm($edit, 'Save');

    // Confirm that the alias works.
    $assert_session = $this->assertSession();
    $assert_session->addressEquals($source_langcode . '/target-page');
    $assert_session->statusCodeEquals(200);
    $assert_session->pageTextContains('Target page');

    // Create a second node that links to the first through the link field.
    $edit = [
      'langcode[0][value]' => $source_langcode,
      'title[0][value]' => 'Link page',
      'field_link[0][uri]' => '/target-page',
      'field_link[0][title]' => 'Target page',
      'path[0][alias]' => '/link-page',
    ];
    $this->drupalGet('node/add/page', $source_url_options);
    $this->submitForm($edit, 'Save');

    // Make sure the link node is displayed with a working link.
    $assert_session->pageTextContains('Link page');
    $this->clickLink('Target page');
    $assert_session->addressEquals($source_langcode . '/target-page');
    $assert_session->statusCodeEquals(200);
    $assert_session->pageTextContains('Target page');

    // Clear all caches, then add a translation for the link node.
    $this->resetAll();

    $this->drupalGet('link-page', $source_url_options);
    $this->clickLink('Translate');
    $this->clickLink('Add');

    // Do not change the link field.
    $edit = [
      'title[0][value]' => 'Translated link page',
      'path[0][alias]' => '/translated-link-page',
    ];
    $this->submitForm($edit, 'Save (this translation)');

    $assert_session->pageTextContains('Translated link page');

    // @todo Clicking on the link does not include the language prefix.
    $this->drupalGet('target-page', $source_url_options);
    $assert_session->statusCodeEquals(200);
    $assert_session->pageTextContains('Target page');
  }

  /**
   * Data provider for testFromUri().
   */
  public static function providerLanguage() {
    return [
      ['en'],
      ['fr'],
    ];
  }

}
