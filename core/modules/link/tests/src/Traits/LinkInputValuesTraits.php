<?php

declare(strict_types=1);

namespace Drupal\Tests\link\Traits;

/**
 * Provides test values for the Link field.
 */
trait LinkInputValuesTraits {

  /**
   * Link field values use for test.
   *
   * @return array
   *   Values to use at link field setter.
   */
  protected function getLinkInputValues(): array {
    $test_values = [
      // External links.
      0 => [
        'uri' => 'http://www.example.com/content/articles/archive?author=John&year=2012#com',
      ],
      1 => [
        'uri' => 'http://www.example.org/content/articles/archive?author=John&year=2012#org',
        'title' => 'A very long & strange example title that could break the nice layout of the site',
      ],
      2 => ['uri' => 'internal:#net', 'title' => 'Fragment only'],

      // Complex internal links.
      // Result link: '?a[0]=1&a[1]=2'.
      3 => ['uri' => 'internal:?a[]=1&a[]=2'],
      4 => ['uri' => 'internal:?b[0]=1&b[1]=2'],
      // Injecting new test value in the middle of array.
      16 => ['uri' => 'internal:?b[0]=9&b[1]=8'],
      // UrlHelper::buildQuery will change order of params.
      // Result link: '?c[0]=1&c[1]=2&d=3'.
      5 => ['uri' => 'internal:?c[]=1&d=3&c[]=2'],
      6 => ['uri' => 'internal:?e[f][g]=h'],
      7 => ['uri' => 'internal:?i[j[k]]=l'],

      // Query string replace value.
      // Result link: '?x=1&x=2'.
      8 => ['uri' => 'internal:?x=1&x=2'],
      // Result link: '?z[0]=2'.
      9 => ['uri' => 'internal:?z[0]=1&z[0]=2'],

      // Special empty links.
      10 => ['uri' => 'route:<none>'],
      11 => ['uri' => 'route:<none>', 'title' => 'Title, no link'],
      12 => ['uri' => 'route:<nolink>'],
      13 => ['uri' => 'route:<nolink>', 'title' => 'Title, no link'],
      14 => ['uri' => 'route:<button>'],
      15 => ['uri' => 'route:<button>', 'title' => 'Title, button'],

    ];
    // Sort by keys, to be able to inject new test anywhere in the array.
    ksort($test_values, SORT_NUMERIC);
    return $test_values;
  }

}
