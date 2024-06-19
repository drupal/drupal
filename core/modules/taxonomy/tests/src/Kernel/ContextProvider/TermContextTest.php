<?php

declare(strict_types=1);

namespace Drupal\Tests\taxonomy\Kernel\ContextProvider;

use Drupal\Core\Routing\RouteMatch;
use Drupal\KernelTests\KernelTestBase;
use Drupal\taxonomy\ContextProvider\TermRouteContext;
use Drupal\Tests\taxonomy\Traits\TaxonomyTestTrait;

/**
 * @coversDefaultClass \Drupal\taxonomy\ContextProvider\TermRouteContext
 *
 * @group taxonomy
 */
class TermContextTest extends KernelTestBase {

  use TaxonomyTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['filter', 'taxonomy', 'text', 'user'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installConfig(['filter']);
    $this->installEntitySchema('user');
    $this->installEntitySchema('taxonomy_term');
  }

  /**
   * @covers ::getAvailableContexts
   */
  public function testGetAvailableContexts(): void {
    $context_repository = $this->container->get('context.repository');

    // Test taxonomy_term.taxonomy_term_route_context:taxonomy_term exists.
    $contexts = $context_repository->getAvailableContexts();
    $this->assertArrayHasKey('@taxonomy_term.taxonomy_term_route_context:taxonomy_term', $contexts);
    $this->assertSame('entity:taxonomy_term', $contexts['@taxonomy_term.taxonomy_term_route_context:taxonomy_term']->getContextDefinition()
      ->getDataType());
  }

  /**
   * @covers ::getRuntimeContexts
   */
  public function testGetRuntimeContexts(): void {
    // Create term.
    $vocabulary = $this->createVocabulary();
    $term = $this->createTerm($vocabulary);

    // Create RouteMatch from term entity.
    $url = $term->toUrl();
    $route_provider = \Drupal::service('router.route_provider');
    $route = $route_provider->getRouteByName($url->getRouteName());
    $route_match = new RouteMatch($url->getRouteName(), $route, [
      'taxonomy_term' => $term,
    ]);

    // Initiate TermRouteContext with RouteMatch.
    $provider = new TermRouteContext($route_match);

    $runtime_contexts = $provider->getRuntimeContexts([]);
    $this->assertArrayHasKey('taxonomy_term', $runtime_contexts);
    $this->assertTrue($runtime_contexts['taxonomy_term']->hasContextValue());
  }

}
