<?php

namespace Drupal\Core\StackMiddleware;

use Drupal\Component\Utility\UrlHelper;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * Expands the compressed ajax_page_state query parameter into an array.
 */
class AjaxPageState implements HttpKernelInterface {

  /**
   * Constructs a new AjaxPageState instance.
   *
   * @param \Symfony\Component\HttpKernel\HttpKernelInterface $httpKernel
   *   The wrapped HTTP kernel.
   */
  public function __construct(protected readonly HttpKernelInterface $httpKernel) {
  }

  /**
   * {@inheritdoc}
   */
  public function handle(Request $request, $type = self::MAIN_REQUEST, $catch = TRUE): Response {
    if ($type === static::MAIN_REQUEST) {
      $request_ajax_page_state = [];
      if ($request->request->has('ajax_page_state')) {
        $request_ajax_page_state = $this->parseAjaxPageState($request->request->all('ajax_page_state'));
        $request->request->set('ajax_page_state', $request_ajax_page_state);
      }

      $query_ajax_page_state = [];
      if ($request->query->has('ajax_page_state')) {
        $query_ajax_page_state = $this->parseAjaxPageState($request->query->all('ajax_page_state'));
        $request->query->set('ajax_page_state', $query_ajax_page_state);
      }

      // If libraries are present in both the request and the query, ensure they
      // match by merging them together.
      if (!empty($request_ajax_page_state['libraries']) && !empty($query_ajax_page_state['libraries'])) {
        $request_libraries = explode(',', $request_ajax_page_state['libraries']);
        $query_libraries = explode(',', $query_ajax_page_state['libraries']);
        $libraries = implode(',', array_unique(array_merge($request_libraries, $query_libraries)));
        $request_ajax_page_state['libraries'] = $libraries;
        $query_ajax_page_state['libraries'] = $libraries;
        $request->request->set('ajax_page_state', $request_ajax_page_state);
        $request->query->set('ajax_page_state', $query_ajax_page_state);
      }
    }
    return $this->httpKernel->handle($request, $type, $catch);
  }

  /**
   * Parse the ajax_page_state variable in the request.
   *
   * Decompresses the libraries array key.
   *
   * @param array $ajax_page_state
   *   An array of query parameters, where the libraries parameter is
   *   compressed.
   *
   * @return array
   *   The input array of query parameters, where the libraries parameter is
   *   changed to be uncompressed.
   */
  private function parseAjaxPageState(array $ajax_page_state): array {
    $ajax_page_state['libraries'] = UrlHelper::uncompressQueryParameter($ajax_page_state['libraries']);
    return $ajax_page_state;
  }

}
