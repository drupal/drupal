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
 *   Media Library is an experimental module and its internal code may be
 *   subject to change in minor releases. External code should not instantiate
 *   or extend this class.
 */
class UpdateSelectionCommand implements CommandInterface {

  /**
   * An array of media IDs to add to the current selection.
   *
   * @var int[]
   */
  protected $mediaIds;

  /**
   * Constructs an UpdateSelectionCommand object.
   *
   * @param int[] $media_ids
   *   An array of media IDs to add to the current selection.
   */
  public function __construct(array $media_ids) {
    $this->mediaIds = $media_ids;
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
