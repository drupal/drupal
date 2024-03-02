<?php

namespace Drupal\file\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\Attribute\FieldFormatter;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Plugin implementation of the 'file_audio' formatter.
 */
#[FieldFormatter(
  id: 'file_audio',
  label: new TranslatableMarkup('Audio'),
  description: new TranslatableMarkup('Display the file using an HTML5 audio tag.'),
  field_types: [
    'file',
  ],
)]
class FileAudioFormatter extends FileMediaFormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function getMediaType() {
    return 'audio';
  }

}
