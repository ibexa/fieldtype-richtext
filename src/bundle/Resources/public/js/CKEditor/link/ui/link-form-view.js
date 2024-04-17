import View from '@ckeditor/ckeditor5-ui/src/view';
import ButtonView from '@ckeditor/ckeditor5-ui/src/button/buttonview';
import LabeledFieldView from '@ckeditor/ckeditor5-ui/src/labeledfield/labeledfieldview';
import Model from '@ckeditor/ckeditor5-ui/src/model';
import Collection from '@ckeditor/ckeditor5-utils/src/collection';
import { createLabeledInputText, createLabeledDropdown } from '@ckeditor/ckeditor5-ui/src/labeledfield/utils';
import { addListToDropdown } from '@ckeditor/ckeditor5-ui/src/dropdown/utils';

import { createLabeledSwitchButton } from '../../common/switch-button/utils';
import { createLabeledInputNumber } from '../../common/input-number/utils';
import { getCustomAttributesConfig, getCustomClassesConfig } from '../../custom-attributes/helpers/config-helper';

class IbexaLinkFormView extends View {
    constructor(props) {
        super(props);

        this.locale = props.locale;
        this.editor = props.editor;

        this.saveButtonView = this.createButton('Save', null, 'ck-button-save', 'save-link');
        this.cancelButtonView = this.createButton('Remove link', null, 'ck-button-cancel', 'remove-link');
        this.selectContentButtonView = this.createButton('Select content', null, 'ibexa-btn--select-content');
        this.urlInputView = this.createTextInput({ label: 'Link to' });
        this.titleView = this.createTextInput({ label: 'Title' });
        this.targetSwitcherView = this.createBoolean({ label: 'Open in tab' });
        this.attributeRenderMethods = {
            string: this.createTextInput,
            number: this.createNumberInput,
            choice: this.createDropdown,
            boolean: this.createBoolean,
        };
        this.setValueMethods = {
            string: this.setStringValue,
            number: this.setNumberValue,
            choice: this.setChoiceValue,
            boolean: this.setBooleanValue,
        };
        this.attributeViews = {};

        const customAttributesConfig = getCustomAttributesConfig();
        const customClassesConfig = getCustomClassesConfig();
        const customAttributesLinkConfig = customAttributesConfig.link;
        const customClassesLinkConfig = customClassesConfig.link;
        const customAttributesDefinitions = [];

        this.children = this.createFormChildren();
        this.attributesChildren = this.createFromAttributesChildren(customAttributesLinkConfig, customClassesLinkConfig);

        if (this.attributesChildren.length > 0) {
            customAttributesDefinitions.push({
                tag: 'div',
                attributes: {
                    class: 'ibexa-ckeditor-balloon-form__header',
                },
                children: ['Custom Attributes'],
            });

            customAttributesDefinitions.push({
                tag: 'div',
                attributes: {
                    class: 'ibexa-ckeditor-balloon-form__fields  ibexa-ckeditor-balloon-form__fields--attributes',
                },

                children: this.attributesChildren,
            });
        }

        this.setTemplate({
            tag: 'div',
            attributes: {
                class: 'ibexa-ckeditor-balloon-form',
            },
            children: [
                {
                    tag: 'form',
                    attributes: {
                        tabindex: '-1',
                    },
                    children: [
                        ...customAttributesDefinitions,
                        {
                            tag: 'div',
                            attributes: {
                                class: 'ibexa-ckeditor-balloon-form__header',
                            },
                            children: ['Link'],
                        },
                        {
                            tag: 'div',
                            attributes: {
                                class: 'ibexa-ckeditor-balloon-form__fields',
                            },
                            children: [
                                this.children.first,
                                {
                                    tag: 'div',
                                    attributes: {
                                        class: 'ibexa-ckeditor-balloon-form__separator',
                                    },
                                    children: ['Or'],
                                },
                                ...this.children.filter((child, index) => index !== 0),
                            ],
                        },
                        {
                            tag: 'div',
                            attributes: {
                                class: 'ibexa-ckeditor-balloon-form__actions',
                            },
                            children: [this.saveButtonView, this.cancelButtonView],
                        },
                    ],
                },
            ],
        });

        this.chooseContent = this.chooseContent.bind(this);
        this.confirmHandler = this.confirmHandler.bind(this);
        this.cancelHandler = this.cancelHandler.bind(this);

        this.listenTo(this.selectContentButtonView, 'execute', this.chooseContent);
    }

