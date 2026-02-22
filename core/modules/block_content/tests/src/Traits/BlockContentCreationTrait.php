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
   * @param array{id?: string, label?: string} $values
   *   The values to create the block content type.
   * @param bool $create_body
   *   Whether or not to create the body field.
   *
   * @return \Drupal\block_content\Entity\BlockContentType
   *   Created block type.
   */
  protected function createBlockContentType(array $values, bool $create_body = FALSE): BlockContentType {
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
      'label' => $values['label'] ?? $id,
      'revision' => FALSE,
    ];
    $bundle = BlockContentType::create($values);
    $bundle->save();
    if ($create_body) {
      $this->createBodyField('block_content', $bundle->id());
    }
    return $bundle;
  }

}
