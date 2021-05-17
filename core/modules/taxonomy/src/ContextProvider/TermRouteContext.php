<?php

namespace Drupal\taxonomy\ContextProvider;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Plugin\Context\Context;
use Drupal\Core\Plugin\Context\ContextProviderInterface;
use Drupal\Core\Plugin\Context\EntityContext;
use Drupal\Core\Plugin\Context\EntityContextDefinition;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\taxonomy\Entity\Term;

/**
 * Sets the current taxonomy term as a context on taxonomy term routes.
 */
class TermRouteContext implements ContextProviderInterface {

  use StringTranslationTrait;

  /**
   * The route match object.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * Constructs a new TermRouteContext.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match object.
   */
  public function __construct(RouteMatchInterface $route_match) {
    $this->routeMatch = $route_match;
  }

  /**
   * {@inheritdoc}
   */
  public function getRuntimeContexts(array $unqualified_context_ids) {
    $result = [];
    $context_definition = EntityContextDefinition::create('taxonomy_term')->setRequired(FALSE);
    $value = NULL;
    if ($route_object = $this->routeMatch->getRouteObject()) {
      $route_parameters = $route_object->getOption('parameters');

      if (isset($route_parameters['taxonomy_term']) && $term = $this->routeMatch->getParameter('taxonomy_term')) {
        $value = $term;
      }
      elseif ($this->routeMatch->getRouteName() == 'entity.taxonomy_term.add_form') {
        $vocabulary = $this->routeMatch->getParameter('taxonomy_vocabulary');
        $value = Term::create(['vid' => $vocabulary->id()]);
      }
    }

    $cacheability = new CacheableMetadata();
    $cacheability->setCacheContexts(['route']);

    $context = new Context($context_definition, $value);
    $context->addCacheableDependency($cacheability);
    $result['taxonomy_term'] = $context;

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function getAvailableContexts() {
    $context = EntityContext::fromEntityTypeId('taxonomy_term', $this->t('Term from URL'));
    return ['taxonomy_term' => $context];
  }

}
