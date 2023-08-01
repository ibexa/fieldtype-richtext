import Plugin from '@ckeditor/ckeditor5-core/src/plugin';
import { createDropdown, addListToDropdown } from '@ckeditor/ckeditor5-ui/src/dropdown/utils';
import Model from '@ckeditor/ckeditor5-ui/src/model';
import Collection from '@ckeditor/ckeditor5-utils/src/collection';

const { Translator, ibexa } = window;

class IbexaCustomStyleInlineUI extends Plugin {
    constructor(props) {
        super(props);

        this.createButton = this.createButton.bind(this);
    }

    createButton([customStyleName, config]) {
        const { editor } = this;
        const customStyleInlineCommand = editor.commands.get('ibexaCustomStyleInline');
        const buttonDef = {
            type: 'button',
            model: new Model({
                label: config.label,
                tooltip: true,
                isToggleable: true,
                withText: true,
            }),
        };

        buttonDef.model.bind('isOn').to(customStyleInlineCommand, 'value', (value) => value === customStyleName);
        buttonDef.model.set({ commandName: customStyleName });

        return buttonDef;
    }

    init() {
        this.editor.ui.componentFactory.add('ibexaCustomStyleInline', (locale) => {
            const dropdownView = createDropdown(locale);
            const { customStyles } = ibexa.richText;
            const customStylesInline = Object.entries(customStyles).filter(([, config]) => config.inline);
            const customStylesButtons = new Collection();
            const defaultLabel = Translator.trans(/*@Desc("Custom styles")*/ 'custom_styles_btn.label', {}, 'ck_editor');
            const customStyleInlineCommand = this.editor.commands.get('ibexaCustomStyleInline');

            dropdownView.buttonView.set({
                label: defaultLabel,
                tooltip: true,
                withText: true,
            });

            customStylesInline.forEach((customStyle) => customStylesButtons.add(this.createButton(customStyle)));

            addListToDropdown(dropdownView, customStylesButtons);

            this.editor.commands.add('ibexaCustomStyleInline', customStyleInlineCommand);

            dropdownView.buttonView.bind('label').to(customStyleInlineCommand, 'value', (value) => {
                const selectedCustomStyle = customStyles[value];

                return selectedCustomStyle?.label ?? defaultLabel;
            });

            this.listenTo(dropdownView, 'execute', (event) => {
                const { editor } = this;
                const { commandName } = event.source;

                editor.execute(commandName);
                editor.editing.view.focus();
            });

            return dropdownView;
        });
    }
}

export default IbexaCustomStyleInlineUI;
