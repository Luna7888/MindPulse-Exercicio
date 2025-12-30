<?php
/**
 * ╔═══════════════════════════════════════════════════════════════════════════╗
 * ║ CHAMADOS.PHP — Gestão de Chamados/Feedbacks Internos (Visão Admin)       ║
 * ╠═══════════════════════════════════════════════════════════════════════════╣
 * ║                                                                           ║
 * ║ @objetivo      Permitir que gestores visualizem e gerenciem todos os     ║
 * ║                feedbacks enviados pelos colaboradores da empresa         ║
 * ║                                                                           ║
 * ║ @acesso        Admin Geral | Gestor (canAccessAdmin)                     ║
 * ║ @escopo        Por empresa (company_id)                                  ║
 * ║                                                                           ║
 * ║ @exibe         - Lista de chamados ordenados por status                  ║
 * ║                - Dados do colaborador (avatar, nome, data)               ║
 * ║                - Sentimento (emoji) e categoria                          ║
 * ║                - Mensagem do feedback                                    ║
 * ║                - Status atual com badge colorido                         ║
 * ║                - Select para alterar status inline                       ║
 * ║                                                                           ║
 * ║ @status        aberto → em_andamento → concluido                         ║
 * ║                                                                           ║
 * ║ @dependências  layout_start.php, feedback.php                            ║
 * ║                                                                           ║
 * ╚═══════════════════════════════════════════════════════════════════════════╝
 */

// ═══════════════════════════════════════════════════════════════════════════
// SEÇÃO: INCLUSÃO DE DEPENDÊNCIAS E VERIFICAÇÃO DE ACESSO
// ═══════════════════════════════════════════════════════════════════════════

require_once __DIR__ . '/../includes/layout_start.php';
require_once __DIR__ . '/../includes/feedback.php';

/**
 * Verifica permissão administrativa
 * Apenas Admin Geral e Gestores podem ver chamados
 */
if (!canAccessManager()) { 
    http_response_code(403); 
    echo '<div class="card" style="padding:20px">Acesso negado</div>'; 
    require_once __DIR__ . '/../includes/layout_end.php'; 
    exit; 
}

// ═══════════════════════════════════════════════════════════════════════════
// SEÇÃO: CARREGAMENTO DE DADOS
// ═══════════════════════════════════════════════════════════════════════════

/**
 * ID da empresa atual
 */
$companyId = currentCompanyId();

/**
 * Lista todos os chamados da empresa
 * Ordenados: aberto → em_andamento → concluido, depois por data
 */
$rows = fb_list_admin($pdo, $companyId);

/**
 * Mapa de categorias para exibição legível
 */
$cats = fb_categories();

/**
 * Mapa de sentimentos para exibição (emoji, título)
 * Transforma array em mapa indexado por key
 */
$sentMap = []; 
foreach (fb_sentiments() as $s) { 
    $sentMap[$s['key']] = $s; 
}
?>

<!-- ╔═══════════════════════════════════════════════════════════════════════╗
     ║ ESTILOS ESPECÍFICOS DA PÁGINA                                         ║
     ╚═══════════════════════════════════════════════════════════════════════╝ -->
<style>
/* ═══════════════════════════════════════════════════════════════════════════
   TABELA DE CHAMADOS
   ═══════════════════════════════════════════════════════════════════════════ */
.table{
    width: 100%; 
    border-collapse: separate; 
    border-spacing: 0 10px;
}

.table thead th{
    font-size: .9rem; 
    color: #9aa4b2; 
    text-align: left; 
    padding: 0 8px;
}

/* ═══════════════════════════════════════════════════════════════════════════
   LINHA DE CHAMADO (Grid responsivo)
   ═══════════════════════════════════════════════════════════════════════════ */
