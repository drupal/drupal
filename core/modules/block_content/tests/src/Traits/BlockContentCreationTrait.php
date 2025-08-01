<?php

declare(strict_types=1);

namespace Drupal\Tests\block_content\Traits;

use Drupal\block_content\Entity\BlockContent;
use Drupal\block_content\Entity\BlockContentType;
use Drupal\Tests\field\Traits\BodyFieldCreationTrait;

/**
 * Provides methods for creating block_content entities and types.
 */
trait BlockContentCreationTrait {

  use BodyFieldCreationTrait;

  /**
   * Creates a content block.
   *
   * @param bool|string $title
   *   (optional) Title of block. When no value is given uses a random name.
   *   Defaults to FALSE.
   * @param string $bundle
   *   (optional) Bundle name. Defaults to 'basic'.
   * @param bool $save
   *   (optional) Whether to save the block. Defaults to TRUE.
   * @param array $values
   *   (optional) Additional values for the block_content entity.
   *
   * @return \Drupal\block_content\Entity\BlockContent
   *   Created content block.
   */
  protected function createBlockContent(bool|string $title = FALSE, string $bundle = 'basic', bool $save = TRUE, array $values = []): BlockContent {
    $title = $title ?: $this->randomMachineName();
    $values += [
      'info' => $title,
      'type' => $bundle,
      'langcode' => 'en',
    ];
    $block_content = BlockContent::create($values);
    if ($block_content && $save === TRUE) {
      $block_content->save();
    }
    return $block_content;
  }

  /**
   * Creates a block type (bundle).
   *
   * @param array|string $values
   *   (deprecated) The variable $values as string is deprecated. Provide as an
   *   array as parameter. The value to create the block content type. If
   *   $values is an array it should be like: ['id' => 'foo', 'label' => 'Foo'].
   *   If $values is a string, it will be considered that it represents the
   *   label.
   * @param bool $create_body
   *   Whether or not to create the body field.
   *
   * @return \Drupal\block_content\Entity\BlockContentType
   *   Created block type.
   */
  protected function createBlockContentType(array|string $values, bool $create_body = FALSE): BlockContentType {
    if (is_string($values)) {
      @trigger_error('Using the variable $values as string is deprecated in drupal:11.3.0 and is removed from drupal:12.0.0. Provide an array as parameter. See https://www.drupal.org/node/3473739', E_USER_DEPRECATED);
    }
    if (is_array($values)) {
      if (!isset($values['id'])) {
        do {
          $id = $this->randomMachineName(8);
        } while (BlockContentType::load($id));
      }
      else {
        $id = $values['id'];
      }
      $values += [
        'id' => $id,
        'label' => $id,
        'revision' => FALSE,
      ];
      $bundle = BlockContentType::create($values);
    }
    else {
      $bundle = BlockContentType::create([
        'id' => $values,
        'label' => $values,
        'revision' => FALSE,
      ]);
    }
    $bundle->save();
    if ($create_body) {
      $this->createBodyField('block_content', $bundle->id());
    }
    return $bundle;
  }

}
