<?php

namespace Drupal\views\Plugin\views\exposed_form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\ViewsPluginInterface;

/**
 * @defgroup views_exposed_form_plugins Views exposed form plugins
 * @{
 * Plugins that handle validation, submission, and rendering of exposed forms.
 *
 * Exposed forms are used for filters, sorts, and pager settings that are
 * exposed to site visitors. Exposed form plugins handle the rendering,
 * validation, and submission of exposed forms, and may add additional form
 * elements.
 *
 * To define an Exposed Form Plugin in a module you need to:
 * - Implement
 *   \Drupal\views\Plugin\views\exposed_form\ExposedFormPluginInterface.
 * - Usually you will want to extend the
 *   \Drupal\views\Plugin\views\exposed_form\ExposedFormPluginBase class.
 * - Exposed form plugins are annotated with
 *   \Drupal\views\Annotation\ViewsExposedForm annotation. See the
 *   @link annotation Annotations topic @endlink for more information about
 *   annotations.
 * - They must be in namespace directory Plugin\views\exposed_form.
 */

/**
 * Interface for exposed filter form plugins.
 *
 * Exposed form plugins handle the rendering, validation, and submission
 * of exposed forms, and may add additional form elements. These plugins can
 * also alter the view query. See
 * \Drupal\views\Plugin\views\exposed_form\InputRequired as an example of
 * that functionality.
 *
 * @see \Drupal\views\Annotation\ViewsExposedForm
 * @see \Drupal\views\Plugin\views\exposed_form\ExposedFormPluginBase
 */
interface ExposedFormPluginInterface extends ViewsPluginInterface {

  /**
   * Renders the exposed form.
   *
   * This method iterates over each handler configured to expose widgets
   * to the end user and attach those widgets to the exposed form.
   *
   * @param bool $block
   *   (optional) TRUE if the exposed form is being rendered as part of a
   *   block; FALSE (default) if not.
   *
   * @return array
   *   Form build array. This method returns an empty array if the form is
   *   being rendered as a block.
   *
   * @see \Drupal\views\ViewExecutable::build()
   */
  public function renderExposedForm($block = FALSE);

  /**
   * Runs before the view is rendered.
   *
   * Implement if your exposed form needs to run code before the view is
   * rendered.
   *
   * @param \Drupal\views\ResultRow[] $values
   *   An array of all ResultRow objects returned from the query.
   *
   * @see \Drupal\views\ViewExecutable::render()
   */
  public function preRender($values);

  /**
   * Runs after the view has been rendered.
   *
   * Implement if your exposed form needs to run code after the view is
   * rendered.
   *
   * @param string $output
   *   The rendered output of the view display.
   *
   * @see \Drupal\views\ViewExecutable::render()
   */
  public function postRender(&$output);

  /**
   * Runs before the view has been executed.
   *
   * Implement if your exposed form needs to run code before query execution.
   *
   * @see \Drupal\views\Plugin\views\display\DisplayPluginBase::preExecute()
   */
  public function preExecute();

  /**
   * Runs after the view has been executed.
   *
   * Implement if your exposed form needs to run code after query execution.
   */
  public function postExecute();

  /**
   * Alters the exposed form.
   *
   * The exposed form is built by calling the renderExposedForm() method on
   * this class, and then letting each exposed filter and sort handler add
   * widgets to the form. After that is finished, this method is called to
   * let the class alter the finished form.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @see \Drupal\views\Plugin\views\exposed_form\ExposedFormPluginInterface::renderExposedForm()
   * @see \Drupal\views\Form\ViewsExposedForm::buildForm()
   */
  public function exposedFormAlter(&$form, FormStateInterface $form_state);

  /**
   * Validates the exposed form submission.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @see \Drupal\views\Form\ViewsExposedForm::validateForm()
   */
  public function exposedFormValidate(&$form, FormStateInterface $form_state);

  /**
   * Submits the exposed form.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param array $exclude
   *   Array of keys that will not appear in $view->exposed_raw_input; for
   *   example, 'form_build_id'.
   *
   * @see \Drupal\views\Form\ViewsExposedForm::submitForm()
   */
  public function exposedFormSubmit(&$form, FormStateInterface $form_state, &$exclude);

}

/**
 * @}
 */
