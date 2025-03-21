<?php

declare(strict_types=1);

namespace Drupal\Core\Validation\Plugin\Validation\Constraint;

use Drupal\Core\Locale\CountryManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Validation\Attribute\Constraint;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\Constraints\Choice;

/**
 * Validation constraint for country codes.
 */
#[Constraint(
  id: 'CountryCode',
  label: new TranslatableMarkup('CountryCode', [], ['context' => 'Validation']),
)]
class CountryCodeConstraint implements ContainerFactoryPluginInterface {

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): Choice {
    $countries = $container->get(CountryManagerInterface::class)->getList();
    $configuration['choices'] = array_keys($countries);
    return new Choice($configuration);
  }

}
