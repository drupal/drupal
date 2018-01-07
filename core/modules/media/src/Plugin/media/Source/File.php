<?php

namespace Drupal\media\Plugin\media\Source;

use Drupal\file\FileInterface;
use Drupal\media\MediaInterface;
use Drupal\media\MediaTypeInterface;
use Drupal\media\MediaSourceBase;

/**
 * File entity media source.
 *
 * @see \Drupal\file\FileInterface
 *
 * @MediaSource(
 *   id = "file",
 *   label = @Translation("File"),
 *   description = @Translation("Use local files for reusable media."),
 *   allowed_field_types = {"file"},
 *   default_thumbnail_filename = "generic.png"
 * )
 */
class File extends MediaSourceBase {

  /**
   * Key for "MIME type" metadata attribute.
   *
   * @var string
   */
  const METADATA_ATTRIBUTE_MIME = 'mimetype';

  /**
   * Key for "File size" metadata attribute.
   *
   * @var string
   */
  const METADATA_ATTRIBUTE_SIZE = 'filesize';


  /**
   * {@inheritdoc}
   */
  public function getMetadataAttributes() {
    return [
      static::METADATA_ATTRIBUTE_MIME => $this->t('MIME type'),
      static::METADATA_ATTRIBUTE_SIZE => $this->t('File size'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getMetadata(MediaInterface $media, $attribute_name) {
    /** @var \Drupal\file\FileInterface $file */
    $file = $media->get($this->configuration['source_field'])->entity;
    // If the source field is not required, it may be empty.
    if (!$file) {
      return parent::getMetadata($media, $attribute_name);
    }
    switch ($attribute_name) {
      case 'mimetype':
        return $file->getMimeType();

      case 'filesize':
        return $file->getSize();

      case 'default_name':
        return $file->getFilename();

      case 'thumbnail_uri':
        return $this->getThumbnail($file) ?: parent::getMetadata($media, $attribute_name);

      default:
        return parent::getMetadata($media, $attribute_name);
    }
  }

  /**
   * Gets the thumbnail image URI based on a file entity.
   *
   * @param \Drupal\file\FileInterface $file
   *   A file entity.
   *
   * @return string
   *   File URI of the thumbnail image or NULL if there is no specific icon.
   */
  protected function getThumbnail(FileInterface $file) {
    $icon_base = $this->configFactory->get('media.settings')->get('icon_base_uri');

    // We try to automatically use the most specific icon present in the
    // $icon_base directory, based on the MIME type. For instance, if an
    // icon file named "pdf.png" is present, it will be used if the file
    // matches this MIME type.
    $mimetype = $file->getMimeType();
    $mimetype = explode('/', $mimetype);

    $icon_names = [
      $mimetype[0] . '--' . $mimetype[1],
      $mimetype[1],
      $mimetype[0],
    ];
    foreach ($icon_names as $icon_name) {
      $thumbnail = $icon_base . '/' . $icon_name . '.png';
      if (is_file($thumbnail)) {
        return $thumbnail;
      }
    }

    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function createSourceField(MediaTypeInterface $type) {
    return parent::createSourceField($type)->set('settings', ['file_extensions' => 'txt doc docx pdf']);
  }

}
