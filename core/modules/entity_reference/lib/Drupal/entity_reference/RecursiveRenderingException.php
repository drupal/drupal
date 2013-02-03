<?php

/**
 * @file
 * Contains \Drupal\entity_reference\RecursiveRenderingException.
 */

namespace Drupal\entity_reference;

/**
 * Exception thrown when the entity view renderer goes into a potentially
 * infinite loop.
 */
class RecursiveRenderingException extends \Exception {}
