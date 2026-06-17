</main> <!-- Fecha main-content do header -->
    
    <!-- Footer -->
    <footer class="footer bg-dark text-white mt-5">
        <div class="container py-5">
            <div class="row">
                <!-- Sobre -->
                <div class="col-lg-4 col-md-6 mb-4">
                    <h5 class="mb-3">
                        <i class="fa-solid fa-paw logo-icon"></i> Cadê Meu Pet?
                    </h5>
                    <p class="text-light-gray">
                        Ajudamos a reunir pets perdidos com suas famílias desde 2025.
                        Cada contribuição mantém a plataforma gratuita e disponível para todos.
                    </p>
                    <div class="social-links mt-3">
                        <a href="#" class="text-white me-3"><i class="bi bi-facebook"></i></a>
                        <a href="#" class="text-white me-3"><i class="bi bi-instagram"></i></a>
                        <a href="#" class="text-white me-3"><i class="bi bi-twitter"></i></a>
                        <a href="#" class="text-white"><i class="bi bi-whatsapp"></i></a>
                    </div>
                </div>
                
                <!-- Links Rápidos -->
                <div class="col-lg-2 col-md-6 mb-4">
                    <h6 class="mb-3">Links Rápidos</h6>
                    <ul class="list-unstyled">
                        <li class="mb-2">
                            <a href="<?php echo BASE_URL; ?>/" class="text-light-gray text-decoration-none">
                                Início
                            </a>
                        </li>
                        <li class="mb-2">
                            <a href="<?php echo BASE_URL; ?>/busca" class="text-light-gray text-decoration-none">
                                Buscar Pets
                            </a>
                        </li>
                        <li class="mb-2">
                            <a href="<?php echo BASE_URL; ?>/novo-anuncio" class="text-light-gray text-decoration-none">
                                Publicar Anúncio
                            </a>
                        </li>
                        <li class="mb-2">
                            <a href="<?php echo BASE_URL; ?>/doar" class="text-light-gray text-decoration-none">
                                Doar
                            </a>
                        </li>
                    </ul>
                </div>
                
                <!-- Links Legais -->
                <div class="col-lg-3 col-md-6 mb-4">
                    <h6 class="mb-3">Informações Legais</h6>
                    <ul class="list-unstyled">
                        <li class="mb-2">
                            <a href="<?php echo BASE_URL; ?>/politica-privacidade" class="text-light-gray text-decoration-none">
                                Política de Privacidade
                            </a>
                        </li>
                        <li class="mb-2">
                            <a href="<?php echo BASE_URL; ?>/termos-uso" class="text-light-gray text-decoration-none">
                                Termos de Uso
                            </a>
                        </li>
                        <li class="mb-2">
                            <a href="<?php echo BASE_URL; ?>/politica-cookies" class="text-light-gray text-decoration-none">
                                Política de Cookies
                            </a>
                        </li>
                        <li class="mb-2">
                            <a href="<?php echo BASE_URL; ?>/lgpd" class="text-light-gray text-decoration-none">
                                LGPD - Lei de Proteção de Dados
                            </a>
                        </li>
                        <li class="mb-2">
                            <a href="<?php echo BASE_URL; ?>/contato-dpo" class="text-light-gray text-decoration-none">
                                Contato do DPO
                            </a>
                        </li>
                    </ul>
                </div>

                <!-- Precisa de Ajuda? -->
                <div class="col-lg-3 col-md-6 mb-4">
                    <h6 class="mb-3">Precisa de Ajuda?</h6>
                    <ul class="list-unstyled text-light-gray">
                        <li class="mb-2"><i class="bi bi-envelope me-2"></i> suporte@cademeupet.com.br</li>
                        <li class="mb-2"><i class="bi bi-whatsapp me-2"></i> (69) 99388-2222</li>
                        <li class="mb-2"><i class="bi bi-clock me-2"></i> Seg - Sex, 9h às 18h</li>
                    </ul>
                </div>

                <!-- Transparência -->
                <div class="col-lg-3 col-md-6">
                    <h6 class="mb-3">Transparência</h6>
                    <p class="text-light-gray mb-3">
                        Acompanhe nossas metas financeiras e veja como sua doação ajuda a manter o Cadê Meu Pet? vivo.
                    </p>
                    <a href="<?php echo BASE_URL; ?>/transparencia.php" class="btn btn-outline-light btn-sm">
                        Ver relatório financeiro
                    </a>
                </div>
            </div>
        </div>
        <div class="bg-black text-center py-3">
            <small class="text-light">&copy; <?php echo date('Y'); ?> Cadê Meu Pet?. Todos os direitos reservados.</small>
            <div class="mt-2">
                <small class="text-light-gray">
                    Feito com <i class="bi bi-heart-fill text-danger"></i> por PageUp Sistemas
                    <a href="https://wa.me/5569993882222" target="_blank" class="text-light ms-2" title="Fale conosco no WhatsApp">
                        <i class="bi bi-whatsapp"></i>
                    </a>
                </small>
            </div>
        </div>
    </footer>

    <!-- Modal Inteligente de Doação -->
    <div class="modal fade" id="donationModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header border-0 bg-gradient" style="background: linear-gradient(135deg, #0ba360 0%, #3cba92 100%);">
                    <h5 class="modal-title text-white fw-bold">
                        <?php echo sanitize(DONATION_MODAL_TITLE); ?>
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4 p-lg-5">
                    <div class="row g-4 align-items-center">
                        <div class="col-lg-5 text-center text-lg-start">
                            <img src="<?php echo ASSETS_URL; ?>/img/donation-heart.svg" alt="Doação Cadê Meu Pet?" class="img-fluid mb-3" style="max-height: 160px;">
                            <p class="text-muted mb-0">
                                <?php echo sanitize(DONATION_MODAL_TEXT); ?>
                            </p>
                        </div>
                        <div class="col-lg-7">
                            <div class="card border-0 bg-light p-3 p-lg-4 shadow-sm">
                                <h6 class="text-uppercase text-muted fw-bold mb-3">Escolha um valor rápido</h6>
                                <div class="d-flex flex-wrap gap-2 mb-3">
                                    <?php $sugestoes = [10, 20, 50]; ?>
                                    <?php foreach ($sugestoes as $valor): ?>
                                        <a href="<?php echo BASE_URL . '/doar.php?valor=' . $valor; ?>" class="btn btn-outline-success flex-fill">
                                            R$ <?php echo number_format($valor, 2, ',', '.'); ?>
                                        </a>
                                    <?php endforeach; ?>
                                    <a href="<?php echo BASE_URL; ?>/doar.php" class="btn btn-outline-success flex-fill">
                                        Outro valor
                                    </a>
                                </div>
                                <div class="alert alert-success d-flex align-items-center gap-2 mb-0" role="alert">
                                    <i class="bi bi-shield-check fs-4 text-success"></i>
                                    <div>
                                        Pagamento 100% seguro via nossos parceiros e ajuda a manter a plataforma gratuita.
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 justify-content-between flex-column flex-lg-row gap-2">
                    <div class="d-flex gap-2 order-2 order-lg-1">
                        <button type="button" class="btn btn-outline-secondary" data-action="maybe-later">
                            Talvez depois
                        </button>
                        <button type="button" class="btn btn-outline-danger" data-action="never-show">
                            Não mostrar novamente
                        </button>
                    </div>
                    <a href="<?php echo BASE_URL; ?>/doar.php" class="btn btn-success btn-lg order-1 order-lg-2" data-action="donate-now">
                        <i class="bi bi-heart-fill me-2"></i> Doar agora
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Banner de Consentimento de Cookies (LGPD) -->
    <div id="cookie-banner"
         class="position-fixed bottom-0 start-0 end-0 bg-dark text-white shadow-lg"
         style="z-index:1055;display:none;">
        <div class="container py-3">
            <div class="row align-items-center g-3">
                <div class="col-lg-8">
                    <p class="mb-0 small">
                        <i class="fa-solid fa-cookie-bite text-warning me-2"></i>
                        Utilizamos cookies essenciais para o funcionamento do site e, com seu consentimento,
                        cookies de preferência para melhorar sua experiência.
                        Leia nossa <a href="<?php echo BASE_URL; ?>/politica-cookies" class="text-warning">Política de Cookies</a>
                        e nossa <a href="<?php echo BASE_URL; ?>/politica-privacidade" class="text-warning">Política de Privacidade</a>.
                    </p>
                </div>
                <div class="col-lg-4 d-flex gap-2 justify-content-lg-end">
                    <button id="btn-cookie-recusar" class="btn btn-outline-light btn-sm">
                        Somente essenciais
                    </button>
                    <button id="btn-cookie-aceitar" class="btn btn-warning btn-sm fw-semibold text-dark">
                        <i class="fa-solid fa-check me-1"></i>Aceitar todos
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?php echo ASSETS_URL; ?>/js/main.js"></script>
    <?php if (!empty($includeMapAssets)): ?>
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
        <script src="<?php echo ASSETS_URL; ?>/js/map.js"></script>
    <?php endif; ?>
    <script>
    (function () {
        var banner = document.getElementById('cookie-banner');
        if (!banner) return;
        if (!localStorage.getItem('cookie_consent')) {
            banner.style.display = 'block';
        }
        document.getElementById('btn-cookie-aceitar').addEventListener('click', function () {
            localStorage.setItem('cookie_consent', 'all');
            banner.style.display = 'none';
        });
        document.getElementById('btn-cookie-recusar').addEventListener('click', function () {
            localStorage.setItem('cookie_consent', 'essential');
            banner.style.display = 'none';
        });
    })();
    </script>
</body>
</html>