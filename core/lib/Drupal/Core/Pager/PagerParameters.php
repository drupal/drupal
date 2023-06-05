<?php

namespace Drupal\Core\Pager;

use Drupal\Component\Utility\UrlHelper;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Provides pager information contained within the current request.
 *
 * @see \Drupal\Core\Pager\PagerManagerInterface
 */
class PagerParameters implements PagerParametersInterface {

  /**
   * The HTTP request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * Construct a PagerManager object.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $stack
   *   The current HTTP request stack.
   */
  public function __construct(RequestStack $stack) {
    $this->requestStack = $stack;
  }

  /**
   * {@inheritdoc}
   */
  public function getQueryParameters() {
    $request = $this->requestStack->getCurrentRequest();
    if ($request) {
      return UrlHelper::filterQueryParameters(
        $request->query->all(), ['page', 'ajax_page_state']
      );
    }
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function findPage($pager_id = 0) {
    $pages = $this->getPagerQuery();
    return (int) ($pages[$pager_id] ?? 0);
  }

  /**
   * {@inheritdoc}
   */
  public function getPagerQuery() {
    $query = $this->getPagerParameter();
    return !empty($query) ? explode(',', $query) : [];
  }

  /**
   * {@inheritdoc}
   */
  public function getPagerParameter() {
    $request = $this->requestStack->getCurrentRequest();
    if ($request) {
      return $request->query->get('page', '');
    }
    return '';
  }

}
