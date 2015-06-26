<?php

/**
 * @file
 * Contains \Drupal\taxonomy\TermBreadcrumbBuilder.
 */

namespace Drupal\taxonomy;

use Drupal\Core\Breadcrumb\BreadcrumbBuilderInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Link;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Provides a custom taxonomy breadcrumb builder that uses the term hierarchy.
 */
class TermBreadcrumbBuilder implements BreadcrumbBuilderInterface {
  use StringTranslationTrait;

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * The taxonomy storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $termStorage;

  /**
   * Constructs the TermBreadcrumbBuilder.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entityManager
   *   The entity manager.
   */
  public function __construct(EntityManagerInterface $entityManager) {
    $this->entityManager = $entityManager;
    $this->termStorage = $entityManager->getStorage('taxonomy_term');
  }

  /**
   * {@inheritdoc}
   */
  public function applies(RouteMatchInterface $route_match) {
    return $route_match->getRouteName() == 'entity.taxonomy_term.canonical'
      && $route_match->getParameter('taxonomy_term') instanceof TermInterface;
  }

  /**
   * {@inheritdoc}
   */
  public function build(RouteMatchInterface $route_match) {
    $term = $route_match->getParameter('taxonomy_term');
    // @todo This overrides any other possible breadcrumb and is a pure
    //   hard-coded presumption. Make this behavior configurable per
    //   vocabulary or term.
    $breadcrumb = array();
    while ($parents = $this->termStorage->loadParents($term->id())) {
      $term = array_shift($parents);
      $term = $this->entityManager->getTranslationFromContext($term);
      $breadcrumb[] = Link::createFromRoute($term->getName(), 'entity.taxonomy_term.canonical', array('taxonomy_term' => $term->id()));
    }
    $breadcrumb[] = Link::createFromRoute($this->t('Home'), '<front>');
    $breadcrumb = array_reverse($breadcrumb);

    return $breadcrumb;
  }

}
