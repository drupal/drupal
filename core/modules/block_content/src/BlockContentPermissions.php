<?php

namespace Drupal\block_content;

use Drupal\block_content\Entity\BlockContentType;
use Drupal\Core\Entity\BundlePermissionHandlerTrait;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Provide dynamic permissions for blocks of different types.
 */
class BlockContentPermissions {

  use StringTranslationTrait;
  use BundlePermissionHandlerTrait;

  /**
   * Build permissions for each block type.
   *
   * @return array
   *   The block type permissions.
   */
  public function blockTypePermissions() {
    return $this->generatePermissions(BlockContentType::loadMultiple(), [$this, 'buildPermissions']);
  }

  /**
   * Return all the permissions available for a custom block type.
   *
   * @param \Drupal\block_content\Entity\BlockContentType $type
   *   The block type.
   *
   * @return array
   *   Permissions available for the given block type.
   */
  protected function buildPermissions(BlockContentType $type) {
    $type_id = $type->id();
    $type_params = ['%type_name' => $type->label()];
    return [
      "edit any $type_id block content" => [
        'title' => $this->t('%type_name: Edit any block content', $type_params),
      ],
    ];
  }

}
