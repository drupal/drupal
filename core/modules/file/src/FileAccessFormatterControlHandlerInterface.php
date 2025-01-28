<?php

namespace Drupal\file;

use Drupal\Core\Entity\EntityAccessControlHandlerInterface;

/**
 * Defines an interface for file access handlers which runs on file formatters.
 *
 * \Drupal\Core\Field\Plugin\Field\FieldFormatter\EntityReferenceFormatterBase,
 * which file and image formatters extend, checks 'view' access on the
 * referenced files before displaying them. That check would be useless and
 * costly with Core's default access control implementation for files
 * (\Drupal\file\FileAccessControlHandler grants access based on whether
 * there are existing entities with granted access that reference the file). But
 * it might be needed if a different access control handler with different logic
 * is swapped in.
 *
 * \Drupal\file\Plugin\Field\FieldFormatter\FileFormatterBase thus adjusts that
 * behavior, and only checks access if the access control handler in use for
 * files opts in by implementing this interface.
 *
 * @see \Drupal\file\Plugin\Field\FieldFormatter\FileFormatterBase::needsAccessCheck()
 */
interface FileAccessFormatterControlHandlerInterface extends EntityAccessControlHandlerInterface {}
