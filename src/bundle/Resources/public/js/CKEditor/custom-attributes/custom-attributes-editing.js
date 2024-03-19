import Plugin from '@ckeditor/ckeditor5-core/src/plugin';
import Widget from '@ckeditor/ckeditor5-widget/src/widget';

import IbexaCustomAttributesCommand from './custom-attributes-command';
import { getCustomAttributesConfig, getCustomClassesConfig } from './helpers/config-helper';

const configElementsMapping = {
    li: 'listItem',
    tr: 'tableRow',
    td: 'tableCell',
};

class IbexaCustomAttributesEditing extends Plugin {
    static get requires() {
        return [Widget];
    }

    defineConverters() {
        const { conversion } = this.editor;
        const customAttributesConfig = getCustomAttributesConfig();

        conversion.attributeToAttribute({
            model: {
                key: 'custom-classes',
            },
            view: {
                key: 'class',
            },
        });

        Object.entries(customAttributesConfig).forEach(([element, customAttributes]) => {
            const isList = element === 'ul' || element === 'ol';

            Object.keys(customAttributes).forEach((customAttributeName) => {
                if (isList) {
                    this.editor.conversion.for('dataDowncast').add((dispatcher) => {
                        dispatcher.on(`attribute:list-${customAttributeName}:listItem`, (event, data, conversionApi) => {
                            const viewItem = conversionApi.mapper.toViewElement(data.item);

                            conversionApi.writer.setAttribute(
                                `data-ezattribute-${customAttributeName}`,
                                data.attributeNewValue,
                                viewItem.parent,
                            );
                        });
                    });

                    this.editor.conversion.for('editingDowncast').add((dispatcher) => {
                        dispatcher.on(`attribute:list-${customAttributeName}:listItem`, (event, data, conversionApi) => {
                            const viewItem = conversionApi.mapper.toViewElement(data.item);

                            conversionApi.writer.setAttribute(
                                `data-ezattribute-${customAttributeName}`,
                                data.attributeNewValue,
                                viewItem.parent,
                            );
                        });
                    });

                    this.editor.conversion.for('upcast').add((dispatcher) => {
                        dispatcher.on('element:li', (event, data, conversionApi) => {
                            const listParent = data.viewItem.parent;
                            const listItem = data.modelRange.start.nodeAfter || data.modelRange.end.nodeBefore;
                            const attributeValue = listParent.getAttribute(`data-ezattribute-${customAttributeName}`);

                            conversionApi.writer.setAttribute(`list-${customAttributeName}`, attributeValue, listItem);
                        });
                    });

                    return;
                }

                conversion.attributeToAttribute({
                    model: {
                        key: customAttributeName,
                    },
                    view: {
                        key: `data-ezattribute-${customAttributeName}`,
                    },
                });
            });
        });

        this.editor.conversion.for('dataDowncast').add((dispatcher) => {
            dispatcher.on('attribute:list-custom-classes:listItem', (event, data, conversionApi) => {
                if (data.attributeKey !== 'list-custom-classes' || data.attributeNewValue === '') {
                    return;
                }

                const viewItem = conversionApi.mapper.toViewElement(data.item);
                const previousElement = viewItem.parent.previousSibling;

                conversionApi.writer.setAttribute('class', data.attributeNewValue, viewItem.parent);

                if (previousElement?.name === viewItem.parent.name) {
                    conversionApi.writer.mergeContainers(conversionApi.writer.createPositionAfter(previousElement));
                }
            });
        });

        this.editor.conversion.for('editingDowncast').add((dispatcher) => {
            dispatcher.on('attribute:list-custom-classes:listItem', (event, data, conversionApi) => {
                if (data.attributeKey !== 'list-custom-classes' || data.attributeNewValue === '') {
                    return;
                }

                const viewItem = conversionApi.mapper.toViewElement(data.item);
                const previousElement = viewItem.parent.previousSibling;
                const nextElement = viewItem.parent.nextSibling;

                conversionApi.writer.setAttribute('class', data.attributeNewValue, viewItem.parent);

                if (previousElement?.name === viewItem.parent.name) {
                    conversionApi.writer.mergeContainers(conversionApi.writer.createPositionAfter(previousElement));
                }

                if (nextElement?.name === viewItem.parent.name) {
                    conversionApi.writer.mergeContainers(conversionApi.writer.createPositionBefore(nextElement));
                }
            });
        });

        this.editor.conversion.for('upcast').add((dispatcher) => {
            dispatcher.on('element:li', (event, data, conversionApi) => {
                const listParent = data.viewItem.parent;
                const listItem = data.modelRange.start.nodeAfter || data.modelRange.end.nodeBefore;
                const classes = listParent.getAttribute('class');

                conversionApi.writer.setAttribute('list-custom-classes', classes, listItem);
            });
        });
    }

    extendSchema(schema, element, definition) {
        const resolvedElement = configElementsMapping[element] ?? element;

        if (schema.getDefinition(resolvedElement)) {
            schema.extend(resolvedElement, definition);
        } else {
            console.warn(`Schema does not have '${element}' element`);
        }
    }

    cleanAttributes(element, customs) {
        const { model } = this.editor;

        Object.entries(customs).forEach(([elementName, config]) => {
            if (elementName === element.name) {
                return;
            }

            model.change((writer) => {
                Object.keys(config).forEach((name) => {
                    writer.removeAttribute(name, element);
                });
            });
        });
    }

    init() {
        const { commands, model } = this.editor;
        const customAttributesConfig = getCustomAttributesConfig();
        const customClassesConfig = getCustomClassesConfig();
        const elementsWithCustomAttributes = Object.keys(customAttributesConfig);
        const elementsWithCustomClasses = Object.keys(customClassesConfig);

        elementsWithCustomAttributes.forEach((element) => {
            const isList = element === 'ul' || element === 'ol';
            const prefix = isList ? 'list-' : '';
            const elementName = isList ? 'listItem' : element;
            const customAttributes = Object.keys(customAttributesConfig[element]);

            customAttributes.forEach((customAttribute) => {
                this.extendSchema(model.schema, elementName, { allowAttributes: `${prefix}${customAttribute}` });
            });
        });

        elementsWithCustomClasses.forEach((element) => {
            const isList = element === 'ul' || element === 'ol';
            const prefix = isList ? 'list-' : '';
            const elementName = isList ? 'listItem' : element;

            this.extendSchema(model.schema, elementName, { allowAttributes: `${prefix}custom-classes` });
        });

        this.defineConverters();

        commands.get('enter').on('afterExecute', () => {
            const blocks = model.document.selection.getSelectedBlocks();

            for (const block of blocks) {
                this.cleanAttributes(block, customAttributesConfig);

                model.change((writer) => {
                    writer.removeAttribute('custom-classes', block);
                });
            }
        });

        commands.add('insertIbexaCustomAttributes', new IbexaCustomAttributesCommand(this.editor));
    }
}

export { IbexaCustomAttributesEditing as default, configElementsMapping };
