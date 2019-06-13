<?php

namespace Drupal\media_library;

use Drupal\Core\Session\AccountInterface;

/**
 * Defines an interface for media library openers.
 *
 * Media library opener services allow modules to check access to the media
 * library selection dialog and respond to selections. Example use cases that
 * require different handling:
 * - when used in an entity reference field widget;
 * - when used in a text editor.
 *
 * Openers that require additional parameters or metadata should retrieve them
 * from the MediaLibraryState object.
 *
 * @see \Drupal\media_library\MediaLibraryState
 * @see \Drupal\media_library\MediaLibraryState::getOpenerParameters()
 */
interface MediaLibraryOpenerInterface {

  /**
   * Checks media library access.
   *
   * @param \Drupal\media_library\MediaLibraryState $state
   *   The media library.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user for which to check access.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   *
   * @see https://www.drupal.org/project/drupal/issues/3038254
   */
  public function checkAccess(MediaLibraryState $state, AccountInterface $account);

  /**
   * Generates a response after selecting media items in the media library.
   *
   * @param \Drupal\media_library\MediaLibraryState $state
   *   The state the media library was in at the time of selection, allowing the
   *   response to be customized based on that state.
   * @param int[] $selected_ids
   *   The IDs of the selected media items.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   The response to update the page after selecting media.
   */
  public function getSelectionResponse(MediaLibraryState $state, array $selected_ids);

}
