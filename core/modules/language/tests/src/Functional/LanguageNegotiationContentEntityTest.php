<?php

namespace Drupal\Tests\language\Functional;

use Drupal\Core\Language\LanguageInterface;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\language\Plugin\LanguageNegotiation\LanguageNegotiationContentEntity;
use Drupal\language\Plugin\LanguageNegotiation\LanguageNegotiationUrl;
use Drupal\Tests\BrowserTestBase;
use Drupal\Core\Routing\RouteObjectInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;

/**
 * Tests language negotiation with the language negotiator content entity.
 *
 * @group language
 */
class LanguageNegotiationContentEntityTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'language',
    'language_test',
    'entity_test',
    'system',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * The entity being used for testing.
   *
   * @var \Drupal\Core\Entity\ContentEntityInterface
   */
  protected $entity;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    ConfigurableLanguage::create(['id' => 'es'])->save();
    ConfigurableLanguage::create(['id' => 'fr'])->save();

    // In order to reflect the changes for a multilingual site in the container
    // we have to rebuild it.
    $this->rebuildContainer();

    $this->createTranslatableEntity();

    $user = $this->drupalCreateUser(['view test entity']);
    $this->drupalLogin($user);
  }

  /**
   * Tests default with content language remaining same as interface language.
   */
  public function testDefaultConfiguration() {
    $translation = $this->entity;
    $this->drupalGet($translation->toUrl());
    $last = $this->container->get('state')->get('language_test.language_negotiation_last');
    $last_content_language = $last[LanguageInterface::TYPE_CONTENT];
    $last_interface_language = $last[LanguageInterface::TYPE_INTERFACE];
    $this->assertSame($last_content_language, $last_interface_language);
    $this->assertSame($translation->language()->getId(), $last_content_language);

    $translation = $this->entity->getTranslation('es');
    $this->drupalGet($translation->toUrl());
    $last = $this->container->get('state')->get('language_test.language_negotiation_last');
    $last_content_language = $last[LanguageInterface::TYPE_CONTENT];
    $last_interface_language = $last[LanguageInterface::TYPE_INTERFACE];
    $this->assertSame($last_content_language, $last_interface_language);
    $this->assertSame($translation->language()->getId(), $last_content_language);

    $translation = $this->entity->getTranslation('fr');
    $this->drupalGet($translation->toUrl());
    $last = $this->container->get('state')->get('language_test.language_negotiation_last');
    $last_content_language = $last[LanguageInterface::TYPE_CONTENT];
    $last_interface_language = $last[LanguageInterface::TYPE_INTERFACE];
    $this->assertSame($last_content_language, $last_interface_language);
    $this->assertSame($translation->language()->getId(), $last_content_language);
  }

  /**
   * Tests enabling the language negotiator language_content_entity.
   */
  public function testEnabledLanguageContentNegotiator() {
    // Define the method language-url with a higher priority than
    // language-content-entity. This configuration should match the default one,
    // where the language-content-entity is turned off.
    $config = $this->config('language.types');
    $config->set('configurable', [LanguageInterface::TYPE_INTERFACE, LanguageInterface::TYPE_CONTENT]);
    $config->set('negotiation.language_content.enabled', [
      LanguageNegotiationUrl::METHOD_ID => 0,
      LanguageNegotiationContentEntity::METHOD_ID => 1,
    ]);
    $config->save();

    // In order to reflect the changes for a multilingual site in the container
    // we have to rebuild it.
    $this->rebuildContainer();

    // The tests for the default configuration should still pass.
    $this->testDefaultConfiguration();

    // Define the method language-content-entity with a higher priority than
    // language-url.
    $config->set('negotiation.language_content.enabled', [
      LanguageNegotiationContentEntity::METHOD_ID => 0,
      LanguageNegotiationUrl::METHOD_ID => 1,
    ]);
    $config->save();

    // In order to reflect the changes for a multilingual site in the container
    // we have to rebuild it.
    $this->rebuildContainer();

    // The method language-content-entity should run before language-url and
    // append query parameter for the content language and prevent language-url
    // from overwriting the URL.
    $default_site_langcode = $this->config('system.site')->get('default_langcode');

    // Now switching to an entity route, so that the URL links are generated
    // while being on an entity route.
    $this->setCurrentRequestForRoute('/entity_test/{entity_test}', 'entity.entity_test.canonical');

    $translation = $this->entity;
    $this->drupalGet($translation->toUrl());
    $last = $this->container->get('state')->get('language_test.language_negotiation_last');
    $last_content_language = $last[LanguageInterface::TYPE_CONTENT];
    $last_interface_language = $last[LanguageInterface::TYPE_INTERFACE];
    // Check that interface language and content language are the same as the
    // default translation language of the entity.
    $this->assertSame($default_site_langcode, $last_interface_language);
    $this->assertSame($last_content_language, $last_interface_language);
    $this->assertSame($translation->language()->getId(), $last_content_language);

    $translation = $this->entity->getTranslation('es');
    $this->drupalGet($translation->toUrl());
    $last = $this->container->get('state')->get('language_test.language_negotiation_last');
    $last_content_language = $last[LanguageInterface::TYPE_CONTENT];
    $last_interface_language = $last[LanguageInterface::TYPE_INTERFACE];
    $this->assertSame($last_interface_language, $default_site_langcode, 'Interface language did not change from the default site language.');
    $this->assertSame($last_content_language, $translation->language()->getId(), 'Content language matches the current entity translation language.');

    $translation = $this->entity->getTranslation('fr');
    $this->drupalGet($translation->toUrl());
    $last = $this->container->get('state')->get('language_test.language_negotiation_last');
    $last_content_language = $last[LanguageInterface::TYPE_CONTENT];
    $last_interface_language = $last[LanguageInterface::TYPE_INTERFACE];
    $this->assertSame($last_interface_language, $default_site_langcode, 'Interface language did not change from the default site language.');
    $this->assertSame($last_content_language, $translation->language()->getId(), 'Content language matches the current entity translation language.');
  }

  /**
   * Creates a translated entity.
   */
  protected function createTranslatableEntity() {
    $this->entity = EntityTest::create();
    $this->entity->addTranslation('es', ['name' => 'name spanish']);
    $this->entity->addTranslation('fr', ['name' => 'name french']);
    $this->entity->save();
  }

  /**
   * Sets the current request to a specific path with the corresponding route.
   *
   * @param string $path
   *   The path for which the current request should be created.
   * @param string $route_name
   *   The route name for which the route object for the request should be
   *   created.
   */
  protected function setCurrentRequestForRoute($path, $route_name) {
    $request = Request::create($path);
    $request->attributes->set(RouteObjectInterface::ROUTE_NAME, $route_name);
    $request->attributes->set(RouteObjectInterface::ROUTE_OBJECT, new Route($path));
    $this->container->get('request_stack')->push($request);
  }

}
