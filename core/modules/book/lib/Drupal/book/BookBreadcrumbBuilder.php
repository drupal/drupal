<?php

/**
 * @file
 * Contains \Drupal\book\BookBreadcrumbBuilder.
 */

namespace Drupal\book;

use Drupal\Core\Access\AccessManager;
use Drupal\Core\Breadcrumb\BreadcrumbBuilderInterface;
use Drupal\Core\Entity\EntityManager;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\Utility\LinkGeneratorInterface;
use Drupal\node\NodeInterface;

/**
 * Provides a breadcrumb builder for nodes in a book.
 */
class BookBreadcrumbBuilder implements BreadcrumbBuilderInterface {

  /**
   * The menu link storage controller.
   *
   * @var \Drupal\menu_link\MenuLinkStorageControllerInterface
   */
  protected $menuLinkStorage;

  /**
   * The translation manager service.
   *
   * @var \Drupal\Core\StringTranslation\TranslationInterface;
   */
  protected $translation;

  /**
   * The link generator service.
   *
   * @var \Drupal\Core\Utility\LinkGeneratorInterface
   */
  protected $linkGenerator;

  /**
   * The access manager.
   *
   * @var \Drupal\Core\Access\AccessManager
   */
  protected $accessManager;

  /**
   * Constructs the BookBreadcrumbBuilder.
   *
   * @param \Drupal\Core\Entity\EntityManager $entity_manager
   *   The entity manager service.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $translation
   *   The translation manager service.
   * @param \Drupal\Core\Utility\LinkGeneratorInterface $link_generator
   *   The link generator.
   * @param \Drupal\Core\Access\AccessManager $access_manager
   *   The access manager.
   */
  public function __construct(EntityManager $entity_manager, TranslationInterface $translation, LinkGeneratorInterface $link_generator, AccessManager $access_manager) {
    $this->menuLinkStorage = $entity_manager->getStorageController('menu_link');
    $this->translation = $translation;
    $this->linkGenerator = $link_generator;
    $this->accessManager = $access_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function build(array $attributes) {
    if (!empty($attributes['node']) && $attributes['node'] instanceof NodeInterface && !empty($attributes['node']->book)) {
      $mlids = array();
      $links = array($this->linkGenerator->generate($this->t('Home'), '<front>'));
      $book = $attributes['node']->book;
      $depth = 1;
      // We skip the current node.
      while (!empty($book['p' . ($depth + 1)])) {
        $mlids[] = $book['p' . $depth];
        $depth++;
      }
      $menu_links = $this->menuLinkStorage->loadMultiple($mlids);
      if (count($menu_links) > 0) {
        $depth = 1;
        while (!empty($book['p' . ($depth + 1)])) {
          if (!empty($menu_links[$book['p' . $depth]]) && ($menu_link = $menu_links[$book['p' . $depth]])) {
            if ($this->accessManager->checkNamedRoute($menu_link->route_name, $menu_link->route_parameters)) {
              $links[] = $this->linkGenerator->generate($menu_link->label(), $menu_link->route_name, $menu_link->route_parameters, $menu_link->options);
            }
          }
          $depth++;
        }
      }
      return $links;
    }
  }

  /**
   * Translates a string to the current language or to a given language.
   *
   * See the t() documentation for details.
   */
  protected function t($string, array $args = array(), array $options = array()) {
    return $this->translation->translate($string, $args, $options);
  }

}
