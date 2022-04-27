/* eslint-disable import/no-extraneous-dependencies */
/* cspell:words drupallinkmediaediting linkediting linkimageediting linkcommand */
import { Plugin } from 'ckeditor5/src/core';
import { Matcher } from 'ckeditor5/src/engine';
import { toMap } from 'ckeditor5/src/utils';

/**
 * Returns the first drupal-media element in a given view element.
 *
 * @param {module:engine/view/element~Element} viewElement
 *   The view element.
 *
 * @return {module:engine/view/element~Element|undefined}
 *   The first <drupal-media> element or undefined if the element doesn't have
 *   <drupal-media> as a child element.
 */
function getFirstMedia(viewElement) {
  return Array.from(viewElement.getChildren()).find(
    (child) => child.name === 'drupal-media',
  );
}

/**
 * Returns a converter that consumes the `href` attribute if a link contains a <drupal-media>.
 *
 * @return {Function}
 *   A function that adds an event listener to upcastDispatcher.
 */
function upcastMediaLink() {
  return (dispatcher) => {
    dispatcher.on(
      'element:a',
      (evt, data, conversionApi) => {
        const viewLink = data.viewItem;
        const mediaInLink = getFirstMedia(viewLink);

        if (!mediaInLink) {
          return;
        }

        // There's an <drupal-media> inside an <a> element - we consume it so it
        // won't be picked up by the Link plugin.
        const consumableAttributes = { attributes: ['href'], name: true };

        // Consume the `href` attribute so the default one will not convert it to
        // $text attribute.
        if (!conversionApi.consumable.consume(viewLink, consumableAttributes)) {
          // Might be consumed by something else - i.e. other converter with
          // priority=highest - a standard check.
          return;
        }

        const linkHref = viewLink.getAttribute('href');

        // Missing the `href` attribute.
        if (!linkHref) {
          return;
        }

        const conversionResult = conversionApi.convertItem(
          mediaInLink,
          data.modelCursor,
        );

        // Set media range as conversion result.
        data.modelRange = conversionResult.modelRange;

        // Continue conversion where <drupal-media> conversion ends.
        data.modelCursor = conversionResult.modelCursor;

        const modelElement = data.modelCursor.nodeBefore;

        if (modelElement && modelElement.is('element', 'drupalMedia')) {
          // Set the `linkHref` attribute from <a> element on model drupalMedia
          // element.
          conversionApi.writer.setAttribute('linkHref', linkHref, modelElement);
        }
      },
      { priority: 'high' },
    );
  };
}

/**
 * Return a converter that adds the <a> element to view data.
 *
 * @return {Function}
 *   A function that adds an event listener to downcastDispatcher.
 */
function dataDowncastMediaLink() {
  return (dispatcher) => {
    dispatcher.on(
      'attribute:linkHref:drupalMedia',
      (evt, data, conversionApi) => {
        const { writer } = conversionApi;
        if (!conversionApi.consumable.consume(data.item, evt.name)) {
          return;
        }

        // The drupalMedia will be already converted - so it will be present in
        // the view.
        const mediaElement = conversionApi.mapper.toViewElement(data.item);

        // If so, update the attribute if it's defined or remove the entire link
        // if the attribute is empty. But if it does not exist. Let's wrap already
        // converted drupalMedia by newly created link element.
        // 1. Create an empty <a> element.
        const linkElement = writer.createContainerElement('a', {
          href: data.attributeNewValue,
        });

        // 2. Insert <a> before the <drupal-media> element.
        writer.insert(writer.createPositionBefore(mediaElement), linkElement);

        // 3. Move the drupal-media element inside the <a>.
        writer.move(
          writer.createRangeOn(mediaElement),
          writer.createPositionAt(linkElement, 0),
        );
      },
      { priority: 'high' },
    );
  };
}

