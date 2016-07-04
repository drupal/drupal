<?php

namespace Drupal\big_pipe\Render;

use Drupal\Component\Utility\Crypt;
use Drupal\Component\Utility\Html;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Asset\AttachedAssets;
use Drupal\Core\Asset\AttachedAssetsInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Render\HtmlResponse;
use Drupal\Core\Render\RendererInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * The default BigPipe service.
 */
class BigPipe implements BigPipeInterface {

  /**
   * The BigPipe placeholder replacements start signal.
   *
   * @var string
   */
  const START_SIGNAL = '<script type="application/vnd.drupal-ajax" data-big-pipe-event="start"></script>';

  /**
   * The BigPipe placeholder replacements stop signal.
   *
   * @var string
   */
  const STOP_SIGNAL = '<script type="application/vnd.drupal-ajax" data-big-pipe-event="stop"></script>';

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * The session.
   *
   * @var \Symfony\Component\HttpFoundation\Session\SessionInterface
   */
  protected $session;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The HTTP kernel.
   *
   * @var \Symfony\Component\HttpKernel\HttpKernelInterface
   */
  protected $httpKernel;

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs a new BigPipe class.
   *
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   * @param \Symfony\Component\HttpFoundation\Session\SessionInterface $session
   *   The session.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   * @param \Symfony\Component\HttpKernel\HttpKernelInterface $http_kernel
   *   The HTTP kernel.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(RendererInterface $renderer, SessionInterface $session, RequestStack $request_stack, HttpKernelInterface $http_kernel, EventDispatcherInterface $event_dispatcher, ConfigFactoryInterface $config_factory) {
    $this->renderer = $renderer;
    $this->session = $session;
    $this->requestStack = $request_stack;
    $this->httpKernel = $http_kernel;
    $this->eventDispatcher = $event_dispatcher;
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public function sendContent($content, array $attachments) {
    // First, gather the BigPipe placeholders that must be replaced.
    $placeholders = isset($attachments['big_pipe_placeholders']) ? $attachments['big_pipe_placeholders'] : [];
    $nojs_placeholders = isset($attachments['big_pipe_nojs_placeholders']) ? $attachments['big_pipe_nojs_placeholders'] : [];

    // BigPipe sends responses using "Transfer-Encoding: chunked". To avoid
    // sending already-sent assets, it is necessary to track cumulative assets
    // from all previously rendered/sent chunks.
    // @see http://www.w3.org/Protocols/rfc2616/rfc2616-sec14.html#sec14.41
    $cumulative_assets = AttachedAssets::createFromRenderArray(['#attached' => $attachments]);
    $cumulative_assets->setAlreadyLoadedLibraries($attachments['library']);

    // The content in the placeholders may depend on the session, and by the
    // time the response is sent (see index.php), the session is already closed.
    // Reopen it for the duration that we are rendering placeholders.
    $this->session->start();

    list($pre_body, $post_body) = explode('</body>', $content, 2);
    $this->sendPreBody($pre_body, $nojs_placeholders, $cumulative_assets);
    $this->sendPlaceholders($placeholders, $this->getPlaceholderOrder($pre_body), $cumulative_assets);
    $this->sendPostBody($post_body);

    // Close the session again.
    $this->session->save();

    return $this;
  }

  /**
   * Sends everything until just before </body>.
   *
   * @param string $pre_body
   *   The HTML response's content until the closing </body> tag.
   * @param array $no_js_placeholders
   *   The no-JS BigPipe placeholders.
   * @param \Drupal\Core\Asset\AttachedAssetsInterface $cumulative_assets
   *   The cumulative assets sent so far; to be updated while rendering no-JS
   *   BigPipe placeholders.
   */
  protected function sendPreBody($pre_body, array $no_js_placeholders, AttachedAssetsInterface $cumulative_assets) {
    // If there are no no-JS BigPipe placeholders, we can send the pre-</body>
    // part of the page immediately.
    if (empty($no_js_placeholders)) {
      print $pre_body;
      flush();
      return;
    }

    // Extract the scripts_bottom markup: the no-JS BigPipe placeholders that we
    // will render may attach additional asset libraries, and if so, it will be
    // necessary to re-render scripts_bottom.
    list($pre_scripts_bottom, $scripts_bottom, $post_scripts_bottom) = explode('<drupal-big-pipe-scripts-bottom-marker>', $pre_body, 3);
    $cumulative_assets_initial = clone $cumulative_assets;

    $this->sendNoJsPlaceholders($pre_scripts_bottom . $post_scripts_bottom, $no_js_placeholders, $cumulative_assets);

    // If additional asset libraries or drupalSettings were attached by any of
    // the placeholders, then we need to re-render scripts_bottom.
    if ($cumulative_assets_initial != $cumulative_assets) {
      // Create a new HtmlResponse. Ensure the CSS and (non-bottom) JS is sent
      // before the HTML they're associated with.
      // @see \Drupal\Core\Render\HtmlResponseSubscriber
      // @see template_preprocess_html()
      $js_bottom_placeholder = '<nojs-bigpipe-placeholder-scripts-bottom-placeholder token="' . Crypt::randomBytesBase64(55) . '">';

      $html_response = new HtmlResponse();
      $html_response->setContent([
        '#markup' => BigPipeMarkup::create($js_bottom_placeholder),
        '#attached' => [
          'drupalSettings' => $cumulative_assets->getSettings(),
          'library' => $cumulative_assets->getAlreadyLoadedLibraries(),
          'html_response_attachment_placeholders' => [
            'scripts_bottom' => $js_bottom_placeholder,
          ],
        ],
      ]);
      $html_response->getCacheableMetadata()->setCacheMaxAge(0);

      // Push a fake request with the asset libraries loaded so far and dispatch
      // KernelEvents::RESPONSE event. This results in the attachments for the
      // HTML response being processed by HtmlResponseAttachmentsProcessor and
      // hence the HTML to load the bottom JavaScript can be rendered.
      $fake_request = $this->requestStack->getMasterRequest()->duplicate();
      $html_response = $this->filterEmbeddedResponse($fake_request, $html_response);
      $scripts_bottom = $html_response->getContent();
    }

    print $scripts_bottom;
    flush();
  }

  /**
   * Sends no-JS BigPipe placeholders' replacements as embedded HTML responses.
   *
   * @param string $html
   *   HTML markup.
   * @param array $no_js_placeholders
   *   Associative array; the no-JS BigPipe placeholders. Keys are the BigPipe
   *   selectors.
   * @param \Drupal\Core\Asset\AttachedAssetsInterface $cumulative_assets
   *   The cumulative assets sent so far; to be updated while rendering no-JS
   *   BigPipe placeholders.
   *
   * @throws \Exception
   *   If an exception is thrown during the rendering of a placeholder, it is
   *   caught to allow the other placeholders to still be replaced. But when
   *   error logging is configured to be verbose, the exception is rethrown to
   *   simplify debugging.
   */
  protected function sendNoJsPlaceholders($html, $no_js_placeholders, AttachedAssetsInterface $cumulative_assets) {
    // Split the HTML on every no-JS placeholder string.
    $prepare_for_preg_split = function ($placeholder_string) {
      return '(' . preg_quote($placeholder_string, '/') . ')';
    };
    $preg_placeholder_strings = array_map($prepare_for_preg_split, array_keys($no_js_placeholders));
    $fragments = preg_split('/' . implode('|', $preg_placeholder_strings) . '/', $html, NULL, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);

    // Determine how many occurrences there are of each no-JS placeholder.
    $placeholder_occurrences = array_count_values(array_intersect($fragments, array_keys($no_js_placeholders)));

    // Set up a variable to store the content of placeholders that have multiple
    // occurrences.
    $multi_occurrence_placeholders_content = [];

    foreach ($fragments as $fragment) {
      // If the fragment isn't one of the no-JS placeholders, it is the HTML in
      // between placeholders and it must be printed & flushed immediately. The
      // rest of the logic in the loop handles the placeholders.
      if (!isset($no_js_placeholders[$fragment])) {
        print $fragment;
        flush();
        continue;
      }

      // If there are multiple occurrences of this particular placeholder, and
      // this is the second occurrence, we can skip all calculations and just
      // send the same content.
      if ($placeholder_occurrences[$fragment] > 1 && isset($multi_occurrence_placeholders_content[$fragment])) {
        print $multi_occurrence_placeholders_content[$fragment];
        flush();
        continue;
      }

      $placeholder = $fragment;
      assert('isset($no_js_placeholders[$placeholder])');
      $token = Crypt::randomBytesBase64(55);

      // Render the placeholder, but include the cumulative settings assets, so
      // we can calculate the overall settings for the entire page.
      $placeholder_plus_cumulative_settings = [
        'placeholder' => $no_js_placeholders[$placeholder],
        'cumulative_settings_' . $token => [
          '#attached' => [
            'drupalSettings' => $cumulative_assets->getSettings(),
          ],
        ],
      ];
      try {
        $elements = $this->renderPlaceholder($placeholder, $placeholder_plus_cumulative_settings);
      }
      catch (\Exception $e) {
        if ($this->configFactory->get('system.logging')->get('error_level') === ERROR_REPORTING_DISPLAY_VERBOSE) {
          throw $e;
        }
        else {
          trigger_error($e, E_USER_ERROR);
          continue;
        }
      }


      // Create a new HtmlResponse. Ensure the CSS and (non-bottom) JS is sent
      // before the HTML they're associated with. In other words: ensure the
      // critical assets for this placeholder's markup are loaded first.
      // @see \Drupal\Core\Render\HtmlResponseSubscriber
      // @see template_preprocess_html()
      $css_placeholder = '<nojs-bigpipe-placeholder-styles-placeholder token="' . $token . '">';
      $js_placeholder = '<nojs-bigpipe-placeholder-scripts-placeholder token="' . $token . '">';
      $elements['#markup'] = BigPipeMarkup::create($css_placeholder . $js_placeholder . (string) $elements['#markup']);
      $elements['#attached']['html_response_attachment_placeholders']['styles'] = $css_placeholder;
      $elements['#attached']['html_response_attachment_placeholders']['scripts'] = $js_placeholder;

      $html_response = new HtmlResponse();
      $html_response->setContent($elements);
      $html_response->getCacheableMetadata()->setCacheMaxAge(0);

      // Push a fake request with the asset libraries loaded so far and dispatch
      // KernelEvents::RESPONSE event. This results in the attachments for the
      // HTML response being processed by HtmlResponseAttachmentsProcessor and
      // hence:
      // - the HTML to load the CSS can be rendered.
      // - the HTML to load the JS (at the top) can be rendered.
      $fake_request = $this->requestStack->getMasterRequest()->duplicate();
      $fake_request->request->set('ajax_page_state', ['libraries' => implode(',', $cumulative_assets->getAlreadyLoadedLibraries())]);
      try {
        $html_response = $this->filterEmbeddedResponse($fake_request, $html_response);
      }
      catch (\Exception $e) {
        if ($this->configFactory->get('system.logging')->get('error_level') === ERROR_REPORTING_DISPLAY_VERBOSE) {
          throw $e;
        }
        else {
          trigger_error($e, E_USER_ERROR);
          continue;
        }
      }


      // Send this embedded HTML response.
      print $html_response->getContent();
      flush();

      // Another placeholder was rendered and sent, track the set of asset
      // libraries sent so far. Any new settings also need to be tracked, so
      // they can be sent in ::sendPreBody().
      $cumulative_assets->setAlreadyLoadedLibraries(array_merge($cumulative_assets->getAlreadyLoadedLibraries(), $html_response->getAttachments()['library']));
      $cumulative_assets->setSettings($html_response->getAttachments()['drupalSettings']);

      // If there are multiple occurrences of this particular placeholder, track
      // the content that was sent, so we can skip all calculations for the next
      // occurrence.
      if ($placeholder_occurrences[$fragment] > 1) {
        $multi_occurrence_placeholders_content[$fragment] = $html_response->getContent();
      }
    }
  }

  /**
   * Sends BigPipe placeholders' replacements as embedded AJAX responses.
   *
   * @param array $placeholders
   *   Associative array; the BigPipe placeholders. Keys are the BigPipe
   *   placeholder IDs.
   * @param array $placeholder_order
   *   Indexed array; the order in which the BigPipe placeholders must be sent.
   *   Values are the BigPipe placeholder IDs. (These values correspond to keys
   *   in $placeholders.)
   * @param \Drupal\Core\Asset\AttachedAssetsInterface $cumulative_assets
   *   The cumulative assets sent so far; to be updated while rendering BigPipe
   *   placeholders.
   *
   * @throws \Exception
   *   If an exception is thrown during the rendering of a placeholder, it is
   *   caught to allow the other placeholders to still be replaced. But when
   *   error logging is configured to be verbose, the exception is rethrown to
   *   simplify debugging.
   */
  protected function sendPlaceholders(array $placeholders, array $placeholder_order, AttachedAssetsInterface $cumulative_assets) {
    // Return early if there are no BigPipe placeholders to send.
    if (empty($placeholders)) {
      return;
    }

    // Send the start signal.
    print "\n";
    print static::START_SIGNAL;
    print "\n";
    flush();

    // A BigPipe response consists of a HTML response plus multiple embedded
    // AJAX responses. To process the attachments of those AJAX responses, we
    // need a fake request that is identical to the master request, but with
    // one change: it must have the right Accept header, otherwise the work-
    // around for a bug in IE9 will cause not JSON, but <textarea>-wrapped JSON
    // to be returned.
    // @see \Drupal\Core\EventSubscriber\AjaxResponseSubscriber::onResponse()
    $fake_request = $this->requestStack->getMasterRequest()->duplicate();
    $fake_request->headers->set('Accept', 'application/vnd.drupal-ajax');

    foreach ($placeholder_order as $placeholder_id) {
      if (!isset($placeholders[$placeholder_id])) {
        continue;
      }

      // Render the placeholder.
      $placeholder_render_array = $placeholders[$placeholder_id];
      try {
        $elements = $this->renderPlaceholder($placeholder_id, $placeholder_render_array);
      }
      catch (\Exception $e) {
        if ($this->configFactory->get('system.logging')->get('error_level') === ERROR_REPORTING_DISPLAY_VERBOSE) {
          throw $e;
        }
        else {
          trigger_error($e, E_USER_ERROR);
          continue;
        }
      }

      // Create a new AjaxResponse.
      $ajax_response = new AjaxResponse();
      // JavaScript's querySelector automatically decodes HTML entities in
      // attributes, so we must decode the entities of the current BigPipe
      // placeholder ID (which has HTML entities encoded since we use it to find
      // the placeholders).
      $big_pipe_js_placeholder_id = Html::decodeEntities($placeholder_id);
      $ajax_response->addCommand(new ReplaceCommand(sprintf('[data-big-pipe-placeholder-id="%s"]', $big_pipe_js_placeholder_id), $elements['#markup']));
      $ajax_response->setAttachments($elements['#attached']);

      // Push a fake request with the asset libraries loaded so far and dispatch
      // KernelEvents::RESPONSE event. This results in the attachments for the
      // AJAX response being processed by AjaxResponseAttachmentsProcessor and
      // hence:
      // - the necessary AJAX commands to load the necessary missing asset
      //   libraries and updated AJAX page state are added to the AJAX response
      // - the attachments associated with the response are finalized, which
      //   allows us to track the total set of asset libraries sent in the
      //   initial HTML response plus all embedded AJAX responses sent so far.
      $fake_request->request->set('ajax_page_state', ['libraries' => implode(',', $cumulative_assets->getAlreadyLoadedLibraries())] + $cumulative_assets->getSettings()['ajaxPageState']);
      try {
        $ajax_response = $this->filterEmbeddedResponse($fake_request, $ajax_response);
      }
      catch (\Exception $e) {
        if ($this->configFactory->get('system.logging')->get('error_level') === ERROR_REPORTING_DISPLAY_VERBOSE) {
          throw $e;
        }
        else {
          trigger_error($e, E_USER_ERROR);
          continue;
        }
      }

      // Send this embedded AJAX response.
      $json = $ajax_response->getContent();
      $output = <<<EOF
    <script type="application/vnd.drupal-ajax" data-big-pipe-replacement-for-placeholder-with-id="$placeholder_id">
    $json
    </script>
EOF;
      print $output;
      flush();

      // Another placeholder was rendered and sent, track the set of asset
      // libraries sent so far. Any new settings are already sent; we don't need
      // to track those.
      if (isset($ajax_response->getAttachments()['drupalSettings']['ajaxPageState']['libraries'])) {
        $cumulative_assets->setAlreadyLoadedLibraries(explode(',', $ajax_response->getAttachments()['drupalSettings']['ajaxPageState']['libraries']));
      }
    }

    // Send the stop signal.
    print "\n";
    print static::STOP_SIGNAL;
    print "\n";
    flush();
  }

  /**
   * Filters the given embedded response, using the cumulative AJAX page state.
   *
   * @param \Symfony\Component\HttpFoundation\Request $fake_request
   *   A fake subrequest that contains the cumulative AJAX page state of the
   *   HTML document and all preceding Embedded HTML or AJAX responses.
   * @param \Symfony\Component\HttpFoundation\Response|\Drupal\Core\Render\HtmlResponse|\Drupal\Core\Ajax\AjaxResponse $embedded_response
   *   Either a HTML response or an AJAX response that will be embedded in the
   *   overall HTML response.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   The filtered response, which will load only the assets that $fake_request
   *   did not indicate to already have been loaded, plus the updated cumulative
   *   AJAX page state.
   */
  protected function filterEmbeddedResponse(Request $fake_request, Response $embedded_response) {
    assert('$embedded_response instanceof \Drupal\Core\Render\HtmlResponse || $embedded_response instanceof \Drupal\Core\Ajax\AjaxResponse');
    $this->requestStack->push($fake_request);
    $event = new FilterResponseEvent($this->httpKernel, $fake_request, HttpKernelInterface::SUB_REQUEST, $embedded_response);
    $this->eventDispatcher->dispatch(KernelEvents::RESPONSE, $event);
    $filtered_response = $event->getResponse();
    $this->requestStack->pop();
    return $filtered_response;
  }

  /**
   * Sends </body> and everything after it.
   *
   * @param string $post_body
   *   The HTML response's content after the closing </body> tag.
   */
  protected function sendPostBody($post_body) {
    print '</body>';
    print $post_body;
    flush();
  }

  /**
   * Renders a placeholder, and just that placeholder.
   *
   * BigPipe renders placeholders independently of the rest of the content, so
   * it needs to be able to render placeholders by themselves.
   *
   * @param string $placeholder
   *   The placeholder to render.
   * @param array $placeholder_render_array
   *   The render array associated with that placeholder.
   *
   * @return array
   *   The render array representing the rendered placeholder.
   *
   * @see \Drupal\Core\Render\RendererInterface::renderPlaceholder()
   */
  protected function renderPlaceholder($placeholder, array $placeholder_render_array) {
    $elements = [
      '#markup' => $placeholder,
      '#attached' => [
        'placeholders' => [
          $placeholder => $placeholder_render_array,
        ],
      ],
    ];
    return $this->renderer->renderPlaceholder($placeholder, $elements);
  }

  /**
   * Gets the BigPipe placeholder order.
   *
   * Determines the order in which BigPipe placeholders must be replaced.
   *
   * @param string $html
   *   HTML markup.
   *
   * @return array
   *   Indexed array; the order in which the BigPipe placeholders must be sent.
   *   Values are the BigPipe placeholder IDs. Note that only unique
   *   placeholders are kept: if the same placeholder occurs multiple times, we
   *   only keep the first occurrence.
   */
  protected function getPlaceholderOrder($html) {
    $fragments = explode('<div data-big-pipe-placeholder-id="', $html);
    array_shift($fragments);
    $order = [];

    foreach ($fragments as $fragment) {
      $t = explode('"></div>', $fragment, 2);
      $placeholder = $t[0];
      $order[] = $placeholder;
    }

    return array_unique($order);
  }

}
