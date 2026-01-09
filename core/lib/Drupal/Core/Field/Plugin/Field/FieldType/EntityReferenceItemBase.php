<?php

namespace Drupal\Core\Field\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Utility\FiberResumeType;

/**
 * Base class for field items referencing other entities.
 *
 * Any field type that is an entity reference should extend from this class in
 * order to remain backwards compatible with any changes added in the future
 * to EntityReferenceItemInterface.
 */
abstract class EntityReferenceItemBase extends FieldItemBase implements EntityReferenceItemInterface {

  /**
   * {@inheritdoc}
   */
  public function __get($property_name) {
    // This is a workaround for a PHP bug where a fiber suspend from within a
    // __get() call can incorrectly trigger PHP's recursion guarding.
    // See https://github.com/php/php-src/issues/14983
    if ($property_name === 'entity' && \Fiber::getCurrent()) {
      $fiber = new \Fiber(fn() => parent::__get($property_name));
      $fiber->start();
      while (!$fiber->isTerminated()) {
        if ($fiber->isSuspended()) {
          $resume_type = $fiber->resume();
          if (!$fiber->isTerminated() && $resume_type !== FiberResumeType::Immediate) {
            usleep(500);
          }
        }
      }
      return $fiber->getReturn();
    }
    return parent::__get($property_name);
  }

}
