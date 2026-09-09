export const getUniqueAttrValues = (models, attr) => {
    const attrValues = models.map((model) => model.getAttribute(attr)).filter(Boolean);

    return [...new Set(attrValues)];
};
