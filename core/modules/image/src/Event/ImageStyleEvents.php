<?php

namespace Drupal\image\Event;

/**
 * Defines events for the image style.
 */
final class ImageStyleEvents {

  /**
   * Event fired to remove all the image derivatives of an image style.
   *
   * @Event
   *
   * @see \Drupal\image\Entity\ImageStyle::flush()
   *
   * @var string
   */
  const FLUSH = 'image.style.flush';

  /**
   * Event fired to remove derivatives of a source image in all image styles.
   *
   * @Event
   *
   * @see image_path_flush()
   *
   * @var string
   */
  const FLUSH_FROM_SOURCE_IMAGE_URI = 'image.style.flush_from_source_image_uri';

}
