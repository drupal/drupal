<?php

/**
 * @file
 * Contains \Drupal\content_translation\Form\TranslatableForm.
 */

namespace Drupal\content_translation\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\field\Entity\Field;
use Drupal\field\Field as FieldInfo;

/**
 * Provides a confirm form for changing translatable status on translation
 * fields.
 */
class TranslatableForm extends ConfirmFormBase {

  /**
   * The field info we are changing translatable status on.
   *
   * @var \Drupal\field\Entity\Field
   */
  protected $field;

  /**
   * The field name we are changing translatable
   * status on.
   *
   * @var string.
   */
  protected $fieldName;

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'content_translation_translatable_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    if ($this->field['translatable']) {
      $question = t('Are you sure you want to disable translation for the %name field?', array('%name' => $this->fieldName));
    }
    else {
      $question = t('Are you sure you want to enable translation for the %name field?', array('%name' => $this->fieldName));
    }
    return $question;
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    $description = t('By submitting this form these changes will apply to the %name field everywhere it is used.',
      array('%name' => $this->fieldName)
    );
    $description .= $this->field['translatable'] ? "<br>" . t("<strong>All the existing translations of this field will be deleted.</strong><br>This action cannot be undone.") : '';
    return $description;
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelPath() {
    return '';
  }

  /**
   * {@inheritdoc}
   * @param string $entity_type
   *   The entity type.
   * @param string $field_name
   *   The field name.
   */
  public function buildForm(array $form, array &$form_state, $entity_type = NULL, $field_name = NULL) {
    $this->fieldName = $field_name;
    $this->fieldInfo = FieldInfo::fieldInfo()->getField($entity_type, $field_name);

    return parent::buildForm($form, $form_state);
  }

  /**
   * Form submission handler.
   *
   * This submit handler maintains consistency between the translatability of an
   * entity and the language under which the field data is stored. When a field
   * is marked as translatable, all the data in
   * $entity->{field_name}[Language::LANGCODE_NOT_SPECIFIED] is moved to
   * $entity->{field_name}[$entity_language]. When a field is marked as
   * untranslatable the opposite process occurs. Note that marking a field as
   * untranslatable will cause all of its translations to be permanently
   * removed, with the exception of the one corresponding to the entity
   * language.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param array $form_state
   *   An associative array containing the current state of the form.
   */
  public function submitForm(array &$form, array &$form_state) {
    // This is the current state that we want to reverse.
    $translatable = $form_state['values']['translatable'];
    if ($this->field['translatable'] !== $translatable) {
      // Field translatability has changed since form creation, abort.
      $t_args = array('%field_name');
      $msg = $translatable ?
        t('The field %field_name is already translatable. No change was performed.', $t_args):
        t('The field %field_name is already untranslatable. No change was performed.', $t_args);
      drupal_set_message($msg, 'warning');
      return;
    }

    // If a field is untranslatable, it can have no data except under
    // Language::LANGCODE_NOT_SPECIFIED. Thus we need a field to be translatable
    // before we convert data to the entity language. Conversely we need to
    // switch data back to Language::LANGCODE_NOT_SPECIFIED before making a
    // field untranslatable lest we lose information.
    $operations = array(
      array(
        'content_translation_translatable_batch', array(
          !$translatable,
          $this->fieldName,
        ),
      ),
      array(
        'content_translation_translatable_switch', array(
          !$translatable,
          $this->field['entity_type'],
          $this->fieldName,
        ),
      ),
    );
    $operations = $translatable ? $operations : array_reverse($operations);

    $t_args = array('%field' => $this->fieldName);
    $title = !$translatable ? t('Enabling translation for the %field field', $t_args) : t('Disabling translation for the %field field', $t_args);

    $batch = array(
      'title' => $title,
      'operations' => $operations,
      'finished' => 'content_translation_translatable_batch_done',
      'file' => drupal_get_path('module', 'content_translation') . '/content_translation.admin.inc',
    );

    batch_set($batch);

  }

}
