import Plugin from '@ckeditor/ckeditor5-core/src/plugin';
import ButtonView from '@ckeditor/ckeditor5-ui/src/button/buttonview';
import { createDropdown, addToolbarToDropdown } from '@ckeditor/ckeditor5-ui/src/dropdown/utils';

const { Translator, ibexa } = window;

class IbexaCustomStyleInlineUI extends Plugin {
    constructor(props) {
        super(props);

        this.createButton = this.createButton.bind(this);
    }

    createButton([customStyleName, config]) {
        const { editor } = this;
        const customStyleInlineCommand = editor.commands.get('ibexaCustomStyleInline');

        this.editor.ui.componentFactory.add(customStyleName, (locale) => {
            const buttonView = new ButtonView(locale);

            buttonView.set({
                label: config.label,
                tooltip: true,
                isToggleable: true,
                withText: true,
            });

            buttonView.bind('isOn').to(customStyleInlineCommand, 'value', (value) => value === customStyleName);

            this.listenTo(buttonView, 'execute', () => {
                editor.execute(customStyleName);
                editor.editing.view.focus();
            });

            return buttonView;
        });

        return this.editor.ui.componentFactory.create(customStyleName);
    }

    init() {
        this.editor.ui.componentFactory.add('ibexaCustomStyleInline', (locale) => {
            const dropdownView = createDropdown(locale);
            const { customStyles } = ibexa.richText;
            const customStylesInline = Object.entries(customStyles).filter(([, config]) => config.inline);
            const customStylesButtons = customStylesInline.map(this.createButton);
            const defaultLabel = Translator.trans(/*@Desc("Custom styles")*/ 'custom_styles_btn.label', {}, 'ck_editor');
            const customStyleInlineCommand = this.editor.commands.get('ibexaCustomStyleInline');

            dropdownView.buttonView.set({
                label: defaultLabel,
                tooltip: true,
                withText: true,
            });

            addToolbarToDropdown(dropdownView, customStylesButtons, { enableActiveItemFocusOnDropdownOpen: true });

            this.editor.commands.add('ibexaCustomStyleInline', customStyleInlineCommand);

            dropdownView.buttonView.bind('label').to(customStyleInlineCommand, 'value', (value) => {
                const selectedCustomStyle = customStyles[value];

                return selectedCustomStyle?.label ?? defaultLabel;
            });

            return dropdownView;
        });
    }
}

export default IbexaCustomStyleInlineUI;
