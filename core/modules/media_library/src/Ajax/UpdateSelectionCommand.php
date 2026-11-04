<?php

namespace Drupal\media_library\Ajax;

use Drupal\Core\Ajax\CommandInterface;

/**
 * AJAX command for adding media items to the media library selection.
 *
 * This command instructs the client to add the given media item IDs to the
 * current selection of the media library stored in
 * Drupal.MediaLibrary.currentSelection.
 *
 * This command is implemented by
 * Drupal.AjaxCommands.prototype.updateMediaLibrarySelection() defined in
 * media_library.ui.js.
 *
 * @ingroup ajax
 *
 * @internal
 *   This is an internal part of Media Library and may be subject to change in
 *   minor releases. External code should not instantiate or extend this class.
 */
class UpdateSelectionCommand implements CommandInterface {

  /**
   * Constructs an UpdateSelectionCommand object.
   *
   * @param int[] $mediaIds
   *   An array of media IDs to add to the current selection.
   */
  public function __construct(
    protected array $mediaIds,
  ) {
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    return [
      'command' => 'updateMediaLibrarySelection',
      'mediaIds' => $this->mediaIds,
    ];
  }

}
