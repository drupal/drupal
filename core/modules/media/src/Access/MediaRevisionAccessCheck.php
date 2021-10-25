<?php

namespace Drupal\media\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\media\MediaInterface;
use Symfony\Component\Routing\Route;

/**
 * Provides an access checker for media item revisions.
 *
 * @ingroup media_access
 */
class MediaRevisionAccessCheck implements AccessInterface {

  /**
   * The media storage.
   *
   * @var \Drupal\Core\Entity\ContentEntityStorageInterface
   */
  protected $mediaStorage;

  /**
   * The media access control handler.
   *
   * @var \Drupal\Core\Entity\EntityAccessControlHandlerInterface
   */
  protected $mediaAccess;

  /**
   * Constructs a new MediaRevisionAccessCheck.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    @trigger_error('MediaRevisionAccessCheck is deprecated in drupal:9.3.0 and will be removed before drupal:10.0.0. Use "_entity_access" requirement with relevant operation instead. See https://www.drupal.org/node/3161210', E_USER_DEPRECATED);

    $this->mediaStorage = $entity_type_manager->getStorage('media');
    $this->mediaAccess = $entity_type_manager->getAccessControlHandler('media');
  }

  /**
   * Checks routing access for the media item revision.
   *
   * @param \Symfony\Component\Routing\Route $route
   *   The route to check against.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The currently logged in account.
   * @param int $media_revision
   *   (optional) The media item revision ID. If not specified, but $media is,
   *   access is checked for that object's revision.
   * @param \Drupal\media\MediaInterface $media
   *   (optional) A media item. Used for checking access to a media items
   *   default revision when $media_revision is unspecified. Ignored when
   *   $media_revision is specified. If neither $media_revision nor $media are
   *   specified, then access is denied.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(Route $route, AccountInterface $account, $media_revision = NULL, MediaInterface $media = NULL) {
    if ($media_revision) {
      $media = $this->mediaStorage->loadRevision($media_revision);
    }
    $operation = $route->getRequirement('_access_media_revision');
    return AccessResult::allowedIf($media && $this->checkAccess($media, $account, $operation))->cachePerPermissions()->addCacheableDependency($media);
  }

  /**
   * Checks media item revision access.
   *
   * @param \Drupal\media\MediaInterface $media
   *   The media item to check.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   A user object representing the user for whom the operation is to be
   *   performed.
   * @param string $op
   *   (optional) The specific operation being checked. Defaults to 'view'.
   *
   * @return bool
   *   TRUE if the operation may be performed, FALSE otherwise.
   */
  public function checkAccess(MediaInterface $media, AccountInterface $account, $op = 'view') {
    if (!$media || $op !== 'view') {
      // If there was no media to check against, or the $op was not one of the
      // supported ones, we return access denied.
      return FALSE;
    }

    return $this->mediaAccess->access($media, 'view all revisions', $account);
  }

}
