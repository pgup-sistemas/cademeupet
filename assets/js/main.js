/**
 * Cadê Meu Pet? - JavaScript Principal
 * Funcionalidades globais do sistema
 */

// ═══════════════════════════════════════════════
// INICIALIZAÇÃO
// ═══════════════════════════════════════════════
document.addEventListener('DOMContentLoaded', function() {
    console.log('🐾 Cadê Meu Pet? carregado!');
    
    // Inicializa componentes
    initTooltips();
    initSmoothScroll();
    initBackToTop();
    initMobileMenu();
    initAlerts();
    initDonationModal();
});

// ═══════════════════════════════════════════════
// TOOLTIPS BOOTSTRAP
// ═══════════════════════════════════════════════
function initTooltips() {
    const tooltipTriggerList = [].slice.call(
        document.querySelectorAll('[data-bs-toggle="tooltip"]')
    );
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
}

// ═══════════════════════════════════════════════
// SMOOTH SCROLL
// ═══════════════════════════════════════════════
function initSmoothScroll() {
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            const href = this.getAttribute('href');
            if (href !== '#' && document.querySelector(href)) {
                e.preventDefault();
                document.querySelector(href).scrollIntoView({
                    behavior: 'smooth'
                });
            }
        });
    });
}

// ═══════════════════════════════════════════════
// BOTÃO VOLTAR AO TOPO
// ═══════════════════════════════════════════════
function initBackToTop() {
    const backToTop = document.getElementById('backToTop');
    
    if (!backToTop) return;
    
    window.addEventListener('scroll', function() {
        if (window.pageYOffset > 300) {
            backToTop.classList.add('show');
        } else {
            backToTop.classList.remove('show');
        }
    });
    
    backToTop.addEventListener('click', function() {
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    });
}

// ═══════════════════════════════════════════════
// MENU MOBILE
// ═══════════════════════════════════════════════
function initMobileMenu() {
    const navbarToggler = document.querySelector('.navbar-toggler');
    const navbarCollapse = document.querySelector('.navbar-collapse');
    
    if (!navbarToggler || !navbarCollapse) return;
    
    // Fecha menu ao clicar em link
    navbarCollapse.querySelectorAll('a').forEach(link => {
        link.addEventListener('click', function() {
            if (window.innerWidth < 992) {
                navbarToggler.click();
            }
        });
    });
}

// ═══════════════════════════════════════════════
// AUTO-DISMISS ALERTS
// ═══════════════════════════════════════════════
function initAlerts() {
    const alerts = document.querySelectorAll('.alert:not(.alert-permanent)');
    
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.classList.add('fade');
            setTimeout(() => alert.remove(), 500);
        }, 5000);
    });
}

// ═══════════════════════════════════════════════
// MÁSCARAS DE INPUT
// ═══════════════════════════════════════════════

// Máscara de Telefone
function maskPhone(value) {
    value = value.replace(/\D/g, '');
    
    if (value.length <= 10) {
        value = value.replace(/(\d{2})(\d{4})(\d{4})/, '($1) $2-$3');
    } else {
        value = value.replace(/(\d{2})(\d{5})(\d{4})/, '($1) $2-$3');
    }
    
    return value;
}

// Máscara de CEP
function maskCEP(value) {
    value = value.replace(/\D/g, '');
    value = value.replace(/(\d{5})(\d{3})/, '$1-$2');
    return value;
}

// Máscara de CPF
function maskCPF(value) {
    value = value.replace(/\D/g, '');
    value = value.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, '$1.$2.$3-$4');
    return value;
}

// Aplica máscaras automaticamente
document.addEventListener('input', function(e) {
    if (e.target.matches('[data-mask="phone"]')) {
        e.target.value = maskPhone(e.target.value);
    }
    if (e.target.matches('[data-mask="cep"]')) {
        e.target.value = maskCEP(e.target.value);
    }
    if (e.target.matches('[data-mask="cpf"]')) {
        e.target.value = maskCPF(e.target.value);
    }
});

