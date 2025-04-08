import Command from '@ckeditor/ckeditor5-core/src/command';

import {
    getCustomAttributesConfig,
    getCustomClassesConfig,
    getCustomAttributesElementConfig,
    getCustomClassesElementConfig,
    findConfigName,
} from './helpers/config-helper';

class IbexaCustomAttributesCommand extends Command {
    cleanAttributes(modelElement, attributes) {
        Object.entries(attributes).forEach(([elementName, config]) => {
            const configName = findConfigName(modelElement.name);

            if (elementName === configName) {
                return;
            }

            this.editor.model.change((writer) => {
                Object.keys(config).forEach((name) => {
                    if (attributes[configName]?.[name]) {
                        return;
                    }

                    writer.removeAttribute(name, modelElement);
                });
            });
        });
    }

    cleanClasses(modelElement, classes) {
        Object.keys(classes).forEach((elementName) => {
            const configName = findConfigName(modelElement.name);
            const selectedCustomClasses = modelElement.getAttribute('custom-classes')?.split(' ') ?? [];
            const elementCustomClassesConfig = classes[configName];
            const filteredSelectedCustomClasses = selectedCustomClasses.filter(
                (className) => !elementCustomClassesConfig || className !== elementCustomClassesConfig.predefinedClass,
            );

            const hasOwnCustomClasses =
                elementCustomClassesConfig &&
                filteredSelectedCustomClasses.every((selectedCustomClass) =>
                    elementCustomClassesConfig.choices.includes(selectedCustomClass),
                );

            this.editor.model.change((writer) => {
                if (elementName !== configName && !hasOwnCustomClasses) {
                    writer.removeAttribute('custom-classes', modelElement);
                } else if (selectedCustomClasses.length !== filteredSelectedCustomClasses.length) {
                    writer.setAttribute('custom-classes', filteredSelectedCustomClasses.join(' '), modelElement);
                }
            });
        });
    }

    isTableColumnSelected(parentElementName) {
        const selectedBlocks = [...this.editor.model.document.selection.getSelectedBlocks()];
        const isTableRow = parentElementName === 'tableRow';
        const areBlocksInSameRow = selectedBlocks.every((selectedBlock, index) => {
            const nextBlock = selectedBlocks[index + 1];

            if (!nextBlock) {
                return true;
            }

            const commonAncestor = selectedBlock.getCommonAncestor(nextBlock);

            return commonAncestor.name === 'tableRow' || commonAncestor.name === 'tableCell';
        });

        return !areBlocksInSameRow && isTableRow;
    }

    refresh() {
        const { selection } = this.editor.model.document;
        const parentElement = selection.getSelectedElement() ?? selection.getFirstPosition().parent;
        let parentElementName = parentElement.name;

        if (this.editor.isListSelected) {
            const mapping = {
                bulleted: 'ul',
                numbered: 'ol',
            };
            const listType = parentElement.getAttribute('listType');

            if (mapping[listType]) {
                parentElementName = mapping[listType];
            }
        }

        const customAttributesConfig = getCustomAttributesConfig();
        const customClassesConfig = getCustomClassesConfig();
        const parentElementAttributesConfig = getCustomAttributesElementConfig(parentElementName);
        const parentElementClassesConfig = getCustomClassesElementConfig(parentElementName);
        const isEnabled = !this.isTableColumnSelected(parentElementName) && (parentElementAttributesConfig || parentElementClassesConfig);

        this.isEnabled = !!isEnabled;

        this.cleanAttributes(parentElement, customAttributesConfig);
        this.cleanClasses(parentElement, customClassesConfig);
    }
}

export default IbexaCustomAttributesCommand;
