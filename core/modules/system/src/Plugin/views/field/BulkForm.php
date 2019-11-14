<?php

namespace Drupal\system\Plugin\views\field;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\views\Plugin\views\field\BulkForm as ViewsBulkForm;

/**
 * Defines a actions-based bulk operation form element.
 *
 * @ViewsField("legacy_bulk_form")
 *
 * @deprecated in drupal:8.5.0 and is removed from drupal:9.0.0. Use
 *   \Drupal\views\Plugin\views\field\BulkForm instead.
 *
 * @see https://www.drupal.org/node/2916716
 */
class BulkForm extends ViewsBulkForm {

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, LanguageManagerInterface $language_manager, MessengerInterface $messenger) {
    @trigger_error(__NAMESPACE__ . '\BulkForm is deprecated in drupal:8.5.0, will be removed before drupal:9.0.0. Use \Drupal\views\Plugin\views\field\BulkForm instead. See https://www.drupal.org/node/2916716.', E_USER_DEPRECATED);
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager, $language_manager, $messenger);
  }

}
