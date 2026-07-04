<?php
/**
 * Cadê Meu Pet? - Camada de abstração para assinatura visual de fotos.
 *
 * Hoje usa apenas o provedor grátis (PHashProvider, hash perceptual via GD).
 * Quando houver orçamento, um provedor pago (ex.: embedding CLIP via
 * Replicate, ou descrição estruturada via Claude/GPT-4V) pode ser plugado
 * aqui sem alterar o MatchEngine — a interface de saída (score de
 * similaridade 0-100) permanece a mesma.
 */
class ImagemMatchService
{
    private PHashProvider $provider;

    public function __construct(?PHashProvider $provider = null)
    {
        $this->provider = $provider ?: new PHashProvider();
    }

    /**
     * Gera a assinatura visual de uma foto já salva em disco.
     * Retorna ['provedor' => string, 'hash_perceptual' => ?string, 'vetor' => null]
     * ou null se a imagem não pôde ser processada.
     */
    public function gerarAssinaturaVisual(string $caminhoArquivo): ?array
    {
        $hash = $this->provider->gerarHash($caminhoArquivo);
        if ($hash === null) {
            return null;
        }

        return [
            'provedor'        => 'phash_local',
            'hash_perceptual' => $hash,
            'vetor'           => null,
        ];
    }

    /**
     * Score de similaridade visual (0-100) entre dois hashes perceptuais.
     * Sinal mais fraco que um embedding pago — por isso deve pesar menos
     * que geo+tempo+atributos no score combinado do MatchEngine.
     */
    public function scoreSimilaridade(?string $hashA, ?string $hashB): ?float
    {
        $distancia = PHashProvider::distanciaNormalizada($hashA, $hashB);
        if ($distancia === null) {
            return null;
        }
        return round((1 - $distancia) * 100, 2);
    }
}
