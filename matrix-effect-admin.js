jQuery(document).ready(function($) {
    initPreview();
    function initPreview() {
        if ($('.matrix-preview .cursor').length === 0) {
            $('.matrix-preview').append('<span class="cursor">|</span>');
        }
        updatePreview();
        updateCursorPreview();
    }
    function updatePreview() {
        function updateWordPreview() {
    const frequency = $('[name="matrix_effect_options[word_effect_frequency]"]').val();
    $('.word-frequency-preview').text(
        frequency > 0 ?
        `Слова будут появляться каждые ${frequency} секунд` :
        "Эффект слов отключен"
    );
}
        const rgb = $('[name="matrix_effect_options[text_bg_color_rgb]"]').val();
        const a = $('[name="matrix_effect_options[text_bg_color_a]"]').val();

        if (rgb && a) {
            const rgba = hexToRgba(rgb, a);
            $('.matrix-preview').css('background-color', rgba);
        }
    }
    function updateCursorPreview() {
        const style = $('[name="matrix_effect_options[cursor_style]"]').val();
        let color = $('[name="matrix_effect_options[cursor_color]"]').val();
        if (!color || !isValidHexColor(color)) {
            color = '#00ff41';
            $('[name="matrix_effect_options[cursor_color]"]').val(color);
        }
        const cursor = $('.matrix-preview .cursor');
        let cursorChar = '|';
        let cursorStyle = `color: ${color};`;

        switch(style) {
            case 'underline':
                cursorChar = '_';
                cursorStyle += 'text-decoration: underline; opacity: 1;';
                break;
            case 'static':
                cursorStyle += 'opacity: 1;';
                break;
            default: // blinking
                const speed = $('[name="matrix_effect_options[cursor_blink_speed]"]').val() || 1000;
                cursorStyle += `animation: blink ${speed}ms step-end infinite;`;
        }
        cursor.attr('style', cursorStyle).text(cursorChar);
    }
    function hexToRgba(hex, alpha) {
        if (!hex || !hex.match(/^#[0-9A-F]{6}$/i)) return '';
        const r = parseInt(hex.slice(1, 3), 16);
        const g = parseInt(hex.slice(3, 5), 16);
        const b = parseInt(hex.slice(5, 7), 16);
        return `rgba(${r}, ${g}, ${b}, ${alpha})`;
    }
    function isValidHexColor(color) {
        return /^#([0-9A-F]{3}){1,2}$/i.test(color);
    }
    $('[name*="text_bg_color"]').on('change input', updatePreview);
    $('[name*="cursor_"]').on('change input', updateCursorPreview);
    $('[name="matrix_effect_options[word_effect_frequency]"]').on('change input', updateWordPreview);
});
