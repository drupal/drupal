<?php

declare(strict_types=1);

namespace Drupal\media_library;

use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\image\Entity\ImageStyle;
use Drupal\image\Plugin\Field\FieldType\ImageItem;
use Drupal\media\MediaTypeInterface;

/**
 * The media library form and view display setup.
 *
 * @internal
 */
class MediaLibraryDisplayManager {

  /**
   * Ensures that the given media type has a media_library form display.
   *
   * @param \Drupal\media\MediaTypeInterface $type
   *   The media type to configure.
   *
   * @return bool
   *   Whether a form display has been created or not.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public static function configureFormDisplay(MediaTypeInterface $type): bool {
    $display = EntityFormDisplay::load('media.' . $type->id() . '.media_library');

    if ($display) {
      return FALSE;
    }

    $values = [
      'targetEntityType' => 'media',
      'bundle' => $type->id(),
      'mode' => 'media_library',
      'status' => TRUE,
    ];
    $display = EntityFormDisplay::create($values);
    // Remove all default components.
    foreach (array_keys($display->getComponents()) as $name) {
      $display->removeComponent($name);
    }
    // Expose the name field when it is not mapped.
    if (!in_array('name', $type->getFieldMap(), TRUE)) {
      $display->setComponent('name', [
        'type' => 'string_textfield',
        'settings' => [
          'size' => 60,
        ],
      ]);
    }
    // If the source field is an image field, expose it so that users can set
    // alt and title text.
    $source_field = $type->getSource()->getSourceFieldDefinition($type);
    if ($source_field->isDisplayConfigurable('form') && is_a($source_field->getItemDefinition()->getClass(), ImageItem::class, TRUE)) {
      $type->getSource()->prepareFormDisplay($type, $display);
    }
    return (bool) $display->save();
  }

  /**
   * Ensures that the given media type has a media_library view display.
   *
   * @param \Drupal\media\MediaTypeInterface $type
   *   The media type to configure.
   *
   * @return bool
   *   Whether a view display has been created or not.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public static function configureViewDisplay(MediaTypeInterface $type): bool {
    $display = EntityViewDisplay::load('media.' . $type->id() . '.media_library');

    if ($display) {
      return FALSE;
    }

    $values = [
      'targetEntityType' => 'media',
      'bundle' => $type->id(),
      'mode' => 'media_library',
      'status' => TRUE,
    ];
    $display = EntityViewDisplay::create($values);
    // Remove all default components.
    foreach (array_keys($display->getComponents()) as $name) {
      $display->removeComponent($name);
    }

    // @todo Remove dependency on 'medium' and 'thumbnail' image styles from
    //   media and media library modules.
    //   https://www.drupal.org/project/drupal/issues/3030437
    $image_style = ImageStyle::load('medium');

    // Expose the thumbnail component. If the medium image style doesn't exist,
    // use the fallback 'media_library' image style.
    $display->setComponent('thumbnail', [
      'type' => 'image',
      'label' => 'hidden',
      'settings' => [
        'image_style' => $image_style ? $image_style->id() : 'media_library',
        'image_link' => '',
      ],
    ]);
    return (bool) $display->save();
  }

}
