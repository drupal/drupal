<?php

declare(strict_types=1);

namespace Drupal\Tests\path\Functional;

use Drupal\Core\Language\LanguageInterface;
use Drupal\language\Plugin\LanguageNegotiation\LanguageNegotiationUrl;
use Drupal\user\Entity\User;
use Drupal\user\Plugin\LanguageNegotiation\LanguageNegotiationUser;
use Drupal\Tests\content_translation\Traits\ContentTranslationTestTrait;

/**
 * Confirm that paths work with translated nodes.
 *
 * @group path
 */
class PathLanguageTest extends PathTestBase {

  use ContentTranslationTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'path',
    'locale',
    'locale_test',
    'content_translation',
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

    // Enable URL language detection and selection.
    $this->container->get('language_negotiator')->saveConfiguration(LanguageInterface::TYPE_URL, [LanguageNegotiationUrl::METHOD_ID => 1]);

    // Enable translation for page node.
    static::enableContentTranslation('node', 'page');
    static::setFieldTranslatable('node', 'page', 'body', TRUE);

    $definitions = \Drupal::service('entity_field.manager')->getFieldDefinitions('node', 'page');
    $this->assertTrue($definitions['path']->isTranslatable(), 'Node path is translatable.');
    $this->assertTrue($definitions['body']->isTranslatable(), 'Node body is translatable.');
  }

  /**
   * Tests alias functionality through the admin interfaces.
   */
  public function testAliasTranslation(): void {
    $node_storage = $this->container->get('entity_type.manager')->getStorage('node');
    $english_node = $this->drupalCreateNode(['type' => 'page', 'langcode' => 'en']);
    $english_alias = $this->randomMachineName();

    // Edit the node to set language and path.
    $edit = [];
    $edit['path[0][alias]'] = '/' . $english_alias;
    $this->drupalGet('node/' . $english_node->id() . '/edit');
    $this->submitForm($edit, 'Save');

    // Confirm that the alias works.
    $this->drupalGet($english_alias);
    $this->assertSession()->pageTextContains($english_node->body->value);

    // Translate the node into French.
    $this->drupalGet('node/' . $english_node->id() . '/translations');
    $this->clickLink('Add');

    $edit = [];
    $edit['title[0][value]'] = $this->randomMachineName();
    $edit['body[0][value]'] = $this->randomMachineName();
    $french_alias = $this->randomMachineName();
    $edit['path[0][alias]'] = '/' . $french_alias;
    $this->submitForm($edit, 'Save (this translation)');

    // Clear the path lookup cache.
    $this->container->get('path_alias.manager')->cacheClear();

    // Languages are cached on many levels, and we need to clear those caches.
    $this->container->get('language_manager')->reset();
    $this->rebuildContainer();
    $languages = $this->container->get('language_manager')->getLanguages();

    // Ensure the node was created.
    $english_node = $node_storage->load($english_node->id());
    $english_node_french_translation = $english_node->getTranslation('fr');
    $this->assertTrue($english_node->hasTranslation('fr'), 'Node found in database.');

    // Confirm that the alias works.
    $this->drupalGet('fr' . $edit['path[0][alias]']);
    $this->assertSession()->pageTextContains($english_node_french_translation->body->value);

    // Confirm that the alias is returned for the URL. Languages are cached on
    // many levels, and we need to clear those caches.
    $this->container->get('language_manager')->reset();
    $languages = $this->container->get('language_manager')->getLanguages();
    $url = $english_node_french_translation->toUrl('canonical', ['language' => $languages['fr']])->toString();

    $this->assertStringContainsString($edit['path[0][alias]'], $url, 'URL contains the path alias.');

    // Confirm that the alias works even when changing language negotiation
    // options. Enable User language detection and selection over URL one.
    $this->container->get('language_negotiator')->saveConfiguration(LanguageInterface::TYPE_INTERFACE, [LanguageNegotiationUser::METHOD_ID => 1]);
    $this->container->get('language_negotiator')->saveConfiguration(LanguageInterface::TYPE_URL, [LanguageNegotiationUrl::METHOD_ID => 1]);

    // Change user language preference.
    $user = User::load($this->webUser->id());
    $user->set('preferred_langcode', 'fr');
    $user->save();

    // Check that the English alias works. In this situation French is the
    // current UI and content language, while URL language is English (since we
    // do not have a path prefix we fall back to the site's default language).
    // We need to ensure that the user language preference is not taken into
    // account while determining the path alias language, because if this
    // happens we have no way to check that the path alias is valid: there is no
    // path alias for French matching the english alias. So the alias manager
    // needs to use the URL language to check whether the alias is valid.
    $this->drupalGet($english_alias);
    $this->assertSession()->pageTextContains($english_node_french_translation->body->value);

    // Check that the French alias works.
    $this->drupalGet("fr/$french_alias");
    $this->assertSession()->pageTextContains($english_node_french_translation->body->value);

    // Disable URL language negotiation.
    $this->container->get('language_negotiator')->saveConfiguration(LanguageInterface::TYPE_URL, [LanguageNegotiationUrl::METHOD_ID => FALSE]);

    // Check that the English alias still works.
    $this->drupalGet($english_alias);
    $this->assertSession()->pageTextContains($english_node_french_translation->body->value);

    // Check that the French alias is not available. We check the unprefixed
    // alias because we disabled URL language negotiation above. In this
    // situation only aliases in the default language and language neutral ones
    // should keep working.
    $this->drupalGet($french_alias);
    $this->assertSession()->statusCodeEquals(404);

    // The alias manager has an internal path lookup cache. Check to see that
    // it has the appropriate contents at this point.
    $this->container->get('path_alias.manager')->cacheClear();
    $french_node_path = $this->container->get('path_alias.manager')->getPathByAlias('/' . $french_alias, 'fr');
    $this->assertEquals('/node/' . $english_node_french_translation->id(), $french_node_path, 'Normal path works.');
    // Second call should return the same path.
    $french_node_path = $this->container->get('path_alias.manager')->getPathByAlias('/' . $french_alias, 'fr');
    $this->assertEquals('/node/' . $english_node_french_translation->id(), $french_node_path, 'Normal path is the same.');

    // Confirm that the alias works.
    $french_node_alias = $this->container->get('path_alias.manager')->getAliasByPath('/node/' . $english_node_french_translation->id(), 'fr');
    $this->assertEquals('/' . $french_alias, $french_node_alias, 'Alias works.');
    // Second call should return the same alias.
    $french_node_alias = $this->container->get('path_alias.manager')->getAliasByPath('/node/' . $english_node_french_translation->id(), 'fr');
    $this->assertEquals('/' . $french_alias, $french_node_alias, 'Alias is the same.');

    // Confirm that the alias is removed if the translation is deleted.
    $english_node->removeTranslation('fr');
    $english_node->save();
    $this->assertPathAliasNotExists('/' . $french_alias, 'fr', NULL, 'Alias for French translation is removed when translation is deleted.');

    // Check that the English alias still works.
    $this->drupalGet($english_alias);
    $this->assertPathAliasExists('/' . $english_alias, 'en', NULL, 'English alias is not deleted when French translation is removed.');
    $this->assertSession()->pageTextContains($english_node->body->value);
  }

}
