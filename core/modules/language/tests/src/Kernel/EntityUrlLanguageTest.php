<?php

namespace Drupal\Tests\language\Kernel;

use Drupal\Core\Language\LanguageInterface;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\language\Plugin\LanguageNegotiation\LanguageNegotiationContentEntity;
use Drupal\language\Plugin\LanguageNegotiation\LanguageNegotiationUrl;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;

/**
 * Tests the language of entity URLs.
 * @group language
 */
class EntityUrlLanguageTest extends LanguageTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['entity_test', 'user'];

  /**
   * The entity being used for testing.
   *
   * @var \Drupal\Core\Entity\ContentEntityInterface
   */
  protected $entity;

  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('entity_test');
    $this->installEntitySchema('configurable_language');
    \Drupal::service('router.builder')->rebuild();

    // In order to reflect the changes for a multilingual site in the container
    // we have to rebuild it.
    ConfigurableLanguage::create(['id' => 'es'])->save();
    ConfigurableLanguage::create(['id' => 'fr'])->save();

    $config = $this->config('language.negotiation');
    $config->set('url.prefixes', ['en' => 'en', 'es' => 'es', 'fr' => 'fr'])
      ->save();

    \Drupal::service('kernel')->rebuildContainer();

    $this->createTranslatableEntity();
  }

  /**
   * Ensures that entity URLs in a language have the right language prefix.
   */
  public function testEntityUrlLanguage() {
    $this->assertStringContainsString('/en/entity_test/' . $this->entity->id(), $this->entity->toUrl()->toString());
    $this->assertStringContainsString('/es/entity_test/' . $this->entity->id(), $this->entity->getTranslation('es')->toUrl()->toString());
    $this->assertStringContainsString('/fr/entity_test/' . $this->entity->id(), $this->entity->getTranslation('fr')->toUrl()->toString());
  }

  /**
   * Ensures correct entity URLs with the method language-content-entity enabled.
   *
   * Test case with the method language-content-entity enabled and configured
   * with higher and also with lower priority than the method language-url.
   */
  public function testEntityUrlLanguageWithLanguageContentEnabled() {
    // Define the method language-content-entity with a higher priority than
    // language-url.
    $config = $this->config('language.types');
    $config->set('configurable', [LanguageInterface::TYPE_INTERFACE, LanguageInterface::TYPE_CONTENT]);
    $config->set('negotiation.language_content.enabled', [
      LanguageNegotiationContentEntity::METHOD_ID => 0,
      LanguageNegotiationUrl::METHOD_ID => 1,
    ]);
    $config->save();

    // Without being on an content entity route the default entity URL tests
    // should still pass.
    $this->testEntityUrlLanguage();

    // Now switching to an entity route, so that the URL links are generated
    // while being on an entity route.
    $this->setCurrentRequestForRoute('/entity_test/{entity_test}', 'entity.entity_test.canonical');

    // The method language-content-entity should run before language-url and
    // append query parameter for the content language and prevent language-url
    // from overwriting the url.
    $this->assertStringContainsString('/en/entity_test/' . $this->entity->id() . '?' . LanguageNegotiationContentEntity::QUERY_PARAMETER . '=en', $this->entity->toUrl('canonical')->toString());
    $this->assertStringContainsString('/en/entity_test/' . $this->entity->id() . '?' . LanguageNegotiationContentEntity::QUERY_PARAMETER . '=es', $this->entity->getTranslation('es')->toUrl('canonical')->toString());
    $this->assertStringContainsString('/en/entity_test/' . $this->entity->id() . '?' . LanguageNegotiationContentEntity::QUERY_PARAMETER . '=fr', $this->entity->getTranslation('fr')->toUrl('canonical')->toString());

    // Define the method language-url with a higher priority than
    // language-content-entity. This configuration should match the default one,
    // where the language-content-entity is turned off.
    $config->set('negotiation.language_content.enabled', [
      LanguageNegotiationUrl::METHOD_ID => 0,
      LanguageNegotiationContentEntity::METHOD_ID => 1,
    ]);
    $config->save();

    // The default entity URL tests should pass again with the current
    // configuration.
    $this->testEntityUrlLanguage();
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
