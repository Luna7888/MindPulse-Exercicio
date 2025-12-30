<?php
/**
 * ╔═══════════════════════════════════════════════════════════════════════════╗
 * ║ TRAINING_NEW.PHP — Formulário de Criação de Treinamento                  ║
 * ╠═══════════════════════════════════════════════════════════════════════════╣
 * ║                                                                           ║
 * ║ @objetivo      Permitir que gestores criem novos treinamentos            ║
 * ║                com vídeo-aulas, cargos e recompensas                     ║
 * ║                                                                           ║
 * ║ @acesso        Admin Geral | Gestor (canAccessAdmin)                     ║
 * ║ @escopo        Por empresa (company_id)                                  ║
 * ║                                                                           ║
 * ║ @campos        Treinamento:                                              ║
 * ║                - Título, Objetivo, Descrição                             ║
 * ║                - Dificuldade, Duração estimada, Status                   ║
 * ║                - Tags, Capa, Recompensa                                  ║
 * ║                - Cargos com acesso                                       ║
 * ║                                                                           ║
 * ║                Vídeo-aulas (dinâmicas):                                  ║
 * ║                - Título, Ordem, Provider                                 ║
 * ║                - Link/ID do vídeo, Miniatura, Duração                    ║
 * ║                - Resumo/descrição                                        ║
 * ║                                                                           ║
 * ║ @providers     youtube, vimeo, cloudflare, mux, url                      ║
 * ║                                                                           ║
 * ║ @submit        POST → training_save.php                                  ║
 * ║                                                                           ║
 * ║ @dependências  layout_start.php, auth.php, db.php                        ║
 * ║                                                                           ║
 * ╚═══════════════════════════════════════════════════════════════════════════╝
 */

// ═══════════════════════════════════════════════════════════════════════════
// SEÇÃO: INCLUSÃO DE DEPENDÊNCIAS E VERIFICAÇÃO DE ACESSO
// ═══════════════════════════════════════════════════════════════════════════

require_once __DIR__ . '/../includes/layout_start.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

/**
 * Verifica permissão administrativa
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
 * Lista de cargos para checkboxes de permissão
 */
$roles = $pdo->query("SELECT id, name FROM roles ORDER BY name")->fetchAll();
?>

<!-- ╔═══════════════════════════════════════════════════════════════════════╗
     ║ ESTILOS ESPECÍFICOS DO FORMULÁRIO                                     ║
     ╚═══════════════════════════════════════════════════════════════════════╝ -->
<style>
/* ═══════════════════════════════════════════════════════════════════════════
   LAYOUT DO FORMULÁRIO
   ═══════════════════════════════════════════════════════════════════════════ */
.form-grid{
    display: grid; 
    grid-template-columns: 1fr 1fr; 
    gap: 14px;
}

@media(max-width: 980px){
    .form-grid{ grid-template-columns: 1fr }
}

/* ═══════════════════════════════════════════════════════════════════════════
   SEÇÃO DE VÍDEOS
   ═══════════════════════════════════════════════════════════════════════════ */
.videos{
    display: flex; 
    flex-direction: column; 
    gap: 12px;
}

.video-item{
    border: 1px solid var(--stroke); 
    border-radius: 12px; 
    padding: 12px; 
    background: rgba(255,255,255,.04);
}

.video-grid{
    display: grid; 
    grid-template-columns: 1fr 1fr; 
    gap: 12px;
}

@media(max-width: 980px){
    .video-grid{ grid-template-columns: 1fr }
}

/* ═══════════════════════════════════════════════════════════════════════════
   ELEMENTOS DIVERSOS
   ═══════════════════════════════════════════════════════════════════════════ */
.badge-btn{
    border: 1px dashed var(--stroke); 
    background: transparent; 
    color: #e8edf7; 
    border-radius: 12px; 
    padding: .6rem .9rem; 
    cursor: pointer;
}

