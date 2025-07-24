<?php

namespace Drupal\Core\Pager;

use Drupal\Component\Utility\Html;
use Drupal\Core\EventSubscriber\MainContentViewSubscriber;
use Drupal\Core\Template\Attribute;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Pager theme preprocess.
 *
 * @internal
 */
class PagerPreprocess {

  public function __construct(
    protected PagerManagerInterface $pagerManager,
    protected RequestStack $requestStack,
  ) {
  }

  /**
   * Prepares variables for pager templates.
   *
   * Default template: pager.html.twig.
   *
   * Menu callbacks that display paged query results should use #type => pager
   * to retrieve a pager control so that users can view other results. Format a
   * list of nearby pages with additional query results.
   *
   * @param array $variables
   *   An associative array containing:
   *   - pager: A render element containing:
   *     - #tags: An array of labels for the controls in the pager.
   *     - #element: An optional integer to distinguish between multiple pagers
   *       on one page.
   *     - #pagination_heading_level: An optional heading level for the pager.
   *     - #parameters: An associative array of query string parameters to
   *       append to the pager links.
   *     - #route_parameters: An associative array of the route parameters.
   *     - #quantity: The number of pages in the list.
   */
  public function preprocessPager(array &$variables): void {
    $element = $variables['pager']['#element'];
    $parameters = $variables['pager']['#parameters'];
    $quantity = empty($variables['pager']['#quantity']) ? 0 : $variables['pager']['#quantity'];
    $route_name = $variables['pager']['#route_name'];
    $route_parameters = $variables['pager']['#route_parameters'] ?? [];

    $link_attributes = [];

    if ($this->requestStack->getCurrentRequest()?->get(MainContentViewSubscriber::WRAPPER_FORMAT) === 'drupal_modal') {
      $link_attributes = [
        'class' => ['use-ajax'],
        'data-dialog-type' => 'modal',
        'data-accepts' => 'application/json',
      ];
    }

    $pager = $this->pagerManager->getPager($element);

    // Nothing to do if there is no pager.
    if (!isset($pager)) {
      return;
    }

    $pager_max = $pager->getTotalPages();

    // Nothing to do if there is only one page.
    if ($pager_max <= 1) {
      return;
    }

    $tags = $variables['pager']['#tags'];

    // Calculate various markers within this pager piece:
    // Middle is used to "center" pages around the current page.
    $pager_middle = ceil($quantity / 2);
    $current_page = $pager->getCurrentPage();
    // The current pager is the page we are currently paged to.
    $pager_current = $current_page + 1;
    // The first pager is the first page listed by this pager piece (re
    // quantity).
    $pager_first = $pager_current - $pager_middle + 1;
    // The last is the last page listed by this pager piece (re quantity).
    $pager_last = $pager_current + $quantity - $pager_middle;
    // End of marker calculations.

    // Prepare for generation loop.
    $i = $pager_first;
    if ($pager_last > $pager_max) {
      // Adjust "center" if at end of query.
      $i = $i + ($pager_max - $pager_last);
      $pager_last = $pager_max;
    }
    if ($i <= 0) {
      // Adjust "center" if at start of query.
      $pager_last = $pager_last + (1 - $i);
      $i = 1;
    }
    // End of generation loop preparation.

    // Create the "first" and "previous" links if we are not on the first page.
    $items = [];
    if ($current_page > 0) {
      $items['first'] = [];
      $items['first']['attributes'] = new Attribute($link_attributes);
      $options = [
        'query' => $this->pagerManager->getUpdatedParameters($parameters, $element, 0),
      ];
      $items['first']['href'] = Url::fromRoute($route_name, $route_parameters, $options)->toString();
      if (isset($tags[0])) {
        $items['first']['text'] = $tags[0];
      }

      $items['previous'] = [];
      $items['previous']['attributes'] = new Attribute($link_attributes);
      $options = [
        'query' => $this->pagerManager->getUpdatedParameters($parameters, $element, $current_page - 1),
      ];
      $items['previous']['href'] = Url::fromRoute($route_name, $route_parameters, $options)->toString();
      if (isset($tags[1])) {
        $items['previous']['text'] = $tags[1];
      }
    }

    // Add an ellipsis if there are further previous pages.
    if ($i > 1) {
      $variables['ellipses']['previous'] = TRUE;
    }
    // Now generate the actual pager piece.
    for (; $i <= $pager_last && $i <= $pager_max; $i++) {
      $options = [
        'query' => $this->pagerManager->getUpdatedParameters($parameters, $element, $i - 1),
      ];
      $items['pages'][$i]['href'] = Url::fromRoute($route_name, $route_parameters, $options)->toString();
      $items['pages'][$i]['attributes'] = new Attribute($link_attributes);
      if ($i == $pager_current) {
        $variables['current'] = $i;
        $items['pages'][$i]['attributes']->setAttribute('aria-current', 'page');
      }
    }
    // Add an ellipsis if there are further next pages.
    if ($i < $pager_max + 1) {
      $variables['ellipses']['next'] = TRUE;
    }

    // Create the "next" and "last" links if we are not on the last page.
    if ($current_page < ($pager_max - 1)) {
      $items['next'] = [];
      $items['next']['attributes'] = new Attribute($link_attributes);
      $options = [
        'query' => $this->pagerManager->getUpdatedParameters($parameters, $element, $current_page + 1),
      ];
      $items['next']['href'] = Url::fromRoute($route_name, $route_parameters, $options)->toString();
      if (isset($tags[3])) {
        $items['next']['text'] = $tags[3];
      }

      $items['last'] = [];
      $items['last']['attributes'] = new Attribute();
      $options = [
        'query' => $this->pagerManager->getUpdatedParameters($parameters, $element, $pager_max - 1),
      ];
      $items['last']['href'] = Url::fromRoute($route_name, $route_parameters, $options)->toString();
      if (isset($tags[4])) {
        $items['last']['text'] = $tags[4];
      }
    }

    $variables['items'] = $items;
    $variables['heading_id'] = Html::getUniqueId('pagination-heading');
    $variables['pagination_heading_level'] = $variables['pager']['#pagination_heading_level'] ?? 'h4';
    if (!preg_match('/^h[1-6]$/', $variables['pagination_heading_level'])) {
      $variables['pagination_heading_level'] = 'h4';
    }

    // The rendered link needs to play well with any other query parameter used
    // on the page, like exposed filters, so for the cacheability all query
    // parameters matter.
    $variables['#cache']['contexts'][] = 'url.query_args';
  }

}
