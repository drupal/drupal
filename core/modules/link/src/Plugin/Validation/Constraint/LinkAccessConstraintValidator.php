<?php

namespace Drupal\link\Plugin\Validation\Constraint;

use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates the LinkAccess constraint.
 */
class LinkAccessConstraintValidator extends ConstraintValidator implements ContainerInjectionInterface {

  /**
   * Proxy for the current user account.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  // phpcs:ignore Drupal.NamingConventions.ValidVariableName.LowerCamelName
  protected $current_user;

  /**
   * Constructs an instance of the LinkAccessConstraintValidator class.
   *
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current user account.
   */
  public function __construct(AccountProxyInterface $current_user) {
    $this->current_user = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_user')
    );
  }

  /**
   * {@inheritdoc}
   *
   * phpcs:ignore Drupal.Commenting.FunctionComment.VoidReturn
   * @return void
   */
  public function validate($value, Constraint $constraint) {
    if (isset($value)) {
      try {
        $url = $value->getUrl();
      }
      // If the URL is malformed this constraint cannot check access.
      catch (\InvalidArgumentException $e) {
        return;
      }
      // Disallow URLs if the current user doesn't have the 'link to any page'
      // permission nor can access this URI.
      $allowed = $this->current_user->hasPermission('link to any page') || $url->access();
      if (!$allowed) {
        $this->context->addViolation($constraint->message, ['@uri' => $value->uri]);
      }
    }
  }

}
