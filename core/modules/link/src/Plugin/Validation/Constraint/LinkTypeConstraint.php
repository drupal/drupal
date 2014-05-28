<?php

/**
 * @file
 * Contains \Drupal\link\Plugin\Validation\Constraint\LinkTypeConstraint.
 */

namespace Drupal\link\Plugin\Validation\Constraint;

use Drupal\link\LinkItemInterface;
use Drupal\Core\Url;
use Drupal\Core\Routing\MatchingRouteNotFoundException;
use Drupal\Core\ParamConverter\ParamNotConvertedException;
use Drupal\Component\Utility\UrlHelper;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidatorInterface;
use Symfony\Component\Validator\ExecutionContextInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Validation constraint for links receiving data allowed by its settings.
 *
 * @Plugin(
 *   id = "LinkType",
 *   label = @Translation("Link data valid for link type.", context = "Validation"),
 * )
 */
class LinkTypeConstraint extends Constraint implements ConstraintValidatorInterface {

  public $message = 'The URL %url is not valid.';

  /**
   * @var \Symfony\Component\Validator\ExecutionContextInterface
   */
  protected $context;

  /**
   * {@inheritDoc}
   */
  public function initialize(ExecutionContextInterface $context) {
    $this->context = $context;
  }

  /**
   * {@inheritdoc}
   */
  public function validatedBy() {
    return get_class($this);
  }

  /**
   * {@inheritdoc}
   */
  public function validate($value, Constraint $constraint) {
    if (isset($value)) {
      $url_is_valid = TRUE;
      /** @var $link_item \Drupal\link\LinkItemInterface */
      $link_item = $value;
      $link_type = $link_item->getFieldDefinition()->getSetting('link_type');
      $url_string = $link_item->url;
      // Validate the url property.
      if ($url_string !== '') {
        try {
          // @todo This shouldn't be needed, but massageFormValues() may not
          //   run.
          $parsed_url = UrlHelper::parse($url_string);

          $url = Url::createFromPath($parsed_url['path']);

          if ($url->isExternal() && !UrlHelper::isValid($url_string, TRUE)) {
            $url_is_valid = FALSE;
          }
          elseif ($url->isExternal() && !($link_type & LinkItemInterface::LINK_EXTERNAL)) {
            $url_is_valid = FALSE;
          }
        }
        catch (NotFoundHttpException $e) {
          $url_is_valid = FALSE;
        }
        catch (MatchingRouteNotFoundException $e) {
          $url_is_valid = FALSE;
        }
        catch (ParamNotConvertedException $e) {
          $url_is_valid = FALSE;
        }
      }
      if (!$url_is_valid) {
        $this->context->addViolation($this->message, array('%url' => $url_string));
      }
    }
  }
}

