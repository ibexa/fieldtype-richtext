import Plugin from '@ckeditor/ckeditor5-core/src/plugin';

import IbexaButtonView from '../common/button-view/button-view';

const { Translator } = window;

class IbexaMoveDownUI extends Plugin {
    constructor(props) {
        super(props);

        this.moveDown = this.moveDown.bind(this);
    }

    moveDown() {
        this.editor.execute('insertIbexaMove', { up: false });
    }

    init() {
        this.editor.ui.componentFactory.add('ibexaMoveDown', (locale) => {
            const buttonView = new IbexaButtonView(locale);

            buttonView.set({
                label: Translator.trans(/*@Desc("Move down")*/ 'move_down_btn.title', {}, 'ck_editor'),
                icon: window.ibexa.helpers.icon.getIconPath('circle-caret-down'),
                tooltip: true,
            });

            this.listenTo(buttonView, 'execute', this.moveDown);

            return buttonView;
        });
    }
}

export default IbexaMoveDownUI;
