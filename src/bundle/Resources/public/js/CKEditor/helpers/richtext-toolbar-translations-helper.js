const { Translator } = window;

const getRichTextToolbarTranslations = () => ({
    Bold: Translator.trans(/*@Desc("Bold")*/ 'bold_btn.label', {}, 'ck_editor'),
    Italic: Translator.trans(/*@Desc("Italic")*/ 'italic_btn.label', {}, 'ck_editor'),
    Underline: Translator.trans(/*@Desc("Underline")*/ 'underline_btn.label', {}, 'ck_editor'),
    'Bulleted List': Translator.trans(/*@Desc("Bulleted List")*/ 'bulleted_list_btn.label', {}, 'ck_editor'),
    'Numbered List': Translator.trans(/*@Desc("Numbered List")*/ 'numbered_list_btn.label', {}, 'ck_editor'),
    'Insert table': Translator.trans(/*@Desc("Insert table")*/ 'insert_table_btn.label', {}, 'ck_editor'),
    'Text alignment': Translator.trans(/*@Desc("Text alignment")*/ 'text_alignment_btn.label', {}, 'ck_editor'),
    Heading: Translator.trans(/*@Desc("Heading")*/ 'heading_btn.label', {}, 'ck_editor'),
    Subscript: Translator.trans(/*@Desc("Subscript")*/ 'subscript_btn.label', {}, 'ck_editor'),
    Superscript: Translator.trans(/*@Desc("Superscript")*/ 'superscript_btn.label', {}, 'ck_editor'),
    Strikethrough: Translator.trans(/*@Desc("Strikethrough")*/ 'strikethrough_btn.label', {}, 'ck_editor'),
    'Block quote': Translator.trans(/*@Desc("Block quote")*/ 'block_quote_btn.label', {}, 'ck_editor'),
    'Special characters': Translator.trans(/*@Desc("Special characters")*/ 'special_characters_btn.label', {}, 'ck_editor'),
    'Align left': Translator.trans(/*@Desc("Align left")*/ 'text_align_left_btn.label', {}, 'ck_editor'),
    'Align right': Translator.trans(/*@Desc("Align right")*/ 'text_align_right_btn.label', {}, 'ck_editor'),
    'Align center': Translator.trans(/*@Desc("Align center")*/ 'text_align_center_btn.label', {}, 'ck_editor'),
    Justify: Translator.trans(/*@Desc("Justify")*/ 'text_align_justify_btn.label', {}, 'ck_editor'),
    Disc: Translator.trans(/*@Desc("Disc")*/ 'bulleted_list_disc_btn.label', {}, 'ck_editor'),
    Circle: Translator.trans(/*@Desc("Circle")*/ 'bulleted_list_circle_btn.label', {}, 'ck_editor'),
    Square: Translator.trans(/*@Desc("Square")*/ 'bulleted_list_square_btn.label', {}, 'ck_editor'),
    Decimal: Translator.trans(/*@Desc("Decimal")*/ 'numbered_list_decimal_btn.label', {}, 'ck_editor'),
    'Decimal with leading zero': Translator.trans(
        /*@Desc("Decimal with leading zero")*/ 'numbered_list_decimal_leading_zero_btn.label',
        {},
        'ck_editor',
    ),
    'Lower-roman': Translator.trans(/*@Desc("Lower-roman")*/ 'numbered_list_lower_roman_btn.label', {}, 'ck_editor'),
    'Upper-roman': Translator.trans(/*@Desc("Upper-roman")*/ 'numbered_list_upper_roman_btn.label', {}, 'ck_editor'),
    'Lower-latin': Translator.trans(/*@Desc("Lower-latin")*/ 'numbered_list_lower_latin_btn.label', {}, 'ck_editor'),
    'Upper-latin': Translator.trans(/*@Desc("Upper-latin")*/ 'numbered_list_upper_latin_btn.label', {}, 'ck_editor'),
});

export { getRichTextToolbarTranslations };
