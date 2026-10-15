// Função para adicionar botões personalizados no popover
// Chaves válidas do driver.js v1: o overlay usa `overlayOpacity` e o recorte
// usa `stagePadding` (as antigas `opacity`/`padding`/`closeBtnText` eram
// ignoradas silenciosamente).
export const configDriver = {
    animate: true,
    overlayOpacity: 0.75,
    stagePadding: 10,
    nextBtnText: "Próximo",
    prevBtnText: "Anterior",
    doneBtnText:"Concluir",
    allowClose: false,
}
export const defaultDriverConfig = {
    animate: true,
    overlayOpacity: 0.75,
    stagePadding: 10,
    allowClose: true,
    nextBtnText: 'Próximo',
    prevBtnText: 'Anterior',
    doneBtnText: 'Concluir',
    showProgress: false
};
export function AddButtonActions(popover, title, callback) {
    const button = document.createElement('button');
    button.innerText = title;
    button.style.cssText = `
        float: left;
        padding: 10px;
        border-radius: 7px;
        border: 1px solid #ccc;
        color: #2d2d2d;
        font-weight: 400;
        text-shadow: none;
    `;
    if (popover.footerButtons.firstChild) {
        popover.footerButtons.insertBefore(button, popover.footerButtons.firstChild);
    } else {
        popover.footerButtons.appendChild(button);
    }
    if (callback) button.addEventListener('click', callback);
    return button;
}
export function startDriverTour(steps, customConfig = {}) {
    const driverObj = drivertour({
        ...defaultDriverConfig,
        ...customConfig,
        steps: steps.map(step => ({
            ...step,
            showButtons: step.onHighlighted ? ['close'] : ['next', 'prev', 'close']
        }))
    });
    driverObj.drive();
    return driverObj;
}

export const sleep = (ms) => new Promise((resolve) => setTimeout(resolve, ms));

export function isElementVisible(element) {
    if (!element || !element.isConnected) return false;

    if (typeof element.checkVisibility === 'function') {
        return element.checkVisibility({
            visibilityProperty: true,
            contentVisibilityAuto: true
        });
    }

    const style = window.getComputedStyle(element);
    return style.display !== 'none'
        && style.visibility !== 'hidden'
        && element.offsetParent !== null;
}

// Resolve os candidatos na ordem dos seletores (não na ordem do documento),
// para que uma lista de seletores possa expressar preferência.
export function getFirstVisible(selector) {
    return selector
        .split(',')
        .map((part) => part.trim())
        .filter(Boolean)
        .flatMap((part) => Array.from(document.querySelectorAll(part)))
        .find(isElementVisible) ?? null;
}

export function waitForElement(selector, { timeout = 10000, visible = true, root = document.body } = {}) {
    return new Promise((resolve, reject) => {
        const find = () => {
            const element = visible
                ? getFirstVisible(selector)
                : document.querySelector(selector);

            return element ?? null;
        };

        const found = find();
        if (found) return resolve(found);

        let timer = null;
        const observer = new MutationObserver(() => {
            const element = find();
            if (!element) return;

            cleanup();
            resolve(element);
        });

        const cleanup = () => {
            observer.disconnect();
            clearTimeout(timer);
        };

        observer.observe(root, {
            childList: true,
            subtree: true,
            attributes: true,
            attributeFilter: ['class', 'style', 'open', 'hidden']
        });

        timer = setTimeout(() => {
            cleanup();
            reject(new Error(`Elemento "${selector}" não encontrado após ${timeout}ms`));
        }, timeout);
    });
}

/**
 * Cria um botão de ação customizável dentro do popover do Driver.js.
 *
 * O callback recebe um objeto com os helpers do tour para que a lógica
 * customizada possa compor o comportamento (avançar, finalizar, recarregar)
 * em vez de reimplementar adestroy/avanço na mão.
 *
 * @param {object}  popover    Elemento do popover atual (via onPopoverRender).
 * @param {object}  driverObj  Instância do driver do tour.
 * @param {object}  options
 * @param {string}  options.label        Texto do botão.
 * @param {Function} options.onClick     Callback async. Recebe { next, prev, finish, popover, driver, button }.
 * @param {Function} options.onFinish    Executado ANTES de o tour ser destruído.
 * @param {boolean} options.reopenOptions Reabre o modal de opções após finalizar. Padrão: true.
 * @param {boolean} options.removeNav    Remove os botões Próximo/Anterior. Padrão: true.
 * @param {boolean} options.style        Aplica o estilo padrão (cinza). Padrão: true.
 * @returns {HTMLButtonElement}
 */
export function addTourAction(popover, driverObj, {
    label = 'Encerrar tour',
    onClick = null,
    onFinish = null,
    reopenOptions = true,
    removeNav = true,
    style = true,
} = {}) {
    if (removeNav) {
        popover.nextButton?.remove();
        popover.previousButton?.remove();
    }

    // finish() é a saída padrão: destroy + reabrir modal de opções.
    const finish = async () => {
        if (onFinish) {
            try {
                await onFinish({ popover, driver: driverObj });
            } catch (error) {
                console.error(`Falha em onFinish do botão "${label}":`, error);
            }
        }

        await driverObj.destroy();
        Alpine.store('tourguide').openMai = reopenOptions;
    };

    const handleClick = async (event) => {
        const button = event?.currentTarget ?? null;

        if (!onClick) {
            await finish();
            return;
        }

        try {
            await onClick({
                button,
                popover,
                driver: driverObj,
                next: () => driverObj.moveNext(),
                prev: () => driverObj.movePrevious(),
                finish,
            });
        } catch (error) {
            console.error(`Falha no onClick do botão "${label}":`, error);
        }
    };

    const button = AddButtonActions(popover, label, handleClick);

    if (!style) {
        button.style.cssText = button.style.cssText
            .replace('border: 1px solid #ccc', 'border: 1px solid #2563eb')
            .replace('color: #2d2d2d', 'color: #2563eb');
    }

    return button;
}

