import { Plugin, createDropdown, addListToDropdown, ViewModel, Collection } from 'ckeditor5';

class IbexaEmbedImageVariationsUI extends Plugin {
    constructor(props) {
        super(props);

        this.changeVariation = this.changeVariation.bind(this);
    }

    changeVariation(dropdownView, event) {
        const modelElement = this.getSelectedElement();
        const { variation } = event.source;

        dropdownView.buttonView.set({
            label: variation,
        });

        this.editor.model.change((writer) => {
            writer.setAttribute('size', variation, modelElement);
        });
    }

    getSelectedElement() {
        return this.editor.model.document.selection.getSelectedElement();
    }

    init() {
        this.editor.ui.componentFactory.add('imageVarations', (locale) => {
            const dropdownView = createDropdown(locale);
            const itemDefinitions = new Collection();

            Object.keys(window.ibexa.adminUiConfig.imageVariations).forEach((variation) => {
                itemDefinitions.add({
                    type: 'button',
                    model: new ViewModel({
                        label: variation,
                        variation: variation,
                        withText: true,
                    }),
                });
            });

            dropdownView.buttonView.set({
                isOn: true,
                withText: true,
                label: this.getSelectedElement().getAttribute('size'),
            });

            addListToDropdown(dropdownView, itemDefinitions);

            this.editor.model.document.selection.on('change:range', () => {
                const modelElement = this.getSelectedElement();

                if (modelElement && modelElement.name === 'embedImage') {
                    dropdownView.buttonView.set({
                        label: modelElement.getAttribute('size'),
                    });
                }
            });

            this.listenTo(dropdownView, 'execute', this.changeVariation.bind(this, dropdownView));

            return dropdownView;
        });
    }
}

export default IbexaEmbedImageVariationsUI;
