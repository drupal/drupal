<?php

namespace Drupal\big_pipe\Render;

/**
 * Interface for sending an HTML response in chunks (to get faster page loads).
 *
 * At a high level, BigPipe sends a HTML response in chunks:
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
 * not HTML, but for example a HTML attribute value (or part thereof). It's not
 * possible to efficiently replace such content using JavaScript, so "classic"
 * BigPipe is out of the question. For example: CSRF tokens in URLs.
 *
 * This allows us to use both no-JS BigPipe and "classic" BigPipe in the same
 * response to maximize the amount of content we can send as early as possible.
 *
 * Finally, a closer look at the implementation, and how it supports and reuses
 * existing Drupal concepts:
 * 1. BigPipe placeholders: 1 HtmlResponse + N embedded AjaxResponses.
 *   - Before a BigPipe response is sent, it is just a HTML response that
 *     contains BigPipe placeholders. Those placeholders look like
 *     <div data-big-pipe-placeholder-id="…"></div>. JavaScript is used to
 *     replace those placeholders.
 *     Therefore these placeholders are actually sent to the client.
 *   - The Skeleton of course has attachments, including most notably asset
 *     libraries. And those we track in drupalSettings.ajaxPageState.libraries —
 *     so that when we load new content through AJAX, we don't load the same
 *     asset libraries again. A HTML page can have multiple AJAX responses, each
 *     of which should take into account the combined AJAX page state of the
 *     HTML document and all preceding AJAX responses.
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
 *     1. <div data-big-pipe-nojs-placeholder-id="…"></div> if it's a
 *        placeholder that will be replaced by HTML
 *     2. big_pipe_nojs_placeholder_attribute_safe:… if it's a placeholder
 *        inside a HTML attribute, in which 1. would be invalid (angle brackets
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
 *  1. Byte zero until 1st no-JS placeholder: headers + <html><head /><div>…</div>
 *  2. 1st no-JS placeholder replacement: <link rel="stylesheet" …><script …><content>
 *  3. Content until 2nd no-JS placeholder: <div>…</div>
 *  4. 2nd no-JS placeholder replacement: <link rel="stylesheet" …><script …><content>
 *  5. Content until 3rd no-JS placeholder: <div>…</div>
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
interface BigPipeInterface {

  /**
   * Sends an HTML response in chunks using the BigPipe technique.
   *
   * @param string $content
   *   The HTML response content to send.
   * @param array $attachments
   *   The HTML response's attachments.
   */
  public function sendContent($content, array $attachments);

}