    setValues({ url, title, target, ibexaLinkClasses, ibexaLinkAttributes = {} }) {
        this.setStringValue(this.urlInputView, url);
        this.setStringValue(this.titleView, title);

        this.targetSwitcherView.fieldView.element.value = !!target;
        this.targetSwitcherView.fieldView.set('value', !!target);
        this.targetSwitcherView.fieldView.isOn = !!target;
        this.targetSwitcherView.fieldView.set('isEmpty', false);

        if (ibexaLinkClasses) {
            this.setChoiceValue(this.classesView, ibexaLinkClasses);
        }

        Object.entries(ibexaLinkAttributes).forEach(([name, value]) => {
            const attributeView = this.attributeViews[`ibexaLink${name}`];
            const setValueMethod = this.setValueMethods[this.customAttributes[name].type];

            if (!attributeView || !setValueMethod) {
                return;
            }

            setValueMethod(attributeView, value);
        });
    }

    setNumberValue(view, value) {
        view.fieldView.element.value = value;
        view.fieldView.set('value', value);
        view.fieldView.set('isEmpty', value !== 0 && !value);
    }

    setStringValue(view, value) {
        view.fieldView.element.value = value;
        view.fieldView.set('value', value);
        view.fieldView.set('isEmpty', !value);
    }

    setChoiceValue(view, value) {
        view.fieldView.element.value = value;
        view.fieldView.buttonView.set({
            label: value,
            withText: true,
        });
        view.set('isEmpty', !value);
    }

    setBooleanValue(view, value) {
        view.fieldView.isOn = value === 'true';
        view.fieldView.element.value = value;
        view.fieldView.set('value', value);
        view.fieldView.set('isEmpty', false);
    }

    getValues() {
        const url = this.setProtocol(this.urlInputView.fieldView.element.value);
        const values = {
            url,
            title: this.titleView.fieldView.element.value,
            target: this.targetSwitcherView.fieldView.isOn ? '_blank' : '',
        };
        const customClassesValue = this.classesView?.fieldView.element.value;
        const customAttributesValue = Object.entries(this.attributeViews).reduce((output, [name, view]) => {
            output[name] = view.fieldView.element.value;

            return output;
        }, {});

        if (customClassesValue) {
            values.ibexaLinkClasses = customClassesValue;
        }

        if (Object.keys(customAttributesValue).length > 0) {
            values.ibexaLinkAttributes = customAttributesValue;
        }

        return values;
    }

    setProtocol(href) {
        if (!href) {
            return;
        }

        const anchorPrefix = '#';
        const schemaPattern = /^[a-z0-9]+:\/?\/?/i;
        const isAnchor = href.indexOf(anchorPrefix) === 0;
        const isLocation = schemaPattern.test(href);

        if (isAnchor || isLocation) {
            return href;
        }

        return `http://${href}`;
    }

    createFromAttributesChildren(customAttributes, customClasses) {
        const children = this.createCollection();

        if (customClasses && Object.keys(customClasses).length !== 0) {
            const classesView = this.createDropdown(customClasses);

            this.classesView = classesView;
            this.customClasses = customClasses;

            children.add(classesView);
        }

        if (customAttributes) {
            Object.entries(customAttributes).forEach(([name, config]) => {
                const createAttributeMethod = this.attributeRenderMethods[config.type];

                if (!createAttributeMethod) {
                    return;
                }

                const createAttribute = createAttributeMethod.bind(this);
                const attributeView = createAttribute(config);

                this.attributeViews[`ibexaLink${name}`] = attributeView;

                children.add(attributeView);
            });

            this.customAttributes = customAttributes;
        }

        return children;
    }

