<?php

namespace Drupal\auto_updates\ReadinessChecker;

use Drupal\Core\StringTranslation\TranslatableMarkup;

class ReadinessCheckerResult {

  /**
   * @var \Drupal\Core\StringTranslation\TranslatableMarkup
   */
  protected $errorsSummary;

  /**
   * @var \Drupal\Core\StringTranslation\TranslatableMarkup
   */
  protected $warningsSummary;

  /**
   * @var \Drupal\Core\StringTranslation\TranslatableMarkup[]
   */
  protected $errorMessages;

  /**
   * @var \Drupal\Core\StringTranslation\TranslatableMarkup[]
   */
  protected $warningMessages;


  /**
   * ReadinessCheckerResult constructor.
   *
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup $errors_summary
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup[] $error_messages
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup $warnings_summary
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup[] $warning_messages
   */
  private function __construct(?TranslatableMarkup $errors_summary, array $error_messages, ?TranslatableMarkup $warnings_summary, array $warning_messages) {
    $this->errorsSummary = $errors_summary;
    $this->warningsSummary = $warnings_summary;
    $this->errorMessages = $error_messages;
    $this->warningMessages = $warning_messages;
  }

  public static function createFromReadinessChecker(ReadinessCheckerInterface $readinessChecker) {
    return new static(
      $readinessChecker->getErrorsSummary(),
      $readinessChecker->getErrors(),
      $readinessChecker->getWarningsSummary(),
      $readinessChecker->getWarnings()
    );
  }


  /**
   * Gets the error summary.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup|null
   *   The summary.
   */
  public function getErrorsSummary():?TranslatableMarkup {
    return $this->errorsSummary;
  }

  /**
   * Gets the warning summary.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup|null
   *   The summary.
   */
  public function getWarningsSummary():?TranslatableMarkup {
    return $this->warningsSummary;
  }

  /**
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup[]
   */
  public function getErrorMessages():array {
    return $this->errorMessages;
  }

  /**
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup[]
   */
  public function getWarningMessages():array {
    return $this->warningMessages;
  }



}
