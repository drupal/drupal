<?php

namespace Drupal\image\Event;

use Drupal\image\ImageProcessPipelineInterface;
use Symfony\Component\EventDispatcher\GenericEvent;

/**
 * Provides a class for events related to processing images through pipelines.
 */
class ImageProcessEvent extends GenericEvent {

  /**
   * Returns the ImageProcessPipeline object subject of the event.
   *
   * @return \Drupal\image\ImageProcessPipelineInterface
   *   The ImageProcessPipeline object.
   */
  public function getPipeline(): ImageProcessPipelineInterface {
    return $this->getSubject();
  }

}
