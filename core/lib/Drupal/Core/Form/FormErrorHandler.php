<?php

/**
 * @file
 * Contains \Drupal\Core\Form\FormErrorHandler.
 */

namespace Drupal\Core\Form;

use Drupal\Core\Render\Element;
use Drupal\Core\Routing\LinkGeneratorTrait;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\Url;
use Drupal\Core\Utility\LinkGeneratorInterface;

/**
 * Handles form errors.
 */
class FormErrorHandler implements FormErrorHandlerInterface {

  use StringTranslationTrait;
  use LinkGeneratorTrait;

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Constructs a new FormErrorHandler.
   *
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translation service.
   * @param \Drupal\Core\Utility\LinkGeneratorInterface $link_generator
   *   The link generation service.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   */
  public function __construct(TranslationInterface $string_translation, LinkGeneratorInterface $link_generator, RendererInterface $renderer) {
    $this->stringTranslation = $string_translation;
    $this->linkGenerator = $link_generator;
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public function handleFormErrors(array &$form, FormStateInterface $form_state) {
    // After validation check if there are errors.
    if ($errors = $form_state->getErrors()) {
      // Display error messages for each element.
      $this->displayErrorMessages($form, $form_state);

      // Loop through and assign each element its errors.
      $this->setElementErrorsFromFormState($form, $form_state);
    }

    return $this;
  }

  /**
   * Loops through and displays all form errors.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  protected function displayErrorMessages(array $form, FormStateInterface $form_state) {
    $error_links = [];
    $errors = $form_state->getErrors();
    // Loop through all form errors and check if we need to display a link.
    foreach ($errors as $name => $error) {
      $form_element = FormElementHelper::getElementByName($name, $form);
      $title = FormElementHelper::getElementTitle($form_element);

      // Only show links to erroneous elements that are visible.
      $is_visible_element = Element::isVisibleElement($form_element);
      // Only show links for elements that have a title themselves or have
      // children with a title.
      $has_title = !empty($title);
      // Only show links for elements with an ID.
      $has_id = !empty($form_element['#id']);

      // Do not show links to elements with suppressed messages. Most often
      // their parent element is used for inline errors.
      if (!empty($form_element['#error_no_message'])) {
        unset($errors[$name]);
      }
      elseif ($is_visible_element && $has_title && $has_id) {
        $error_links[] = $this->l($title, Url::fromRoute('<none>', [], ['fragment' => $form_element['#id'], 'external' => TRUE]));
        unset($errors[$name]);
      }
    }

    // Set normal error messages for all remaining errors.
    foreach ($errors as $error) {
      $this->drupalSetMessage($error, 'error');
    }

    if (!empty($error_links)) {
      $render_array = [
        [
         '#markup' => $this->formatPlural(count($error_links), '1 error has been found: ', '@count errors have been found: '),
        ],
        [
          '#theme' => 'item_list',
          '#items' => $error_links,
          '#context' => ['list_style' => 'comma-list'],
        ],
      ];
      $message = $this->renderer->renderPlain($render_array);
      $this->drupalSetMessage($message, 'error');
    }
  }

  /**
   * Stores the errors of each element directly on the element.
   *
   * We must provide a way for non-form functions to check the errors for a
   * specific element. The most common usage of this is a #pre_render callback.
   *
   * @param array $elements
   *   An associative array containing the structure of a form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  protected function setElementErrorsFromFormState(array &$elements, FormStateInterface &$form_state) {
    // Recurse through all children.
    foreach (Element::children($elements) as $key) {
      if (isset($elements[$key]) && $elements[$key]) {
        $this->setElementErrorsFromFormState($elements[$key], $form_state);
      }
    }

    // Store the errors for this element on the element directly.
    $elements['#errors'] = $form_state->getError($elements);
  }

  /**
   * Wraps drupal_set_message().
   *
   * @codeCoverageIgnore
   */
  protected function drupalSetMessage($message = NULL, $type = 'status', $repeat = FALSE) {
    drupal_set_message($message, $type, $repeat);
  }

}
