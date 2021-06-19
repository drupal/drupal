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
        throw new Exception('Exception in template.');
      }

      print theme('exception_at_level', ['level' => $level - 1]);
      ?>
    </td>
  </tr>
</table>
