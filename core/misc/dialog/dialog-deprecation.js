/**
 * @file
 * Maintains and deprecates Dialog jQuery events.
 */

(function ($, Drupal, once) {
  if (once('drupal-dialog-deprecation-listener', 'html').length) {
    const eventSpecial = {
      handle($event) {
        const $element = $($event.target);
        const event = $event.originalEvent;
        const dialog = event.dialog;
        const dialogArguments = [$event, dialog, $element, event?.settings];
        $event.handleObj.handler.apply(this, dialogArguments);
      },
    };

    $.event.special['dialog:beforecreate'] = eventSpecial;
    $.event.special['dialog:aftercreate'] = eventSpecial;
    $.event.special['dialog:beforeclose'] = eventSpecial;
    $.event.special['dialog:afterclose'] = eventSpecial;

    const listenDialogEvent = (event) => {
      const windowEvents = $._data(window, 'events');
      const isWindowHasDialogListener = windowEvents[event.type];
      if (isWindowHasDialogListener) {
        Drupal.deprecationError({
          message: `jQuery event ${event.type} is deprecated in 10.3.0 and is removed from Drupal:12.0.0. See https://www.drupal.org/node/3422670`,
        });
      }
    };

    [
      'dialog:beforecreate',
      'dialog:aftercreate',
      'dialog:beforeclose',
      'dialog:afterclose',
    ].forEach((e) => window.addEventListener(e, listenDialogEvent));

    window.addEventListener('dialog:beforecreate', (event) => {
      const dialog = event.target;
      $(dialog).on('dialogButtonsChange.dialogDeprecation', (e) => {
        // If triggered by jQuery.
        if (!e?.originalEvent) {
          Drupal.deprecationError({
            message: `jQuery event dialogButtonsChange is deprecated in 11.2.0 and is removed from Drupal:12.0.0. See https://www.drupal.org/node/3464202`,
          });
          dialog.dispatchEvent(new CustomEvent('dialogButtonsChange'));
        }
      });
    });

    window.addEventListener('dialog:beforeclose', (event) => {
      const dialog = event.target;
      $(dialog).off(`dialogButtonsChange.dialogDeprecation`);
    });
  }
})(jQuery, Drupal, once);
