<?php

namespace Drupal\Tests\node\Traits;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\node\Entity\NodeType;
use PHPUnit\Framework\TestCase;

/**
 * Provides methods to create content type from given values.
 *
 * This trait is meant to be used only by test classes.
 */
trait ContentTypeCreationTrait {

  /**
   * Creates a custom content type based on default settings.
   *
   * @param array $values
   *   An array of settings to change from the defaults.
   *   Example: 'type' => 'foo'.
   *
   * @return \Drupal\node\Entity\NodeType
   *   Created content type.
   */
  protected function createContentType(array $values = []) {
    // Find a non-existent random type name.
    if (!isset($values['type'])) {
      do {
        $id = strtolower($this->randomMachineName(8));
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
    node_add_body_field($type);

    if ($this instanceof TestCase) {
      $this->assertSame($status, SAVED_NEW, (new FormattableMarkup('Created content type %type.', ['%type' => $type->id()]))->__toString());
    }
    else {
      $this->assertEqual(SAVED_NEW, $status, (new FormattableMarkup('Created content type %type.', ['%type' => $type->id()]))->__toString());
    }

    return $type;
  }

}