/**
 * Return a converter that adds the <a> element to editing view.
 *
 * @return {Function}
 *   A function that adds an event listener to downcastDispatcher.
 *
 * @see https://github.com/ckeditor/ckeditor5/blob/v31.0.0/packages/ckeditor5-link/src/linkimageediting.js#L180
 */
function editingDowncastMediaLink() {
  return (dispatcher) => {
    dispatcher.on(
      'attribute:linkHref:drupalMedia',
      (evt, data, conversionApi) => {
        const { writer } = conversionApi;
        if (!conversionApi.consumable.consume(data.item, evt.name)) {
          return;
        }

        // The drupalMedia will be already converted - so it will be present in
        // the view.
        const mediaContainer = conversionApi.mapper.toViewElement(data.item);
        const linkInMedia = Array.from(mediaContainer.getChildren()).find(
          (child) => child.name === 'a',
        );

        // If link already exists, instead of creating new link from scratch,
        // update the existing link. This makes the UI rendering much smoother.
        if (linkInMedia) {
          // If attribute has a new value, update it. If new value doesn't exist,
          // the link will be removed.
          if (data.attributeNewValue) {
            writer.setAttribute('href', data.attributeNewValue, linkInMedia);
          } else {
            // This is triggering elementToElement conversion for drupalMedia
            // element which makes caused re-render of the media preview, making
            // the media preview flicker once when media is unlinked.
            // @todo ensure that this doesn't cause flickering after
            //   https://www.drupal.org/i/3246380 has been addressed.
            writer.move(
              writer.createRangeIn(linkInMedia),
              writer.createPositionAt(mediaContainer, 0),
            );
            writer.remove(linkInMedia);
          }
        } else {
          const mediaPreview = Array.from(mediaContainer.getChildren()).find(
            (child) => child.getAttribute('data-drupal-media-preview'),
          );
          // 1. Create an empty <a> element.
          const linkElement = writer.createContainerElement('a', {
            href: data.attributeNewValue,
          });

          // 2. Insert <a> inside the media container.
          writer.insert(
            writer.createPositionAt(mediaContainer, 0),
            linkElement,
          );

          // 3. Move the media preview inside the <a>.
          writer.move(
            writer.createRangeOn(mediaPreview),
            writer.createPositionAt(linkElement, 0),
          );
        }
      },
      { priority: 'high' },
    );
  };
}

/**
 * Returns a converter that enables manual decorators on linked Drupal Media.
 *
 * @see \Drupal\editor\EditorXssFilter\Standard
 *
 * @param {module:link/link~LinkDecoratorDefinition} decorator
 *   The link decorator.
 * @return {function}
 *   Function attaching event listener to dispatcher.
 *
 * @private
 */
function downcastMediaLinkManualDecorator(decorator) {
  return (dispatcher) => {
    dispatcher.on(
      `attribute:${decorator.id}:drupalMedia`,
      (evt, data, conversionApi) => {
        const mediaContainer = conversionApi.mapper.toViewElement(data.item);

        // Scenario 1: `<figure>` element that contains `<a>`, generated by
        // `dataDowncast`.
        let mediaLink = Array.from(mediaContainer.getChildren()).find(
          (child) => child.name === 'a',
        );

        // Scenario 2: `<drupal-media>` wrapped with `<a>`, generated by
        // `editingDowncast`.
        if (!mediaLink && mediaContainer.is('element', 'a')) {
          mediaLink = mediaContainer;
        } else {
          mediaLink = Array.from(mediaContainer.getAncestors()).find(
            (ancestor) => ancestor.name === 'a',
          );
        }

        // The <a> element was removed by the time this converter is executed.
        // It may happen when the base `linkHref` and decorator attributes are
        // removed at the same time.
        if (!mediaLink) {
          return;
        }

        // eslint-disable-next-line no-restricted-syntax
        for (const [key, val] of toMap(decorator.attributes)) {
          conversionApi.writer.setAttribute(key, val, mediaLink);
        }

        if (decorator.classes) {
          conversionApi.writer.addClass(decorator.classes, mediaLink);
        }

        // Add support for `style` attribute in manual decorators to remain
        // consistent with CKEditor 5. This only works with text formats that
        // have no HTMl filtering enabled.
        // eslint-disable-next-line no-restricted-syntax
        for (const key in decorator.styles) {
          if (Object.prototype.hasOwnProperty.call(decorator.styles, key)) {
            conversionApi.writer.setStyle(
              key,
              decorator.styles[key],
              mediaLink,
            );
          }
        }
      },
    );
  };
}

