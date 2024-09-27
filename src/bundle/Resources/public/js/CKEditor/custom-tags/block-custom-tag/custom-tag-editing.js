import Plugin from '@ckeditor/ckeditor5-core/src/plugin';
import Widget from '@ckeditor/ckeditor5-widget/src/widget';
import { toWidget, toWidgetEditable } from '@ckeditor/ckeditor5-widget/src/utils';

import IbexaCustomTagCommand from './custom-tag-command';

class IbexaCustomTagEditing extends Plugin {
    static get requires() {
        return [Widget];
    }

    defineSchema() {
        const { schema } = this.editor.model;

        schema.register('customTag', {
            isObject: true,
            allowWhere: '$block',
            allowAttributes: ['customTagName', 'values'],
        });

        schema.register('customTagContent', {
            isBlock: true,
            allowIn: 'customTag',
            allowContentOf: '$root',
        });
    }

    defineConverters() {
        const { customTags } = window.ibexa.richText;
        const { conversion } = this.editor;

        conversion.for('editingDowncast').elementToElement({
            model: 'customTag',
            view: (modelElement, { writer: downcastWriter }) => {
                const customTagName = modelElement.getAttribute('customTagName');
                const container = downcastWriter.createContainerElement('div', {
                    'data-ezelement': 'eztemplate',
                    'data-ezname': customTagName,
                    class: 'ibexa-custom-tag',
                });
                const header = downcastWriter.createUIElement('div', { class: 'ibexa-custom-tag__header' }, function (domDocument) {
                    const domElement = this.toDomElement(domDocument);
                    const customTagConfig = window.ibexa.richText.customTags[customTagName];

                    domElement.innerHTML = `
                        <div class="ibexa-custom-tag__header-title" data-cke-tooltip-text="${customTagConfig.label}">
                            ${customTagConfig.label}
                        </div>
                    `;

                    return domElement;
                });

                downcastWriter.insert(downcastWriter.createPositionAt(container, 0), header);

                return toWidget(container, downcastWriter);
            },
        });

        conversion.for('dataDowncast').elementToElement({
            model: 'customTag',
            view: (modelElement, { writer: downcastWriter }) => {
                const container = downcastWriter.createContainerElement('div', {
                    'data-ezelement': 'eztemplate',
                    'data-ezname': modelElement.getAttribute('customTagName'),
                });
                const values = modelElement.getAttribute('values');
                const config = downcastWriter.createUIElement('span', { 'data-ezelement': 'ezconfig' }, function (domDocument) {
                    const domElement = this.toDomElement(domDocument);

                    domElement.innerHTML = Object.entries(values).reduce((total, [attribute, value]) => {
                        // Escaping
                        // <script>alert("Hello! I am a script!");</script> --> &lt;script&gt;alert("Hello! I am a script!");&lt;/script&gt;
                        const stringTempNode = domDocument.createElement('div');
                        stringTempNode.appendChild(domDocument.createTextNode(value !== null ? value : ''));
                        const attributeValue = stringTempNode.innerHTML;

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
                name: 'div',
                attributes: {
                    'data-ezelement': 'eztemplate',
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
                const tagConfig = customTags[customTagName] ?? {};
                const values = {};

                for (const configValue of configValuesIterator) {
                    const configName = configValue.getAttribute('data-ezvalue-key');
                    const configAttributes = tagConfig.attributes ?? {};

                    if (!Object.prototype.hasOwnProperty.call(configAttributes, configName)) {
                        continue;
                    }

                    const value = (configValue.getChild(0) && configValue.getChild(0).data) || null;

                    values[configValue.getAttribute('data-ezvalue-key')] = value;
                }

                const modelElement = upcastWriter.createElement('customTag', { customTagName, values });

                return modelElement;
            },
        });

        conversion.for('editingDowncast').elementToElement({
            model: 'customTagContent',
            view: (modelElement, { writer: downcastWriter }) => {
                const div = downcastWriter.createEditableElement('div');

                return toWidgetEditable(div, downcastWriter);
            },
        });

        conversion.for('dataDowncast').elementToElement({
            model: 'customTagContent',
            view: {
                name: 'div',
                attributes: {
                    'data-ezelement': 'ezcontent',
                },
            },
        });

        conversion.for('upcast').elementToElement({
            model: 'customTagContent',
            view: {
                name: 'div',
                attributes: {
                    'data-ezelement': 'ezcontent',
                },
            },
        });
    }

    init() {
        this.defineSchema();
        this.defineConverters();

        this.editor.commands.add('insertIbexaCustomTag', new IbexaCustomTagCommand(this.editor));
    }
}

export default IbexaCustomTagEditing;
