<?php

namespace Drupal\contact\Plugin\rest\resource;

use Drupal\rest\Plugin\rest\resource\EntityResource;

/**
 * Customizes the entity REST Resource plugin for Contact's Message entities.
 *
 * Message entities are not stored, so they cannot be:
 * - retrieved (GET)
 * - modified (PATCH)
 * - deleted (DELETE)
 * Messages can only be sent/created (POST).
 */
class ContactMessageResource extends EntityResource {

  /**
   * {@inheritdoc}
   */
  public function availableMethods() {
    return ['POST'];
  }

}
