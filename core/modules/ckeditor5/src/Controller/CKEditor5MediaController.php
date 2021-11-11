<?php

declare(strict_types = 1);

namespace Drupal\ckeditor5\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\editor\Entity\Editor;
use Drupal\image\Plugin\Field\FieldType\ImageItem;
use Drupal\media\MediaInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Provides an API for checking if a media entity has image field.
 *
 * @internal
 *   Controller classes are internal.
 */
class CKEditor5MediaController extends ControllerBase {

  /**
   * The currently authenticated user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The entity repository.
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface
   */
  protected $entityRepository;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * Constructs a new CKEditor5MediaController.
   *
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The currently authenticated user.
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *   The entity repository.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   */
  public function __construct(AccountInterface $current_user, EntityRepositoryInterface $entity_repository, RequestStack $request_stack) {
    $this->currentUser = $current_user;
    $this->entityRepository = $entity_repository;
    $this->requestStack = $request_stack;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_user'),
      $container->get('entity.repository'),
      $container->get('request_stack')
    );
  }

  /**
   * Checks if media has a image field.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request object.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   A JSON object including the response.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\BadRequestHttpException
   *   Thrown when no media UUID is provided.
   * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
   *   Thrown when no media with the provided UUID exists.
   */
  public function isMediaImage(Request $request) {
    $uuid = $request->query->get('uuid');
    if ($uuid == '') {
      throw new BadRequestHttpException();
    }
    // Access is enforced on route level.
    // @see \Drupal\ckeditor5\Controller\CKEditor5MediaController::access().
    if (!$media = $this->entityRepository->loadEntityByUuid('media', $uuid)) {
      throw new NotFoundHttpException();
    }

    // Note that we intentionally do not use:
    // - \Drupal\Core\Cache\CacheableResponse because caching it on the server
    //   side is wasteful, hence there is no need for cacheability metadata.
    // - \Drupal\Core\Render\HtmlResponse because there is no need for
    //   attachments nor cacheability metadata.
    return (new JsonResponse($this->hasImageField($media), 200))
      // Do not allow any intermediary to cache the response, only the end user.
      ->setPrivate()
      // Allow the end user to cache it for up to 5 minutes.
      ->setMaxAge(300);
  }

  /**
   * Additional access check for ::isMediaImage().
   *
   * This grants access if media embed filter is enabled on the filter format
   * and user has access to view the media entity.
   *
   * Note that access to the filter format is not checked here because the route
   * is configured to check entity access to the filter format.
   *
   * @param \Drupal\editor\Entity\Editor $editor
   *   The text editor.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\BadRequestHttpException
   *   Thrown when no media UUID is provided.
   * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
   *   Thrown when no media with the provided UUID exists.
   */
  public function access(Editor $editor): AccessResultInterface {
    if ($editor->getEditor() !== 'ckeditor5') {
      return AccessResult::forbidden();
    }
    // @todo add current request as an argument after
    // https://www.drupal.org/project/drupal/issues/2786941 has been resolved.
    $request = $this->requestStack->getCurrentRequest();
    $uuid = $request->query->get('uuid');
    if (!$uuid) {
      throw new BadRequestHttpException();
    }
    $media = $this->entityRepository->loadEntityByUuid('media', $uuid);
    if (!$media) {
      throw new NotFoundHttpException();
    }
    $filters = $editor->getFilterFormat()->filters();

    return AccessResult::allowedIf($filters->has('media_embed') && $filters->get('media_embed')->status)
      ->andIf($media->access('view', $this->currentUser, TRUE))
      ->addCacheableDependency($editor->getFilterFormat());
  }

  /**
   * Check if the media type of the entity has a image field.
   *
   * @param \Drupal\media\MediaInterface $media
   *   The media item being embedded.
   *
   * @return bool
   *   Flag indicating whether the media type has image field or not.
   *
   * @see \Drupal\media\Form\EditorMediaDialog::getMediaImageSourceFieldName()
   */
  protected function hasImageField(MediaInterface $media) {
    $field_definition = $media->getSource()
      ->getSourceFieldDefinition($media->bundle->entity);
    $item_class = $field_definition->getItemDefinition()->getClass();
    if (is_a($item_class, ImageItem::class, TRUE)) {
      return TRUE;
    }
    return FALSE;
  }

}
