<?php

declare(strict_types=1);

namespace Drupal\contact_storage_test\Hook;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for contact_storage_test.
 */
class ContactStorageTestHooks {

  /**
   * Implements hook_entity_base_field_info().
   */
  #[Hook('entity_base_field_info')]
  public function entityBaseFieldInfo(EntityTypeInterface $entity_type) {
    if ($entity_type->id() == 'contact_message') {
      $fields = [];
      $fields['id'] = BaseFieldDefinition::create('integer')->setLabel(t('Message ID'))->setDescription(t('The message ID.'))->setReadOnly(TRUE)->setSetting('unsigned', TRUE);
      return $fields;
    }
  }

  /**
   * Implements hook_entity_type_alter().
   */
  #[Hook('entity_type_alter')]
  public function entityTypeAlter(array &$entity_types) : void {
    /** @var \Drupal\Core\Entity\EntityTypeInterface[] $entity_types */
    // Set the controller class for nodes to an alternate implementation of the
    // Drupal\Core\Entity\EntityStorageInterface interface.
    $entity_types['contact_message']->setStorageClass('\Drupal\Core\Entity\Sql\SqlContentEntityStorage');
    $keys = $entity_types['contact_message']->getKeys();
    $keys['id'] = 'id';
    $entity_types['contact_message']->set('entity_keys', $keys);
    $entity_types['contact_message']->set('base_table', 'contact_message');
  }

  /**
   * Implements hook_form_FORM_ID_alter() for contact_form_form().
   */
  #[Hook('form_contact_form_form_alter')]
  public function formContactFormFormAlter(&$form, FormStateInterface $form_state) : void {
    /** @var \Drupal\contact\ContactFormInterface $contact_form */
    $contact_form = $form_state->getFormObject()->getEntity();
    $form['send_a_pony'] = [
      '#type' => 'checkbox',
      '#title' => t('Send submitters a voucher for a free pony.'),
      '#description' => t('Enable to send an additional email with a free pony voucher to anyone who submits the form.'),
      '#default_value' => $contact_form->getThirdPartySetting('contact_storage_test', 'send_a_pony', FALSE),
    ];
    $form['#entity_builders'][] = 'contact_storage_test_contact_form_form_builder';
  }

}
