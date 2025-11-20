/* eslint-disable import/no-extraneous-dependencies */
// cspell:ignore linksuggestionediting focusables

import { Plugin } from 'ckeditor5/src/core';
import { SwitchButtonView, View, ViewCollection } from 'ckeditor5/src/ui';
import { ensureSafeUrl } from '@ckeditor/ckeditor5-link/src/utils';
import DrupalEntityLinkSuggestionsEditing from './linksuggestionediting';
import initializeAutocomplete from './autocomplete';

class DrupalEntityLinkSuggestions extends Plugin {
  /**
   * @inheritdoc
   */
  static get requires() {
    return [DrupalEntityLinkSuggestionsEditing];
  }

  init() {
    const { editor } = this;

    // TRICKY: Work-around until the CKEditor team offers a better solution: force the ContextualBalloon to get instantiated early thanks to imageBlock not yet being optimized like https://github.com/ckeditor/ckeditor5/commit/c276c45a934e4ad7c2a8ccd0bd9a01f6442d4cd3#diff-1753317a1a0b947ca8b66581b533616a5309f6d4236a527b9d21ba03e13a78d8.
    editor.plugins.get('LinkUI')._createViews();

    this._buttonViews = new ViewCollection();

    this._enableLinkAutocomplete();
    this._handleExtraFormFieldSubmit();
    this._handleDataLoadingIntoExtraFormField();
    this._handleEntityLinkPreviews();
  }

  _handleEntityLinkPreviews() {
    const { editor } = this;
    const linkCommand = editor.commands.get('link');
    const linkSuggestionMetadataCommand = editor.commands.get(
      'linkSuggestionMetadata',
    );
    const linkToolbarView = editor.plugins.get('LinkUI').toolbarView;
    const previewButton = linkToolbarView.items.first;

    previewButton.unbind('href');
    previewButton
      .bind('href')
      .toMany(
        [linkCommand, linkSuggestionMetadataCommand],
        'value',
        (href, metadata) => {
          if (metadata && metadata.path && metadata.path.startsWith('/')) {
            return `${drupalSettings.path.baseUrl}${metadata.path.substring(1)}`;
          }

          // If path is not available via metadata, use the href directly.
          if (href && href.startsWith('/')) {
            return `${drupalSettings.path.baseUrl}${href.substring(1)}`;
          }
          return (
            href &&
            ensureSafeUrl(href, editor.config.get('link.allowedProtocols'))
          );
        },
      );

    previewButton.unbind('label');
    previewButton
      .bind('label')
      .toMany(
        [linkCommand, linkSuggestionMetadataCommand],
        'value',
        (href, metadata) => {
          if (!metadata) {
            return href || editor.locale.t('This link has no URL');
          }

          const group = metadata.group ? ` (${metadata.group})` : '';
          return `${metadata.label}${group.replace(' - )', ')')}`;
        },
      );
  }

