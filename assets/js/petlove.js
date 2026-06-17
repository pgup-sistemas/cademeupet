/**
 * Pet Love — interações de interface
 */

document.addEventListener('DOMContentLoaded', function () {

    // Auto-submit dos filtros da vitrine ao mudar select
    const formFiltros = document.getElementById('formFiltros');
    if (formFiltros) {
        formFiltros.querySelectorAll('select').forEach(sel => {
            sel.addEventListener('change', () => formFiltros.submit());
        });
    }

    // Barra de compatibilidade: animar entrada
    document.querySelectorAll('.petlove-compatibility-fill').forEach(bar => {
        const target = bar.style.width;
        bar.style.width = '0';
        requestAnimationFrame(() => {
            setTimeout(() => { bar.style.width = target; }, 100);
        });
    });

    // Pré-visualização de fotos no formulário de cadastro
    const inputFotos = document.getElementById('inputFotos');
    if (inputFotos) {
        inputFotos.addEventListener('change', function () {
            const preview = document.getElementById('previewFotos');
            if (!preview) return;
            preview.innerHTML = '';
            Array.from(this.files).slice(0, 5).forEach(file => {
                if (!file.type.startsWith('image/')) return;
                const reader = new FileReader();
                reader.onload = e => {
                    const wrap = document.createElement('div');
                    wrap.style.cssText = 'position:relative;display:inline-block;';
                    const img = document.createElement('img');
                    img.src = e.target.result;
                    img.style.cssText = 'width:80px;height:80px;object-fit:cover;border-radius:.5rem;border:2px solid #dee2e6;';
                    wrap.appendChild(img);
                    preview.appendChild(wrap);
                };
                reader.readAsDataURL(file);
            });
        });
    }

    // Feedback visual ao enviar interesse
    const formInteresse = document.querySelector('#modalInteresse form');
    if (formInteresse) {
        formInteresse.addEventListener('submit', function () {
            const btn = this.querySelector('[type=submit]');
            if (btn) {
                btn.disabled = true;
                btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i>Enviando...';
            }
        });
    }

});
