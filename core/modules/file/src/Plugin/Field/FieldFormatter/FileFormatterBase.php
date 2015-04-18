<?php

/**
 * @file
 * Contains \Drupal\file\Plugin\Field\FieldFormatter\FileFormatterBase.
 */

namespace Drupal\file\Plugin\Field\FieldFormatter;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityInterface;
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
  protected function checkAccess(EntityInterface $entity) {
    // Only check access if the current file access control handler explicitly
    // opts in by implementing FileAccessFormatterControlHandlerInterface.
    $access_handler_class = $entity->getEntityType()->getHandlerClass('access');
    if (is_subclass_of($access_handler_class, '\Drupal\file\FileAccessFormatterControlHandlerInterface')) {
      return $entity->access('view', NULL, TRUE);
    }
    else {
      return AccessResult::allowed();
    }
  }

}
