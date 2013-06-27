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
   * Constructs a CommentController object.
   *
   * @param \Drupal\Core\Routing\PathBasedGeneratorInterface $url_generator
   *   The url generator service.
   */
  public function __construct(PathBasedGeneratorInterface $url_generator) {
    $this->urlGenerator = $url_generator;
  }
  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('url_generator'));
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

}