/**
 * Atalho para o botão padrão "encerrar tour".
 * Mantém compatibilidade com as chamadas existentes em todo o projeto,
 * mas também repassa onClick/onFinish/removeNav/style para addTourAction,
 * para que nenhuma opção passada aqui seja descartada silenciosamente.
 */
export function addTourFinishButton(popover, driverObj, {
    label = 'Encerrar tour',
    reopenOptions = true,
    onClick = null,
    onFinish = null,
    removeNav = true,
    style = true,
} = {}) {
    return addTourAction(popover, driverObj, {
        label,
        reopenOptions,
        onClick,
        onFinish,
        removeNav,
        style,
    });
}

/**
 * Considera um elemento "oculto" quando não existe ou quando está com
 * `display:none`/`visibility:hidden`. Depende do `x-show` do Alpine, que
 * alterna o `display` inline — por isso checamos o estilo inline primeiro.
 */
export function isElementHidden(element) {
    if (!element) return true;

    if (element.style?.display === 'none') return true;

    const view = element.ownerDocument?.defaultView;
    if (!view) return false;

    const style = view.getComputedStyle(element);
    return style.display === 'none' || style.visibility === 'hidden';
}

/**
 * Aguarda a pré-visualização do vídeo (`#temp-video-preview-<module>`) ganhar
 * um `src` (arquivo selecionado) e trata os dois casos de falha:
 *  - timeout (nada selecionado a tempo);
 *  - aviso de duração (`#message-warning-duration-<module>`) visível (>12min).
 *
 * Resolve com o elemento de preview; rejeita com Error em falha.
 */
export function waitForVideoPreview(moduleId, {
    timeout = 15000,
    previewSelector = `#temp-video-preview-${moduleId}`,
    warningSelector = `#message-warning-duration-${moduleId}`,
    root = globalThis.document?.body,
} = {}) {
    return new Promise((resolve, reject) => {
        let settled = false;
        let timer = null;

        const getPreview = () => {
            const el = globalThis.document?.querySelector(previewSelector);
            if (!el) return null;
            return el.getAttribute('src') || el.getAttribute('data-src') ? el : null;
        };

        const warningShown = () => {
            const el = globalThis.document?.querySelector(warningSelector);
            return Boolean(el) && !isElementHidden(el);
        };

        const cleanup = () => {
            observer.disconnect();
            clearTimeout(timer);
        };

        const settle = (error, value) => {
            if (settled) return;
            settled = true;
            cleanup();
            if (error) reject(error);
            else resolve(value);
        };

        const check = () => {
            if (settled) return;
            if (warningShown()) {
                settle(new Error('O vídeo excede o limite de duração permitido.'));
                return;
            }
            const preview = getPreview();
            if (preview) settle(null, preview);
        };

        const observer = new MutationObserver(check);

        const existing = getPreview();
        if (existing) {
            settle(null, existing);
            return;
        }

        observer.observe(root ?? globalThis.document.body, {
            attributes: true,
            attributeFilter: ['src', 'style', 'class'],
            childList: true,
            subtree: true,
        });

        timer = setTimeout(() => {
            settle(new Error(`Pré-visualização do vídeo não apareceu após ${timeout}ms`));
        }, timeout);

        check();
    });
}

/**
 * Aguarda o evento Livewire `after-video-upload` do módulo informado, com
 * cleanup do listener e timeout real. Diferente do fluxo antigo, NÃO avança
 * o tour quando o upload não é confirmado — rejeita e deixa o chamador decidir.
 *
 * @returns {Promise<{module_id: string, lesson_id: string}>}
 */
export function waitForUploadComplete(moduleId, {
    timeout = 60000,
    livewire = globalThis.Livewire,
} = {}) {
    return new Promise((resolve, reject) => {
        if (!livewire || typeof livewire.on !== 'function') {
            reject(new Error('Livewire não está disponível para aguardar o upload.'));
            return;
        }

        let settled = false;
        let off = null;

        const cleanup = () => {
            clearTimeout(timer);
            if (typeof off === 'function') off();
        };

        const timer = setTimeout(() => {
            if (settled) return;
            settled = true;
            cleanup();
            reject(new Error(`Upload do vídeo não confirmado após ${timeout}ms`));
        }, timeout);

        off = livewire.on('after-video-upload', (event) => {
            if (String(event?.module_id) !== String(moduleId)) return;
            if (settled) return;
            settled = true;
            cleanup();
            resolve(event);
        });
    });
}

/**
 * Dispara `callback` quando o elemento ficar oculto (`display:none`), corrigindo
 * o observer antigo que usava `attributeFilter` sem `attributes: true` (e por
 * isso nunca disparava). Retorna uma função de cleanup.
 */
export function whenElementHidden(element, callback, { timeout = 60000 } = {}) {
    if (!element) return () => {};

    let done = false;
    let timer = null;

    const observer = new MutationObserver(() => {
        if (done) return;
        if (isElementHidden(element)) finish(true);
    });

    function finish(notify = false) {
        if (done) return;
        done = true;
        observer.disconnect();
        clearTimeout(timer);
        if (notify && callback) callback();
    }

    observer.observe(element, { attributes: true, attributeFilter: ['style', 'class'] });
    timer = setTimeout(() => finish(false), timeout);

    return () => finish(false);
}
