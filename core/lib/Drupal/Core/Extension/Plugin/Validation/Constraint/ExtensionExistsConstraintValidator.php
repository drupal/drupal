<?php

declare(strict_types = 1);

namespace Drupal\Core\Extension\Plugin\Validation\Constraint;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Extension\ThemeHandlerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates that a given extension exists.
 */
class ExtensionExistsConstraintValidator extends ConstraintValidator implements ContainerInjectionInterface {

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected ModuleHandlerInterface $moduleHandler;

  /**
   * The theme handler service.
   *
   * @var \Drupal\Core\Extension\ThemeHandlerInterface
   */
  protected ThemeHandlerInterface $themeHandler;

  /**
   * Constructs a ExtensionExistsConstraintValidator object.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler service.
   * @param \Drupal\Core\Extension\ThemeHandlerInterface $theme_handler
   *   The theme handler service.
   */
  public function __construct(ModuleHandlerInterface $module_handler, ThemeHandlerInterface $theme_handler) {
    $this->moduleHandler = $module_handler;
    $this->themeHandler = $theme_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('module_handler'),
      $container->get('theme_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function validate(mixed $extension_name, Constraint $constraint) {
    $variables = ['@name' => $extension_name];

    switch ($constraint->type) {
      case 'module':
        if (!$this->moduleHandler->moduleExists($extension_name)) {
          $this->context->addViolation($constraint->moduleMessage, $variables);
        }
        break;

      case 'theme':
        if (!$this->themeHandler->themeExists($extension_name)) {
          $this->context->addViolation($constraint->themeMessage, $variables);
        }
        break;

      default:
        throw new \InvalidArgumentException("Unknown extension type: '$constraint->type'");
    }
  }

}
