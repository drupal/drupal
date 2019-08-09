<?php

namespace Drupal\migrate\Exception;

use Drupal\Core\Entity\EntityConstraintViolationListInterface;
use Drupal\Core\Entity\RevisionableInterface;
use Drupal\migrate\MigrateException;
use Symfony\Component\Validator\ConstraintViolationInterface;

/**
 * To throw when an entity generated during the import is not valid.
 */
class EntityValidationException extends MigrateException {

  /**
   * The separator for combining multiple messages into a single string.
   *
   * Afterwards, the separator could be used to split a concatenated string
   * onto multiple lines.
   *
   * @code
   * explode(EntityValidationException::MESSAGES_SEPARATOR, $messages);
   * @endcode
   */
  const MESSAGES_SEPARATOR = '||';

  /**
   * The list of violations generated during the entity validation.
   *
   * @var \Drupal\Core\Entity\EntityConstraintViolationListInterface
   */
  protected $violations;

  /**
   * EntityValidationException constructor.
   *
   * @param \Drupal\Core\Entity\EntityConstraintViolationListInterface $violations
   *   The list of violations generated during the entity validation.
   */
  public function __construct(EntityConstraintViolationListInterface $violations) {
    $this->violations = $violations;

    $entity = $this->violations->getEntity();
    $locator = $entity->getEntityTypeId();

    if ($entity_id = $entity->id()) {
      $locator = sprintf('%s: %s', $locator, $entity_id);

      if ($entity instanceof RevisionableInterface && $revision_id = $entity->getRevisionId()) {
        $locator .= sprintf(', revision: %s', $revision_id);
      }
    }

    // Example: "[user]: field_a=Violation 1., field_b=Violation 2.".
    // Example: "[user: 1]: field_a=Violation 1., field_b=Violation 2.".
    // Example: "[node: 19, revision: 12129]: field_a=Violation 1.".
    parent::__construct(sprintf('[%s]: %s', $locator, implode(static::MESSAGES_SEPARATOR, $this->getViolationMessages())));
  }

  /**
   * Returns the list of violation messages.
   *
   * @return string[]
   *   The list of violation messages.
   */
  public function getViolationMessages() {
    $messages = [];

    foreach ($this->violations as $violation) {
      assert($violation instanceof ConstraintViolationInterface);
      $messages[] = sprintf('%s=%s', $violation->getPropertyPath(), $violation->getMessage());
    }

    return $messages;
  }

  /**
   * Returns the list of violations generated during the entity validation.
   *
   * @return \Drupal\Core\Entity\EntityConstraintViolationListInterface
   *   The list of violations generated during the entity validation.
   */
  public function getViolations() {
    return $this->violations;
  }

}
