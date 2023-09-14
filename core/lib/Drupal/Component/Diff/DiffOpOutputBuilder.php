<?php declare(strict_types=1);

namespace Drupal\Component\Diff;

use Drupal\Component\Diff\Engine\DiffOp;
use Drupal\Component\Diff\Engine\DiffOpAdd;
use Drupal\Component\Diff\Engine\DiffOpChange;
use Drupal\Component\Diff\Engine\DiffOpCopy;
use Drupal\Component\Diff\Engine\DiffOpDelete;
use SebastianBergmann\Diff\Differ;
use SebastianBergmann\Diff\Output\DiffOutputBuilderInterface;

/**
 * Returns a diff as an array of DiffOp operations.
 */
final class DiffOpOutputBuilder implements DiffOutputBuilderInterface {

  /**
   * A constant to manage removal+addition as a single operation.
   */
  private const CHANGED = 999;

  /**
   * {@inheritdoc}
   */
  public function getDiff(array $diff): string {
    return serialize($this->toOpsArray($diff));
  }

  /**
   * Converts the output of Differ to an array of DiffOp* value objects.
   *
   * @param array $diff
   *   The array output of Differ::diffToArray().
   *
   * @return \Drupal\Component\Diff\Engine\DiffOp[]
   *   An array of DiffOp* value objects.
   */
  public function toOpsArray(array $diff): array {
    $ops = [];
    $hunkMode = NULL;
    $hunkSource = [];
    $hunkTarget = [];

    for ($i = 0; $i < count($diff); $i++) {

      // Handle a sequence of removals + additions as a sequence of changes, and
      // manages the tail if required.
      if ($diff[$i][1] === Differ::REMOVED) {
        if ($hunkMode !== NULL) {
          $ops[] = $this->hunkOp($hunkMode, $hunkSource, $hunkTarget);
          $hunkSource = [];
          $hunkTarget = [];
        }
        for ($n = $i; $n < count($diff) && $diff[$n][1] === Differ::REMOVED; $n++) {
          $hunkSource[] = $diff[$n][0];
        }
        for (; $n < count($diff) && $diff[$n][1] === Differ::ADDED; $n++) {
          $hunkTarget[] = $diff[$n][0];
        }
        if (count($hunkTarget) === 0) {
          $ops[] = $this->hunkOp(Differ::REMOVED, $hunkSource, $hunkTarget);
        }
        else {
          $ops[] = $this->hunkOp(self::CHANGED, $hunkSource, $hunkTarget);
        }
        $hunkMode = NULL;
        $hunkSource = [];
        $hunkTarget = [];
        $i = $n - 1;
        continue;
      }

      // When here, we are adding or copying the item. Removing or changing is
      // managed above.
      if ($hunkMode === NULL) {
        $hunkMode = $diff[$i][1];
      }
      elseif ($hunkMode !== $diff[$i][1]) {
        $ops[] = $this->hunkOp($hunkMode, $hunkSource, $hunkTarget);
        $hunkMode = $diff[$i][1];
        $hunkSource = [];
        $hunkTarget = [];
      }

      $hunkSource[] = $diff[$i][0];
    }

    if ($hunkMode !== NULL) {
      $ops[] = $this->hunkOp($hunkMode, $hunkSource, $hunkTarget);
    }

    return $ops;
  }

  /**
   * Returns the proper DiffOp object based on the hunk mode.
   *
   * @param int $mode
   *   A Differ constant or self::CHANGED.
   * @param string[] $source
   *   An array of strings to be changed/added/removed/copied.
   * @param string[] $source
   *   The array of strings to be changed to when self::CHANGED is specified.
   *
   * @return \Drupal\Component\Diff\Engine\DiffOp
   *   A DiffOp* value object.
   *
   * @throw \InvalidArgumentException
   *   When $mode is not valid.
   */
  private function hunkOp(int $mode, array $source, array $target): DiffOp {
    switch ($mode) {
      case Differ::OLD:
        return new DiffOpCopy($source);

      case self::CHANGED:
        return new DiffOpChange($source, $target);

      case Differ::ADDED:
        return new DiffOpAdd($source);

      case Differ::REMOVED:
        return new DiffOpDelete($source);

    }
    throw new \InvalidArgumentException("Invalid \$mode {$mode} specified");
  }

}
