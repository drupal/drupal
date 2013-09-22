<?php

/**
 * @file
 * Contains \Drupal\comment\Controller\CommentController.
 */

namespace Drupal\comment\Controller;

use Drupal\comment\CommentInterface;
use Drupal\comment\Entity\Comment;
use Drupal\Core\Access\CsrfTokenGenerator;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Node\NodeInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * Controller for the comment entity.
 *
 * @see \Drupal\comment\Entity\Comment.
 */
class CommentController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * The HTTP kernel.
   *
   * @var \Symfony\Component\HttpKernel\HttpKernelInterface
   */
  protected $httpKernel;

  /**
   * The CSRF token manager service.
   *
   * @var \Drupal\Core\Access\CsrfTokenGenerator
   */
  protected $csrfToken;

  /**
   * The current user service.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * Constructs a CommentController object.
   *
   * @param \Symfony\Component\HttpKernel\HttpKernelInterface $httpKernel
   *   HTTP kernel to handle requests.
   * @param \Drupal\Core\Access\CsrfTokenGenerator $csrf_token
   *   The CSRF token manager service.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user service.
   */
  public function __construct(HttpKernelInterface $httpKernel, CsrfTokenGenerator $csrf_token, AccountInterface $current_user) {
    $this->httpKernel = $httpKernel;
    $this->csrfToken = $csrf_token;
    $this->currentUser = $current_user;
  }
  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('http_kernel'),
      $container->get('csrf_token'),
      $container->get('current_user')
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
    if (!isset($token) || !$this->csrfToken->validate($token, 'comment/' . $comment->id() . '/approve')) {
      throw new AccessDeniedHttpException();
    }

    $comment->status->value = COMMENT_PUBLISHED;
    $comment->save();

    drupal_set_message($this->t('Comment approved.'));
    $permalink_uri = $comment->permalink();
    $permalink_uri['options']['absolute'] = TRUE;
    $url = $this->urlGenerator()->generateFromPath($permalink_uri['path'], $permalink_uri['options']);
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
      $page = comment_get_display_page($comment->id(), $node->getType());
      // @todo: Cleaner sub request handling.
      $redirect_request = Request::create('/node/' . $node->id(), 'GET', $request->query->all(), $request->cookies->all(), array(), $request->server->all());
      $redirect_request->query->set('page', $page);
      // @todo: Convert the pager to use the request object.
      $request->query->set('page', $page);
      return $this->httpKernel->handle($redirect_request, HttpKernelInterface::SUB_REQUEST);
    }
    throw new NotFoundHttpException();
  }

  /**
   * Form constructor for the comment reply form.
   *
   * Both replies on the node itself and replies on other comments are
   * supported. To provide context, the node or comment that is being replied on
   * will be displayed along the comment reply form.
   * The constructor takes care of access permissions and checks whether the
   * node still accepts comments.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request object.
   * @param \Drupal\node\NodeInterface $node
   *   Every comment belongs to a node. This is that node.
   * @param int $pid
   *   (optional) Some comments are replies to other comments. In those cases,
   *   $pid is the parent comment's ID. Defaults to NULL.
   *
   * @return array|\Symfony\Component\HttpFoundation\RedirectResponse
   *   One of the following:
   *   - An associative array containing:
   *     - An array for rendering the node or parent comment.
   *        - comment_node: If the comment is a reply to the node.
   *        - comment_parent: If the comment is a reply to another comment.
   *     - comment_form: The comment form as a renderable array.
   *   - A redirect response to current node:
   *     - If user is not authorized to post comments.
   *     - If parent comment doesn't belong to current node.
   *     - If user is not authorized to view comments.
   *     - If current node comments are disable.
   */
  public function getReplyForm(Request $request, NodeInterface $node, $pid = NULL) {
    $uri = $node->uri();
    $build = array();
    $account = $this->currentUser();

    $build['#title'] = $this->t('Add new comment');

    // Check if the user has the proper permissions.
    if (!$account->hasPermission('post comments')) {
      drupal_set_message($this->t('You are not authorized to post comments.'), 'error');
      return new RedirectResponse($this->urlGenerator()->generateFromPath($uri['path'], array('absolute' => TRUE)));
    }

    // The user is not just previewing a comment.
    if ($request->request->get('op') != $this->t('Preview')) {
      if ($node->comment->value != COMMENT_NODE_OPEN) {
        drupal_set_message($this->t("This discussion is closed: you can't post new comments."), 'error');
        return new RedirectResponse($this->urlGenerator()->generateFromPath($uri['path'], array('absolute' => TRUE)));
      }

      // $pid indicates that this is a reply to a comment.
      if ($pid) {
        // Check if the user has the proper permissions.
        if (!$account->hasPermission('access comments')) {
          drupal_set_message($this->t('You are not authorized to view comments.'), 'error');
          return new RedirectResponse($this->urlGenerator()->generateFromPath($uri['path'], array('absolute' => TRUE)));
        }
        // Load the parent comment.
        $comment = $this->entityManager()->getStorageController('comment')->load($pid);
        // Check if the parent comment is published and belongs to the current nid.
        if (($comment->status->value == COMMENT_NOT_PUBLISHED) || ($comment->nid->target_id != $node->id())) {
          drupal_set_message($this->t('The comment you are replying to does not exist.'), 'error');
          return new RedirectResponse($this->urlGenerator()->generateFromPath($uri['path'], array('absolute' => TRUE)));
        }
        // Display the parent comment.
        $build['comment_parent'] = $this->entityManager()->getRenderController('comment')->view($comment);
      }

      // The comment is in response to a node.
      elseif ($account->hasPermission('access content')) {
        // Display the node.
        $build['comment_node'] = $this->entityManager()->getRenderController('node')->view($node);
        unset($build['comment_node']['#cache']);
      }
    }
    else {
      $build['#title'] = $this->t('Preview comment');
    }

    // Show the actual reply box.
    $comment = $this->entityManager()->getStorageController('comment')->create(array(
      'nid' => $node->id(),
      'pid' => $pid,
      'node_type' => 'comment_node_' . $node->getType(),
    ));
    $build['comment_form'] = $this->entityManager()->getForm($comment);

    return $build;
  }

  /**
   * Returns a set of nodes' last read timestamps.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request of the page.
   *
   * @return Symfony\Component\HttpFoundation\JsonResponse
   *   The JSON response.
   */
  public function renderNewCommentsNodeLinks(Request $request) {
    if ($this->currentUser->isAnonymous()) {
      throw new AccessDeniedHttpException();
    }

    $nids = $request->request->get('node_ids');
    if (!isset($nids)) {
      throw new NotFoundHttpException();
    }
    // Only handle up to 100 nodes.
    $nids = array_slice($nids, 0, 100);

    $links = array();
    foreach ($nids as $nid) {
      $node = node_load($nid);
      $new = comment_num_new($node->id());
      $query = comment_new_page_count($node->comment_count, $new, $node);
      $links[$nid] = array(
        'new_comment_count' => (int)$new,
        'first_new_comment_link' => url('node/' . $node->id(), array('query' => $query, 'fragment' => 'new')),
      );
    }

    return new JsonResponse($links);
  }

  /**
   * @todo Remove comment_admin().
   */
  public function adminPage($type) {
    module_load_include('admin.inc', 'comment');
    return comment_admin($type);
  }

}
