<?php

declare(strict_types=1);

namespace Drupal\http_response_debug_cacheability_headers_test\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Provides responses for testing debug cacheability headers in HTTP responses.
 *
 * Apache has a response header line limit of 8190 bytes. Complex applications
 * can have a lot of cache tags (and cache contexts, though less likely) that
 * bubble to response and are sent as HTTP response headers when the container
 * parameter http.response.debug_cacheability_headers is set to TRUE. To solve
 * this, the debug cache headers are split into multiple lines with the same
 * header name.
 *
 * Nginx has a limit on the total HTTP response header size, including all
 * lines, does not have limits per header line, so these responses will not
 * cause server errors even if the lines are not split.
 */
class TestResponseController extends ControllerBase {

  /**
   * Provides a render array response that has a large number of cache contexts.
   *
   * @return array
   *   Render array.
   */
  public function testCacheContextsHeaders(): array {
    // Create multiple cache contexts that add up to more than 8k bytes.
    for ($i = 0; $i < 700; $i++) {
      $contexts[] = 'url.query_args:' . str_pad("$i", 4, '0', STR_PAD_LEFT);
    }
    $contexts_length = strlen(implode(' ', $contexts));

    return [
      '#markup' => 'This is a test of a list of cache contexts debug headers that exceed ' . $contexts_length . ' bytes in total.',
      '#cache' => [
        'contexts' => $contexts,
      ],
    ];
  }

  /**
   * Provides a render array response that has a large number of cache tags.
   *
   * @return array
   *   Render array.
   */
  public function testCacheTagsHeaders(): array {
    // Create multiple cache tags that add up to more than 8k bytes.
    for ($i = 0; $i < 800; $i++) {
      $tags[] = 'cache-tag:' . str_pad("$i", 5, '0', STR_PAD_LEFT);
    }
    $tags_length = strlen(implode(' ', $tags));

    return [
      '#markup' => 'This is a test of a list of cache tags debug headers that exceed ' . $tags_length . ' bytes in total.',
      '#cache' => [
        'tags' => $tags,
      ],
    ];
  }

}
