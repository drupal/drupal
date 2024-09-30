<?php

namespace Drupal\Core\Utility;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Link;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides a class for table sorting processing and rendering.
 */
class TableSort {

  const ASC = 'asc';
  const DESC = 'desc';

  /**
   * Initializes the table sort context.
   *
   * @param array $headers
   *   An array of column headers in the format described in '#type' => 'table'.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   A current request.
   *
   * @return array
   *   The current table sort context.
   */
  public static function getContextFromRequest(array $headers, Request $request) {
    $context = static::getOrder($headers, $request);
    $context['sort'] = static::getSort($headers, $request);
    $context['query'] = static::getQueryParameters($request);
    return $context;
  }

  /**
   * Formats a column header.
   *
   * If the cell in question is the column header for the current sort
   * criterion, it gets special formatting. All possible sort criteria become
   * links.
   *
   * @param string $cell_content
   *   The cell content to format. Passed by reference.
   * @param array $cell_attributes
   *   The cell attributes. Passed by reference.
   * @param array $header
   *   An array of column headers in the format described in '#type' => 'table'.
   * @param array $context
   *   The current table sort context as returned from
   *   TableSort::getContextFromRequest() method.
   *
   * @throws \Exception
   *
   * @see getContextFromRequest()
   */
  public static function header(&$cell_content, array &$cell_attributes, array $header, array $context) {
    // Special formatting for the currently sorted column header.
    if (isset($cell_attributes['field'])) {
      $title = new TranslatableMarkup('sort by @s', ['@s' => $cell_content]);
      if ($cell_content == $context['name']) {
        // aria-sort is a WAI-ARIA property that indicates if items in a table
        // or grid are sorted in ascending or descending order. See
        // https://www.w3.org/TR/wai-aria/states_and_properties#aria-sort
        $cell_attributes['aria-sort'] = ($context['sort'] == self::ASC) ? 'ascending' : 'descending';
        $context['sort'] = (($context['sort'] == self::ASC) ? self::DESC : self::ASC);
        $cell_attributes['class'][] = 'is-active';
        $tablesort_indicator = [
          '#theme' => 'tablesort_indicator',
          '#style' => $context['sort'],
        ];
        $image = \Drupal::service('renderer')->render($tablesort_indicator);
      }
      else {
        // This determines the sort order when the column gets first clicked by
        // the user. It is "asc" by default but the sort can be changed if
        // $cell['initial_click_sort'] is defined. The possible values are "asc"
        // or "desc".
        $context['sort'] = $cell_attributes['initial_click_sort'] ?? self::ASC;
        $image = '';
      }
      $cell_content = Link::createFromRoute(new FormattableMarkup('@cell_content@image', ['@cell_content' => $cell_content, '@image' => $image]), '<current>', [], [
        'attributes' => ['title' => $title, 'rel' => 'nofollow'],
        'query' => array_merge($context['query'], [
          'sort' => $context['sort'],
          'order' => $cell_content,
        ]),
      ]);

      unset($cell_attributes['field'], $cell_attributes['sort'], $cell_attributes['initial_click_sort']);
    }
  }

  /**
   * Determines the current sort criterion.
   *
   * @param array $headers
   *   An array of column headers in the format described in '#type' => 'table'.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   A current request.
   *
   * @return array
   *   An associative array describing the criterion, containing the keys:
   *   - "name": The localized title of the table column.
   *   - "sql": The name of the database field to sort on.
   */
  public static function getOrder(array $headers, Request $request) {
    $order = $request->query->get('order', '');
    foreach ($headers as $header) {
      if (is_array($header)) {
        if (isset($header['data']) && $order == $header['data']) {
          $default = $header;
          break;
        }

        if (empty($default) && isset($header['sort']) && in_array($header['sort'], [self::ASC, self::DESC])) {
          $default = $header;
        }
      }
    }

    if (!isset($default)) {
      $default = reset($headers);
      if (!is_array($default)) {
        $default = ['data' => $default];
      }
    }

    $default += ['data' => NULL, 'field' => NULL];
    return ['name' => $default['data'], 'sql' => $default['field']];
  }

  /**
   * Determines the current sort direction.
   *
   * @param array $headers
   *   An array of column headers in the format described in '#type' => 'table'.
   * @param \Symfony\Component\HttpFoundation\Request|null $request
   *   A current request.
   *
   * @return string
   *   The current sort direction ("asc" or "desc").
   */
  public static function getSort(array $headers, Request $request) {
    $query = $request->query;
    if ($query->has('sort')) {
      return (strtolower($query->get('sort')) == self::DESC) ? self::DESC : self::ASC;
    }
    // The user has not specified a sort. Use the default for the currently
    // sorted header if specified; otherwise use "asc".
    // Find out which header is currently being sorted.
    $order = static::getOrder($headers, $request);
    foreach ($headers as $header) {
      if (is_array($header) && isset($header['data']) && $header['data'] == $order['name']) {
        if (isset($header['sort'])) {
          return $header['sort'];
        }
        if (isset($header['initial_click_sort'])) {
          return $header['initial_click_sort'];
        }
      }
    }
    return self::ASC;
  }

  /**
   * Composes a URL query parameter array for table sorting links.
   *
   * @param \Symfony\Component\HttpFoundation\Request|null $request
   *   A current request.
   *
   * @return array
   *   A URL query parameter array that consists of all components of the
   *   current page request except for those pertaining to table sorting.
   *
   * @internal
   */
  public static function getQueryParameters(Request $request) {
    return UrlHelper::filterQueryParameters($request->query->all(), ['sort', 'order']);
  }

}
