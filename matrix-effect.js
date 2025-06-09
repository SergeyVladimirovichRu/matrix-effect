document.addEventListener("DOMContentLoaded", function() {
    if (typeof matrixEffectData === 'undefined') {
        console.error('Matrix Effect: Data not loaded!');
        return;
    }
    applyMatrixSettings(matrixEffectData.settings);
    initMatrixEffect(matrixEffectData);
    function updateSettings(newSettings) {
        if (!newSettings) return;
        document.documentElement.style.setProperty(
            '--matrix-color',
            newSettings.text_color || '#00ff41'
        );
        document.documentElement.style.setProperty(
            '--text-bg-color',
            newSettings.text_bg_color || 'rgba(0, 0, 0, 0.7)'
        );
        restartEffects(newSettings);
    }
    function restartEffects(settings) {
        const container = document.getElementById('matrixEffectContainer');
        if (container) {
            container.innerHTML = '';
            initMatrixEffect({
                content: matrixEffectData.content,
                settings: settings
            });
        }
    }
    function applyMatrixSettings(settings) {
        if (!settings) {
            console.warn('Settings not provided, using defaults');
            settings = {
                text_color: '#00ff41',
                text_bg_color: 'rgba(0, 0, 0, 0.7)'
            };
        }
        document.documentElement.style.setProperty(
            '--matrix-color',
            settings.text_color || '#00ff41'
        );
        document.documentElement.style.setProperty(
            '--text-bg-color',
            settings.text_bg_color || 'rgba(0, 0, 0, 0.7)'
        );
        const container = document.getElementById('finalTextContainer');
        if (container) {
            container.style.setProperty(
                'background-color',
                settings.text_bg_color || 'rgba(0, 0, 0, 0.7)',
                'important'
            );
            console.log('Background set to:', getComputedStyle(container).backgroundColor);
        }
    }
    /**
     *
     * @param {Object} data
     */
    function initMatrixEffect(data) {
        let lastWordTime = 0;
        const finalMessage = data.content || "Text not found(Текст не найден)";
        const settings = data.settings || {};
        const matrixWords = [
    "MATRIX", "NEO", "MORPHEUS", "TRINITY",
    "ZION", "AGENT", "REDPILL", "BLUEPILL",
    "ORACLE", "ARCHITECT", "THE ONE", "CODE",
    "DECLARATION", "RESISTANCE", "DESTINY"
];
        const config = {
            textColor: settings.text_color || '#00ff41',
            textBgColor: settings.text_bg_color || 'rgba(0, 0, 0, 0.7)',
            animationSpeed: settings.animation_speed ? parseInt(settings.animation_speed) : 5,
            enablePause: settings.enable_pause !== undefined ? Boolean(parseInt(settings.enable_pause)) : true,
            typingSpeed: settings.typing_speed ? parseInt(settings.typing_speed) : 30,
            fallingFontSize: settings.falling_font_size ? parseInt(settings.falling_font_size) : 20,
            charsOpacity: settings.chars_opacity !== undefined ? parseFloat(settings.chars_opacity) : 0.8,
            cursorStyle: settings.cursor_style || 'blinking',
            cursorColor: settings.cursor_color || settings.text_color || '#00ff41',
            cursorBlinkSpeed: isNaN(parseInt(settings.cursor_blink_speed)) ? 1000 : parseInt(settings.cursor_blink_speed),
            wordFrequency: settings.word_effect_frequency ? parseInt(settings.word_effect_frequency) : 15,
            wordSize: settings.word_effect_size ? parseInt(settings.word_effect_size) : 24
        };
        const matrixContainer = document.getElementById("matrixEffectContainer");
        const finalTextContainer = document.getElementById("finalTextContainer");
        const pauseOverlay = document.getElementById("pauseOverlay");
        const matrixChars = "1234567890qwertyuiopasdfghjkl;zxcvbnm,./'";
        let isPaused = false;
        let matrixInterval;
        let typeWriterTimeout;
        let currentCharIndex = 0;
        let currentText = "";
        function createMatrixEffect() {
            if (!matrixContainer) return;
            if (config.wordFrequency > 0 &&
                Date.now() - lastWordTime > config.wordFrequency * 1000) {
                showMatrixWord();
                lastWordTime = Date.now();
            }
            const charCount = 150;
            const duration = 5000 * (6 / config.animationSpeed);
            if (Math.random() < 0.05) {
                showMatrixWord();
            }
            for (let i = 0; i < charCount; i++) {
                setTimeout(() => {
                    if (isPaused) return;

                    const span = document.createElement("span");
                    span.classList.add("falling-char");
                    span.textContent = matrixChars[Math.floor(Math.random() * matrixChars.length)];
                    span.style.left = Math.random() * 100 + "vw";
                    span.style.fontSize = config.fallingFontSize + 'px';
                    span.style.animationDuration = (Math.random() * 3 + 2) * (6 / config.animationSpeed) + "s";
                    span.style.opacity = Math.random() * (config.charsOpacity - 0.3) + 0.3;
                    matrixContainer.appendChild(span);
                    setTimeout(() => {
                        if (span.parentNode) span.remove();
                    }, (Math.random() * 3 + 2) * 1000 * (6 / config.animationSpeed));
                }, Math.random() * duration);
            }
        }
        function showMatrixWord() {
            const word = matrixWords[Math.floor(Math.random() * matrixWords.length)];
            const wordElement = document.createElement("div");
            wordElement.className = "matrix-word";
            wordElement.textContent = word;
            wordElement.style.fontSize = config.wordSize + 'px';
            wordElement.style.left = Math.random() * 70 + 15 + "vw";
            wordElement.style.animationDuration = (Math.random() * 2 + 3) + "s";
            matrixContainer.appendChild(wordElement);
            setTimeout(() => {
                wordElement.remove();
            }, 5000);
        }
        function typeWriter() {
            if (isPaused || currentCharIndex >= finalMessage.length) {
                if (currentCharIndex >= finalMessage.length) {
                    finalTextContainer.innerHTML = currentText + `<span class="cursor" style="${getCursorStyle()}">${getCursorChar()}</span>`;
                }
                return;
            }
            const currentChar = finalMessage.charAt(currentCharIndex);
            currentText += currentChar;
            finalTextContainer.innerHTML = currentText + `<span class="cursor" style="${getCursorStyle()}">${getCursorChar()}</span>`;
            finalTextContainer.scrollTop = finalTextContainer.scrollHeight;
            currentCharIndex++;
            let delay = config.typingSpeed + (Math.random() * 50);
            if (/[.,:;—–]/.test(currentChar)) delay = 1000 + Math.random() * 2000;
            if (/[!?]/.test(currentChar)) delay = 500 + Math.random() * 1000;

            typeWriterTimeout = setTimeout(typeWriter, delay);
        }
        function getCursorStyle() {
            let style = `color: ${config.cursorColor};`;

            switch(config.cursorStyle) {
                case 'static':
                    return style + 'opacity: 1;';
                case 'underline':
                    return style + 'text-decoration: underline; opacity: 1;';
                default:
                    return style + `animation: blink ${config.cursorBlinkSpeed}ms step-end infinite;`;
            }
        }
        function getCursorChar() {
            return config.cursorStyle === 'underline' ? '_' : '|';
        }
        function togglePause() {
            isPaused = !isPaused;
            if (isPaused) {
                pauseOverlay.style.display = 'flex';
                clearTimeout(typeWriterTimeout);
                document.querySelectorAll('.falling-char').forEach(char => {
                    char.style.animationPlayState = 'paused';
                });
            } else {
                pauseOverlay.style.display = 'none';
                document.querySelectorAll('.falling-char').forEach(char => {
                    char.style.animationPlayState = 'running';
                });
                typeWriter();
            }
        }
        if (matrixContainer) matrixContainer.innerHTML = '';
        if (finalTextContainer) {
            finalTextContainer.innerHTML = '<span class="cursor">_</span>';
            finalTextContainer.style.backgroundColor = config.textBgColor;
        }
        if (pauseOverlay) pauseOverlay.style.display = 'none';
        if (matrixContainer) {
            createMatrixEffect();
            matrixInterval = setInterval(() => {
        createMatrixEffect();
        }, 15000 / config.animationSpeed);
        }

        if (finalTextContainer) {
            setTimeout(typeWriter, 1000);
        }
        if (config.enablePause) {
            document.addEventListener('click', togglePause);
            document.addEventListener('keydown', (e) => {
                if (e.code === 'Space') {
                    e.preventDefault();
                    togglePause();
                }
            });
        }
        window.addEventListener('beforeunload', () => {
            clearInterval(matrixInterval);
            clearTimeout(typeWriterTimeout);
        });
    }
});
