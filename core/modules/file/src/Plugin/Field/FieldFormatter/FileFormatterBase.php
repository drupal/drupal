<?php

/**
 * @file
 * Contains \Drupal\file\Plugin\Field\FieldFormatter\FileFormatterBase.
 */

namespace Drupal\file\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\Plugin\Field\FieldFormatter\EntityReferenceFormatterBase;
use Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem;

/**
 * Base class for file formatters.
 */
abstract class FileFormatterBase extends EntityReferenceFormatterBase {

  /**
   * {@inheritdoc}
   */
  protected function needsEntityLoad(EntityReferenceItem $item) {
    return parent::needsEntityLoad($item) && $item->isDisplayed();
  }

  /**
   * {@inheritdoc}
   */
  protected function needsAccessCheck(EntityReferenceItem $item) {
    // Only check access if the current file access control handler explicitly
    // opts in by implementing FileAccessFormatterControlHandlerInterface.
    $access_handler_class = $item->entity->getEntityType()->getHandlerClass('access');
    return is_subclass_of($access_handler_class, '\Drupal\file\FileAccessFormatterControlHandlerInterface');
  }

}
