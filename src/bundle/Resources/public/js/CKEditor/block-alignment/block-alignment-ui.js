import Plugin from '@ckeditor/ckeditor5-core/src/plugin';
import ClickObserver from '@ckeditor/ckeditor5-engine/src/view/observer/clickobserver';

import IbexaButtonView from '../common/button-view/button-view';

const { ibexa, Translator } = window;

class IbexaInlineCustomTagUI extends Plugin {
    constructor(props) {
        super(props);

        this.setAlignment = this.setAlignment.bind(this);
    }

    getModelElement() {
        return this.editor.model.document.selection.getSelectedElement();
    }

    setAlignment(alignment) {
        this.editor.execute('addBlockAlignment', { alignment });
    }

    createButton(label, icon, alignment, locale) {
        const buttonView = new IbexaButtonView(locale);
        const command = this.editor.commands.get('addBlockAlignment');

        buttonView.set({
            label: label,
            icon: icon,
            tooltip: true,
        });

        buttonView.bind('isOn').to(command, 'value', (value) => value === alignment);

        this.listenTo(buttonView, 'execute', this.setAlignment.bind(this, alignment));

        return buttonView;
    }

    init() {
        this.editor.ui.componentFactory.add(
            'ibexaBlockLeftAlignment',
            this.createButton.bind(
                this,
                Translator.trans(/*@Desc("Left")*/ 'block_alignment.left', {}, 'ck_editor'),
                ibexa.helpers.icon.getIconPath('image-left'),
                'left'
            )
        );
        this.editor.ui.componentFactory.add(
            'ibexaBlockCenterAlignment',
            this.createButton.bind(
                this,
                Translator.trans(/*@Desc("Center")*/ 'block_alignment.center', {}, 'ck_editor'),
                ibexa.helpers.icon.getIconPath('image-center'),
                'center'
            )
        );
        this.editor.ui.componentFactory.add(
            'ibexaBlockRightAlignment',
            this.createButton.bind(
                this,
                Translator.trans(/*@Desc("Right")*/ 'block_alignment.right', {}, 'ck_editor'),
                ibexa.helpers.icon.getIconPath('image-right'),
                'right'
            )
        );

        this.editor.editing.view.addObserver(ClickObserver);
    }
}

export default IbexaInlineCustomTagUI;
