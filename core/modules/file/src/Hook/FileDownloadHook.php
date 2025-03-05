<?php

declare(strict_types=1);

namespace Drupal\file\Hook;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Session\AccountInterface;
use Drupal\file\FileRepositoryInterface;
use Drupal\file\FileUsage\FileUsageInterface;

/**
 * Implements hook_file_download().
 */
#[Hook('file_download')]
class FileDownloadHook {

  public function __construct(
    private readonly FileRepositoryInterface $fileRepository,
    private readonly FileUsageInterface $fileUsage,
    private readonly AccountInterface $currentUser,
  ) {}

  /**
   * Implements hook_file_download().
   */
  public function __invoke($uri): array|int|null {
    // Get the file record based on the URI. If not in the database just return.
    $file = $this->fileRepository->loadByUri($uri);
    if (!$file) {
      return NULL;
    }
    // Find out if a temporary file is still used in the system.
    if ($file->isTemporary()) {
      $usage = $this->fileUsage->listUsage($file);
      if (empty($usage) && $file->getOwnerId() != $this->currentUser->id()) {
        // Deny access to temporary files without usage that are not owned by
        // the same user. This prevents the security issue that a private file
        // that was protected by field permissions becomes available after its
        // usage was removed and before it is actually deleted from the file
        // system. Modules that depend on this behavior should make the file
        // permanent instead.
        return -1;
      }
    }
    // Find out which (if any) fields of this type contain the file.
    $references = file_get_file_references($file, NULL, EntityStorageInterface::FIELD_LOAD_CURRENT, NULL);
    // Stop processing if there are no references in order to avoid returning
    // headers for files controlled by other modules. Make an exception for
    // temporary files where the host entity has not yet been saved (for
    // example, an image preview on a node/add form) in which case, allow
    // download by the file's owner.
    if (empty($references) && ($file->isPermanent() || $file->getOwnerId() != $this->currentUser->id())) {
      return NULL;
    }
    if (!$file->access('download')) {
      return -1;
    }
    // Access is granted.
    return $file->getDownloadHeaders();
  }

}
