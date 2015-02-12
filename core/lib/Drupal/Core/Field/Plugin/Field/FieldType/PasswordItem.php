<?php

/**
 * @file
 * Contains \Drupal\Core\Field\Plugin\Field\FieldType\PasswordItem.
 */

namespace Drupal\Core\Field\Plugin\Field\FieldType;

/**
 * Defines the 'password' entity field type.
 *
 * @FieldType(
 *   id = "password",
 *   label = @Translation("Password"),
 *   description = @Translation("An entity field containing a password value."),
 *   no_ui = TRUE,
 * )
 */
class PasswordItem extends StringItem {}
