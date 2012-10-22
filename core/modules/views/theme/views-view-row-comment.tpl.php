<?php

/**
 * @file
 * Default simple view template to display a single comment.
 *
 * Rather than doing anything with this particular template, it is more
 * efficient to use a variant of the comment.tpl.php based upon the view,
 * which will be named comment-view-VIEWNAME.tpl.php. This isn't actually
 * a views template, which is why it's not used here, but is a template
 * 'suggestion' given to the comment template, and is used exactly
 * the same as any other variant of the comment template file, such as
 * node-nodeTYPE.tpl.php
 *
 * @ingroup views_templates
 */
?>
<?php print $comment; ?>