.small{ font-size: .9rem; color: #9aa4b2 }
</style>

<!-- ╔═══════════════════════════════════════════════════════════════════════╗
     ║ FORMULÁRIO DE CRIAÇÃO DE TREINAMENTO                                  ║
     ║                                                                        ║
     ║ Envia dados via POST para training_save.php                           ║
     ╚═══════════════════════════════════════════════════════════════════════╝ -->
<form class="card" style="padding:16px" method="POST" action="<?= url_for('/pages/training_save.php') ?>">
    
    <!-- Cabeçalho -->
    <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:10px">
        <h2 style="margin:0; font-weight:900">Novo Treinamento</h2>
        <button class="button" type="submit">Salvar treinamento</button>
    </div>

    <!-- Campo hidden: ID da empresa -->
    <input type="hidden" name="company_id" value="<?= (int)$companyId ?>"/>

    <!-- ════════════════════════════════════════════════════════════════════
         GRID: Informações básicas + Configurações
         ════════════════════════════════════════════════════════════════════ -->
    <div class="form-grid">
        
        <!-- Coluna 1: Dados principais -->
        <div>
            <!-- Título -->
            <label class="label">Título*</label>
            <input class="input" name="title" required>

            <!-- Objetivo -->
            <label class="label" style="margin-top:10px">Objetivo*</label>
            <textarea class="input" name="objective" rows="3" required></textarea>

            <!-- Descrição -->
            <label class="label" style="margin-top:10px">Descrição</label>
            <textarea class="input" name="description" rows="5"></textarea>

            <!-- Dificuldade, Duração, Status -->
            <div style="display:flex; gap:10px; flex-wrap:wrap; margin-top:10px">
                <div style="flex:1; min-width:220px">
                    <label class="label">Dificuldade</label>
                    <select class="input" name="difficulty">
                        <option>Iniciante</option>
                        <option>Intermediário</option>
                        <option>Avançado</option>
                    </select>
                </div>
                <div style="flex:1; min-width:220px">
                    <label class="label">Estimativa (min)</label>
                    <input class="input" name="estimated_minutes" type="number" min="0" step="5" value="30">
                </div>
                <div style="flex:1; min-width:220px">
                    <label class="label">Ativo?</label>
                    <select class="input" name="is_active">
                        <option value="1">Sim</option>
                        <option value="0">Não</option>
                    </select>
                </div>
            </div>

            <!-- Tags -->
            <label class="label" style="margin-top:10px">Tags (separe por vírgula)</label>
            <input class="input" name="tags" placeholder="Higiene, Segurança, Atendimento">
        </div>

        <!-- Coluna 2: Mídia e Permissões -->
        <div>
            <!-- Capa -->
            <label class="label">Capa (URL da imagem)</label>
            <input class="input" name="cover_image" placeholder="/assets/img/capas/arquivo.jpg ou https://...">

            <!-- Recompensa -->
            <label class="label" style="margin-top:10px">Recompensa (URL da imagem)</label>
            <input class="input" name="reward_image" placeholder="/assets/img/rewards/icone.png">

            <!-- Cargos com acesso -->
            <label class="label" style="margin-top:10px">Visível para Cargos</label>
            <div class="card" style="padding:10px; max-height:220px; overflow:auto">
                <?php foreach ($roles as $r): ?>
                    <label style="display:flex; align-items:center; gap:8px; margin:6px 0">
                        <input type="checkbox" name="roles[]" value="<?= (int)$r['id'] ?>"> 
                        <?= htmlspecialchars($r['name']) ?>
                    </label>
                <?php endforeach; ?>
                <div class="small">Se nenhum cargo for marcado, somente Admin verá.</div>
            </div>
        </div>
    </div>

    <!-- ════════════════════════════════════════════════════════════════════
         SEÇÃO: Vídeo-aulas
         
         Aulas são adicionadas dinamicamente via JavaScript
         Cada aula tem: título, ordem, provider, link, miniatura, duração
         ════════════════════════════════════════════════════════════════════ -->
    <div style="margin-top:16px">
        <div style="display:flex; align-items:center; justify-content:space-between">
            <h3 style="margin:0; font-weight:900">Aulas / Vídeo-aulas</h3>
            <button class="badge-btn" type="button" id="btnAddVideo">+ Adicionar aula</button>
        </div>
        <div class="small" style="margin-top:4px">
            Provider padrão: YouTube (não listado). Preencha o link ou o ID do vídeo. Ordene pelo campo "Ordem".
        </div>

        <!-- Container onde as aulas serão inseridas -->
        <div class="videos" id="videos"></div>
    </div>

    <!-- Botões de ação (rodapé) -->
    <div style="margin-top:14px; display:flex; gap:8px">
        <button class="button" type="submit">Salvar treinamento</button>
        <a class="button ghost" href="<?= url_for('/pages/treinamentos.php') ?>">Cancelar</a>
    </div>
</form>

<!-- ╔═══════════════════════════════════════════════════════════════════════╗
     ║ TEMPLATE: Bloco de Vídeo-aula                                         ║
     ║                                                                        ║
     ║ Template HTML usado pelo JavaScript para criar novas aulas            ║
     ║ IDX é substituído pelo índice da aula                                 ║
     ╚═══════════════════════════════════════════════════════════════════════╝ -->
<template id="tplVideo">
    <div class="video-item">
        <!-- Cabeçalho da aula -->
        <div style="display:flex; align-items:center; justify-content:space-between; gap:10px">
            <strong>Aula <span class="vi-num">#</span></strong>
            <button class="badge-btn" type="button" onclick="this.closest('.video-item').remove(); renumber()">
                Remover
            </button>
        </div>
        
        <!-- Grid de campos -->
        <div class="video-grid" style="margin-top:10px">
            <div>
                <label class="label">Título da aula*</label>
                <input class="input" name="videos[IDX][title]" required>
            </div>
            <div>
                <label class="label">Ordem*</label>
                <input class="input" name="videos[IDX][order_index]" type="number" min="1" step="1" value="1" required>
            </div>
            <div>
                <label class="label">Provider</label>
                <select class="input" name="videos[IDX][video_provider]">
                    <option value="youtube" selected>youtube</option>
                    <option value="vimeo">vimeo</option>
                    <option value="cloudflare">cloudflare</option>
                    <option value="mux">mux</option>
                    <option value="url">url</option>
                </select>
            </div>
            <div>
                <label class="label">Link/ID do vídeo*</label>
                <input class="input" name="videos[IDX][video_ref]" required placeholder="https://youtube.com/watch?v=...">
            </div>
            <div>
                <label class="label">Miniatura (URL)</label>
                <input class="input" name="videos[IDX][thumb_image]" placeholder="/assets/img/thumbs/aula.jpg">
            </div>
            <div>
                <label class="label">Duração (segundos)</label>
                <input class="input" name="videos[IDX][duration_seconds]" type="number" min="0" step="10" value="0">
            </div>
        </div>
        
        <!-- Resumo -->
        <label class="label" style="margin-top:10px">Resumo/descrição</label>
        <textarea class="input" name="videos[IDX][summary]" rows="3"></textarea>
    </div>
</template>

<!-- ╔═══════════════════════════════════════════════════════════════════════╗
     ║ JAVASCRIPT: Gerenciamento Dinâmico de Vídeo-aulas                     ║
     ╚═══════════════════════════════════════════════════════════════════════╝ -->
<script>
/**
 * Contador de índice para nomes únicos dos campos
 */
let idx = 0;

/**
 * Referências aos elementos
 */
const list = document.getElementById('videos');
const tpl  = document.getElementById('tplVideo').innerHTML;

/**
 * Event listener para o botão "Adicionar aula"
 */
document.getElementById('btnAddVideo').addEventListener('click', addVideo);

/**
 * addVideo() — Adiciona um novo bloco de vídeo-aula
 * 
 * 1. Clona o template substituindo IDX pelo índice atual
 * 2. Insere no container
 * 3. Incrementa o índice
 * 4. Renumera todas as aulas
 */
function addVideo() {
    const html = tpl.replaceAll('IDX', String(idx));
    const wrap = document.createElement('div');
    wrap.innerHTML = html;
    list.appendChild(wrap.firstElementChild);
    idx++; 
    renumber();
}

/**
 * renumber() — Renumera os títulos das aulas
 * 
 * Atualiza o número exibido em "Aula #X" para refletir
 * a posição atual de cada bloco
 */
function renumber() {
    Array.from(document.querySelectorAll('.video-item .vi-num')).forEach((el, i) => {
        el.textContent = (i + 1);
    });
}

/**
 * Adiciona 1 bloco inicial de aula
 */
addVideo();
</script>

<?php require_once __DIR__ . '/../includes/layout_end.php'; ?>
