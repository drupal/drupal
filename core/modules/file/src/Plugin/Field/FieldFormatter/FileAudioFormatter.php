<?php

namespace Drupal\file\Plugin\Field\FieldFormatter;

/**
 * Plugin implementation of the 'file_audio' formatter.
 *
 * @FieldFormatter(
 *   id = "file_audio",
 *   label = @Translation("Audio"),
 *   description = @Translation("Display the file using an HTML5 audio tag."),
 *   field_types = {
 *     "file"
 *   }
 * )
 */
class FileAudioFormatter extends FileMediaFormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function getMediaType() {
    return 'audio';
  }

}
