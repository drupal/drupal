<?php

namespace Drupal\media\Plugin\media\Source;

use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\media\MediaTypeInterface;

/**
 * Media source wrapping around an audio file.
 *
 * @see \Drupal\file\FileInterface
 *
 * @MediaSource(
 *   id = "audio_file",
 *   label = @Translation("Audio file"),
 *   description = @Translation("Use audio files for reusable media."),
 *   allowed_field_types = {"file"},
 *   default_thumbnail_filename = "audio.png"
 * )
 */
class AudioFile extends File {

  /**
   * {@inheritdoc}
   */
  public function createSourceField(MediaTypeInterface $type) {
    return parent::createSourceField($type)->set('settings', ['file_extensions' => 'mp3 wav aac']);
  }

  /**
   * {@inheritdoc}
   */
  public function prepareViewDisplay(MediaTypeInterface $type, EntityViewDisplayInterface $display) {
    $display->setComponent($this->getSourceFieldDefinition($type)->getName(), [
      'type' => 'file_audio',
    ]);
  }

}
