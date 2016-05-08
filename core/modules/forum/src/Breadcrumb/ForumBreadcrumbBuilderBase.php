<?php

namespace Drupal\forum\Breadcrumb;

use Drupal\Core\Breadcrumb\BreadcrumbBuilderInterface;
use Drupal\Core\Breadcrumb\Breadcrumb;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Link;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\forum\ForumManagerInterface;

/**
 * Provides a forum breadcrumb base class.
 *
 * This just holds the dependency-injected config, entity manager, and forum
 * manager objects.
 */
abstract class ForumBreadcrumbBuilderBase implements BreadcrumbBuilderInterface {
  use StringTranslationTrait;

  /**
   * Configuration object for this builder.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $config;

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * The forum manager service.
   *
   * @var \Drupal\forum\ForumManagerInterface
   */
  protected $forumManager;

  /**
   * Constructs a forum breadcrumb builder object.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   * @param \Drupal\forum\ForumManagerInterface $forum_manager
   *   The forum manager service.
   */
  public function __construct(EntityManagerInterface $entity_manager, ConfigFactoryInterface $config_factory, ForumManagerInterface $forum_manager) {
    $this->entityManager = $entity_manager;
    $this->config = $config_factory->get('forum.settings');
    $this->forumManager = $forum_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function build(RouteMatchInterface $route_match) {
    $breadcrumb = new Breadcrumb();
    $breadcrumb->addCacheContexts(['route']);

    $links[] = Link::createFromRoute($this->t('Home'), '<front>');

    $vocabulary = $this->entityManager
      ->getStorage('taxonomy_vocabulary')
      ->load($this->config->get('vocabulary'));
    $breadcrumb->addCacheableDependency($vocabulary);
    $links[] = Link::createFromRoute($vocabulary->label(), 'forum.index');

    return $breadcrumb->setLinks($links);
  }

}
