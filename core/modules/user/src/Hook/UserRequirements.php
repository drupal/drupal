<?php

declare(strict_types=1);

namespace Drupal\user\Hook;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\Requirement\RequirementSeverity;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Requirements for the User module.
 */
class UserRequirements {

  use StringTranslationTrait;

  public function __construct(
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly Connection $connection,
  ) {}

  /**
   * Implements hook_runtime_requirements().
   */
  #[Hook('runtime_requirements')]
  public function runtime(): array {
    $requirements = [];

    $result = (bool) $this->entityTypeManager->getStorage('user')->getQuery()
      ->accessCheck(FALSE)
      ->condition('uid', 0)
      ->range(0, 1)
      ->execute();

    if ($result === FALSE) {
      $requirements['anonymous user'] = [
        'title' => $this->t('Anonymous user'),
        'description' => $this->t('The anonymous user does not exist. See the <a href=":url">restore the anonymous (user ID 0) user record</a> for more information', [
          ':url' => 'https://www.drupal.org/node/1029506',
        ]),
        'severity' => RequirementSeverity::Warning,
      ];
    }

    $query = $this->connection->select('users_field_data');
    $query->addExpression('LOWER(mail)', 'lower_mail');
    $query->isNotNull('mail');
    $query->groupBy('lower_mail');
    $query->having('COUNT(uid) > :matches', [':matches' => 1]);
    $conflicts = $query->countQuery()->execute()->fetchField();

    if ($conflicts > 0) {
      $requirements['conflicting emails'] = [
        'title' => $this->t('Conflicting user emails'),
        'description' => $this->t('Some user accounts have email addresses that differ only by case. For example, one account might have alice@example.com and another might have Alice@Example.com. See <a href=":url">Conflicting User Emails</a> for more information.', [
          ':url' => 'https://www.drupal.org/node/3486109',
        ]),
        'severity' => RequirementSeverity::Warning,
      ];
    }

    return $requirements;
  }

}