// ═══════════════════════════════════════════════
// BUSCA VIA CEP
// ═══════════════════════════════════════════════
async function buscarCEP(cep) {
    cep = (cep || '').toString().replace(/\D/g, '');
    
    if (cep.length !== 8) {
        showToast('CEP inválido', 'error');
        return null;
    }
    
    try {
        const response = await fetch(`https://viacep.com.br/ws/${cep}/json/`);
        const data = await response.json();
        
        if (data.erro) {
            showToast('CEP não encontrado', 'error');
            return null;
        }
        
        return data;
    } catch (error) {
        showToast('Erro ao buscar CEP', 'error');
        return null;
    }
}

// Auto-preenchimento de endereço
document.addEventListener('blur', async function(e) {
    if (e.target.matches('[name="cep"]')) {
        const cep = e.target.value;
        const data = await buscarCEP(cep);
        
        if (data) {
            const form = e.target.closest('form');
            if (form.querySelector('[name="endereco"]')) {
                form.querySelector('[name="endereco"]').value = data.logradouro || '';
            }
            if (form.querySelector('[name="bairro"]')) {
                form.querySelector('[name="bairro"]').value = data.bairro || '';
            }
            if (form.querySelector('[name="cidade"]')) {
                form.querySelector('[name="cidade"]').value = data.localidade || '';
            }
            if (form.querySelector('[name="estado"]')) {
                form.querySelector('[name="estado"]').value = data.uf || '';
            }
        }
    }
}, true);

// ═══════════════════════════════════════════════
// GEOLOCALIZAÇÃO
// ═══════════════════════════════════════════════
function getCurrentLocation() {
    return new Promise((resolve, reject) => {
        if (!navigator.geolocation) {
            reject('Geolocalização não suportada');
            return;
        }
        
        navigator.geolocation.getCurrentPosition(
            position => {
                resolve({
                    lat: position.coords.latitude,
                    lng: position.coords.longitude
                });
            },
            error => {
                reject('Erro ao obter localização');
            }
        );
    });
}

// Geocoding Reverso (coordenadas para endereço)
async function reverseGeocode(lat, lng) {
    // Implementar com Google Maps Geocoding API
    console.log('Reverse geocoding:', lat, lng);
    showToast('Funcionalidade de GPS em desenvolvimento', 'info');
}

