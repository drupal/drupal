<?php

namespace Drupal\action_bulk_test\Plugin\Action;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Action\ActionBase;
use Drupal\Core\Action\Attribute\Action;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Test action.
 */
#[Action(
  id: 'test_action',
  label: new TranslatableMarkup('Test action'),
  type: 'node',
  confirm_form_route_name: 'action_bulk_test.action.confirm'
)]
class TestAction extends ActionBase {

  /**
   * {@inheritdoc}
   */
  public function access($object, ?AccountInterface $account = NULL, $return_as_object = FALSE): bool|AccessResultInterface {
    return $return_as_object ? AccessResult::allowed() : TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function execute(): void {
  }

}