.row{
    display: grid; 
    grid-template-columns: 1.8fr .9fr .9fr 1.2fr .9fr 140px; 
    gap: 10px; 
    align-items: center;
    border: 1px solid var(--stroke); 
    border-radius: 12px; 
    background: rgba(255,255,255,.04); 
    padding: 10px;
}

/* ═══════════════════════════════════════════════════════════════════════════
   AVATAR DO COLABORADOR
   ═══════════════════════════════════════════════════════════════════════════ */
.avatar{
    width: 40px; 
    height: 40px; 
    border-radius: 12px; 
    object-fit: cover; 
    border: 1px solid var(--stroke);
}

/* ═══════════════════════════════════════════════════════════════════════════
   BADGE DE SENTIMENTO
   ═══════════════════════════════════════════════════════════════════════════ */
.badge.sent{
    border: 1px solid var(--stroke); 
    border-radius: 999px; 
    padding: 4px 10px; 
    display: inline-flex; 
    gap: 6px; 
    align-items: center;
}

/* ═══════════════════════════════════════════════════════════════════════════
   SELECT DE STATUS (com loading)
   ═══════════════════════════════════════════════════════════════════════════ */
.select{
    appearance: none; 
    background: transparent; 
    border: 1px solid var(--stroke); 
    padding: 8px 12px; 
    border-radius: 10px; 
    color: #e8edf7;
}

/* Estado de loading no select */
.select.is-loading{
    color: transparent; 
    position: relative;
}

.select.is-loading::after{
    content: ""; 
    position: absolute; 
    right: 10px; 
    top: 50%; 
    width: 14px; 
    height: 14px; 
    margin-top: -7px; 
    border-radius: 50%;
    border: 2px solid rgba(255,255,255,.35); 
    border-top-color: #fff; 
    animation: spin .8s linear infinite;
}

@keyframes spin{ to{ transform: rotate(360deg) } }

