<?php

/**
 * @file
 * Contains \Drupal\comment\Controller\CommentController.
 */

namespace Drupal\comment\Controller;

use Drupal\comment\CommentInterface;
use Drupal\comment\CommentManager;
use Drupal\comment\Entity\Comment;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Routing\UrlGeneratorInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Drupal\field\FieldInfo;
use Drupal\node\NodeInterface;

/**
 * Controller for the comment entity.
 *
 * @see \Drupal\comment\Entity\Comment.
 */
class CommentController implements ContainerInjectionInterface {

  /**
   * The url generator service.
   *
   * @var \Drupal\Core\Routing\UrlGeneratorInterface
   */
  protected $urlGenerator;

  /**
   * The HTTP kernel.
   *
   * @var \Symfony\Component\HttpKernel\HttpKernelInterface
   */
  protected $httpKernel;

  /**
   * Field info service.
   *
   * @var \Drupal\field\FieldInfo
   */
  protected $fieldInfo;

  /**
   * Constructs a CommentController object.
   *
   * @param \Drupal\Core\Routing\UrlGeneratorInterface $url_generator
   *   The url generator service.
   * @param \Symfony\Component\HttpKernel\HttpKernelInterface $httpKernel
   *   HTTP kernel to handle requests.
   * @param \Drupal\field\FieldInfo $field_info
   *   Field Info service.
   */
  public function __construct(UrlGeneratorInterface $url_generator, HttpKernelInterface $httpKernel, FieldInfo $field_info, CommentManager $comment_manager) {
    $this->urlGenerator = $url_generator;
    $this->httpKernel = $httpKernel;
    $this->fieldInfo = $field_info;
    $this->commentManager = $comment_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('url_generator'),
      $container->get('http_kernel'),
      $container->get('field.info'),
      $container->get('comment.manager')
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
    if ($entity = entity_load($comment->entity_type->value, $comment->entity_id->value)) {
      // Check access permissions for the entity.
      if (!$entity->access('view')) {
        throw new AccessDeniedHttpException();
      }
      $instance = $this->fieldInfo->getInstance($entity->entityType(), $entity->bundle(), $comment->field_name->value);

      // Find the current display page for this comment.
      $page = comment_get_display_page($comment->id(), $instance);
      // @todo: Cleaner sub request handling.
      $uri = $entity->uri();
      $redirect_request = Request::create($uri['path'], 'GET', $request->query->all(), $request->cookies->all(), array(), $request->server->all());
      $redirect_request->query->set('page', $page);
      // @todo: Convert the pager to use the request object.
      $request->query->set('page', $page);
      return $this->httpKernel->handle($redirect_request, HttpKernelInterface::SUB_REQUEST);
    }
    throw new NotFoundHttpException();
  }

  /**
   * Redirects legacy node links to the new path.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node object identified by the legacy URL.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   Redirects user to new url.
   */
  public function redirectNode(NodeInterface $node) {
    $fields = $this->commentManager->getFields('node');
    // Legacy nodes only had a single comment field, so use the first comment
    // field on the entity.
    if (!empty($fields) && ($field_names = array_keys($fields)) && ($field_name = reset($field_names))) {
      return new RedirectResponse(url('comment/reply/node/' . $node->id() . '/' . $field_name, array('absolute' => TRUE)));
    }
    throw new NotFoundHttpException();
  }

}
