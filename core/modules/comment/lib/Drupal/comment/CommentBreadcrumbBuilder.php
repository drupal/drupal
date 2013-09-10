<?php

/**
 * @file
 * Contains \Drupal\comment\CommentBreadcrumbBuilder.
 */

namespace Drupal\comment;

use Drupal\Core\Breadcrumb\BreadcrumbBuilderInterface;
use Drupal\Core\StringTranslation\TranslationManager;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;

/**
 * Class to define the comment breadcrumb builder.
 */
class CommentBreadcrumbBuilder implements BreadcrumbBuilderInterface {

  /**
   * The translation manager service.
   *
   * @var \Drupal\Core\StringTranslation\TranslationManager
   */
  protected $translation;

  /**
   * Constructs a CommentBreadcrumbBuilder object.
   *
   * @param \Drupal\Core\StringTranslation\TranslationManager $translation
   *   The translation manager.
   */
  public function __construct(TranslationManager $translation) {
    $this->translation = $translation;
  }

  /**
   * {@inheritdoc}
   */
  public function build(array $attributes) {
    if (isset($attributes[RouteObjectInterface::ROUTE_NAME]) && $attributes[RouteObjectInterface::ROUTE_NAME] == 'comment_reply' && isset($attributes['node'])) {
      $node = $attributes['node'];
      $uri = $node->uri();
      $breadcrumb[] = l($this->t('Home'), NULL);
      $breadcrumb[] = l($node->label(), $uri['path']);
      return $breadcrumb;
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
