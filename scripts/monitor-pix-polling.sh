#!/bin/bash
###############################################################################
# PetFinder - Script de Monitoramento de Polling PIX
# 
# Monitora webhooks e polling em tempo real
# Uso: bash monitor-pix-polling.sh
###############################################################################

# Cores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuração
PETFINDER_PATH="${1:-.}"
LOG_FILE="$PETFINDER_PATH/includes/petfinder_error_log"
WEBHOOK_FILE="$PETFINDER_PATH/api/efi-webhook.php"
API_FILE="$PETFINDER_PATH/api/status-doacao.php"
VIEW_FILE="$PETFINDER_PATH/views/doacao-pix.php"

echo -e "${BLUE}╔════════════════════════════════════════════════════════════════╗${NC}"
echo -e "${BLUE}║  PetFinder - Monitor de Polling PIX                           ║${NC}"
echo -e "${BLUE}╚════════════════════════════════════════════════════════════════╝${NC}"
echo ""

# ═══════════════════════════════════════════════════════════════════════════
# Função: Verificar Arquivo
# ═══════════════════════════════════════════════════════════════════════════

check_file() {
    local file=$1
    local name=$2
    
    if [ -f "$file" ]; then
        local size=$(stat -f%z "$file" 2>/dev/null || stat -c%s "$file" 2>/dev/null)
        echo -e "${GREEN}✓${NC} $name existe (${size} bytes)"
        return 0
    else
        echo -e "${RED}✗${NC} $name NÃO encontrado: $file"
        return 1
    fi
}

# ═══════════════════════════════════════════════════════════════════════════
# Função: Buscar Padrão em Arquivo
# ═══════════════════════════════════════════════════════════════════════════

check_pattern() {
    local file=$1
    local pattern=$2
    local name=$3
    
    if grep -q "$pattern" "$file" 2>/dev/null; then
        echo -e "${GREEN}✓${NC} $name encontrado"
        return 0
    else
        echo -e "${RED}✗${NC} $name NÃO encontrado"
        return 1
    fi
}

# ═══════════════════════════════════════════════════════════════════════════
# 1. Verificação de Arquivos
# ═══════════════════════════════════════════════════════════════════════════

echo -e "${YELLOW}1️⃣  VERIFICAÇÃO DE ARQUIVOS${NC}"
echo "────────────────────────────────────────────────────────────────"

check_file "$WEBHOOK_FILE" "Webhook EFI"
check_file "$API_FILE" "API Status"
check_file "$VIEW_FILE" "Página PIX"

echo ""

# ═══════════════════════════════════════════════════════════════════════════
# 2. Verificação de Componentes
# ═══════════════════════════════════════════════════════════════════════════

echo -e "${YELLOW}2️⃣  VERIFICAÇÃO DE COMPONENTES${NC}"
echo "────────────────────────────────────────────────────────────────"

echo "Webhook:"
check_pattern "$WEBHOOK_FILE" "sincronizarStatusDoacaoPix" "  - Chamada a sincronizarStatusDoacaoPix"
check_pattern "$WEBHOOK_FILE" "X-EFI-Webhook-Token" "  - Validação de Token"

echo ""
echo "API Status:"
check_pattern "$API_FILE" "status-doacao.php" "  - Arquivo correto"
check_pattern "$API_FILE" "sincronizarStatusDoacaoPix" "  - Sincronização com EFI"
check_pattern "$API_FILE" "transaction_id" "  - Validação de TXID"

echo ""
echo "Página PIX:"
check_pattern "$VIEW_FILE" "iniciarPolling" "  - Função iniciarPolling()"
check_pattern "$VIEW_FILE" "atualizarStatusPix" "  - Função atualizarStatusPix()"
check_pattern "$VIEW_FILE" "btnAtualizarStatus" "  - Botão de atualização"
check_pattern "$VIEW_FILE" "/api/status-doacao.php" "  - Chamada a /api/status-doacao.php"

echo ""

# ═══════════════════════════════════════════════════════════════════════════
# 3. Monitoramento de Logs
# ═══════════════════════════════════════════════════════════════════════════

echo -e "${YELLOW}3️⃣  LOGS RECENTES${NC}"
echo "────────────────────────────────────────────────────────────────"

if [ -f "$LOG_FILE" ]; then
    echo "Últimas ocorrências de 'status-doacao' nos logs:"
    grep "status-doacao" "$LOG_FILE" 2>/dev/null | tail -10 || echo "  (nenhuma encontrada)"
    
    echo ""
    echo "Últimas ocorrências de 'efi-webhook' nos logs:"
    grep "efi-webhook" "$LOG_FILE" 2>/dev/null | tail -10 || echo "  (nenhuma encontrada)"
    
    echo ""
    echo "Últimas ocorrências de 'sincronizarStatusDoacaoPix' nos logs:"
    grep "sincronizarStatusDoacaoPix" "$LOG_FILE" 2>/dev/null | tail -10 || echo "  (nenhuma encontrada)"
else
    echo -e "${RED}✗${NC} Log file not found: $LOG_FILE"
fi

echo ""

# ═══════════════════════════════════════════════════════════════════════════
# 4. Instruções de Teste
# ═══════════════════════════════════════════════════════════════════════════

echo -e "${YELLOW}4️⃣  PRÓXIMOS PASSOS${NC}"
echo "────────────────────────────────────────────────────────────────"

echo ""
echo "📋 Teste via Script:"
echo "   http://seu-site.com/test-pix-polling.php"

echo ""
echo "🔧 Teste manual via cURL:"
echo "   curl -X POST https://seu-site.com/api/status-doacao.php \\"
echo "     -H 'Content-Type: application/json' \\"
echo "     -d '{\"id\": 32, \"txid\": \"sua-txid-aqui\"}'"

echo ""
echo "🌐 Teste no navegador:"
echo "   1. Abra: https://seu-site.com/doacao-pix?id=32"
echo "   2. Pressione F12 para abrir DevTools"
echo "   3. Vá à aba 'Console'"
echo "   4. Procure por mensagens: [PIX Polling]"

echo ""
echo "📊 Monitor de logs em tempo real:"
echo "   tail -f $LOG_FILE | grep -E 'status-doacao|efi-webhook'"

echo ""

# ═══════════════════════════════════════════════════════════════════════════
# 5. Resumo
# ═══════════════════════════════════════════════════════════════════════════

echo -e "${BLUE}╔════════════════════════════════════════════════════════════════╗${NC}"
echo -e "${BLUE}║  Verificação Concluída                                         ║${NC}"
echo -e "${BLUE}╚════════════════════════════════════════════════════════════════╝${NC}"

echo ""
echo "ℹ️  Mais informações:"
echo "   cat $PETFINDER_PATH/SOLUCAO_PIX_POLLING.md"

echo ""
echo "✅ Se todos os itens estão verdes, o sistema está pronto!"
echo ""
