<?php

/**
 * @file
 * Template for 'fatal_error_at_level' theme hook.
 *
 * @var int $level
 *   Recursion level at which to trigger an error.
 */
?>
<table>
  <tr>
    <td>Template begin</td>
    <td>
      <?php
      if ($level < 1) {
        // Call a non-existing function, to trigger a fatal error in any php version.
        non_existing_function();
      }
      else {
        print theme('fatal_error_at_level', ['level' => $level - 1]);
      }
      ?>
    </td>
  </tr>
</table>