// ═══════════════════════════════════════════════
// TOAST NOTIFICATIONS
// ═══════════════════════════════════════════════
function showToast(message, type = 'info') {
    const toastContainer = getToastContainer();
    
    const colors = {
        success: '#4CAF50',
        error: '#f44336',
        warning: '#ff9800',
        info: '#2196F3'
    };
    
    const icons = {
        success: '✓',
        error: '✗',
        warning: '⚠',
        info: 'ℹ'
    };
    
    const toast = document.createElement('div');
    toast.className = 'custom-toast';
    toast.style.cssText = `
        background: ${colors[type] || colors.info};
        color: white;
        padding: 15px 20px;
        border-radius: 8px;
        margin-bottom: 10px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        display: flex;
        align-items: center;
        gap: 10px;
        animation: slideIn 0.3s ease;
    `;
    
    toast.innerHTML = `
        <span style="font-size: 1.5em;">${icons[type] || icons.info}</span>
        <span>${message}</span>
    `;
    
    toastContainer.appendChild(toast);
    
    setTimeout(() => {
        toast.style.animation = 'slideOut 0.3s ease';
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

function getToastContainer() {
    let container = document.getElementById('toast-container');
    
    if (!container) {
        container = document.createElement('div');
        container.id = 'toast-container';
        container.style.cssText = `
            position: fixed;
            top: 80px;
            right: 20px;
            z-index: 9999;
            max-width: 350px;
        `;
        document.body.appendChild(container);
    }
    
    return container;
}

// Animações para toast
const style = document.createElement('style');
style.textContent = `
    @keyframes slideIn {
        from {
            transform: translateX(400px);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    
    @keyframes slideOut {
        from {
            transform: translateX(0);
            opacity: 1;
        }
        to {
            transform: translateX(400px);
            opacity: 0;
        }
    }
`;
document.head.appendChild(style);

// ═══════════════════════════════════════════════
// CONFIRMAÇÃO DE AÇÕES
// ═══════════════════════════════════════════════
function confirm(message, callback) {
    if (window.confirm(message)) {
        callback();
    }
}

// Confirmação em links/botões de exclusão
document.addEventListener('click', function(e) {
    if (e.target.matches('[data-confirm]')) {
        e.preventDefault();
        const message = e.target.getAttribute('data-confirm');
        
        if (window.confirm(message)) {
            // Se for link, redireciona
            if (e.target.tagName === 'A') {
                window.location.href = e.target.href;
            }
            // Se for form, submete
            else if (e.target.closest('form')) {
                e.target.closest('form').submit();
            }
        }
    }
});

// ═══════════════════════════════════════════════
// AJAX HELPERS
// ═══════════════════════════════════════════════
async function fetchJSON(url, options = {}) {
    try {
        const response = await fetch(url, {
            ...options,
            headers: {
                'Content-Type': 'application/json',
                ...options.headers
            }
        });
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        return await response.json();
    } catch (error) {
        console.error('Fetch error:', error);
        showToast('Erro na comunicação com o servidor', 'error');
        throw error;
    }
}

// POST Helper
async function postJSON(url, data) {
    return fetchJSON(url, {
        method: 'POST',
        body: JSON.stringify(data)
    });
}

// ═══════════════════════════════════════════════
// FAVORITOS
// ═══════════════════════════════════════════════
async function toggleFavorito(anuncioId, button) {
    try {
        const response = await postJSON('/api/favoritos.php', {
            anuncio_id: anuncioId
        });
        
        if (response.success) {
            const icon = button.querySelector('i');
            if (response.favorited) {
                icon.classList.remove('bi-heart');
                icon.classList.add('bi-heart-fill');
                button.classList.add('text-danger');
                showToast('Adicionado aos favoritos!', 'success');
            } else {
                icon.classList.remove('bi-heart-fill');
                icon.classList.add('bi-heart');
                button.classList.remove('text-danger');
                showToast('Removido dos favoritos', 'info');
            }
        }
    } catch (error) {
        showToast('Erro ao favoritar', 'error');
    }
}

// ═══════════════════════════════════════════════
// LOADING OVERLAY
// ═══════════════════════════════════════════════
function showLoading(message = 'Carregando...') {
    let overlay = document.getElementById('loading-overlay');
    
    if (!overlay) {
        overlay = document.createElement('div');
        overlay.id = 'loading-overlay';
        overlay.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.7);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 99999;
        `;
        
        overlay.innerHTML = `
            <div style="text-align: center; color: white;">
                <div class="spinner-border" role="status" style="width: 3rem; height: 3rem;">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p style="margin-top: 20px; font-size: 1.2em;">${message}</p>
            </div>
        `;
        
        document.body.appendChild(overlay);
    }
    
    overlay.style.display = 'flex';
}

function hideLoading() {
    const overlay = document.getElementById('loading-overlay');
    if (overlay) {
        overlay.style.display = 'none';
    }
}

// ═══════════════════════════════════════════════
// VALIDAÇÃO DE FORMULÁRIOS
// ═══════════════════════════════════════════════
function validateForm(form) {
    const inputs = form.querySelectorAll('[required]');
    let isValid = true;
    
    inputs.forEach(input => {
        if (!input.value.trim()) {
            input.classList.add('is-invalid');
            isValid = false;
        } else {
            input.classList.remove('is-invalid');
        }
    });
    
    return isValid;
}

// Remove validação ao digitar
document.addEventListener('input', function(e) {
    if (e.target.classList.contains('is-invalid')) {
        if (e.target.value.trim()) {
            e.target.classList.remove('is-invalid');
        }
    }
});

// ═══════════════════════════════════════════════
// FORMATAÇÃO
// ═══════════════════════════════════════════════
function formatMoney(value) {
    return new Intl.NumberFormat('pt-BR', {
        style: 'currency',
        currency: 'BRL'
    }).format(value);
}

function formatDate(date) {
    return new Intl.DateTimeFormat('pt-BR').format(new Date(date));
}

function timeAgo(date) {
    const seconds = Math.floor((new Date() - new Date(date)) / 1000);
    
    const intervals = {
        ano: 31536000,
        mês: 2592000,
        dia: 86400,
        hora: 3600,
        minuto: 60
    };
    
    for (const [name, value] of Object.entries(intervals)) {
        const interval = Math.floor(seconds / value);
        if (interval >= 1) {
            return `${interval} ${name}${interval > 1 ? 's' : ''} atrás`;
        }
    }
    
    return 'agora mesmo';
}

// ═══════════════════════════════════════════════
// UTILITÁRIOS
// ═══════════════════════════════════════════════
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

function throttle(func, limit) {
    let inThrottle;
    return function() {
        const args = arguments;
        const context = this;
        if (!inThrottle) {
            func.apply(context, args);
            inThrottle = true;
            setTimeout(() => inThrottle = false, limit);
        }
    };
}

// ═══════════════════════════════════════════════
// MODAL DE DOAÇÃO INTELIGENTE
// ═══════════════════════════════════════════════
const donationStorageKey = 'pf_donation_modal';

function initDonationModal() {
    const modalEl = document.getElementById('donationModal');
    if (!modalEl) return;

    const saved = getDonationState();
    const now = Date.now();

    if (saved.neverShow) {
        return;
    }

    if (saved.lastDismissed && now - saved.lastDismissed < 7 * 24 * 60 * 60 * 1000) {
        return;
    }

    modalEl.querySelector('[data-action="maybe-later"]').addEventListener('click', () => {
        updateDonationState({ lastDismissed: Date.now() });
        bootstrap.Modal.getInstance(modalEl)?.hide();
    });

    modalEl.querySelector('[data-action="never-show"]').addEventListener('click', () => {
        updateDonationState({ neverShow: true });
        bootstrap.Modal.getInstance(modalEl)?.hide();
    });

    modalEl.querySelector('[data-action="donate-now"]').addEventListener('click', () => {
        updateDonationState({ donatedRecently: Date.now() });
    });
}

function showDonationModal(reason = 'default') {
    const modalEl = document.getElementById('donationModal');
    if (!modalEl) return;

    const saved = getDonationState();
    const now = Date.now();

    if (saved.neverShow) return;
    if (saved.donatedRecently && now - saved.donatedRecently < 30 * 24 * 60 * 60 * 1000) return;
    if (saved.lastShown && now - saved.lastShown < 24 * 60 * 60 * 1000) return;

    updateDonationState({ lastShown: now, lastReason: reason });

    const modal = new bootstrap.Modal(modalEl, {
        backdrop: 'static',
        keyboard: true
    });

    modal.show();
}

function getDonationState() {
    try {
        const data = localStorage.getItem(donationStorageKey);
        return data ? JSON.parse(data) : {};
    } catch (error) {
        console.warn('Falha ao ler estado do modal de doação', error);
        return {};
    }
}

function updateDonationState(partial) {
    try {
        const current = getDonationState();
        localStorage.setItem(donationStorageKey, JSON.stringify({ ...current, ...partial }));
    } catch (error) {
        console.warn('Falha ao salvar estado do modal de doação', error);
    }
}

// ═══════════════════════════════════════════════
// EXPORT GLOBAL
// ═══════════════════════════════════════════════
window.Cadê Meu Pet? = {
    showToast,
    showLoading,
    hideLoading,
    buscarCEP,
    getCurrentLocation,
    toggleFavorito,
    formatMoney,
    formatDate,
    timeAgo,
    debounce,
    throttle,
    showDonationModal
};

console.log('✅ Cadê Meu Pet? JS inicializado!');