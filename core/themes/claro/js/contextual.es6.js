/**
 * @file
 * Theme overrides for contextual trigger.
 */

((Drupal) => {
  /**
   * Override Contextual's AuralView render() so contextual trigger text can be
   * processed with a theme function.
   *
   * @todo this entire override can be removed after
   *   https://drupal.org/node/3172956
   */
  Drupal.contextual.AuralView = Drupal.contextual.AuralView.extend({
    render: function render() {
      const isOpen = this.model.get('isOpen');
      this.$el.find('.contextual-links').prop('hidden', !isOpen);
      const triggerText = Drupal.t('@action @title configuration options', {
        '@action': !isOpen
          ? this.options.strings.open
          : this.options.strings.close,
        '@title': this.model.get('title'),
      });

      this.$el
        .find('.trigger')
        .html(Drupal.theme('contextualTriggerText', triggerText))
        .attr('aria-pressed', isOpen);
    },
  });

  /**
   * Constructs a contextual trigger element.
   *
   * @return {string}
   *   A string representing a DOM fragment.
   */
  Drupal.theme.contextualTrigger = () =>
    `<button class="contextual__trigger trigger visually-hidden focusable icon-link icon-link--small" type="button"></button>`;

  /**
   * Contextual link trigger text, typically seen only by screenreaders.
   *
   * @param {string} text
   *   The contextual link trigger text.
   *
   * @return {string}
   *   A string representing a DOM fragment.
   */
  Drupal.theme.contextualTriggerText = (text) =>
    `<span class="visually-hidden">${text}</span>`;
})(Drupal, Backbone);
