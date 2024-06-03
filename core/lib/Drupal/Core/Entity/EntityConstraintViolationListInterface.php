<?php

namespace Drupal\Core\Entity;

use Drupal\Core\Session\AccountInterface;
use Symfony\Component\Validator\ConstraintViolationListInterface;

/**
 * Interface for the result of entity validation.
 *
 * The Symfony violation list is extended with methods that allow filtering
 * violations by fields and field access. Forms leverage that to skip possibly
 * pre-existing violations that cannot be caused or fixed by the form.
 */
interface EntityConstraintViolationListInterface extends ConstraintViolationListInterface {

  /**
   * Gets violations flagged on entity level, not associated with any field.
   *
   * @return \Drupal\Core\Entity\EntityConstraintViolationListInterface
   *   A list of violations on the entity level.
   */
  public function getEntityViolations();

  /**
   * Gets the violations of the given field.
   *
   * @param string $field_name
   *   The name of the field to get violations for.
   *
   * @return \Symfony\Component\Validator\ConstraintViolationListInterface
   *   The violations of the given field.
   */
  public function getByField($field_name);

  /**
   * Gets the violations of the given fields.
   *
   * When violations should be displayed for a sub-set of visible fields only,
   * this method may be used to filter the set of visible violations first.
   *
   * @param string[] $field_names
   *   The names of the fields to get violations for.
   *
   * @return \Drupal\Core\Entity\EntityConstraintViolationListInterface
   *   A list of violations of the given fields.
   */
  public function getByFields(array $field_names);

  /**
   * Filters this violation list by the given error codes.
   *
   * Copied from Symfony parent class
   * \Symfony\Component\Validator\ConstraintViolationList.
   *
   * @param string|string[] $codes
   *   The codes to find.
   *
   * @return \Drupal\Core\Entity\EntityConstraintViolationListInterface
   *   A list of violations of the given fields.
   */
  public function findByCodes(string|array $codes): static;

  /**
   * Filters this violation list by the given fields.
   *
   * The returned object just has violations attached to the provided fields.
   *
   * When violations should be displayed for a sub-set of visible fields only,
   * this method may be used to filter the set of visible violations first.
   *
   * @param string[] $field_names
   *   The names of the fields to filter violations for.
   *
   * @return $this
   */
  public function filterByFields(array $field_names);

  /**
   * Filters this violation list to apply for accessible fields only.
   *
   * Violations for inaccessible fields are removed so the returned object just
   * has the remaining violations.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   (optional) The user for which to check access, or NULL to check access
   *   for the current user. Defaults to NULL.
   *
   * @return $this
   */
  public function filterByFieldAccess(?AccountInterface $account = NULL);

  /**
   * Returns the names of all violated fields.
   *
   * @return string[]
   *   An array of field names.
   */
  public function getFieldNames();

  /**
   * The entity which has been validated.
   *
   * @return \Drupal\Core\Entity\FieldableEntityInterface
   *   The entity object.
   */
  public function getEntity();

}
