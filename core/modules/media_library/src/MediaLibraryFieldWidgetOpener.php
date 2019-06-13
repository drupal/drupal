<?php

namespace Drupal\media_library;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Session\AccountInterface;

/**
 * The media library opener for field widgets.
 */
class MediaLibraryFieldWidgetOpener implements MediaLibraryOpenerInterface {

  /**
   * {@inheritdoc}
   */
  public function checkAccess(MediaLibraryState $state, AccountInterface $account) {
    throw new \Exception('Not yet implemented, see https://www.drupal.org/project/drupal/issues/3038254.');
  }

  /**
   * {@inheritdoc}
   */
  public function getSelectionResponse(MediaLibraryState $state, array $selected_ids) {
    $response = new AjaxResponse();

    $parameters = $state->getOpenerParameters();
    if (empty($parameters['field_widget_id'])) {
      throw new \InvalidArgumentException('field_widget_id parameter is missing.');
    }

    // Create a comma-separated list of media IDs, insert them in the hidden
    // field of the widget, and trigger the field update via the hidden submit
    // button.
    $widget_id = $parameters['field_widget_id'];
    $ids = implode(',', $selected_ids);
    $response
      ->addCommand(new InvokeCommand("[data-media-library-widget-value=\"$widget_id\"]", 'val', [$ids]))
      ->addCommand(new InvokeCommand("[data-media-library-widget-update=\"$widget_id\"]", 'trigger', ['mousedown']));

    return $response;
  }

}
