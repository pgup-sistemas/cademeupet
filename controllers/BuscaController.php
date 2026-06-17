<?php

/**
 * Cadê Meu Pet? - Controller de Busca
 * Orquestra as buscas de anúncios aplicando filtros e preparando dados para as views.
 */
class BuscaController
{
    private $anuncioController;

    public function __construct()
    {
        $this->anuncioController = new AnuncioController();
    }

    public function listar(array $params)
    {
        $page = isset($params['page']) ? (int)$params['page'] : 1;
        $resultado = $this->anuncioController->search($params, $page);

        return [
            'anuncios' => $resultado['results'],
            'filters' => $resultado['filters'],
            'page' => $resultado['page'],
            'temResultados' => !empty($resultado['results'])
        ];
    }
}

