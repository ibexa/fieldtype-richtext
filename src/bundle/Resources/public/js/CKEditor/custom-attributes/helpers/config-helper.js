const headingsList = ['heading1', 'heading2', 'heading3', 'heading4', 'heading5', 'heading6'];

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

export { getCustomAttributesConfig, getCustomClassesConfig };
