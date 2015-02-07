<?php

/**
 * @file
 * Contains \Drupal\comment\Controller\CommentController.
 */

namespace Drupal\comment\Controller;

use Drupal\comment\CommentInterface;
use Drupal\comment\CommentManagerInterface;
use Drupal\comment\Plugin\Field\FieldType\CommentItemInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityManagerInterface;
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
class CommentController extends ControllerBase {

  /**
   * The HTTP kernel.
   *
   * @var \Symfony\Component\HttpKernel\HttpKernelInterface
   */
  protected $httpKernel;

  /**
   * The comment manager service.
   *
   * @var \Drupal\comment\CommentManagerInterface
   */
  protected $commentManager;

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $entityManager;

  /**
   * Constructs a CommentController object.
   *
   * @param \Symfony\Component\HttpKernel\HttpKernelInterface $http_kernel
   *   HTTP kernel to handle requests.
   * @param \Drupal\comment\CommentManagerInterface $comment_manager
   *   The comment manager service.
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager service.
   */
  public function __construct(HttpKernelInterface $http_kernel, CommentManagerInterface $comment_manager, EntityManagerInterface $entity_manager) {
    $this->httpKernel = $http_kernel;
    $this->commentManager = $comment_manager;
    $this->entityManager = $entity_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('http_kernel'),
      $container->get('comment.manager'),
      $container->get('entity.manager')
    );
  }

  /**
   * Publishes the specified comment.
   *
   * @param \Drupal\comment\CommentInterface $comment
   *   A comment entity.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse.
   *   Redirects to the permalink URL for this comment.
   */
  public function commentApprove(CommentInterface $comment) {
    $comment->setPublished(TRUE);
    $comment->save();

    drupal_set_message($this->t('Comment approved.'));
    $permalink_uri = $comment->permalink();
    $permalink_uri->setAbsolute();
    return new RedirectResponse($permalink_uri->toString());
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
    if ($entity = $comment->getCommentedEntity()) {
      // Check access permissions for the entity.
      if (!$entity->access('view')) {
        throw new AccessDeniedHttpException();
      }
      $field_definition = $this->entityManager()->getFieldDefinitions($entity->getEntityTypeId(), $entity->bundle())[$comment->getFieldName()];

      // Find the current display page for this comment.
      $page = $this->entityManager()->getStorage('comment')->getDisplayOrdinal($comment, $field_definition->getSetting('default_mode'), $field_definition->getSetting('per_page'));
      // @todo: Cleaner sub request handling.
      $redirect_request = Request::create($entity->url(), 'GET', $request->query->all(), $request->cookies->all(), array(), $request->server->all());
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
   * @param \Drupal\Core\Entity\EntityInterface $node
   *   The node object identified by the legacy URL.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   Redirects user to new url.
   */
  public function redirectNode(EntityInterface $node) {
    $fields = $this->commentManager->getFields('node');
    // Legacy nodes only had a single comment field, so use the first comment
    // field on the entity.
    if (!empty($fields) && ($field_names = array_keys($fields)) && ($field_name = reset($field_names))) {
      return $this->redirect('comment.reply', array(
        'entity_type' => 'node',
        'entity' => $node->id(),
        'field_name' => $field_name,
      ));
    }
    throw new NotFoundHttpException();
  }

  /**
   * Form constructor for the comment reply form.
   *
   * There are several cases that have to be handled, including:
   *   - replies to comments
   *   - replies to entities
   *   - attempts to reply to entities that can no longer accept comments
   *   - respecting access permissions ('access comments', 'post comments',
   *     etc.)
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request object.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity this comment belongs to.
   * @param string $field_name
   *   The field_name to which the comment belongs.
   * @param int $pid
   *   (optional) Some comments are replies to other comments. In those cases,
   *   $pid is the parent comment's comment ID. Defaults to NULL.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
   * @return array|\Symfony\Component\HttpFoundation\RedirectResponse
   *   One of the following:
   *   An associative array containing:
   *   - An array for rendering the entity or parent comment.
   *     - comment_entity: If the comment is a reply to the entity.
   *     - comment_parent: If the comment is a reply to another comment.
   *   - comment_form: The comment form as a renderable array.
   *   - An associative array containing:
   *     - An array for rendering the entity or parent comment.
   *        - comment_entity: If the comment is a reply to the entity.
   *        - comment_parent: If the comment is a reply to another comment.
   *     - comment_form: The comment form as a renderable array.
   *   - A redirect response to current node:
   *     - If user is not authorized to post comments.
   *     - If parent comment doesn't belong to current entity.
   *     - If user is not authorized to view comments.
   *     - If current entity comments are disable.
   */
  public function getReplyForm(Request $request, EntityInterface $entity, $field_name, $pid = NULL) {
    // Check if entity and field exists.
    $fields = $this->commentManager->getFields($entity->getEntityTypeId());
    if (empty($fields[$field_name])) {
      throw new NotFoundHttpException();
    }

    $account = $this->currentUser();
    $uri = $entity->urlInfo()->setAbsolute();
    $build = array();

    // Check if the user has the proper permissions.
    if (!$account->hasPermission('post comments')) {
      drupal_set_message($this->t('You are not authorized to post comments.'), 'error');
      return new RedirectResponse($uri->toString());
    }

    // The user is not just previewing a comment.
    if ($request->request->get('op') != $this->t('Preview')) {
      $status = $entity->{$field_name}->status;
      if ($status != CommentItemInterface::OPEN) {
        drupal_set_message($this->t("This discussion is closed: you can't post new comments."), 'error');
        return new RedirectResponse($uri->toString());
      }

      // $pid indicates that this is a reply to a comment.
      if ($pid) {
        // Check if the user has the proper permissions.
        if (!$account->hasPermission('access comments')) {
          drupal_set_message($this->t('You are not authorized to view comments.'), 'error');
          return new RedirectResponse($uri->toString());
        }
        // Load the parent comment.
        $comment = $this->entityManager()->getStorage('comment')->load($pid);
        // Check if the parent comment is published and belongs to the entity.
        if (!$comment->isPublished() || ($comment->getCommentedEntityId() != $entity->id())) {
          drupal_set_message($this->t('The comment you are replying to does not exist.'), 'error');
          return new RedirectResponse($uri->toString());
        }
        // Display the parent comment.
        $build['comment_parent'] = $this->entityManager()->getViewBuilder('comment')->view($comment);
      }

      // The comment is in response to a entity.
      elseif ($entity->access('view', $account)) {
        // We make sure the field value isn't set so we don't end up with a
        // redirect loop.
        $entity = clone $entity;
        $entity->{$field_name}->status = CommentItemInterface::HIDDEN;
        // Render array of the entity full view mode.
        $build['commented_entity'] = $this->entityManager()->getViewBuilder($entity->getEntityTypeId())->view($entity, 'full');
        unset($build['commented_entity']['#cache']);
      }
    }
    else {
      $build['#title'] = $this->t('Preview comment');
    }

    // Show the actual reply box.
    $comment = $this->entityManager()->getStorage('comment')->create(array(
      'entity_id' => $entity->id(),
      'pid' => $pid,
      'entity_type' => $entity->getEntityTypeId(),
      'field_name' => $field_name,
    ));
    $build['comment_form'] = $this->entityFormBuilder()->getForm($comment);

    return $build;
  }

  /**
   * Returns a set of nodes' last read timestamps.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request of the page.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
   * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   The JSON response.
   */
  public function renderNewCommentsNodeLinks(Request $request) {
    if ($this->currentUser()->isAnonymous()) {
      throw new AccessDeniedHttpException();
    }

    $nids = $request->request->get('node_ids');
    $field_name = $request->request->get('field_name');
    if (!isset($nids)) {
      throw new NotFoundHttpException();
    }
    // Only handle up to 100 nodes.
    $nids = array_slice($nids, 0, 100);

    $links = array();
    foreach ($nids as $nid) {
      $node = $this->entityManager->getStorage('node')->load($nid);
      $new = $this->commentManager->getCountNewComments($node);
      $page_number = $this->entityManager()->getStorage('comment')
        ->getNewCommentPageNumber($node->{$field_name}->comment_count, $new, $node);
      $query = $page_number ? array('page' => $page_number) : NULL;
      $links[$nid] = array(
        'new_comment_count' => (int) $new,
        'first_new_comment_link' => $this->getUrlGenerator()->generateFromPath('node/' . $node->id(), array('query' => $query, 'fragment' => 'new')),
      );
    }

    return new JsonResponse($links);
  }

}
