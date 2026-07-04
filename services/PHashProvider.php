<?php
/**
 * Cadê Meu Pet? - Hash perceptual local (dHash) via GD.
 *
 * Provedor de assinatura visual 100% grátis, sem dependência externa.
 * Não tem a precisão de um embedding de rede neural (CLIP/GPT-4V), mas
 * captura sinal real de cor/padrão/silhueta a custo zero — adequado para
 * o MVP. Trocável no futuro por um provedor pago via ImagemMatchService,
 * sem alterar o motor de matching (MatchEngine).
 *
 * Algoritmo: difference hash (dHash) 8x8 — reduz a imagem a 9x8 pixels em
 * escala de cinza e compara cada pixel com o vizinho à direita, gerando
 * 64 bits (16 caracteres hex). Robusto a pequenas variações de brilho/escala.
 */
class PHashProvider
{
    private const TAMANHO_X = 9;
    private const TAMANHO_Y = 8;

    /**
     * Gera o hash perceptual (hex de 16 caracteres) de um arquivo de imagem.
     * Retorna null se o arquivo não puder ser processado (arquivo corrompido,
     * formato não suportado, etc.) — falha deve ser tratada pelo chamador
     * sem travar o restante do processamento em lote.
     */
    public function gerarHash(string $caminhoArquivo): ?string
    {
        if (!is_file($caminhoArquivo) || !is_readable($caminhoArquivo)) {
            return null;
        }

        $info = @getimagesize($caminhoArquivo);
        if ($info === false) {
            return null;
        }

        $imagem = $this->carregarImagem($caminhoArquivo, $info[2]);
        if ($imagem === null) {
            return null;
        }

        $reduzida = imagecreatetruecolor(self::TAMANHO_X, self::TAMANHO_Y);
        if ($reduzida === false) {
            imagedestroy($imagem);
            return null;
        }
        imagecopyresampled(
            $reduzida, $imagem,
            0, 0, 0, 0,
            self::TAMANHO_X, self::TAMANHO_Y,
            imagesx($imagem), imagesy($imagem)
        );
        imagedestroy($imagem);

        $cinza = [];
        for ($y = 0; $y < self::TAMANHO_Y; $y++) {
            for ($x = 0; $x < self::TAMANHO_X; $x++) {
                $rgb = imagecolorat($reduzida, $x, $y);
                $r = ($rgb >> 16) & 0xFF;
                $g = ($rgb >> 8) & 0xFF;
                $b = $rgb & 0xFF;
                $cinza[$y][$x] = (int)round(0.299 * $r + 0.587 * $g + 0.114 * $b);
            }
        }
        imagedestroy($reduzida);

        $bits = '';
        for ($y = 0; $y < self::TAMANHO_Y; $y++) {
            for ($x = 0; $x < self::TAMANHO_X - 1; $x++) {
                $bits .= ($cinza[$y][$x] > $cinza[$y][$x + 1]) ? '1' : '0';
            }
        }

        // 64 bits -> 16 caracteres hexadecimais
        $hex = '';
        foreach (str_split($bits, 4) as $nibble) {
            $hex .= dechex(bindec(str_pad($nibble, 4, '0')));
        }
        return $hex;
    }

    /**
     * Distância de Hamming normalizada entre dois hashes (0 = idêntico, 1 = totalmente diferente).
     * Retorna null se algum hash for inválido/ausente.
     */
    public static function distanciaNormalizada(?string $hashA, ?string $hashB): ?float
    {
        if (!$hashA || !$hashB || strlen($hashA) !== strlen($hashB)) {
            return null;
        }

        $binA = self::hexParaBinario($hashA);
        $binB = self::hexParaBinario($hashB);
        if ($binA === null || $binB === null || strlen($binA) !== strlen($binB)) {
            return null;
        }

        $diferentes = 0;
        for ($i = 0, $len = strlen($binA); $i < $len; $i++) {
            if ($binA[$i] !== $binB[$i]) {
                $diferentes++;
            }
        }

        return $diferentes / strlen($binA);
    }

    private static function hexParaBinario(string $hex): ?string
    {
        $bin = '';
        foreach (str_split($hex) as $char) {
            if (!ctype_xdigit($char)) {
                return null;
            }
            $bin .= str_pad(decbin(hexdec($char)), 4, '0', STR_PAD_LEFT);
        }
        return $bin;
    }

    /** @return resource|\GdImage|null */
    private function carregarImagem(string $caminho, int $tipo)
    {
        try {
            switch ($tipo) {
                case IMAGETYPE_JPEG:
                    return @imagecreatefromjpeg($caminho) ?: null;
                case IMAGETYPE_PNG:
                    return @imagecreatefrompng($caminho) ?: null;
                case IMAGETYPE_WEBP:
                    return function_exists('imagecreatefromwebp') ? (@imagecreatefromwebp($caminho) ?: null) : null;
                case IMAGETYPE_GIF:
                    return @imagecreatefromgif($caminho) ?: null;
                default:
                    return null;
            }
        } catch (Throwable $e) {
            return null;
        }
    }
}
