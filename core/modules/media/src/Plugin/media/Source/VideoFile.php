<?php

namespace Drupal\media\Plugin\media\Source;

use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\media\MediaTypeInterface;

/**
 * Media source wrapping around a video file.
 *
 * @see \Drupal\file\FileInterface
 *
 * @MediaSource(
 *   id = "video_file",
 *   label = @Translation("Video file"),
 *   description = @Translation("Use video files for reusable media."),
 *   allowed_field_types = {"file"},
 *   default_thumbnail_filename = "video.png"
 * )
 */
class VideoFile extends File {

  /**
   * {@inheritdoc}
   */
  public function createSourceField(MediaTypeInterface $type) {
    return parent::createSourceField($type)->set('settings', ['file_extensions' => 'mp4']);
  }

  /**
   * {@inheritdoc}
   */
  public function prepareViewDisplay(MediaTypeInterface $type, EntityViewDisplayInterface $display) {
    $display->setComponent($this->getSourceFieldDefinition($type)->getName(), [
      'type' => 'file_video',
    ]);
  }

}
