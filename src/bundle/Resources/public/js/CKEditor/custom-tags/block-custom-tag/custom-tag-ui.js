import Plugin from '@ckeditor/ckeditor5-core/src/plugin';
import clickOutsideHandler from '@ckeditor/ckeditor5-ui/src/bindings/clickoutsidehandler';
import ClickObserver from '@ckeditor/ckeditor5-engine/src/view/observer/clickobserver';

import IbexaCustomTagFormView from '../ui/custom-tag-form-view';
import IbexaCustomTagAttributesView from '../ui/custom-tag-attributes-view';
import IbexaButtonView from '../../common/button-view/button-view';

class IbexaCustomTagUI extends Plugin {
    constructor(props) {
        super(props);

        this.balloon = this.editor.plugins.get('ContextualBalloon');
        this.formView = this.createFormView();
        this.attributesView = this.createAttributesView();

        this.showForm = this.showForm.bind(this);
        this.addCustomTag = this.addCustomTag.bind(this);

        this.isNew = false;
    }

    isCustomTagSelected(eventData) {
        const modelElement = this.editor.model.document.selection.getSelectedElement();

        return (
            !!eventData.domTarget.closest(`[data-ezname="${this.componentName}"]`) &&
            modelElement?.name === 'customTag' &&
            modelElement?.getAttribute('customTagName') === this.componentName
        );
    }

    isRemoveButtonClicked(eventData) {
        return !!eventData.domTarget.closest('.ibexa-btn--remove-custom-tag');
    }

    isShowAttributesButtonClicked(eventData) {
        return !!eventData.domTarget.closest('.ibexa-btn--show-custom-tag-attributes');
    }

    hasAttributes() {
        return !!Object.keys(this.config.attributes).length;
    }

    enableUserBalloonInteractions() {
        const viewDocument = this.editor.editing.view.document;

        this.listenTo(viewDocument, 'click', (eventInfo, eventData) => {
            if (this.isCustomTagSelected(eventData)) {
                if (this.isRemoveButtonClicked(eventData)) {
                    this.removeCustomTag();
                }

                if (this.isShowAttributesButtonClicked(eventData)) {
                    this.showAttributes(eventData.domTarget);
                }
            }
        });

        clickOutsideHandler({
            emitter: this.formView,
            activator: () => this.balloon.hasView(this.formView),
            contextElements: [this.balloon.view.element],
            callback: () => this.hideForm(),
        });

        clickOutsideHandler({
            emitter: this.attributesView,
            activator: () => this.balloon.hasView(this.attributesView),
            contextElements: [this.balloon.view.element],
            callback: () => this.hideAttributes(),
        });
    }

    createAttributesView() {
        const attributesView = new IbexaCustomTagAttributesView({ locale: this.editor.locale });

        this.listenTo(attributesView, 'edit-attributes', () => {
            this.hideAttributes();
            this.showForm();
        });

        return attributesView;
    }

    reinitAttributesView() {
        this.attributesView.destroy();

        this.attributesView = this.createAttributesView();

        this.attributesView.setChildren({
            attributes: this.config.attributes,
        });

        clickOutsideHandler({
            emitter: this.attributesView,
            activator: () => this.balloon.hasView(this.attributesView),
            contextElements: [this.balloon.view.element],
            callback: () => this.hideAttributes(),
        });
    }

    createFormView() {
        const formView = new IbexaCustomTagFormView({ locale: this.editor.locale });

        this.listenTo(formView, 'save-custom-tag', () => {
            const modelElement = this.editor.model.document.selection.getSelectedElement();
            const values = modelElement.getAttribute('values');
            const newValues = { ...values };

            this.isNew = false;

            Object.keys(values).forEach((name) => {
                const attributeView = this.formView.attributeViews[name];

                if (!attributeView) {
                    return;
                }

                newValues[name] = attributeView.fieldView.element.value;
            });

            this.editor.model.change((writer) => {
                writer.setAttribute('values', newValues, modelElement);
            });

            this.reinitAttributesView();
            this.hideForm();
        });

        this.listenTo(formView, 'cancel-custom-tag', () => {
            this.hideForm();
        });

        return formView;
    }

    showAttributes(target) {
        const modelElement = this.editor.model.document.selection.getSelectedElement();
        const values = modelElement.getAttribute('values');

        this.attributesView.setValues(values, window.ibexa.richText.customTags[this.componentName].label);

        this.balloon.add({
            view: this.attributesView,
            position: { target },
        });

        this.balloon.updatePosition({ target });
    }

    hideAttributes() {
        this.balloon.remove(this.attributesView);
        this.editor.editing.view.focus();
        this.reinitAttributesView();
    }

    showForm() {
        const modelElement = this.editor.model.document.selection.getSelectedElement();
        const values = modelElement.getAttribute('values');
        const parsedValues = Object.entries(values).reduce((output, [key, value]) => {
            if (this.config.attributes[key]?.type === 'boolean') {
                return {
                    ...output,
                    [key]: value === 'true',
                };
            }

            return {
                ...output,
                [key]: value,
            };
        }, {});

        this.formView.setValues(parsedValues);

        this.balloon.add({
            view: this.formView,
            position: this.getBalloonPositionData(),
        });

        this.balloon.updatePosition(this.getBalloonPositionData());
    }

    hideForm() {
        if (this.isNew) {
            this.isNew = false;

            this.removeCustomTag();
        }

        this.balloon.remove(this.formView);
        this.editor.editing.view.focus();
    }

    removeCustomTag() {
        const modelElement = this.editor.model.document.selection.getSelectedElement();

        this.editor.model.change((writer) => {
            if (this.balloon.hasView(this.attributesView)) {
                this.hideAttributes();
            }

            writer.remove(modelElement);
        });
    }

    getBalloonPositionData() {
        const { view } = this.editor.editing;
        const viewDocument = view.document;
        const range = viewDocument.selection.getFirstRange();

        return { target: view.domConverter.viewRangeToDom(range) };
    }

    addCustomTag() {
        const values = Object.entries(this.config.attributes).reduce((outputValues, [attributeName, config]) => {
            outputValues[attributeName] = config.defaultValue;

            return outputValues;
        }, {});

        this.editor.focus();
        this.editor.execute('insertIbexaCustomTag', { customTagName: this.componentName, values });

        if (this.hasAttributes()) {
            this.isNew = true;

            this.showForm();
        }
    }

    init() {
        this.editor.ui.componentFactory.add(this.componentName, (locale) => {
            const buttonView = new IbexaButtonView(locale);

            buttonView.set({
                label: this.config.label,
                icon: this.config.icon,
                tooltip: true,
            });

            this.listenTo(buttonView, 'execute', this.addCustomTag);

            return buttonView;
        });

        this.editor.editing.view.addObserver(ClickObserver);

        this.enableUserBalloonInteractions();
    }
}

export default IbexaCustomTagUI;
