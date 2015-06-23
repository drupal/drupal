<?php

/**
 * @file
 * Definition of Drupal\Core\Ajax\AjaxResponse.
 */

namespace Drupal\Core\Ajax;

use Drupal\Core\Asset\AttachedAssets;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\Render\Renderer;
use Drupal\Core\Render\AttachmentsInterface;
use Drupal\Core\Render\AttachmentsTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * JSON response object for AJAX requests.
 *
 * @ingroup ajax
 */
class AjaxResponse extends JsonResponse implements AttachmentsInterface {

  use AttachmentsTrait;

  /**
   * The array of ajax commands.
   *
   * @var array
   */
  protected $commands = array();

  /**
   * Add an AJAX command to the response.
   *
   * @param \Drupal\Core\Ajax\CommandInterface $command
   *   An AJAX command object implementing CommandInterface.
   * @param bool $prepend
   *   A boolean which determines whether the new command should be executed
   *   before previously added commands. Defaults to FALSE.
   *
   * @return AjaxResponse
   *   The current AjaxResponse.
   */
  public function addCommand(CommandInterface $command, $prepend = FALSE) {
    if ($prepend) {
      array_unshift($this->commands, $command->render());
    }
    else {
      $this->commands[] = $command->render();
    }
    if ($command instanceof CommandWithAttachedAssetsInterface) {
      $assets = $command->getAttachedAssets();
      $attachments = [
        'library' => $assets->getLibraries(),
        'drupalSettings' => $assets->getSettings(),
      ];
      $attachments = BubbleableMetadata::mergeAttachments($this->getAttachments(), $attachments);
      $this->setAttachments($attachments);
    }

    return $this;
  }

  /**
   * Gets all AJAX commands.
   *
   * @return \Drupal\Core\Ajax\CommandInterface[]
   *   Returns all previously added AJAX commands.
   */
  public function &getCommands() {
    return $this->commands;
  }

}