.small{ color: #9aa4b2 }
</style>

<!-- ╔═══════════════════════════════════════════════════════════════════════╗
     ║ CABEÇALHO DA PÁGINA                                                   ║
     ╚═══════════════════════════════════════════════════════════════════════╝ -->
<h2 style="margin:0 0 10px; font-weight:900">Chamados Internos</h2>
<div class="small" style="margin-bottom:8px">
    Abertos primeiro • atualize o status em linha
</div>

<!-- ╔═══════════════════════════════════════════════════════════════════════╗
     ║ LISTA DE CHAMADOS                                                     ║
     ║                                                                        ║
     ║ Cada linha contém:                                                    ║
     ║ 1. Avatar + nome + data                                               ║
     ║ 2. Sentimento (emoji)                                                 ║
     ║ 3. Categoria                                                          ║
     ║ 4. Mensagem                                                           ║
     ║ 5. Badge de status                                                    ║
     ║ 6. Select para alterar status                                         ║
     ╚═══════════════════════════════════════════════════════════════════════╝ -->

<?php if (empty($rows)): ?>
    <!-- Estado vazio: sem chamados -->
    <div class="card" style="padding:20px">
        Sem chamados no momento.
    </div>
<?php else: ?>
    <div style="display:flex; flex-direction:column; gap:10px">
        <?php foreach ($rows as $r):
            /**
             * Obtém dados do sentimento para exibição
             */
            $s = $sentMap[$r['sentiment_key']] ?? null;
            $emoji = $s['emoji'] ?? '🙂';
            $title = $s['title'] ?? ucfirst($r['sentiment_key']);
        ?>
            <!-- ────────────────────────────────────────────────────────────
                 LINHA DO CHAMADO
                 
                 data-id: ID do chamado (usado pelo JS para atualizar)
                 ──────────────────────────────────────────────────────────── -->
            <div class="row" data-id="<?= (int)$r['id'] ?>">
                
                <!-- Coluna 1: Avatar + Nome + Data -->
                <div style="display:flex; align-items:center; gap:10px">
                    <img 
                        class="avatar" 
                        src="<?= htmlspecialchars(($r['avatar_url'] ?: url_for('/assets/img/avatar.svg'))) ?>" 
                        alt=""
                    >
                    <div>
                        <div style="font-weight:800">
                            <?= htmlspecialchars($r['user_name']) ?>
                        </div>
                        <div class="small">
                            <?= date('d/m/Y H:i', strtotime($r['created_at'])) ?>
                        </div>
                    </div>
                </div>

                <!-- Coluna 2: Sentimento (emoji + título) -->
                <div>
                    <span class="badge sent">
                        <?= $emoji ?> 
                        <strong><?= htmlspecialchars($title) ?></strong>
                    </span>
                </div>

                <!-- Coluna 3: Categoria -->
                <div class="small">
                    <?= htmlspecialchars($cats[$r['category']] ?? $r['category']) ?>
                </div>

                <!-- Coluna 4: Mensagem -->
                <div style="white-space:pre-wrap">
                    <?= nl2br(htmlspecialchars($r['message'])) ?>
                </div>

                <!-- Coluna 5: Badge de status atual -->
                <div>
                    <?= fb_status_badge($r['status']) ?>
                </div>

                <!-- Coluna 6: Select para alterar status -->
                <div>
                    <select class="select statusSel">
                        <option value="aberto" <?= $r['status'] == 'aberto' ? 'selected' : '' ?>>
                            Aberto
                        </option>
                        <option value="em_andamento" <?= $r['status'] == 'em_andamento' ? 'selected' : '' ?>>
                            Em andamento
                        </option>
                        <option value="concluido" <?= $r['status'] == 'concluido' ? 'selected' : '' ?>>
                            Concluído
                        </option>
                    </select>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<!-- ╔═══════════════════════════════════════════════════════════════════════╗
     ║ JAVASCRIPT: Atualização de Status Inline                              ║
     ║                                                                        ║
     ║ Quando o usuário muda o select, envia AJAX para atualizar             ║
     ║ e recarrega a página para refletir a mudança                          ║
     ╚═══════════════════════════════════════════════════════════════════════╝ -->
<script>
/**
 * postJSON() — Requisição POST com JSON
 * 
 * @param {string} url - URL do endpoint
 * @param {object} payload - Dados a enviar
 * @returns {object} - Resposta parseada
 */
async function postJSON(url, payload) {
    const r = await fetch(url, {
        method: 'POST', 
        headers: {'Content-Type': 'application/json'}, 
        body: JSON.stringify(payload)
    });
    const t = await r.text(); 
    try { 
        return JSON.parse(t); 
    } catch(e) { 
        throw new Error(t); 
    }
}

/**
 * Adiciona listener em todos os selects de status
 * 
 * Quando o valor muda:
 * 1. Adiciona classe de loading
 * 2. Envia requisição para atualizar
 * 3. Recarrega a página se sucesso
 * 4. Mostra erro se falhar
 */
document.querySelectorAll('.statusSel').forEach(sel => {
    sel.addEventListener('change', async () => {
        // Obtém o ID do chamado do atributo data-id da linha
        const row = sel.closest('.row'); 
        const id = parseInt(row.dataset.id, 10);
        
        // Mostra loading
        sel.classList.add('is-loading'); 
        sel.disabled = true;
        
        try {
            // Envia requisição de atualização
            const res = await postJSON('<?= url_for("/pages/chamados_update.php") ?>', {
                id, 
                status: sel.value
            });
            
            if (res.status === 'ok') { 
                // Sucesso: recarrega para atualizar a ordem e badges
                location.reload(); 
            } else { 
                alert(res.message || 'Falha ao atualizar'); 
            }
        } catch(e) { 
            alert('Erro: ' + e.message); 
        } finally { 
            // Remove loading
            sel.classList.remove('is-loading'); 
            sel.disabled = false; 
        }
    });
});
</script>

<?php require_once __DIR__ . '/../includes/layout_end.php'; ?>
