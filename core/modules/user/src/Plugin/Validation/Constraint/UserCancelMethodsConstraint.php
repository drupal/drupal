<?php

declare(strict_types=1);

namespace Drupal\user\Plugin\Validation\Constraint;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Validation\Attribute\Constraint;
use Drupal\user\AccountCancellation;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\Constraints\Choice;

/**
 * Constraint for the user cancel method.
 */
#[Constraint(
  id: 'UserCancelMethod',
  label: new TranslatableMarkup('UserCancelMethod', [], ['context' => 'Validation']),
)]
class UserCancelMethodsConstraint implements ContainerFactoryPluginInterface {

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): Choice {
    $configuration['choices'] = array_keys($container->get(AccountCancellation::class)->cancelMethods()['#options']);
    return new Choice(...$configuration);
  }

}
