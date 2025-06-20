import {
    View,
    ButtonView,
    LabeledFieldView,
    ViewModel,
    Collection,
    createLabeledInputText,
    createLabeledDropdown,
    addListToDropdown,
} from 'ckeditor5';

import { createLabeledInputNumber } from '../../common/input-number/utils';
import { createLabeledSwitchButton } from '../../common/switch-button/utils';

class IbexaCustomTagFormView extends View {
    constructor(props) {
        super(props);

        this.locale = props.locale;

        this.saveButtonView = this.createButton('Save', null, 'ck-button-save', 'save-custom-tag');
        this.cancelButtonView = this.createButton('Cancel', null, 'ck-button-cancel', 'cancel-custom-tag');

        const attributeRenderMethods = window.ibexa.richText.CKEditor.customTags?.attributeRenderMethods || {};
        const setValueMethods = window.ibexa.richText.CKEditor.customTags?.setValueMethods || {};
        const getValueMethods = window.ibexa.richText.CKEditor.customTags?.getValueMethods || {};

        this.attributeViews = {};
        this.attributeRenderMethods = {
            string: this.createTextInput,
            number: this.createNumberInput,
            choice: this.createDropdown,
            boolean: this.createBoolean,
            link: this.createTextInput,
            ...attributeRenderMethods,
        };
        this.setValueMethods = {
            string: this.setStringValue,
            number: this.setNumberValue,
            choice: this.setChoiceValue,
            boolean: this.setBooleanValue,
            link: this.setStringValue,
            ...setValueMethods,
        };

        this.getValueMethods = {
            string: this.getStringValue,
            number: this.getNumberValue,
            choice: this.getChoiceValue,
            boolean: this.getBooleanValue,
            link: this.getStringValue,
            ...getValueMethods,
        };
    }

    setValues(values) {
        Object.entries(values).forEach(([name, value]) => {
            const attributeView = this.attributeViews[name];
            const setValueMethod = this.setValueMethods[this.childrenData.attributes[name].type];

            if (!attributeView || !setValueMethod) {
                return;
            }

            setValueMethod(attributeView, value);
        });
    }

    setNumberValue(attributeView, value) {
        attributeView.fieldView.element.value = value;
        attributeView.fieldView.set('value', value);
        attributeView.fieldView.set('isEmpty', value !== 0 && !value);
    }

    setStringValue(attributeView, value) {
        attributeView.fieldView.element.value = value;
        attributeView.fieldView.set('value', value);
        attributeView.fieldView.set('isEmpty', !value);
    }

    setChoiceValue(attributeView, value) {
        attributeView.fieldView.element.value = value;
        attributeView.fieldView.buttonView.set({
            label: value,
            withText: true,
        });
        attributeView.set('isEmpty', !value);
    }

    setBooleanValue(attributeView, value) {
        attributeView.fieldView.isOn = !!value || value === 'true';
        attributeView.fieldView.element.value = value;
        attributeView.fieldView.set('value', value);
        attributeView.fieldView.set('isEmpty', false);
    }

    getNumberValue(attributeView) {
        return attributeView.fieldView.element.value;
    }

    getStringValue(attributeView) {
        return attributeView.fieldView.element.value;
    }

    getChoiceValue(attributeView) {
        return attributeView.fieldView.element.value;
    }

    getBooleanValue(attributeView) {
        return attributeView.fieldView.element.value;
    }

    setChildren(childrenData, label) {
        this.childrenData = childrenData;
        this.children = this.createFormChildren(childrenData);

        this.setTemplate({
            tag: 'div',
            attributes: {
                class: 'ibexa-ckeditor-balloon-form ibexa-custom-panel',
            },
            children: [
                {
                    tag: 'div',
                    attributes: {
                        class: 'ibexa-ckeditor-balloon-form__header ibexa-custom-panel__header',
                    },
                    children: [label],
                },
                {
                    tag: 'form',
                    attributes: {
                        tabindex: '-1',
                    },
                    children: [
                        {
                            tag: 'div',
                            attributes: {
                                class: 'ibexa-ckeditor-balloon-form__fields ibexa-custom-panel__content ibexa-custom-panel__content--overflow-with-scroll',
                            },
                            children: this.children,
                        },
                        {
                            tag: 'div',
                            attributes: {
                                class: 'ibexa-ckeditor-balloon-form__actions ibexa-custom-panel__footer',
                            },
                            children: [this.saveButtonView, this.cancelButtonView],
                        },
                    ],
                },
            ],
        });
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

    createFormChildren({ attributes }) {
        const children = this.createCollection();

        if (attributes) {
            Object.entries(attributes).forEach(([name, config]) => {
                const createAttributeMethod = this.attributeRenderMethods[config.type];

                if (!createAttributeMethod) {
                    return;
                }

                const createAttribute = createAttributeMethod.bind(this);
                const attributeView = createAttribute(config, this.locale, name);

                attributeView.delegate('ibexa-ckeditor-update-balloon-position').to(this, 'ibexa-ckeditor-update-balloon-position');

                this.attributeViews[name] = attributeView;

                children.add(attributeView);
            });
        }

        return children;
    }

    createDropdown(config) {
        const labeledDropdown = new LabeledFieldView(this.locale, createLabeledDropdown);
        const itemsList = new Collection();

        labeledDropdown.label = config.label;

        config.choices.forEach((choice) => {
            itemsList.add({
                type: 'button',
                model: new ViewModel({
                    withText: true,
                    label: config.choicesLabel[choice],
                    value: choice,
                }),
            });
        });

        addListToDropdown(labeledDropdown.fieldView, itemsList);

        this.listenTo(labeledDropdown.fieldView, 'execute', (event) => {
            labeledDropdown.fieldView.buttonView.set({
                label: config.choicesLabel[event.source.value],
                withText: true,
            });

            labeledDropdown.fieldView.element.value = event.source.value;

            if (event.source.value) {
                labeledDropdown.set('isEmpty', false);
            }
        });

        return labeledDropdown;
    }

    createTextInput(config) {
        const labeledInput = new LabeledFieldView(this.locale, createLabeledInputText);

        labeledInput.label = config.label;

        return labeledInput;
    }

    createNumberInput(config) {
        const labeledInput = new LabeledFieldView(this.locale, createLabeledInputNumber);

        labeledInput.label = config.label;

        return labeledInput;
    }

    createBoolean(config) {
        const labeledSwitch = new LabeledFieldView(this.locale, createLabeledSwitchButton);

        this.listenTo(labeledSwitch.fieldView, 'execute', () => {
            const value = !labeledSwitch.fieldView.isOn;

            labeledSwitch.fieldView.element.value = value;
            labeledSwitch.fieldView.set('value', value);
            labeledSwitch.fieldView.isOn = value;
        });

        labeledSwitch.label = config.label;

        return labeledSwitch;
    }
}

export default IbexaCustomTagFormView;