/**
 * Returns a converter that applies manual decorators to linked Drupal Media.
 *
 * @param {module:core/editor/editor~Editor} editor
 *   The editor.
 * @param {module:link/link~LinkDecoratorDefinition} decorator
 *   The link decorator.
 * @return {function}
 *   Function attaching event listener to dispatcher.
 *
 * @private
 */
function upcastMediaLinkManualDecorator(editor, decorator) {
  return (dispatcher) => {
    dispatcher.on(
      'element:a',
      (evt, data, conversionApi) => {
        const viewLink = data.viewItem;
        const drupalMediaInLink = getFirstMedia(viewLink);

        // We need to check whether Drupal Media is inside a link because the
        // converter handles only manual decorators for linked Drupal Media.
        if (!drupalMediaInLink) {
          return;
        }

        const matcher = new Matcher(decorator._createPattern());
        const result = matcher.match(viewLink);

        // The link element does not have required attributes or/and proper
        // values.
        if (!result) {
          return;
        }

        // Check whether we can consume those attributes.
        if (!conversionApi.consumable.consume(viewLink, result.match)) {
          return;
        }

        // At this stage we can assume that we have the `<drupalMedia>` element.
        const modelElement = data.modelCursor.nodeBefore;

        conversionApi.writer.setAttribute(decorator.id, true, modelElement);
      },
      { priority: 'high' },
    );
    // Using the same priority as the media link upcast converter guarantees
    // that the linked `<drupalMedia>` was already converted.
    // @see upcastMediaLink().
  };
}

/**
 * Model to view and view to model conversions for linked media elements.
 *
 * @private
 *
 * @see https://github.com/ckeditor/ckeditor5/blob/v31.0.0/packages/ckeditor5-link/src/linkimage.js
 */
export default class DrupalLinkMediaEditing extends Plugin {
  /**
   * @inheritdoc
   */
  static get requires() {
    return ['LinkEditing', 'DrupalMediaEditing'];
  }

  /**
   * @inheritdoc
   */
  static get pluginName() {
    return 'DrupalLinkMediaEditing';
  }

  /**
   * @inheritdoc
   */
  init() {
    const { editor } = this;
    editor.model.schema.extend('drupalMedia', {
      allowAttributes: ['linkHref'],
    });

    editor.conversion.for('upcast').add(upcastMediaLink());
    editor.conversion.for('editingDowncast').add(editingDowncastMediaLink());
    editor.conversion.for('dataDowncast').add(dataDowncastMediaLink());

    this._enableManualDecorators();
  }

  /**
   * Processes transformed manual link decorators and attaches proper converters
   * that will work when linking Drupal Media.
   *
   * @see module:link/linkimageediting~LinkImageEditing
   * @see module:link/linkcommand~LinkCommand
   * @see module:link/utils~ManualDecorator
   *
   * @private
   */
  _enableManualDecorators() {
    const editor = this.editor;
    const command = editor.commands.get('link');

    // eslint-disable-next-line no-restricted-syntax
    for (const decorator of command.manualDecorators) {
      editor.model.schema.extend('drupalMedia', {
        allowAttributes: decorator.id,
      });
      editor.conversion
        .for('downcast')
        .add(downcastMediaLinkManualDecorator(decorator));
      editor.conversion
        .for('upcast')
        .add(upcastMediaLinkManualDecorator(editor, decorator));
    }
  }
}
