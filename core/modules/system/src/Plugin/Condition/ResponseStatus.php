<?php

declare(strict_types=1);

namespace Drupal\system\Plugin\Condition;

use Drupal\Core\Condition\Attribute\Condition;
use Drupal\Core\Condition\ConditionPluginBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\PluralTranslatableMarkup;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

/**
 * Provides a 'Response status' condition.
 */
#[Condition(
  id: "response_status",
  label: new TranslatableMarkup("Response status"),
)]
class ResponseStatus extends ConditionPluginBase implements ContainerFactoryPluginInterface {

  /**
   * The request stack.
   */
  protected RequestStack $requestStack;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    $instance = new static($configuration, $plugin_id, $plugin_definition);
    $instance->setRequestStack($container->get('request_stack'));
    return $instance;
  }

  public function setRequestStack(RequestStack $requestStack): void {
    $this->requestStack = $requestStack;
  }

  /**
   * {@inheritdoc}
   */
  public function isNegated(): bool {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return ['status_codes' => []] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    $status_codes = [
      Response::HTTP_OK => $this->t('Success (@status_code)', ['@status_code' => Response::HTTP_OK]),
      Response::HTTP_FORBIDDEN => $this->t('Access denied (@status_code)', ['@status_code' => Response::HTTP_FORBIDDEN]),
      Response::HTTP_NOT_FOUND => $this->t('Page not found (@status_code)', ['@status_code' => Response::HTTP_NOT_FOUND]),
    ];
    $form['status_codes'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Response status'),
      '#options' => $status_codes,
      '#default_value' => $this->configuration['status_codes'],
      '#description' => $this->t('Shows the block on pages with any matching response status. If nothing is checked, the block is shown on all pages. Other response statuses are not used.'),
    ];
    return parent::buildConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state): void {
    $this->configuration['status_codes'] = array_keys(array_filter($form_state->getValue('status_codes')));
    parent::submitConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function summary(): PluralTranslatableMarkup {
    $allowed_codes = $this->configuration['status_codes'];
    $status_codes = [Response::HTTP_OK, Response::HTTP_FORBIDDEN, Response::HTTP_NOT_FOUND];
    $result = empty($allowed_codes) ? $status_codes : $allowed_codes;
    $count = count($result);
    $codes = implode(', ', $result);
    if (!empty($this->configuration['negate'])) {
      return $this->formatPlural($count, 'Request response code is not: @codes', 'Request response code is not one of the following: @codes', ['@codes' => $codes]);
    }
    return $this->formatPlural($count, 'Request response code is: @codes', 'Request response code is one of the following: @codes', ['@codes' => $codes]);
  }

  /**
   * {@inheritdoc}
   */
  public function evaluate(): bool {
    $allowed_codes = $this->configuration['status_codes'];
    if (empty($allowed_codes)) {
      return TRUE;
    }
    $exception = $this->requestStack->getCurrentRequest()->attributes->get('exception');
    if ($exception) {
      return ($exception instanceof HttpExceptionInterface && in_array($exception->getStatusCode(), $allowed_codes, TRUE));
    }
    return in_array(Response::HTTP_OK, $allowed_codes, TRUE);
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts(): array {
    $contexts = parent::getCacheContexts();
    $contexts[] = 'url.path';
    return $contexts;
  }

}
