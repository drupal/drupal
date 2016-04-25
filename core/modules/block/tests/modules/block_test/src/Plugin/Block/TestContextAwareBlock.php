<?php

namespace Drupal\block_test\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\user\UserInterface;

/**
 * Provides a context-aware block.
 *
 * @Block(
 *   id = "test_context_aware",
 *   admin_label = @Translation("Test context-aware block"),
 *   context = {
 *     "user" = @ContextDefinition("entity:user", required = FALSE)
 *   }
 * )
 */
class TestContextAwareBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    /** @var $user \Drupal\user\UserInterface */
    $user = $this->getContextValue('user');
    return array(
      '#prefix' => '<div id="' . $this->getPluginId() . '--username">',
      '#suffix' => '</div>',
      '#markup' => $user ? $user->getUsername() : 'No context mapping selected.' ,
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account) {
    if ($this->getContextValue('user') instanceof UserInterface) {
      drupal_set_message('User context found.');
    }

    return parent::blockAccess($account);
  }

}
