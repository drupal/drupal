<?php

namespace Drupal\views\Plugin\views\field;

use Drupal\Core\Session\AccountInterface;
use Drupal\views\Attribute\ViewsField;

/**
 * Displays the language of an entity.
 *
 * @ingroup views_field_handlers
 */
#[ViewsField("field_language")]
class FieldLanguage extends EntityField {

  /**
   * {@inheritdoc}
   */
  public function access(AccountInterface $account): bool {
    // No point in displaying the language field on monolingual sites,
    // as only one language value is available.
    return $this->languageManager->isMultilingual() && parent::access($account);
  }

}
