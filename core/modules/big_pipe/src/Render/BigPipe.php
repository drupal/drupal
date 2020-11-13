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
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Service for sending an HTML response in chunks (to get faster page loads).
 *
 * At a high level, BigPipe sends an HTML response in chunks:
 * 1. one chunk: everything until just before </body> — this contains BigPipe
 *    placeholders for the personalized parts of the page. Hence this sends the
 *    non-personalized parts of the page. Let's call it The Skeleton.
 * 2. N chunks: a <script> tag per BigPipe placeholder in The Skeleton.
 * 3. one chunk: </body> and everything after it.
 *
 * This is conceptually identical to Facebook's BigPipe (hence the name).
 *
 * @see https://www.facebook.com/notes/facebook-engineering/bigpipe-pipelining-web-pages-for-high-performance/389414033919
 *
 * The major way in which Drupal differs from Facebook's implementation (and
 * others) is in its ability to automatically figure out which parts of the page
 * can benefit from BigPipe-style delivery. Drupal's render system has the
 * concept of "auto-placeholdering": content that is too dynamic is replaced
 * with a placeholder that can then be rendered at a later time. On top of that,
 * it also has the concept of "placeholder strategies": by default, placeholders
 * are replaced on the server side and the response is blocked on all of them
 * being replaced. But it's possible to add additional placeholder strategies.
 * BigPipe is just another placeholder strategy. Others could be ESI, AJAX …
 *
 * @see https://www.drupal.org/developing/api/8/render/arrays/cacheability/auto-placeholdering
 * @see \Drupal\Core\Render\PlaceholderGeneratorInterface::shouldAutomaticallyPlaceholder()
 * @see \Drupal\Core\Render\Placeholder\PlaceholderStrategyInterface
 * @see \Drupal\Core\Render\Placeholder\SingleFlushStrategy
 * @see \Drupal\big_pipe\Render\Placeholder\BigPipeStrategy
 *
 * There is also one noteworthy technical addition that Drupal makes. BigPipe as
 * described above, and as implemented by Facebook, can only work if JavaScript
 * is enabled. The BigPipe module also makes it possible to replace placeholders
 * using BigPipe in-situ, without JavaScript. This is not technically BigPipe at
 * all; it's just the use of multiple flushes. Since it is able to reuse much of
 * the logic though, we choose to call this "no-JS BigPipe".
 *
 * However, there is also a tangible benefit: some dynamic/expensive content is
 * not HTML, but for example an HTML attribute value (or part thereof). It's not
 * possible to efficiently replace such content using JavaScript, so "classic"
 * BigPipe is out of the question. For example: CSRF tokens in URLs.
 *
 * This allows us to use both no-JS BigPipe and "classic" BigPipe in the same
 * response to maximize the amount of content we can send as early as possible.
 *
 * Finally, a closer look at the implementation, and how it supports and reuses
 * existing Drupal concepts:
 * 1. BigPipe placeholders: 1 HtmlResponse + N embedded AjaxResponses.
 *   - Before a BigPipe response is sent, it is just an HTML response that
 *     contains BigPipe placeholders. Those placeholders look like
 *     <span data-big-pipe-placeholder-id="…"></span>. JavaScript is used to
 *     replace those placeholders.
 *     Therefore these placeholders are actually sent to the client.
 *   - The Skeleton of course has attachments, including most notably asset
 *     libraries. And those we track in drupalSettings.ajaxPageState.libraries —
 *     so that when we load new content through AJAX, we don't load the same
 *     asset libraries again. An HTML page can have multiple AJAX responses,
 *     each of which should take into account the combined AJAX page state of
 *     the HTML document and all preceding AJAX responses.
 *   - BigPipe does not make use of multiple AJAX requests/responses. It uses a
 *     single HTML response. But it is a more long-lived one: The Skeleton is
 *     sent first, the closing </body> tag is not yet sent, and the connection
 *     is kept open. Whenever another BigPipe Placeholder is rendered, Drupal
 *     sends (and so actually appends to the already-sent HTML) something like
 *     <script type="application/vnd.drupal-ajax">[{"command":"settings","settings":{…}}, {"command":…}.
 *   - So, for every BigPipe placeholder, we send such a <script
 *     type="application/vnd.drupal-ajax"> tag. And the contents of that tag is
 *     exactly like an AJAX response. The BigPipe module has JavaScript that
 *     listens for these and applies them. Let's call it an Embedded AJAX
 *     Response (since it is embedded in the HTML response). Now for the
 *     interesting bit: each of those Embedded AJAX Responses must also take
 *     into account the cumulative AJAX page state of the HTML document and all
 *     preceding Embedded AJAX responses.
 * 2. No-JS BigPipe placeholders: 1 HtmlResponse + N embedded HtmlResponses.
 *   - Before a BigPipe response is sent, it is just a HTML response that
 *     contains no-JS BigPipe placeholders. Those placeholders can take two
 *     different forms:
 *     1. <span data-big-pipe-nojs-placeholder-id="…"></span> if it's a
 *        placeholder that will be replaced by HTML
 *     2. big_pipe_nojs_placeholder_attribute_safe:… if it's a placeholder
 *        inside an HTML attribute, in which 1. would be invalid (angle brackets
 *        are not allowed inside HTML attributes)
 *     No-JS BigPipe placeholders are not replaced using JavaScript, they must
 *     be replaced upon sending the BigPipe response. So, while the response is
 *     being sent, upon encountering these placeholders, their corresponding
 *     placeholder replacements are sent instead.
 *     Therefore these placeholders are never actually sent to the client.
 *   - See second bullet of point 1.
 *   - No-JS BigPipe does not use multiple AJAX requests/responses. It uses a
 *     single HTML response. But it is a more long-lived one: The Skeleton is
 *     split into multiple parts, the separators are where the no-JS BigPipe
 *     placeholders used to be. Whenever another no-JS BigPipe placeholder is
 *     rendered, Drupal sends (and so actually appends to the already-sent HTML)
 *     something like
 *     <link rel="stylesheet" …><script …><content>.
 *   - So, for every no-JS BigPipe placeholder, we send its associated CSS and
 *     header JS that has not already been sent (the bottom JS is not yet sent,
 *     so we can accumulate all of it and send it together at the end). This
 *     ensures that the markup is rendered as it was originally intended: its
 *     CSS and JS used to be blocking, and it still is. Let's call it an
 *     Embedded HTML response. Each of those Embedded HTML Responses must also
 *     take into account the cumulative AJAX page state of the HTML document and
 *     all preceding Embedded HTML responses.
 *   - Finally: any non-critical JavaScript associated with all Embedded HTML
 *     Responses, i.e. any footer/bottom/non-header JavaScript, is loaded after
 *     The Skeleton.
 *
 * Combining all of the above, when using both BigPipe placeholders and no-JS
 * BigPipe placeholders, we therefore send: 1 HtmlResponse + M Embedded HTML
 * Responses + N Embedded AJAX Responses. Schematically, we send these chunks:
 *  1. Byte zero until 1st no-JS placeholder: headers + <html><head /><span>…</span>
 *  2. 1st no-JS placeholder replacement: <link rel="stylesheet" …><script …><content>
 *  3. Content until 2nd no-JS placeholder: <span>…</span>
 *  4. 2nd no-JS placeholder replacement: <link rel="stylesheet" …><script …><content>
 *  5. Content until 3rd no-JS placeholder: <span>…</span>
 *  6. [… repeat until all no-JS placeholder replacements are sent …]
 *  7. Send content after last no-JS placeholder.
 *  8. Send script_bottom (markup to load bottom i.e. non-critical JS).
 *  9. 1st placeholder replacement: <script type="application/vnd.drupal-ajax">[{"command":"settings","settings":{…}}, {"command":…}
 * 10. 2nd placeholder replacement: <script type="application/vnd.drupal-ajax">[{"command":"settings","settings":{…}}, {"command":…}
 * 11. [… repeat until all placeholder replacements are sent …]
 * 12. Send </body> and everything after it.
 * 13. Terminate request/response cycle.
 *
 * @see \Drupal\big_pipe\EventSubscriber\HtmlResponseBigPipeSubscriber
 * @see \Drupal\big_pipe\Render\Placeholder\BigPipeStrategy
 */
class BigPipe {

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
   * Performs tasks before sending content (and rendering placeholders).
   */
  protected function performPreSendTasks() {
    // The content in the placeholders may depend on the session, and by the
    // time the response is sent (see index.php), the session is already
    // closed. Reopen it for the duration that we are rendering placeholders.
    $this->session->start();
  }

  /**
   * Performs tasks after sending content (and rendering placeholders).
   */
  protected function performPostSendTasks() {
    // Close the session again.
    $this->session->save();
  }

  /**
   * Sends a chunk.
   *
   * @param string|\Drupal\Core\Render\HtmlResponse $chunk
   *   The string or response to append. String if there's no cacheability
   *   metadata or attachments to merge.
   */
  protected function sendChunk($chunk) {
    assert(is_string($chunk) || $chunk instanceof HtmlResponse);
    if ($chunk instanceof HtmlResponse) {
      print $chunk->getContent();
    }
    else {
      print $chunk;
    }
    flush();
  }

  /**
   * Sends an HTML response in chunks using the BigPipe technique.
   *
   * @param \Drupal\big_pipe\Render\BigPipeResponse $response
   *   The BigPipe response to send.
   *
   * @internal
   *   This method should only be invoked by
   *   \Drupal\big_pipe\Render\BigPipeResponse, which is itself an internal
   *   class.
   */
  public function sendContent(BigPipeResponse $response) {
    $content = $response->getContent();
    $attachments = $response->getAttachments();

    // First, gather the BigPipe placeholders that must be replaced.
    $placeholders = isset($attachments['big_pipe_placeholders']) ? $attachments['big_pipe_placeholders'] : [];
    $nojs_placeholders = isset($attachments['big_pipe_nojs_placeholders']) ? $attachments['big_pipe_nojs_placeholders'] : [];

    // BigPipe sends responses using "Transfer-Encoding: chunked". To avoid
    // sending already-sent assets, it is necessary to track cumulative assets
    // from all previously rendered/sent chunks.
    // @see http://www.w3.org/Protocols/rfc2616/rfc2616-sec14.html#sec14.41
    $cumulative_assets = AttachedAssets::createFromRenderArray(['#attached' => $attachments]);
    $cumulative_assets->setAlreadyLoadedLibraries($attachments['library']);

    $this->performPreSendTasks();

    // Find the closing </body> tag and get the strings before and after. But be
    // careful to use the latest occurrence of the string "</body>", to ensure
    // that strings in inline JavaScript or CDATA sections aren't used instead.
    $parts = explode('</body>', $content);
    $post_body = array_pop($parts);
    $pre_body = implode('', $parts);

    $this->sendPreBody($pre_body, $nojs_placeholders, $cumulative_assets);
    $this->sendPlaceholders($placeholders, $this->getPlaceholderOrder($pre_body, $placeholders), $cumulative_assets);
    $this->sendPostBody($post_body);

    $this->performPostSendTasks();
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
      $this->sendChunk($pre_body);
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

    $this->sendChunk($scripts_bottom);
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
    $placeholder_strings = array_keys($no_js_placeholders);
    $fragments = static::splitHtmlOnPlaceholders($html, $placeholder_strings);

    // Determine how many occurrences there are of each no-JS placeholder.
    $placeholder_occurrences = array_count_values(array_intersect($fragments, $placeholder_strings));

    // Set up a variable to store the content of placeholders that have multiple
    // occurrences.
    $multi_occurrence_placeholders_content = [];

    foreach ($fragments as $fragment) {
      // If the fragment isn't one of the no-JS placeholders, it is the HTML in
      // between placeholders and it must be printed & flushed immediately. The
      // rest of the logic in the loop handles the placeholders.
      if (!isset($no_js_placeholders[$fragment])) {
        $this->sendChunk($fragment);
        continue;
      }

      // If there are multiple occurrences of this particular placeholder, and
      // this is the second occurrence, we can skip all calculations and just
      // send the same content.
      if ($placeholder_occurrences[$fragment] > 1 && isset($multi_occurrence_placeholders_content[$fragment])) {
        $this->sendChunk($multi_occurrence_placeholders_content[$fragment]);
        continue;
      }

      $placeholder = $fragment;
      assert(isset($no_js_placeholders[$placeholder]));
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
      $this->sendChunk($html_response);

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
    $this->sendChunk("\n" . static::START_SIGNAL . "\n");

    // A BigPipe response consists of an HTML response plus multiple embedded
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
      $this->sendChunk($output);

      // Another placeholder was rendered and sent, track the set of asset
      // libraries sent so far. Any new settings are already sent; we don't need
      // to track those.
      if (isset($ajax_response->getAttachments()['drupalSettings']['ajaxPageState']['libraries'])) {
        $cumulative_assets->setAlreadyLoadedLibraries(explode(',', $ajax_response->getAttachments()['drupalSettings']['ajaxPageState']['libraries']));
      }
    }

    // Send the stop signal.
    $this->sendChunk("\n" . static::STOP_SIGNAL . "\n");
  }

  /**
   * Filters the given embedded response, using the cumulative AJAX page state.
   *
   * @param \Symfony\Component\HttpFoundation\Request $fake_request
   *   A fake subrequest that contains the cumulative AJAX page state of the
   *   HTML document and all preceding Embedded HTML or AJAX responses.
   * @param \Symfony\Component\HttpFoundation\Response|\Drupal\Core\Render\HtmlResponse|\Drupal\Core\Ajax\AjaxResponse $embedded_response
   *   Either an HTML response or an AJAX response that will be embedded in the
   *   overall HTML response.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   The filtered response, which will load only the assets that $fake_request
   *   did not indicate to already have been loaded, plus the updated cumulative
   *   AJAX page state.
   */
  protected function filterEmbeddedResponse(Request $fake_request, Response $embedded_response) {
    assert($embedded_response instanceof HtmlResponse || $embedded_response instanceof AjaxResponse);
    return $this->filterResponse($fake_request, HttpKernelInterface::SUB_REQUEST, $embedded_response);
  }

  /**
   * Filters the given response.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request for which a response is being sent.
   * @param int $request_type
   *   The request type. Can either be
   *   \Symfony\Component\HttpKernel\HttpKernelInterface::MASTER_REQUEST or
   *   \Symfony\Component\HttpKernel\HttpKernelInterface::SUB_REQUEST.
   * @param \Symfony\Component\HttpFoundation\Response $response
   *   The response to filter.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   The filtered response.
   */
  protected function filterResponse(Request $request, $request_type, Response $response) {
    assert($request_type === HttpKernelInterface::MASTER_REQUEST || $request_type === HttpKernelInterface::SUB_REQUEST);
    $this->requestStack->push($request);
    $event = new ResponseEvent($this->httpKernel, $request, $request_type, $response);
    $this->eventDispatcher->dispatch($event, KernelEvents::RESPONSE);
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
    $this->sendChunk('</body>' . $post_body);
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
   * @param array $placeholders
   *   Associative array; the BigPipe placeholders. Keys are the BigPipe
   *   placeholder IDs.
   *
   * @return array
   *   Indexed array; the order in which the BigPipe placeholders must be sent.
   *   Values are the BigPipe placeholder IDs. Note that only unique
   *   placeholders are kept: if the same placeholder occurs multiple times, we
   *   only keep the first occurrence.
   */
  protected function getPlaceholderOrder($html, $placeholders) {
    $fragments = explode('<span data-big-pipe-placeholder-id="', $html);
    array_shift($fragments);
    $placeholder_ids = [];

    foreach ($fragments as $fragment) {
      $t = explode('"></span>', $fragment, 2);
      $placeholder_id = $t[0];
      $placeholder_ids[] = $placeholder_id;
    }
    $placeholder_ids = array_unique($placeholder_ids);

    // The 'status messages' placeholder needs to be special cased, because it
    // depends on global state that can be modified when other placeholders are
    // being rendered: any code can add messages to render.
    // This violates the principle that each lazy builder must be able to render
    // itself in isolation, and therefore in any order. However, we cannot
    // change the way \Drupal\Core\Messenger\MessengerInterface::addMessage()
    // works in the Drupal 8 cycle. So we have to accommodate its special needs.
    // Allowing placeholders to be rendered in a particular order (in this case:
    // last) would violate this isolation principle. Thus a monopoly is granted
    // to this one special case, with this hard-coded solution.
    // @see \Drupal\Core\Render\Element\StatusMessages
    // @see \Drupal\Core\Render\Renderer::replacePlaceholders()
    // @see https://www.drupal.org/node/2712935#comment-11368923
    $message_placeholder_ids = [];
    foreach ($placeholders as $placeholder_id => $placeholder_element) {
      if (isset($placeholder_element['#lazy_builder']) && $placeholder_element['#lazy_builder'][0] === 'Drupal\Core\Render\Element\StatusMessages::renderMessages') {
        $message_placeholder_ids[] = $placeholder_id;
      }
    }

    // Return placeholder IDs in DOM order, but with the 'status messages'
    // placeholders at the end, if they are present.
    $ordered_placeholder_ids = array_merge(
      array_diff($placeholder_ids, $message_placeholder_ids),
      array_intersect($placeholder_ids, $message_placeholder_ids)
    );
    return $ordered_placeholder_ids;
  }

  /**
   * Splits an HTML string into fragments.
   *
   * Creates an array of HTML fragments, separated by placeholders. The result
   * includes the placeholders themselves. The original order is respected.
   *
   * @param string $html_string
   *   The HTML to split.
   * @param string[] $html_placeholders
   *   The HTML placeholders to split on.
   *
   * @return string[]
   *   The resulting HTML fragments.
   */
  private static function splitHtmlOnPlaceholders($html_string, array $html_placeholders) {
    $prepare_for_preg_split = function ($placeholder_string) {
      return '(' . preg_quote($placeholder_string, '/') . ')';
    };
    $preg_placeholder_strings = array_map($prepare_for_preg_split, $html_placeholders);
    $pattern = '/' . implode('|', $preg_placeholder_strings) . '/';
    if (strlen($pattern) < 31000) {
      // Only small (<31K characters) patterns can be handled by preg_split().
      $flags = PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE;
      $result = preg_split($pattern, $html_string, NULL, $flags);
    }
    else {
      // For large amounts of placeholders we use a simpler but slower approach.
      foreach ($html_placeholders as $placeholder) {
        $html_string = str_replace($placeholder, "\x1F" . $placeholder . "\x1F", $html_string);
      }
      $result = array_filter(explode("\x1F", $html_string));
    }
    return $result;
  }

}
