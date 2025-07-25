import { Plugin, Widget, toWidget } from 'ckeditor5';

import IbexaInlineCustomTagCommand from './inline-custom-tag-command';

class IbexaInlineCustomTagEditing extends Plugin {
    static get requires() {
        return [Widget];
    }

    defineSchema() {
        const { schema } = this.editor.model;

        schema.register('inlineCustomTag', {
            isInline: true,
            isObject: true,
            allowWhere: '$text',
            allowAttributes: ['customTagName', 'values'],
        });

        schema.register('inlineCustomTagContent', {
            isInline: true,
            allowIn: 'inlineCustomTag',
            allowChildren: '$text',
        });
    }

    defineConverters() {
        const { conversion } = this.editor;

        conversion.for('editingDowncast').elementToElement({
            model: 'inlineCustomTag',
            view: (modelElement, { writer: downcastWriter }) => {
                const container = downcastWriter.createContainerElement('span', {
                    'data-ezelement': 'eztemplateinline',
                    'data-ezname': modelElement.getAttribute('customTagName'),
                    class: 'ibexa-custom-tag',
                });

                return toWidget(container, downcastWriter);
            },
        });

        conversion.for('dataDowncast').elementToElement({
            model: 'inlineCustomTag',
            view: (modelElement, { writer: downcastWriter }) => {
                const container = downcastWriter.createContainerElement('span', {
                    'data-ezelement': 'eztemplateinline',
                    'data-ezname': modelElement.getAttribute('customTagName'),
                });
                const values = modelElement.getAttribute('values');
                const config = downcastWriter.createUIElement('span', { 'data-ezelement': 'ezconfig' }, function (domDocument) {
                    const domElement = this.toDomElement(domDocument);

                    domElement.innerHTML = Object.entries(values).reduce((total, [attribute, value]) => {
                        const attributeValue = value ?? '';
                        const ezvalue = `<span data-ezelement="ezvalue" data-ezvalue-key="${attribute}">${attributeValue}</span>`;

                        return `${total}${ezvalue}`;
                    }, '');

                    return domElement;
                });

                downcastWriter.remove(downcastWriter.createRangeIn(container));

                if (Object.keys(values).length) {
                    downcastWriter.insert(downcastWriter.createPositionAt(container, 0), config);
                }

                return container;
            },
        });

        conversion.for('upcast').elementToElement({
            view: {
                name: 'span',
                attributes: {
                    'data-ezelement': 'eztemplateinline',
                },
            },
            model: (viewElement, { writer: upcastWriter }) => {
                if (viewElement.getAttribute('data-eztype') === 'style') {
                    return;
                }

                const configElement = viewElement.getChild(1);
                const configValuesIterator =
                    configElement?.getAttribute('data-ezelement') === 'ezconfig' ? configElement.getChildren() : [];
                const customTagName = viewElement.getAttribute('data-ezname');
                const values = {};

                for (const configValue of configValuesIterator) {
                    if (!configValue.is?.('element')) {
                        continue;
                    }

                    const value = configValue.getChild(0)?.data ?? null;

                    values[configValue.getAttribute('data-ezvalue-key')] = value;
                }

                const modelElement = upcastWriter.createElement('inlineCustomTag', { customTagName, values });

                return modelElement;
            },
        });

        conversion.for('editingDowncast').elementToElement({
            model: 'inlineCustomTagContent',
            view: (modelElement, { writer: downcastWriter }) => {
                const span = downcastWriter.createEditableElement('span');

                return toWidget(span, downcastWriter);
            },
        });

        conversion.for('dataDowncast').elementToElement({
            model: 'inlineCustomTagContent',
            view: {
                name: 'span',
                attributes: {
                    'data-ezelement': 'ezcontent',
                },
            },
        });

        conversion.for('upcast').elementToElement({
            model: 'inlineCustomTagContent',
            view: {
                name: 'span',
                attributes: {
                    'data-ezelement': 'ezcontent',
                },
            },
        });
    }

    init() {
        this.defineSchema();
        this.defineConverters();

        this.editor.commands.add('insertIbexaInlineCustomTag', new IbexaInlineCustomTagCommand(this.editor));
    }
}

export default IbexaInlineCustomTagEditing;
