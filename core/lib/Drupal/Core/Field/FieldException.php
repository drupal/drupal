<?php

/*
 * @file
 * Contains \Drupal\Core\Field\FieldException.
 */

namespace Drupal\Core\Field;

/**
 * Base class for all exceptions thrown by the Entity Field API functions.
 *
 * This class has no functionality of its own other than allowing all
 * Entity Field API exceptions to be caught by a single catch block.
 */
class FieldException extends \RuntimeException {}
