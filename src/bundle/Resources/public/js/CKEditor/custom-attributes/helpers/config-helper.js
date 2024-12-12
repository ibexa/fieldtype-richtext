import { configElementsMapping } from '../custom-attributes-editing';

const headingsList = ['heading1', 'heading2', 'heading3', 'heading4', 'heading5', 'heading6'];
const findConfigName = (elementName) => {
    const configName = Object.entries(configElementsMapping).find(([, value]) => value === elementName);

    return configName?.[0] ?? elementName;
};
const getCustomAttributesConfig = () => {
    const attributes = { ...window.ibexa.richText.alloyEditor.attributes };

    if (Object.hasOwn(attributes, 'heading')) {
        headingsList.forEach((headingType) => {
            if (Object.hasOwn(attributes, headingType)) {
                return;
            }

            attributes[headingType] = attributes.heading;
        });

        delete attributes.heading;
    }

    return attributes;
};
const getCustomAttributesElementConfig = (elementName) => {
    const config = getCustomAttributesConfig();
    const configName = findConfigName(elementName);

    return config[configName];
};
const getCustomClassesConfig = () => {
    const classes = { ...window.ibexa.richText.alloyEditor.classes };

    if (Object.hasOwn(classes, 'heading')) {
        headingsList.forEach((headingType) => {
            if (Object.hasOwn(classes, headingType)) {
                return;
            }

            classes[headingType] = classes.heading;
        });

        delete classes.heading;
    }

    return classes;
};
const getCustomClassesElementConfig = (elementName) => {
    const config = getCustomClassesConfig();
    const configName = findConfigName(elementName);

    return config[configName];
};
const addPredefinedClassToConfig = (elementName, className) => {
    const configName = findConfigName(elementName);
    const config = window.ibexa.richText.alloyEditor.classes[configName];

    if (config) {
        config.predefinedClass = className;
    }
};

export {
    getCustomAttributesConfig,
    getCustomClassesConfig,
    getCustomAttributesElementConfig,
    getCustomClassesElementConfig,
    findConfigName,
    addPredefinedClassToConfig,
};
