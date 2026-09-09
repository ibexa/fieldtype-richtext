import { Plugin, Widget } from 'ckeditor5';

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
                    this.editor.conversion.for('upcast').add((dispatcher) => {
                        const customAttributeUpcastConverter = (event, data, conversionApi) => {
                            if (!data.modelRange) {
                                Object.assign(data, conversionApi.convertChildren(data.viewItem, data.modelCursor));
                            }

                            const listParent = data.viewItem;
                            const attributeValue = listParent.getAttribute(`data-ezattribute-${customAttributeName}`);

                            for (const listItem of data.modelRange.getItems({ shallow: true })) {
                                conversionApi.writer.setAttribute(`list-${customAttributeName}`, attributeValue, listItem);
                            }
                        };

                        dispatcher.on('element:ul', customAttributeUpcastConverter);
                        dispatcher.on('element:ol', customAttributeUpcastConverter);
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

        this.editor.conversion.for('upcast').add((dispatcher) => {
            const customClassesUpcastConverter = (event, data, conversionApi) => {
                if (!data.modelRange) {
                    Object.assign(data, conversionApi.convertChildren(data.viewItem, data.modelCursor));
                }

                const listParent = data.viewItem;
                const classes = listParent.getAttribute('class');

                for (const listItem of data.modelRange.getItems({ shallow: true })) {
                    conversionApi.writer.setAttribute('list-custom-classes', classes, listItem);
                }
            };

            dispatcher.on('element:ul', customClassesUpcastConverter);
            dispatcher.on('element:ol', customClassesUpcastConverter);
        });
    }

    extendSchemaAttribute(schema, element, isList, attributeName) {
        if (isList) {
            const elementName = schema.getDefinition('$listItem') ? '$listItem' : '$block';

            schema.extend(elementName, { allowAttributes: `list-${attributeName}` });
        } else {
            this.extendSchema(schema, element, { allowAttributes: attributeName });
        }
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
            if (element === 'link') {
                return;
            }

            const isList = element === 'ul' || element === 'ol';
            const customAttributes = Object.keys(customAttributesConfig[element]);

            customAttributes.forEach((customAttribute) => {
                this.extendSchemaAttribute(model.schema, element, isList, customAttribute);
            });
        });

        elementsWithCustomClasses.forEach((element) => {
            if (element === 'link') {
                return;
            }

            this.extendSchema(model.schema, element, { allowAttributes: 'custom-classes' });
            const isList = element === 'ul' || element === 'ol';

            this.extendSchemaAttribute(model.schema, element, isList, 'custom-classes');
        });

        const listEditing = this.editor.plugins.has('ListEditing') ? this.editor.plugins.get('ListEditing') : null;

        if (listEditing) {
            const registeredListAttributes = new Set();

            elementsWithCustomClasses.forEach((element) => {
                if (element !== 'ul' && element !== 'ol') {
                    return;
                }

                if (!registeredListAttributes.has('list-custom-classes')) {
                    registeredListAttributes.add('list-custom-classes');
                    listEditing.registerDowncastStrategy({
                        scope: 'list',
                        attributeName: 'list-custom-classes',
                        setAttributeOnDowncast(writer, value, viewElement) {
                            if (value) {
                                writer.setAttribute('class', value, viewElement);
                            } else {
                                writer.removeAttribute('class', viewElement);
                            }
                        },
                    });
                }
            });

            elementsWithCustomAttributes.forEach((element) => {
                if (element !== 'ul' && element !== 'ol') {
                    return;
                }

                Object.keys(customAttributesConfig[element]).forEach((customAttribute) => {
                    const attrName = `list-${customAttribute}`;

                    if (registeredListAttributes.has(attrName)) {
                        return;
                    }

                    registeredListAttributes.add(attrName);
                    listEditing.registerDowncastStrategy({
                        scope: 'list',
                        attributeName: attrName,
                        setAttributeOnDowncast(writer, value, viewElement) {
                            if (value) {
                                writer.setAttribute(`data-ezattribute-${customAttribute}`, value, viewElement);
                            } else {
                                writer.removeAttribute(`data-ezattribute-${customAttribute}`, viewElement);
                            }
                        },
                    });
                });
            });
        }

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
