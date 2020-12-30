<?php


namespace Drupal\auto_updates_test\ReadinessChecker;


use Drupal\auto_updates\ReadinessChecker\ReadinessCheckerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;

class TestChecker2 implements ReadinessCheckerInterface {

  use StringTranslationTrait;

  public function getErrorsSummary(): ?TranslatableMarkup {
    return $this->t('Errors summary');
  }

  public function getWarningsSummary(): ?TranslatableMarkup {
    return $this->t('Warning summary');
  }

  /**
   * @inheritDoc
   */
  public function getWarnings(): array {
    return [
      $this->t("warning1"),
      $this->t("warning2"),
    ];
  }

  /**
   * @inheritDoc
   */
  public function getErrors(): array {
    return [
      $this->t("err1"),
      $this->t("err2"),
    ];
  }
}
