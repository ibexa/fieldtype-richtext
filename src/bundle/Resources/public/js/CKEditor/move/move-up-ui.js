import { Plugin } from 'ckeditor5';

import IbexaButtonView from '../common/button-view/button-view';

const { Translator } = window;

class IbexaMoveUpUI extends Plugin {
    constructor(props) {
        super(props);

        this.moveUp = this.moveUp.bind(this);
    }

    moveUp() {
        this.editor.execute('insertIbexaMove', { up: true });
    }

    init() {
        this.editor.ui.componentFactory.add('ibexaMoveUp', (locale) => {
            const buttonView = new IbexaButtonView(locale);

            buttonView.set({
                label: Translator.trans(/*@Desc("Move up")*/ 'move_up_btn.title', {}, 'ck_editor'),
                icon: window.ibexa.helpers.icon.getIconPath('circle-caret-up'),
                tooltip: true,
            });

            this.listenTo(buttonView, 'execute', this.moveUp);

            return buttonView;
        });
    }
}

export default IbexaMoveUpUI;
