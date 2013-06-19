<?php

/**
 * @file
 * Contains \Drupal\common_test\Controller\CommonTestController.
 */

namespace Drupal\common_test\Controller;

use Drupal\Core\Controller\ControllerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller routines for common_test routes.
 */
class CommonTestController implements ControllerInterface {

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static();
  }

  /**
   * Returns links to the current page, with and without query strings.
   *
   * Using #type 'link' causes these links to be rendered with l().
   */
  public function typeLinkActiveClass() {
    return array(
      'no_query' => array(
        '#type' => 'link',
        '#title' => t('Link with no query string'),
        '#href' => current_path(),
      ),
      'with_query' => array(
        '#type' => 'link',
        '#title' => t('Link with a query string'),
        '#href' => current_path(),
        '#options' => array(
          'query' => array(
            'foo' => 'bar',
            'one' => 'two',
          ),
        ),
      ),
      'with_query_reversed' => array(
        '#type' => 'link',
        '#title' => t('Link with the same query string in reverse order'),
        '#href' => current_path(),
        '#options' => array(
          'query' => array(
            'one' => 'two',
            'foo' => 'bar',
          ),
        ),
      ),
    );
  }

}
