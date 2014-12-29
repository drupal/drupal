<?php

/**
 * @file
 * Contains \Drupal\content_translation\ContentTranslationHandler.
 */

namespace Drupal\content_translation;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Render\Element;

/**
 * Base class for content translation handlers.
 *
 * @ingroup entity_api
 */
class ContentTranslationHandler implements ContentTranslationHandlerInterface {

  /**
   * The type of the entity being translated.
   *
   * @var string
   */
  protected $entityTypeId;

  /**
   * Information about the entity type.
   *
   * @var \Drupal\Core\Entity\EntityTypeInterface
   */
  protected $entityType;

  /**
   * Initializes an instance of the content translation controller.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The info array of the given entity type.
   */
  public function __construct(EntityTypeInterface $entity_type) {
    $this->entityTypeId = $entity_type->id();
    $this->entityType = $entity_type;
  }

  /**
   * {@inheritdoc}
   */
  public function retranslate(EntityInterface $entity, $langcode = NULL) {
    $updated_langcode = !empty($langcode) ? $langcode : $entity->language()->getId();
    $translations = $entity->getTranslationLanguages();
    foreach ($translations as $langcode => $language) {
      $entity->translation[$langcode]['outdated'] = $langcode != $updated_langcode;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getTranslationAccess(EntityInterface $entity, $op) {
    // @todo Move this logic into a translation access control handler checking also
    //   the translation language and the given account.
    $entity_type = $entity->getEntityType();
    $translate_permission = TRUE;
    // If no permission granularity is defined this entity type does not need an
    // explicit translate permission.
    $current_user = \Drupal::currentUser();
    if (!$current_user->hasPermission('translate any entity') && $permission_granularity = $entity_type->getPermissionGranularity()) {
      $translate_permission = $current_user->hasPermission($permission_granularity == 'bundle' ? "translate {$entity->bundle()} {$entity->getEntityTypeId()}" : "translate {$entity->getEntityTypeId()}");
    }
    return AccessResult::allowedIf($translate_permission && $current_user->hasPermission("$op content translations"))->cachePerRole();
  }

  /**
   * {@inheritdoc}
   */
  public function getSourceLangcode(FormStateInterface $form_state) {
    if ($source = $form_state->get(['content_translation', 'source'])) {
      return $source->getId();
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function entityFormAlter(array &$form, FormStateInterface $form_state, EntityInterface $entity) {
    $form_object = $form_state->getFormObject();
    $form_langcode = $form_object->getFormLangcode($form_state);
    $entity_langcode = $entity->getUntranslated()->language()->getId();
    $source_langcode = $this->getSourceLangcode($form_state);

    $new_translation = !empty($source_langcode);
    $translations = $entity->getTranslationLanguages();
    if ($new_translation) {
      // Make sure a new translation does not appear as existing yet.
      unset($translations[$form_langcode]);
    }
    $is_translation = !$form_object->isDefaultFormLangcode($form_state);
    $has_translations = count($translations) > 1;

    // Adjust page title to specify the current language being edited, if we
    // have at least one translation.
    $languages = language_list();
    if (isset($languages[$form_langcode]) && ($has_translations || $new_translation)) {
      $title = $this->entityFormTitle($entity);
      // When editing the original values display just the entity label.
      if ($form_langcode != $entity_langcode) {
        $t_args = array('%language' => $languages[$form_langcode]->getName(), '%title' => $entity->label());
        $title = empty($source_langcode) ? $title . ' [' . t('%language translation', $t_args) . ']' : t('Create %language translation of %title', $t_args);
      }
      $form['#title'] = $title;
    }

    // Display source language selector only if we are creating a new
    // translation and there are at least two translations available.
    if ($has_translations && $new_translation) {
      $form['source_langcode'] = array(
        '#type' => 'details',
        '#title' => t('Source language: @language', array('@language' => $languages[$source_langcode]->getName())),
        '#tree' => TRUE,
        '#weight' => -100,
        '#multilingual' => TRUE,
        'source' => array(
          '#title' => t('Select source language'),
          '#title_display' => 'invisible',
          '#type' => 'select',
          '#default_value' => $source_langcode,
          '#options' => array(),
        ),
        'submit' => array(
          '#type' => 'submit',
          '#value' => t('Change'),
          '#submit' => array(array($this, 'entityFormSourceChange')),
        ),
      );
      foreach (language_list(LanguageInterface::STATE_CONFIGURABLE) as $language) {
        if (isset($translations[$language->getId()])) {
          $form['source_langcode']['source']['#options'][$language->getId()] = $language->getName();
        }
      }
    }

    // Locate the language widget.
    $langcode_key = $this->entityType->getKey('langcode');
    if (isset($form[$langcode_key])) {
      $language_widget = &$form[$langcode_key];
    }

    // If we are editing the source entity, limit the list of languages so that
    // it is not possible to switch to a language for which a translation
    // already exists. Note that this will only work if the widget is structured
    // like \Drupal\Core\Field\Plugin\Field\FieldWidget\LanguageSelectWidget.
    if (isset($language_widget['widget'][0]['value']) && !$is_translation && $has_translations) {
      $language_select = &$language_widget['widget'][0]['value'];
      if ($language_select['#type'] == 'language_select') {
        $options = array();
        foreach (\Drupal::languageManager()->getLanguages() as $language) {
          // Show the current language, and the languages for which no
          // translation already exists.
          if (empty($translations[$language->getId()]) || $language->getId() == $entity_langcode) {
            $options[$language->getId()] = $language->getName();
          }
        }
        $language_select['#options'] = $options;
      }
    }
    if ($is_translation) {
      if (isset($language_widget)) {
        $language_widget['widget']['#access'] = FALSE;
      }

      // Replace the delete button with the delete translation one.
      if (!$new_translation) {
        $weight = 100;
        foreach (array('delete', 'submit') as $key) {
          if (isset($form['actions'][$key]['weight'])) {
            $weight = $form['actions'][$key]['weight'];
            break;
          }
        }
        $form['actions']['delete_translation'] = array(
          '#type' => 'submit',
          '#value' => t('Delete translation'),
          '#weight' => $weight,
          '#submit' => array(array($this, 'entityFormDeleteTranslation')),
          '#access' => $this->getTranslationAccess($entity, 'delete')->isAllowed(),
        );
      }

      // Always remove the delete button on translation forms.
      unset($form['actions']['delete']);
    }

    // We need to display the translation tab only when there is at least one
    // translation available or a new one is about to be created.
    if ($new_translation || $has_translations) {
      $form['content_translation'] = array(
        '#type' => 'details',
        '#title' => t('Translation'),
        '#tree' => TRUE,
        '#weight' => 10,
        '#access' => $this->getTranslationAccess($entity, $source_langcode ? 'create' : 'update')->isAllowed(),
        '#multilingual' => TRUE,
      );

      // A new translation is enabled by default.
      $status = $new_translation || $entity->translation[$form_langcode]['status'];
      // If there is only one published translation we cannot unpublish it,
      // since there would be nothing left to display.
      $enabled = TRUE;
      if ($status) {
        // A new translation is not available in the translation metadata, hence
        // it should count as one more.
        $published = $new_translation;
        // When creating a brand new translation, $entity->translation is not
        // set.
        if (!$new_translation) {
          foreach ($entity->translation as $translation) {
            $published += $translation['status'];
          }
        }
        $enabled = $published > 1;
      }
      $description = $enabled ?
        t('An unpublished translation will not be visible without translation permissions.') :
        t('Only this translation is published. You must publish at least one more translation to unpublish this one.');

      $form['content_translation']['status'] = array(
        '#type' => 'checkbox',
        '#title' => t('This translation is published'),
        '#default_value' => $status,
        '#description' => $description,
        '#disabled' => !$enabled,
      );

      $translate = !$new_translation && $entity->translation[$form_langcode]['outdated'];
      if (!$translate) {
        $form['content_translation']['retranslate'] = array(
          '#type' => 'checkbox',
          '#title' => t('Flag other translations as outdated'),
          '#default_value' => FALSE,
          '#description' => t('If you made a significant change, which means the other translations should be updated, you can flag all translations of this content as outdated. This will not change any other property of them, like whether they are published or not.'),
        );
      }
      else {
        $form['content_translation']['outdated'] = array(
          '#type' => 'checkbox',
          '#title' => t('This translation needs to be updated'),
          '#default_value' => $translate,
          '#description' => t('When this option is checked, this translation needs to be updated. Uncheck when the translation is up to date again.'),
        );
        $form['content_translation']['#open'] = TRUE;
      }

      // Default to the anonymous user.
      $name = '';
      if ($new_translation) {
        $name = \Drupal::currentUser()->getUsername();
      }
      elseif ($entity->translation[$form_langcode]['uid']) {
        $name = user_load($entity->translation[$form_langcode]['uid'])->getUsername();
      }
      $form['content_translation']['name'] = array(
        '#type' => 'textfield',
        '#title' => t('Authored by'),
        '#maxlength' => 60,
        '#autocomplete_route_name' => 'user.autocomplete',
        '#default_value' => $name,
        '#description' => t('Leave blank for %anonymous.', array('%anonymous' => \Drupal::config('user.settings')->get('anonymous'))),
      );

      $date = $new_translation ? REQUEST_TIME : $entity->translation[$form_langcode]['created'];
      $form['content_translation']['created'] = array(
        '#type' => 'textfield',
        '#title' => t('Authored on'),
        '#maxlength' => 25,
        '#description' => t('Format: %time. The date format is YYYY-MM-DD and %timezone is the time zone offset from UTC. Leave blank to use the time of form submission.', array('%time' => format_date($date, 'custom', 'Y-m-d H:i:s O'), '%timezone' => format_date($date, 'custom', 'O'))),
        '#default_value' => $new_translation ? '' : format_date($date, 'custom', 'Y-m-d H:i:s O'),
      );

      if (isset($language_widget)) {
        $language_widget['#multilingual'] = TRUE;
      }

      $form['#process'][] = array($this, 'entityFormSharedElements');
    }

    // Process the submitted values before they are stored.
    $form['#entity_builders'][] = array($this, 'entityFormEntityBuild');

    // Handle entity validation.
    if (isset($form['actions']['submit'])) {
      $form['actions']['submit']['#validate'][] = array($this, 'entityFormValidate');
    }

    // Handle entity deletion.
    if (isset($form['actions']['delete'])) {
      $form['actions']['delete']['#submit'][] = array($this, 'entityFormDelete');
    }
  }

  /**
   * Process callback: determines which elements get clue in the form.
   *
   * @see \Drupal\content_translation\ContentTranslationHandler::entityFormAlter()
   */
  public function entityFormSharedElements($element, FormStateInterface $form_state, $form) {
    static $ignored_types;

    // @todo Find a more reliable way to determine if a form element concerns a
    //   multilingual value.
    if (!isset($ignored_types)) {
      $ignored_types = array_flip(array('actions', 'value', 'hidden', 'vertical_tabs', 'token', 'details'));
    }

    foreach (Element::children($element) as $key) {
      if (!isset($element[$key]['#type'])) {
        $this->entityFormSharedElements($element[$key], $form_state, $form);
      }
      else {
        // Ignore non-widget form elements.
        if (isset($ignored_types[$element[$key]['#type']])) {
          continue;
        }
        // Elements are considered to be non multilingual by default.
        if (empty($element[$key]['#multilingual'])) {
          // If we are displaying a multilingual entity form we need to provide
          // translatability clues, otherwise the shared form elements should be
          // hidden.
          if (!$form_state->get(['content_translation', 'translation_form'])) {
            $this->addTranslatabilityClue($element[$key]);
          }
          else {
            $element[$key]['#access'] = FALSE;
          }
        }
      }
    }

    return $element;
  }

  /**
   * Adds a clue about the form element translatability.
   *
   * If the given element does not have a #title attribute, the function is
   * recursively applied to child elements.
   *
   * @param array $element
   *   A form element array.
   */
  protected function addTranslatabilityClue(&$element) {
    static $suffix, $fapi_title_elements;

    // Elements which can have a #title attribute according to FAPI Reference.
    if (!isset($suffix)) {
      $suffix = ' <span class="translation-entity-all-languages">(' . t('all languages') . ')</span>';
      $fapi_title_elements = array_flip(array('checkbox', 'checkboxes', 'date', 'details', 'fieldset', 'file', 'item', 'password', 'password_confirm', 'radio', 'radios', 'select', 'text_format', 'textarea', 'textfield', 'weight'));
    }

    // Update #title attribute for all elements that are allowed to have a
    // #title attribute according to the Form API Reference. The reason for this
    // check is because some elements have a #title attribute even though it is
    // not rendered, e.g. field containers.
    if (isset($element['#type']) && isset($fapi_title_elements[$element['#type']]) && isset($element['#title'])) {
      $element['#title'] .= $suffix;
    }
    // If the current element does not have a (valid) title, try child elements.
    elseif ($children = Element::children($element)) {
      foreach ($children as $delta) {
        $this->addTranslatabilityClue($element[$delta], $suffix);
      }
    }
    // If there are no children, fall back to the current #title attribute if it
    // exists.
    elseif (isset($element['#title'])) {
      $element['#title'] .= $suffix;
    }
  }

  /**
   * Entity builder method.
   *
   * @param string $entity_type
   *   The type of the entity.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity whose form is being built.
   *
   * @see \Drupal\content_translation\ContentTranslationHandler::entityFormAlter()
   */
  public function entityFormEntityBuild($entity_type, EntityInterface $entity, array $form, FormStateInterface $form_state) {
    $form_object = $form_state->getFormObject();
    $form_langcode = $form_object->getFormLangcode($form_state);

    if (!isset($entity->translation[$form_langcode])) {
      $entity->translation[$form_langcode] = array();
    }
    $values = $form_state->getValue('content_translation', array());
    $translation = &$entity->translation[$form_langcode];

    // @todo Use the entity setter when all entities support multilingual
    // properties.
    $translation['uid'] = !empty($values['name']) && ($account = user_load_by_name($values['name'])) ? $account->id() : 0;
    $translation['status'] = !empty($values['status']);
    $translation['created'] = !empty($values['created']) ? strtotime($values['created']) : REQUEST_TIME;
    $translation['changed'] = REQUEST_TIME;

    $source_langcode = $this->getSourceLangcode($form_state);
    if ($source_langcode) {
      $translation['source'] = $source_langcode;
    }

    $translation['outdated'] = !empty($values['outdated']);
    if (!empty($values['retranslate'])) {
      $this->retranslate($entity, $form_langcode);
    }

    // Set contextual information that can be reused during the storage phase.
    // @todo Remove this once translation metadata are converted to regular
    //   fields.
    $attributes = \Drupal::request()->attributes;
    $attributes->set('source_langcode', $source_langcode);
  }

  /**
   * Form validation handler for ContentTranslationHandler::entityFormAlter().
   *
   * Validates the submitted content translation metadata.
   */
  function entityFormValidate($form, FormStateInterface $form_state) {
    if (!$form_state->isValueEmpty('content_translation')) {
      $translation = $form_state->getValue('content_translation');
      // Validate the "authored by" field.
      if (!empty($translation['name']) && !($account = user_load_by_name($translation['name']))) {
        $form_state->setErrorByName('content_translation][name', t('The translation authoring username %name does not exist.', array('%name' => $translation['name'])));
      }
      // Validate the "authored on" field.
      if (!empty($translation['created']) && strtotime($translation['created']) === FALSE) {
        $form_state->setErrorByName('content_translation][created', t('You have to specify a valid translation authoring date.'));
      }
    }
  }

  /**
   * Form submission handler for ContentTranslationHandler::entityFormAlter().
   *
   * Takes care of the source language change.
   */
  public function entityFormSourceChange($form, FormStateInterface $form_state) {
    $form_object = $form_state->getFormObject();
    $entity = $form_object->getEntity();
    $source = $form_state->getValue(array('source_langcode', 'source'));

    $entity_type_id = $entity->getEntityTypeId();
    $form_state->setRedirect('content_translation.translation_add_' . $entity_type_id, array(
      $entity_type_id => $entity->id(),
      'source' => $source,
      'target' => $form_object->getFormLangcode($form_state),
    ));
    $languages = language_list();
    drupal_set_message(t('Source language set to: %language', array('%language' => $languages[$source]->getName())));
  }

  /**
   * Form submission handler for ContentTranslationHandler::entityFormAlter().
   *
   * Takes care of entity deletion.
   */
  function entityFormDelete($form, FormStateInterface $form_state) {
    $form_object = $form_state->getFormObject()->getEntity();
    $entity = $form_object->getEntity();
    if (count($entity->getTranslationLanguages()) > 1) {
      drupal_set_message(t('This will delete all the translations of %label.', array('%label' => $entity->label())), 'warning');
    }
  }

  /**
   * Form submission handler for ContentTranslationHandler::entityFormAlter().
   *
   * Takes care of content translation deletion.
   */
  function entityFormDeleteTranslation($form, FormStateInterface $form_state) {
    $form_object = $form_state->getFormObject();
    $entity = $form_object->getEntity();
    $entity_type_id = $entity->getEntityTypeId();
    $form_state->setRedirect('content_translation.translation_delete_' . $entity_type_id, array(
      $entity_type_id => $entity->id(),
      'language' => $form_object->getFormLangcode($form_state),
    ));
  }

  /**
   * Returns the title to be used for the entity form page.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity whose form is being altered.
   */
  protected function entityFormTitle(EntityInterface $entity) {
    return $entity->label();
  }

}
