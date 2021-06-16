<?php

namespace Drupal\Tests\rest\Functional\EntityResource;

use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Field\Plugin\Field\FieldType\BooleanItem;
use Drupal\Core\Field\Plugin\Field\FieldType\ChangedItem;
use Drupal\Core\Field\Plugin\Field\FieldType\CreatedItem;
use Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem;
use Drupal\Core\Field\Plugin\Field\FieldType\IntegerItem;
use Drupal\Core\Field\Plugin\Field\FieldType\TimestampItem;
use Drupal\file\Plugin\Field\FieldType\FileItem;
use Drupal\image\Plugin\Field\FieldType\ImageItem;
use Drupal\options\Plugin\Field\FieldType\ListIntegerItem;
use Drupal\path\Plugin\Field\FieldType\PathItem;
use Drupal\Tests\rest\Functional\XmlNormalizationQuirksTrait;
use Drupal\user\StatusItem;

/**
 * Trait for EntityResourceTestBase subclasses testing $format='xml'.
 */
trait XmlEntityNormalizationQuirksTrait {

  use XmlNormalizationQuirksTrait;

  /**
   * {@inheritdoc}
   */
  protected function getExpectedNormalizedEntity() {
    $default_normalization = parent::getExpectedNormalizedEntity();

    if ($this->entity instanceof FieldableEntityInterface) {
      $normalization = $this->applyXmlFieldDecodingQuirks($default_normalization);
    }
    else {
      $normalization = $this->applyXmlConfigEntityDecodingQuirks($default_normalization);
    }
    $normalization = $this->applyXmlDecodingQuirks($normalization);

    return $normalization;
  }

  /**
   * Applies the XML entity field encoding quirks that remain after decoding.
   *
   * The XML encoding:
   * - loses type data (int and bool become string)
   *
   * @param array $normalization
   *   An entity normalization.
   *
   * @return array
   *   The updated fieldable entity normalization.
   *
   * @see \Symfony\Component\Serializer\Encoder\XmlEncoder
   */
  protected function applyXmlFieldDecodingQuirks(array $normalization) {
    foreach ($this->entity->getFields(TRUE) as $field_name => $field) {
      // Not every field is accessible.
      if (!isset($normalization[$field_name])) {
        continue;
      }

      for ($i = 0; $i < count($normalization[$field_name]); $i++) {
        switch ($field->getItemDefinition()->getClass()) {
          case BooleanItem::class:
          case StatusItem::class:
            // @todo Remove the StatusItem case in
            //   https://www.drupal.org/project/drupal/issues/2936864.
            $value = &$normalization[$field_name][$i]['value'];
            $value = $value === TRUE ? '1' : '0';
            break;

          case IntegerItem::class:
          case ListIntegerItem::class:
            $value = &$normalization[$field_name][$i]['value'];
            $value = (string) $value;
            break;

          case PathItem::class:
            $pid = &$normalization[$field_name][$i]['pid'];
            $pid = (string) $pid;
            break;

          case EntityReferenceItem::class:
          case FileItem::class:
            $target_id = &$normalization[$field_name][$i]['target_id'];
            $target_id = (string) $target_id;
            break;

          case ChangedItem::class:
          case CreatedItem::class:
          case TimestampItem::class:
            $value = &$normalization[$field_name][$i]['value'];
            if (is_numeric($value)) {
              $value = (string) $value;
            }
            break;

          case ImageItem::class:
            $height = &$normalization[$field_name][$i]['height'];
            $height = (string) $height;
            $width = &$normalization[$field_name][$i]['width'];
            $width = (string) $width;
            $target_id = &$normalization[$field_name][$i]['target_id'];
            $target_id = (string) $target_id;
            break;
        }
      }

      if (count($normalization[$field_name]) === 1) {
        $normalization[$field_name] = $normalization[$field_name][0];
      }
    }

    return $normalization;
  }

  /**
   * Applies the XML config entity encoding quirks that remain after decoding.
   *
   * The XML encoding:
   * - loses type data (int and bool become string)
   * - converts single-item arrays into single items (non-arrays)
   *
   * @param array $normalization
   *   An entity normalization.
   *
   * @return array
   *   The updated config entity normalization.
   *
   * @see \Symfony\Component\Serializer\Encoder\XmlEncoder
   */
  protected function applyXmlConfigEntityDecodingQuirks(array $normalization) {
    $normalization = static::castToString($normalization);

    // When a single dependency is listed, it's not decoded into an array.
    if (isset($normalization['dependencies'])) {
      foreach ($normalization['dependencies'] as $dependency_type => $dependency_list) {
        if (count($dependency_list) === 1) {
          $normalization['dependencies'][$dependency_type] = $dependency_list[0];
        }
      }
    }

    return $normalization;
  }

  /**
   * {@inheritdoc}
   */
  public function testPost() {
    // Deserialization of the XML format is not supported.
    $this->markTestSkipped();
  }

  /**
   * {@inheritdoc}
   */
  public function testPatch() {
    // Deserialization of the XML format is not supported.
    $this->markTestSkipped();
  }

}