  _enableLinkAutocomplete() {
    const { editor } = this;
    const hostEntityTypeId = editor.sourceElement.getAttribute(
      'data-ckeditor5-host-entity-type',
    );
    const hostEntityLangcode = editor.sourceElement.getAttribute(
      'data-ckeditor5-host-entity-langcode',
    );
    const linkFormView = editor.plugins.get('LinkUI').formView;

    let wasAutocompleteAdded = false;

    linkFormView.extendTemplate({
      attributes: {
        class: ['ck-vertical-form', 'ck-link-form_layout-vertical'],
      },
    });

    const additionalButtonsView = new View();
    additionalButtonsView.setTemplate({
      tag: 'ul',
      children: this._buttonViews.map((buttonView) => ({
        tag: 'li',
        children: [buttonView],
        attributes: {
          class: ['ck', 'ck-list__item'],
        },
      })),
      attributes: {
        class: ['ck', 'ck-reset', 'ck-list'],
      },
    });
    linkFormView.children.add(additionalButtonsView, 1);

    editor.plugins
      .get('ContextualBalloon')
      .on('set:visibleView', (evt, propertyName, newValue) => {
        if (newValue !== linkFormView || wasAutocompleteAdded) {
          return;
        }

        /**
         * Used to know if a selection was made from the autocomplete results.
         *
         * @type {boolean}
         */
        let selected;

        initializeAutocomplete(linkFormView.urlInputView.fieldView.element, {
          // @see \Drupal\ckeditor5\Plugin\CKEditor5Plugin\EntityLinkSuggestions::getDynamicPluginConfig()
          autocompleteUrl: this.editor.config.get('drupalEntityLinkSuggestions')
            .suggestionsUrl,
          queryParams: {
            hostEntityLangcode,
            hostEntityTypeId,
          },
          selectHandler: (event, { item }) => {
            if (!item.path && !item.href) {
              // eslint-disable-next-line no-throw-literal
              throw `Missing path or href param. ${JSON.stringify(item)}`;
            }

            if (item.entity_type_id || item.entity_uuid) {
              if (!item.entity_type_id || !item.entity_uuid) {
                // eslint-disable-next-line no-throw-literal
                throw `Missing entity type id and/or entity uuid. ${JSON.stringify(
                  item,
                )}`;
              }
              this.set('entityType', item.entity_type_id);
              this.set('entityUuid', item.entity_uuid);
              this.set('entityMetadata', JSON.stringify(item));
            } else {
              this.set('entityType', null);
              this.set('entityUuid', null);
              this.set('entityMetadata', null);
            }

            // If the displayed text is empty use the entity label as the default value.
            if (
              linkFormView.hasOwnProperty('displayedTextInputView') &&
              linkFormView.displayedTextInputView.fieldView.element.value ===
                '' &&
              item.label
            ) {
              // The item label has been sanitized for display as HTML. We want this back in the original format so that
              // characters are not double encoded (e.g. we want "foo &amp; bar" to be "foo & bar").
              const label = document.createElement('span');
              label.innerHTML = item.label;
              linkFormView.displayedTextInputView.fieldView.value =
                label.textContent;
            }

            event.target.value = item.path ?? item.href;
            selected = true;
            return false;
          },
          openHandler: () => {
            selected = false;
          },
          closeHandler: () => {
            selected = false;
          },
        });

        wasAutocompleteAdded = true;
      });
  }

  _handleExtraFormFieldSubmit() {
    const { editor } = this;
    const linkFormView = editor.plugins.get('LinkUI').formView;
    const linkCommand = editor.commands.get('link');

    this.listenTo(
      linkFormView,
      'submit',
      () => {
        // Stop the execution of the link command caused by closing the form.
        // Inject the extra attribute value. The highest priority listener here
        // injects the argument (here below 👇).
        // - The high priority listener in
        //   _addExtraAttributeOnLinkCommandExecute() gets that argument and sets
        //   the extra attribute.
        // - The normal (default) priority listener in ckeditor5-link sets
        //   (creates) the actual link.
        linkCommand.once(
          'execute',
          (evt, args) => {
            // Clear out link attributes for external URLs.
            if (DrupalEntityLinkSuggestions._isValidHttpUrl(args[0])) {
              args[1].entityLinkAttributes = {
                'data-link-entity-type': 'external',
              };
            } else {
              // In CKEditor v45+ decorators go in the second argument (args[1]).
              args[1].entityLinkAttributes = {
                'data-entity-type': this.entityType,
                'data-entity-uuid': this.entityUuid,
                'data-entity-metadata': this.entityMetadata,
                'data-link-entity-type': this.entityType,
                'data-link-entity-uuid': this.entityUuid,
                'data-link-entity-metadata': this.entityMetadata,
              };
            }
          },
          { priority: 'highest' },
        );
      },
      { priority: 'high' },
    );
  }

  _handleDataLoadingIntoExtraFormField() {
    const { editor } = this;
    const linkCommand = editor.commands.get('link');

    this.bind('entityType').to(linkCommand, 'data-entity-type');
    this.bind('entityUuid').to(linkCommand, 'data-entity-uuid');
    this.bind('entityMetadata').to(linkCommand, 'data-entity-metadata');
  }

  static _isValidHttpUrl(string) {
    let url;
    try {
      url = new URL(string);
    } catch (_) {
      return false;
    }
    return url.protocol === 'https:';
  }

  /**
   * @inheritdoc
   */
  static get pluginName() {
    return 'DrupalEntityLinkSuggestions';
  }
}

export default {
  DrupalEntityLinkSuggestions,
};
