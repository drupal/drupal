<?php

namespace Drupal\Core\Form;

use Drupal\Component\Utility\NestedArray;

/**
 * Stores information about the state of a subform.
 */
class SubformState extends FormStateDecoratorBase implements SubformStateInterface {

  use FormStateValuesTrait;

  /**
   * The parent form.
   *
   * @var mixed[]
   */
  protected $parentForm;

  /**
   * The subform.
   *
   * @var mixed[]
   */
  protected $subform;

  /**
   * Constructs a new instance.
   *
   * @param mixed[] $subform
   *   The subform for which to create a form state.
   * @param mixed[] $parent_form
   *   The subform's parent form.
   * @param \Drupal\Core\Form\FormStateInterface $parent_form_state
   *   The parent form state.
   * @param \Drupal\Core\Form\FormInterface|null $subformFormObject
   *   The subform form object when it's not the same as the parent form.
   */
  protected function __construct(array &$subform, array &$parent_form, FormStateInterface $parent_form_state, protected readonly ?FormInterface $subformFormObject = NULL) {
    $this->decoratedFormState = $parent_form_state;
    $this->parentForm = $parent_form;
    $this->subform = $subform;
  }

  /**
   * Creates a new instance for a subform.
   *
   * @param mixed[] $subform
   *   The subform for which to create a form state.
   * @param mixed[] $parent_form
   *   The subform's parent form.
   * @param \Drupal\Core\Form\FormStateInterface $parent_form_state
   *   The parent form state.
   * @param \Drupal\Core\Form\FormInterface|null $subform_form_object
   *   The subform form object when it's not the same as the parent form.
   *
   * @return static
   */
  public static function createForSubform(array &$subform, array &$parent_form, FormStateInterface $parent_form_state, ?FormInterface $subform_form_object = NULL) {
    return new static($subform, $parent_form, $parent_form_state, $subform_form_object);
  }

  /**
   * Gets the subform's parents relative to its parent form.
   *
   * @param string $property
   *   The property name (#parents or #array_parents).
   *
   * @return mixed
   *
   * @throws \InvalidArgumentException
   *   Thrown when the requested property does not exist.
   * @throws \UnexpectedValueException
   *   Thrown when the subform is not contained by the given parent form.
   */
  protected function getParents($property) {
    foreach ([$this->subform, $this->parentForm] as $form) {
      if (!isset($form[$property]) || !is_array($form[$property])) {
        throw new \RuntimeException(sprintf('The subform and parent form must contain the %s property, which must be an array. Try calling this method from a #process callback instead.', $property));
      }
    }

    $relative_subform_parents = $this->subform[$property];
    // Remove all of the subform's parents that are also the parent form's
    // parents, so we are left with the parents relative to the parent form.
    foreach ($this->parentForm[$property] as $parent_form_parent) {
      if ($parent_form_parent !== $relative_subform_parents[0]) {
        // The parent form's parents are the subform's parents as well. If we
        // find no match, that means the given subform is not contained by the
        // given parent form.
        throw new \UnexpectedValueException('The subform is not contained by the given parent form.');
      }
      array_shift($relative_subform_parents);
    }

    return $relative_subform_parents;
  }

  /**
   * {@inheritdoc}
   */
  public function &getValues() {
    $exists = NULL;
    $values = &NestedArray::getValue(parent::getValues(), $this->getParents('#parents'), $exists);
    if (!$exists) {
      $values = [];
    }
    elseif (!is_array($values)) {
      throw new \UnexpectedValueException('The form state values do not belong to the subform.');
    }

    return $values;
  }

  /**
   * {@inheritdoc}
   */
  public function getCompleteFormState() {
    return $this->decoratedFormState instanceof SubformStateInterface ? $this->decoratedFormState->getCompleteFormState() : $this->decoratedFormState;
  }

  /**
   * {@inheritdoc}
   */
  public function setLimitValidationErrors($limit_validation_errors) {
    if (is_array($limit_validation_errors)) {
      $limit_validation_errors = array_merge($this->getParents('#parents'), $limit_validation_errors);
    }

    return parent::setLimitValidationErrors($limit_validation_errors);
  }

  /**
   * {@inheritdoc}
   */
  public function getLimitValidationErrors() {
    $limit_validation_errors = parent::getLimitValidationErrors();
    if (is_array($limit_validation_errors)) {
      return array_slice($limit_validation_errors, count($this->getParents('#parents')));

    }
    return $limit_validation_errors;
  }

  /**
   * {@inheritdoc}
   */
  public function setErrorByName($name, $message = '') {
    $parents = $this->subform['#array_parents'];
    $parents[] = $name;
    $name = implode('][', $parents);
    parent::setErrorByName($name, $message);

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormObject() {
    if ($this->subformFormObject) {
      return $this->subformFormObject;
    }

    return parent::getFormObject();
  }

}
