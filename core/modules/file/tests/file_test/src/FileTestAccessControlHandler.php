<?php

declare(strict_types=1);

namespace Drupal\file_test;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\file\FileAccessFormatterControlHandlerInterface;
use Drupal\file\FileAccessControlHandler;

/**
 * Defines a class for an alternate file access control handler.
 */
class FileTestAccessControlHandler extends FileAccessControlHandler implements FileAccessFormatterControlHandlerInterface {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    \Drupal::state()->set('file_access_formatter_check', TRUE);
    return parent::checkAccess($entity, $operation, $account);
  }

}
