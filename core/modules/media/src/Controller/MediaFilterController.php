<?php

namespace Drupal\media\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\CsrfTokenGenerator;
use Drupal\filter\FilterFormatInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Controller which renders a preview of the provided text.
 *
 * @internal
 *   This is an internal part of the media system in Drupal core and may be
 *   subject to change in minor releases. This class should not be
 *   instantiated or extended by external code.
 */
class MediaFilterController extends ControllerBase {

  public function __construct(
    protected RendererInterface $renderer,
    protected EntityRepositoryInterface $entityRepository,
    protected CsrfTokenGenerator $csrfToken,
  ) {}

  /**
   * Returns a HTML response containing a preview of the text after filtering.
   *
   * Applies all of the given text format's filters, not just the `media_embed`
   * filter, because for example `filter_align` and `filter_caption` may apply
   * to it as well.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   * @param \Drupal\filter\FilterFormatInterface $filter_format
   *   The text format.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   The filtered text.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
   *   Throws an exception if 'text' parameter is not found in the query
   *   string.
   *
   * @see \Drupal\editor\EditorController::getUntransformedText
   */
  public function preview(Request $request, FilterFormatInterface $filter_format) {
    self::checkCsrf($request, $this->currentUser(), $this->csrfToken);

    $text = $request->query->get('text');
    $uuid = $request->query->get('uuid');
    if ($text == '' || $uuid == '') {
      throw new NotFoundHttpException();
    }

    $build = [
      '#type' => 'processed_text',
      '#text' => $text,
      '#format' => $filter_format->id(),
    ];
    $html = $this->renderer->renderInIsolation($build);

    // Load the media item so we can embed the label in the response, for use
    // in an ARIA label.
    $headers = [];
    if ($media = $this->entityRepository->loadEntityByUuid('media', $uuid)) {
      $headers['Drupal-Media-Label'] = $this->entityRepository->getTranslationFromContext($media)->label();
    }

    // Note that we intentionally do not use:
    // - \Drupal\Core\Cache\CacheableResponse because caching it on the server
    //   side is wasteful, hence there is no need for cacheability metadata.
    // - \Drupal\Core\Render\HtmlResponse because there is no need for
    //   attachments nor cacheability metadata.
    return (new Response($html, 200, $headers))
      // Do not allow any intermediary to cache the response, only the end user.
      ->setPrivate()
      // Allow the end user to cache it for up to 5 minutes.
      ->setMaxAge(300);
  }

  /**
   * Checks access based on media_embed filter status on the text format.
   *
   * @param \Drupal\filter\FilterFormatInterface $filter_format
   *   The text format for which to check access.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public static function formatUsesMediaEmbedFilter(FilterFormatInterface $filter_format) {
    $filters = $filter_format->filters();
    return AccessResult::allowedIf($filters->has('media_embed') && $filters->get('media_embed')->status)
      ->addCacheableDependency($filter_format);
  }

  /**
   * Throws an AccessDeniedHttpException if the request fails CSRF validation.
   *
   * This is used instead of \Drupal\Core\Access\CsrfAccessCheck, in order to
   * allow access for anonymous users.
   *
   * @todo Refactor this to an access checker.
   */
  private static function checkCsrf(Request $request, AccountInterface $account, CsrfTokenGenerator $csrf_token) {
    $header = 'X-Drupal-MediaPreview-CSRF-Token';

    if (!$request->headers->has($header)) {
      throw new AccessDeniedHttpException();
    }
    if ($account->isAnonymous()) {
      // For anonymous users, just the presence of the custom header is
      // sufficient protection.
      return;
    }
    // For authenticated users, validate the token value.
    $token = $request->headers->get($header);
    if (!$csrf_token->validate($token, $header)) {
      throw new AccessDeniedHttpException();
    }
  }

}
