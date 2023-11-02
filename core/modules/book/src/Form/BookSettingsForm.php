<?php

namespace Drupal\book\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\ConfigTarget;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure book settings for this site.
 *
 * @internal
 */
class BookSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'book_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['book.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $types = node_type_get_names();
    $form['book_allowed_types'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Content types allowed in book outlines'),
      '#config_target' => new ConfigTarget('book.settings', 'allowed_types', toConfig: static::class . '::filterAndSortAllowedTypes'),
      '#options' => $types,
      '#description' => $this->t('Users with the %outline-perm permission can add all content types.', ['%outline-perm' => $this->t('Administer book outlines')]),
      '#required' => TRUE,
    ];
    $form['book_child_type'] = [
      '#type' => 'radios',
      '#title' => $this->t('Content type for the <em>Add child page</em> link'),
      '#config_target' => 'book.settings:child_type',
      '#options' => $types,
      '#required' => TRUE,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $child_type = $form_state->getValue('book_child_type');
    if ($form_state->isValueEmpty(['book_allowed_types', $child_type])) {
      $form_state->setErrorByName('book_child_type', $this->t('The content type for the %add-child link must be one of those selected as an allowed book outline type.', ['%add-child' => $this->t('Add child page')]));
    }

    parent::validateForm($form, $form_state);
  }

  /**
   * Transformation callback for the book_allowed_types config value.
   *
   * @param array $allowed_types
   *   The config value to transform.
   *
   * @return array
   *   The transformed value.
   */
  public static function filterAndSortAllowedTypes(array $allowed_types): array {
    $allowed_types = array_filter($allowed_types);
    // We need to save the allowed types in an array ordered by machine_name so
    // that we can save them in the correct order if node type changes.
    // @see book_node_type_update().
    sort($allowed_types);
    return $allowed_types;
  }

}
