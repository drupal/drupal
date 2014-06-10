<?php

/**
 * @file
 * Contains \Drupal\path\Plugin\Field\FieldWidget\PathWidget.
 */

namespace Drupal\path\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Language\LanguageInterface;
use Symfony\Component\Validator\ConstraintViolationInterface;

/**
 * Plugin implementation of the 'path' widget.
 *
 * @FieldWidget(
 *   id = "path",
 *   label = @Translation("URL alias"),
 *   field_types = {
 *     "path"
 *   }
 * )
 */
class PathWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, array &$form_state) {
    $entity = $items->getEntity();
    $path = array();
    if (!$entity->isNew()) {
      $conditions = array('source' => $entity->getSystemPath());
      if ($items->getLangcode() != LanguageInterface::LANGCODE_NOT_SPECIFIED) {
        $conditions['langcode'] = $items->getLangcode();
      }
      $path = \Drupal::service('path.alias_storage')->load($conditions);
      if ($path === FALSE) {
        $path = array();
      }
    }
    $path += array(
      'pid' => NULL,
      'source' => !$entity->isNew() ? $entity->getSystemPath() : NULL,
      'alias' => '',
      'langcode' => $items->getLangcode(),
    );

    $element += array(
      '#element_validate' => array(array(get_class($this), 'validateFormElement')),
    );
    $element['alias'] = array(
      '#type' => 'textfield',
      '#title' => $element['#title'],
      '#default_value' => $path['alias'],
      '#required' => $element['#required'],
      '#maxlength' => 255,
      '#description' => t('The alternative URL for this content. Use a relative path without a trailing slash. For example, enter "about" for the about page.'),
    );
    $element['pid'] = array(
      '#type' => 'value',
      '#value' => $path['pid'],
    );
    $element['source'] = array(
      '#type' => 'value',
      '#value' => $path['source'],
    );
    $element['langcode'] = array(
      '#type' => 'value',
      '#value' => $path['langcode'],
    );
    return $element;
  }

  /**
   * Form element validation handler for URL alias form element.
   *
   * @param array $element
   *   The form element.
   * @param array $form_state
   *   The form state.
   */
  public static function validateFormElement(array &$element, array &$form_state) {
    if (!empty($element['alias']['#value'])) {
      // Trim the submitted value.
      $alias = trim($element['alias']['#value']);
      $form_builder = \Drupal::formBuilder();
      $form_builder->setValue($element['alias'], $alias, $form_state);

      // Entity language needs special care. Since the language of the URL alias
      // depends on the entity language, and the entity language can be switched
      // right within the same form, we need to conditionally overload the
      // originally assigned URL alias language.
      // @see \Drupal\content_translation\ContentTranslationController::entityFormAlter()
      if (isset($form_state['values']['langcode'])) {
        $form_builder->setValue($element['langcode'], $form_state['values']['langcode'], $form_state);
      }

      // Validate that the submitted alias does not exist yet.
      $is_exists = \Drupal::service('path.alias_storage')->aliasExists($alias, $element['langcode']['#value'], $element['source']['#value']);
      if ($is_exists) {
        $form_builder->setError($element, $form_state, t('The alias is already in use.'));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function errorElement(array $element, ConstraintViolationInterface $violation, array $form, array &$form_state) {
    return $element['alias'];
  }

}
