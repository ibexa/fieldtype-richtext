import Plugin from '@ckeditor/ckeditor5-core/src/plugin';
import clickOutsideHandler from '@ckeditor/ckeditor5-ui/src/bindings/clickoutsidehandler';
import ClickObserver from '@ckeditor/ckeditor5-engine/src/view/observer/clickobserver';

import { setPanelContentMaxHeight } from '../helpers/panel-helper';
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
        this.activeModelElement = null;

        let timeoutId = null;
        this.listenTo(this.balloon.view, 'change:top', () => {
            clearTimeout(timeoutId);
            timeoutId = setTimeout(() => {
                setPanelContentMaxHeight(this.balloon.view);
            }, 0);
        });
    }

    isCustomTagSelected() {
        const modelElement = this.editor.model.document.selection.getSelectedElement();

        return modelElement?.name === 'customTag' && modelElement?.getAttribute('customTagName') === this.componentName;
    }

    hasAttributes() {
        return !!Object.keys(this.config.attributes).length;
    }

    enableUserBalloonInteractions() {
        const viewDocument = this.editor.editing.view.document;

        this.listenTo(viewDocument, 'ibexa-show-custom-tag-settings', () => {
            if (this.isCustomTagSelected()) {
                this.showAttributes(viewDocument.selection.getSelectedElement());
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
            const values = this.activeModelElement.getAttribute('values');
            const newValues = { ...values };

            this.isNew = false;

            Object.entries(this.formView.attributeViews).forEach(([name, attributeView]) => {
                const getValue = this.formView.getValueMethods[this.config.attributes[name].type];

                if (!getValue) {
                    return;
                }

                newValues[name] = getValue(attributeView);
            });

            this.editor.model.change((writer) => {
                writer.setAttribute('values', newValues, this.activeModelElement);
            });

            this.reinitAttributesView();
            this.hideForm();
        });

        this.listenTo(formView, 'cancel-custom-tag', () => {
            this.hideForm();
        });

        this.listenTo(formView, 'ibexa-ckeditor-update-balloon-position', () => {
            this.balloon.updatePosition(this.getBalloonPositionData());
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

        this.balloon.updatePosition(this.getBalloonPositionData());
    }

    hideAttributes() {
        this.balloon.remove(this.attributesView);
        this.editor.editing.view.focus();
        this.reinitAttributesView();
    }

    showForm() {
        this.activeModelElement = this.editor.model.document.selection.getSelectedElement();

        const values = this.activeModelElement.getAttribute('values');
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
        this.editor.model.change((writer) => {
            if (this.balloon.hasView(this.attributesView)) {
                this.hideAttributes();
            }

            writer.remove(this.activeModelElement);

            this.activeModelElement = null;
        });
    }

    getBalloonPositionData() {
        const { view } = this.editor.editing;
        const viewDocument = view.document;
        const range = viewDocument.selection.getFirstRange();

        return { target: view.domConverter.viewRangeToDom(range) };
    }

    addCustomTag() {
        if (this.balloon.hasView(this.formView)) {
            return;
        }

        const values = Object.entries(this.config.attributes).reduce((outputValues, [attributeName, config]) => {
            outputValues[attributeName] = config.defaultValue;

            return outputValues;
        }, {});

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
