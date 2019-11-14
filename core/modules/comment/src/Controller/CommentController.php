<?php

namespace Drupal\comment\Controller;

use Drupal\comment\CommentInterface;
use Drupal\comment\CommentManagerInterface;
use Drupal\comment\Plugin\Field\FieldType\CommentItemInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Cache\CacheableResponseInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Url;
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
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * The entity repository.
   *
   * @var Drupal\Core\Entity\EntityRepositoryInterface
   */
  protected $entityRepository;

  /**
   * Constructs a CommentController object.
   *
   * @param \Symfony\Component\HttpKernel\HttpKernelInterface $http_kernel
   *   HTTP kernel to handle requests.
   * @param \Drupal\comment\CommentManagerInterface $comment_manager
   *   The comment manager service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager service.
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *   The entity repository service.
   */
  public function __construct(HttpKernelInterface $http_kernel, CommentManagerInterface $comment_manager, EntityTypeManagerInterface $entity_type_manager, EntityFieldManagerInterface $entity_field_manager, EntityRepositoryInterface $entity_repository) {
    $this->httpKernel = $http_kernel;
    $this->commentManager = $comment_manager;
    $this->entityTypeManager = $entity_type_manager;
    $this->entityFieldManager = $entity_field_manager;
    $this->entityRepository = $entity_repository;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('http_kernel'),
      $container->get('comment.manager'),
      $container->get('entity_type.manager'),
      $container->get('entity_field.manager'),
      $container->get('entity.repository')
    );
  }

  /**
   * Publishes the specified comment.
   *
   * @param \Drupal\comment\CommentInterface $comment
   *   A comment entity.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   */
  public function commentApprove(CommentInterface $comment) {
    $comment->setPublished();
    $comment->save();

    $this->messenger()->addStatus($this->t('Comment approved.'));
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
   * @return \Symfony\Component\HttpFoundation\Response
   *   The comment listing set to the page on which the comment appears.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
   * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
   */
  public function commentPermalink(Request $request, CommentInterface $comment) {
    if ($entity = $comment->getCommentedEntity()) {
      // Check access permissions for the entity.
      if (!$entity->access('view')) {
        throw new AccessDeniedHttpException();
      }
      $field_definition = $this->entityFieldManager->getFieldDefinitions($entity->getEntityTypeId(), $entity->bundle())[$comment->getFieldName()];

      // Find the current display page for this comment.
      $page = $this->entityTypeManager()->getStorage('comment')->getDisplayOrdinal($comment, $field_definition->getSetting('default_mode'), $field_definition->getSetting('per_page'));
      // @todo: Cleaner sub request handling.
      $subrequest_url = $entity->toUrl()->setOption('query', ['page' => $page])->toString(TRUE);
      $redirect_request = Request::create($subrequest_url->getGeneratedUrl(), 'GET', $request->query->all(), $request->cookies->all(), [], $request->server->all());
      // Carry over the session to the subrequest.
      if ($request->hasSession()) {
        $redirect_request->setSession($request->getSession());
      }
      $request->query->set('page', $page);
      $response = $this->httpKernel->handle($redirect_request, HttpKernelInterface::SUB_REQUEST);
      if ($response instanceof CacheableResponseInterface) {
        // @todo Once path aliases have cache tags (see
        //   https://www.drupal.org/node/2480077), add test coverage that
        //   the cache tag for a commented entity's path alias is added to the
        //   comment's permalink response, because there can be blocks or
        //   other content whose renderings depend on the subrequest's URL.
        $response->addCacheableDependency($subrequest_url);
      }
      return $response;
    }
    throw new NotFoundHttpException();
  }

  /**
   * The _title_callback for the page that renders the comment permalink.
   *
   * @param \Drupal\comment\CommentInterface $comment
   *   The current comment.
   *
   * @return string
   *   The translated comment subject.
   */
  public function commentPermalinkTitle(CommentInterface $comment) {
    return $this->entityRepository->getTranslationFromContext($comment)->label();
  }

  /**
   * Redirects legacy node links to the new path.
   *
   * @param \Drupal\Core\Entity\EntityInterface $node
   *   The node object identified by the legacy URL.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   Redirects user to new url.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
   */
  public function redirectNode(EntityInterface $node) {
    $fields = $this->commentManager->getFields('node');
    // Legacy nodes only had a single comment field, so use the first comment
    // field on the entity.
    if (!empty($fields) && ($field_names = array_keys($fields)) && ($field_name = reset($field_names))) {
      return $this->redirect('comment.reply', [
        'entity_type' => 'node',
        'entity' => $node->id(),
        'field_name' => $field_name,
      ]);
    }
    throw new NotFoundHttpException();
  }

  /**
   * Form constructor for the comment reply form.
   *
   * There are several cases that have to be handled, including:
   *   - replies to comments
   *   - replies to entities
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
   * @return array|\Symfony\Component\HttpFoundation\RedirectResponse
   *   An associative array containing:
   *   - An array for rendering the entity or parent comment.
   *     - comment_entity: If the comment is a reply to the entity.
   *     - comment_parent: If the comment is a reply to another comment.
   *   - comment_form: The comment form as a renderable array.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
   */
  public function getReplyForm(Request $request, EntityInterface $entity, $field_name, $pid = NULL) {
    $account = $this->currentUser();
    $build = [];

    // The user is not just previewing a comment.
    if ($request->request->get('op') != $this->t('Preview')) {

      // $pid indicates that this is a reply to a comment.
      if ($pid) {
        // Load the parent comment.
        $comment = $this->entityTypeManager()->getStorage('comment')->load($pid);
        // Display the parent comment.
        $build['comment_parent'] = $this->entityTypeManager()->getViewBuilder('comment')->view($comment);
      }

      // The comment is in response to a entity.
      elseif ($entity->access('view', $account)) {
        // We make sure the field value isn't set so we don't end up with a
        // redirect loop.
        $entity = clone $entity;
        $entity->{$field_name}->status = CommentItemInterface::HIDDEN;
        // Render array of the entity full view mode.
        $build['commented_entity'] = $this->entityTypeManager()->getViewBuilder($entity->getEntityTypeId())->view($entity, 'full');
        unset($build['commented_entity']['#cache']);
      }
    }
    else {
      $build['#title'] = $this->t('Preview comment');
    }

    // Show the actual reply box.
    $comment = $this->entityTypeManager()->getStorage('comment')->create([
      'entity_id' => $entity->id(),
      'pid' => $pid,
      'entity_type' => $entity->getEntityTypeId(),
      'field_name' => $field_name,
    ]);
    $build['comment_form'] = $this->entityFormBuilder()->getForm($comment);

    return $build;
  }

  /**
   * Access check for the reply form.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity this comment belongs to.
   * @param string $field_name
   *   The field_name to which the comment belongs.
   * @param int $pid
   *   (optional) Some comments are replies to other comments. In those cases,
   *   $pid is the parent comment's comment ID. Defaults to NULL.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   An access result
   *
   * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
   */
  public function replyFormAccess(EntityInterface $entity, $field_name, $pid = NULL) {
    // Check if entity and field exists.
    $fields = $this->commentManager->getFields($entity->getEntityTypeId());
    if (empty($fields[$field_name])) {
      throw new NotFoundHttpException();
    }

    $account = $this->currentUser();

    // Check if the user has the proper permissions.
    $access = AccessResult::allowedIfHasPermission($account, 'post comments');

    // If commenting is open on the entity.
    $status = $entity->{$field_name}->status;
    $access = $access->andIf(AccessResult::allowedIf($status == CommentItemInterface::OPEN)
      ->addCacheableDependency($entity))
      // And if user has access to the host entity.
      ->andIf(AccessResult::allowedIf($entity->access('view')));

    // $pid indicates that this is a reply to a comment.
    if ($pid) {
      // Check if the user has the proper permissions.
      $access = $access->andIf(AccessResult::allowedIfHasPermission($account, 'access comments'));

      // Load the parent comment.
      $comment = $this->entityTypeManager()->getStorage('comment')->load($pid);
      // Check if the parent comment is published and belongs to the entity.
      $access = $access->andIf(AccessResult::allowedIf($comment && $comment->isPublished() && $comment->getCommentedEntityId() == $entity->id()));
      if ($comment) {
        $access->addCacheableDependency($comment);
      }
    }
    return $access;
  }

  /**
   * Returns a set of nodes' last read timestamps.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request of the page.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   The JSON response.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
   * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
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

    $links = [];
    foreach ($nids as $nid) {
      $node = $this->entityTypeManager()->getStorage('node')->load($nid);
      $new = $this->commentManager->getCountNewComments($node);
      $page_number = $this->entityTypeManager()->getStorage('comment')
        ->getNewCommentPageNumber($node->{$field_name}->comment_count, $new, $node, $field_name);
      $query = $page_number ? ['page' => $page_number] : NULL;
      $links[$nid] = [
        'new_comment_count' => (int) $new,
        'first_new_comment_link' => Url::fromRoute('entity.node.canonical', ['node' => $node->id()], ['query' => $query, 'fragment' => 'new'])->toString(),
      ];
    }

    return new JsonResponse($links);
  }

}
