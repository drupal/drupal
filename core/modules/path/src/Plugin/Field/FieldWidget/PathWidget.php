<?php

namespace Drupal\path\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
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
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $entity = $items->getEntity();
    $path = array();
    if (!$entity->isNew()) {
      $conditions = array('source' => '/' . $entity->urlInfo()->getInternalPath());
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
      'source' => !$entity->isNew() ? '/' . $entity->urlInfo()->getInternalPath() : NULL,
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
      '#description' => $this->t('The alternative URL for this content. Use a relative path. For example, enter "/about" for the about page.'),
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
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public static function validateFormElement(array &$element, FormStateInterface $form_state) {
    // Trim the submitted value of whitespace and slashes.
    $alias = rtrim(trim($element['alias']['#value']), " \\/");
    if (!empty($alias)) {
      $form_state->setValueForElement($element['alias'], $alias);

      // Validate that the submitted alias does not exist yet.
      $is_exists = \Drupal::service('path.alias_storage')->aliasExists($alias, $element['langcode']['#value'], $element['source']['#value']);
      if ($is_exists) {
        $form_state->setError($element, t('The alias is already in use.'));
      }
    }

    if ($alias && $alias[0] !== '/') {
      $form_state->setError($element, t('The alias needs to start with a slash.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function errorElement(array $element, ConstraintViolationInterface $violation, array $form, FormStateInterface $form_state) {
    return $element['alias'];
  }

}
