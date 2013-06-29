<?php

/**
 * @file
 * Contains \Drupal\comment\Controller\CommentController.
 */

namespace Drupal\comment\Controller;

use Drupal\comment\CommentInterface;
use Drupal\comment\Plugin\Core\Entity\Comment;
use Drupal\Core\Controller\ControllerInterface;
use Drupal\Core\Routing\PathBasedGeneratorInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * Controller for the comment entity.
 *
 * @see \Drupal\comment\Plugin\Core\Entity\Comment.
 */
class CommentController implements ControllerInterface {

  /**
   * The url generator service.
   *
   * @var \Drupal\Core\Routing\PathBasedGeneratorInterface
   */
  protected $urlGenerator;

  /**
   * The HTTP kernel.
   *
   * @var \Symfony\Component\HttpKernel\HttpKernelInterface
   */
  protected $httpKernel;

  /**
   * Constructs a CommentController object.
   *
   * @param \Drupal\Core\Routing\PathBasedGeneratorInterface $url_generator
   *   The url generator service.
   * @param \Symfony\Component\HttpKernel\HttpKernelInterface $httpKernel
   *   HTTP kernel to handle requests.
   */
  public function __construct(PathBasedGeneratorInterface $url_generator, HttpKernelInterface $httpKernel) {
    $this->urlGenerator = $url_generator;
    $this->httpKernel = $httpKernel;
  }
  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('url_generator'),
      $container->get('http_kernel')
    );
  }

  /**
   * Publishes the specified comment.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   * @param \Drupal\comment\CommentInterface $comment
   *   A comment entity.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
   * @return \Symfony\Component\HttpFoundation\RedirectResponse.
   */
  public function commentApprove(Request $request, CommentInterface $comment) {
    // @todo CRSF tokens are validated in the content controller until it gets
    //   moved to the access layer:
    //   Integrate CSRF link token directly into routing system:
    //   https://drupal.org/node/1798296.
    $token = $request->query->get('token');
    if (!isset($token) || !drupal_valid_token($token, 'comment/' . $comment->id() . '/approve')) {
      throw new AccessDeniedHttpException();
    }

    $comment->status->value = COMMENT_PUBLISHED;
    $comment->save();

    drupal_set_message(t('Comment approved.'));
    $permalink_uri = $comment->permalink();
    $permalink_uri['options']['absolute'] = TRUE;
    $url = $this->urlGenerator->generateFromPath($permalink_uri['path'], $permalink_uri['options']);
    return new RedirectResponse($url);
  }

  /**
   * Redirects comment links to the correct page depending on comment settings.
   *
   * Since comments are paged there is no way to guarantee which page a comment
   * appears on. Comment paging and threading settings may be changed at any
   * time. With threaded comments, an individual comment may move between pages
   * as comments can be added either before or after it in the overall
   * discussion. Therefore we use a central routing function for comment links,
   * which calculates the page number based on current comment settings and
   * returns the full comment view with the pager set dynamically.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request of the page.
   * @param \Drupal\comment\CommentInterface $comment
   *   A comment entity.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
   * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   The comment listing set to the page on which the comment appears.
   */
  public function commentPermalink(Request $request, CommentInterface $comment) {
    if ($node = $comment->nid->entity) {
      // Check access permissions for the node entity.
      if (!$node->access('view')) {
        throw new AccessDeniedHttpException();
      }
      // Find the current display page for this comment.
      $page = comment_get_display_page($comment->id(), $node->type);
      // @todo: Cleaner sub request handling.
      $redirect_request = Request::create('/node/' . $node->nid, 'GET', $request->query->all(), $request->cookies->all(), array(), $request->server->all());
      $redirect_request->query->set('page', $page);
      // @todo: Convert the pager to use the request object.
      $request->query->set('page', $page);
      return $this->httpKernel->handle($redirect_request, HttpKernelInterface::SUB_REQUEST);
    }
    throw new NotFoundHttpException();
  }

}
