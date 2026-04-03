<?php

namespace Drupal\filter\Plugin\DataType;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\Attribute\DataType;
use Drupal\Core\TypedData\OptionsProviderInterface;
use Drupal\Core\TypedData\Plugin\DataType\StringData;
use Drupal\filter\FilterFormatInterface;
use Drupal\filter\FilterFormatRepositoryInterface;

/**
 * The filter format data type.
 */
#[DataType(
  id: "filter_format",
  label: new TranslatableMarkup("Filter format"),
)]
class FilterFormat extends StringData implements OptionsProviderInterface {

  /**
   * {@inheritdoc}
   */
  public function getPossibleValues(?AccountInterface $account = NULL) {
    return array_keys($this->getPossibleOptions($account));
  }

  /**
   * {@inheritdoc}
   */
  public function getPossibleOptions(?AccountInterface $account = NULL) {
    return $this->getFormatsAsOptions($account);
  }

  /**
   * {@inheritdoc}
   */
  public function getSettableValues(?AccountInterface $account = NULL) {
    return array_keys($this->getSettableOptions($account));
  }

  /**
   * {@inheritdoc}
   */
  public function getSettableOptions(?AccountInterface $account = NULL) {
    return $this->getFormatsAsOptions($account);
  }

  /**
   * Returns a list of filter format config entity labels keyed by their ID.
   *
   * @param \Drupal\Core\Session\AccountInterface|null $account
   *   (optional) The user account for which to filter the options. If omitted,
   *   all options are returned.
   *
   * @return array<string, \Drupal\filter\FilterFormatInterface>
   *   A list of filter format config entity labels keyed by their ID.
   */
  protected function getFormatsAsOptions(?AccountInterface $account = NULL): array {
    $repository = \Drupal::service(FilterFormatRepositoryInterface::class);
    return array_map(
      fn(FilterFormatInterface $format): string|\Stringable => $format->label(),
      $account ? $repository->getFormatsForAccount($account) : $repository->getAllFormats(),
    );
  }

}
