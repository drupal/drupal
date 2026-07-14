<?php

declare(strict_types=1);

namespace Drupal\Tests\node\Traits;

use Drupal\node\Entity\NodeType;
use Drupal\Tests\field\Traits\BodyFieldCreationTrait;

/**
 * Provides methods to create content type from given values.
 *
 * This trait is meant to be used only by test classes.
 */
trait ContentTypeCreationTrait {

  use BodyFieldCreationTrait;

  /**
   * Creates a custom content type based on default settings.
   *
   * @param array $values
   *   An array of settings to change from the defaults.
   *   Example: 'type' => 'foo'.
   * @param bool $create_body
   *   Whether to create the body field.
   *
   * @return \Drupal\node\Entity\NodeType
   *   Created content type.
   */
  protected function createContentType(array $values = [], bool $create_body = TRUE) {
    // Find a non-existent random type name.
    if (!isset($values['type'])) {
      do {
        $id = $this->randomMachineName(8);
      } while (NodeType::load($id));
    }
    else {
      $id = $values['type'];
    }
    $values += [
      'type' => $id,
      'name' => $id,
    ];
    $type = NodeType::create($values);
    $status = $type->save();

    if ($create_body) {
      $this->createBodyField('node', $type->id());
    }

    $id = $type->id();
    $this->assertSame(SAVED_NEW, $status, "Created content type $id.");

    return $type;
  }

}
