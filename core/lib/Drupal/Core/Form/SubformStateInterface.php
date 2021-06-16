<?php

namespace Drupal\Core\Form;

/**
 * Stores information about the state of a subform.
 *
 * In the context of Drupal's Form API, a subform is a form definition array
 * that will be nested into a "parent" form. For instance:
 *
 * @code
 * $subform = [
 *   'method' => [
 *     '#type' => 'select',
 *     // …
 *   ],
 * ];
 * $form = [
 *   // …
 *   'settings' => $subform,
 * ];
 * @endcode
 *
 * All input fields nested under "settings" are then considered part of that
 * "subform". The concept is used mostly when the subform is defined by a
 * different class (potentially even in a different module) than the parent
 * form. This is often the case for plugins: a plugin's buildConfigurationForm()
 * would then be handed an instance of this interface as the second parameter.
 *
 * The benefit of doing this is that the plugin can then just define the form –
 * and use the form state – as if it would define a "proper" form, not nested in
 * some other form structure. This means that it won't have to know the key(s)
 * under which its form structure will be nested – for instance, when retrieving
 * the form values during form validation or submission.
 *
 * Contrary to "proper" forms, subforms don't translate to a <form> tag in the
 * HTML response. Instead, they can only be discerned in the HTML code by the
 * nesting of the input tags' names.
 *
 * @see \Drupal\Core\Plugin\PluginFormInterface::buildConfigurationForm()
 */
interface SubformStateInterface extends FormStateInterface {

  /**
   * Gets the complete form state.
   *
   * @return \Drupal\Core\Form\FormStateInterface
   */
  public function getCompleteFormState();

}