    createFormChildren() {
        const children = this.createCollection();

        children.add(this.selectContentButtonView);
        children.add(this.urlInputView);
        children.add(this.titleView);
        children.add(this.targetSwitcherView);

        return children;
    }

    createDropdown(config) {
        const labeledDropdown = new LabeledFieldView(this.locale, createLabeledDropdown);
        const itemsList = new Collection();

        labeledDropdown.label = config.label;

        config.choices.forEach((choice) => {
            itemsList.add({
                type: 'button',
                model: new Model({
                    withText: true,
                    label: choice,
                    value: choice,
                }),
            });
        });

        addListToDropdown(labeledDropdown.fieldView, itemsList);

        this.listenTo(labeledDropdown.fieldView, 'execute', (event) => {
            const value = this.getNewValue(event.source.value, config.multiple, labeledDropdown.fieldView.element.value);

            labeledDropdown.fieldView.buttonView.set({
                label: value,
                withText: true,
            });

            labeledDropdown.fieldView.element.value = value;

            if (event.source.value) {
                labeledDropdown.set('isEmpty', false);
            }
        });

        return labeledDropdown;
    }

    getNewValue(clickedValue, multiple, previousValue = '') {
        const selectedItems = new Set(previousValue?.split(' '));

        if (selectedItems.has(clickedValue)) {
            selectedItems.delete(clickedValue);

            return [...selectedItems].join(' ');
        }

        if (!multiple) {
            selectedItems.clear();
        }

        selectedItems.add(clickedValue);

        return [...selectedItems].join(' ');
    }

    createTextInput({ label }) {
        const labeledInput = new LabeledFieldView(this.locale, createLabeledInputText);

        labeledInput.label = label;

        return labeledInput;
    }

    createNumberInput(config) {
        const labeledInput = new LabeledFieldView(this.locale, createLabeledInputNumber);

        labeledInput.label = config.label;

        return labeledInput;
    }

    createBoolean({ label }) {
        const labeledSwitch = new LabeledFieldView(this.locale, createLabeledSwitchButton);

        this.listenTo(labeledSwitch.fieldView, 'execute', () => {
            const value = !labeledSwitch.fieldView.isOn;

            labeledSwitch.fieldView.element.value = value;
            labeledSwitch.fieldView.set('value', value);
            labeledSwitch.fieldView.isOn = value;
        });

        labeledSwitch.label = label;
        labeledSwitch.fieldView.set('isEmpty', false);

        return labeledSwitch;
    }

    setError(message) {
        this.urlInputView.errorText = message;
    }

    createButton(label, icon, className, eventName) {
        const button = new ButtonView(this.locale);

        button.set({
            label,
            icon,
            withText: true,
        });

        button.extendTemplate({
            attributes: {
                class: className,
            },
        });

        if (eventName) {
            button.delegate('execute').to(this, eventName);
        }

        return button;
    }

    chooseContent() {
        const languageCode = document.querySelector('meta[name="LanguageCode"]').content;
        const config = JSON.parse(document.querySelector(`[data-udw-config-name="richtext_embed"]`).dataset.udwConfig);
        const { selectContent } = window.ibexa.richText.alloyEditor.callbacks;
        const mergedConfig = {
            onConfirm: this.confirmHandler,
            onCancel: this.cancelHandler,
            multiple: false,
            ...config,
            contentOnTheFly: {
                allowedLanguages: [languageCode],
            },
        };

        if (typeof selectContent === 'function') {
            selectContent(mergedConfig);
        }
    }

    confirmHandler(items) {
        const url = `ezlocation://${items[0].id}`;

        this.urlInputView.fieldView.element.value = url;
        this.urlInputView.fieldView.set('value', url);
        this.urlInputView.fieldView.set('isEmpty', !url);

        this.editor.focus();
    }

    cancelHandler() {
        this.editor.focus();
    }
}

export default IbexaLinkFormView;
