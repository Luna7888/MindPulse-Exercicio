/**
 * ╔═══════════════════════════════════════════════════════════════════════════╗
 * ║ APP.JS — JavaScript Global da Plataforma Mindpulse                       ║
 * ╠═══════════════════════════════════════════════════════════════════════════╣
 * ║                                                                           ║
 * ║ @objetivo      Fornecer funções JavaScript utilitárias usadas em         ║
 * ║                múltiplas páginas da aplicação                            ║
 * ║                                                                           ║
 * ║ @carregamento  Carregado com defer em todas as páginas protegidas        ║
 * ║                (via layout_start.php)                                    ║
 * ║                                                                           ║
 * ║ @dependências  window.BASE_URL (definido no PHP antes deste script)      ║
 * ║                                                                           ║
 * ║ @funções       qs()              - Query selector simplificado           ║
 * ║                togglePassword()  - Mostrar/ocultar senha                 ║
 * ║                submitSwitchOrg() - Trocar organização (legado)           ║
 * ║                                                                           ║
 * ╚═══════════════════════════════════════════════════════════════════════════╝
 */

// ═══════════════════════════════════════════════════════════════════════════
// SEÇÃO: FUNÇÕES UTILITÁRIAS
// ═══════════════════════════════════════════════════════════════════════════

/**
 * qs() — Query Selector simplificado
 * 
 * Atalho para document.querySelector() com suporte a escopo
 * 
 * @param {string} s - Seletor CSS (ex: '#meuId', '.minhaClasse', 'button')
 * @param {Element} el - Elemento pai para busca (opcional, padrão: document)
 * @returns {Element|null} - Elemento encontrado ou null
 * 
 * @exemplo
 * // Busca no documento inteiro
 * const btn = qs('#btnSubmit');
 * 
 * // Busca dentro de um elemento específico
 * const form = qs('#meuForm');
 * const input = qs('input[name="email"]', form);
 * 
 * @por_que_usar
 * - Mais curto que document.querySelector()
 * - Suporta escopo (buscar dentro de elemento)
 * - Padrão comum em projetos JavaScript
 */
function qs(s, el) {
    // Se el não for passado, usa document como padrão
    // O operador || retorna o primeiro valor truthy
    return (el || document).querySelector(s);
}

// ═══════════════════════════════════════════════════════════════════════════
// SEÇÃO: FUNÇÕES DE FORMULÁRIO
// ═══════════════════════════════════════════════════════════════════════════

/**
 * togglePassword() — Alterna visibilidade de campo de senha
 * 
 * Muda o tipo do input entre 'password' (oculto) e 'text' (visível)
 * Também atualiza o ícone para indicar o estado atual
 * 
 * @param {string} id - ID do input de senha
 * @param {string} iconId - ID do elemento do ícone (para atualizar visual)
 * 
 * @exemplo
 * // HTML
 * <input type="password" id="senha">
 * <span id="iconeSenha" onclick="togglePassword('senha', 'iconeSenha')">👁</span>
 * 
 * // Ao clicar, alterna entre mostrar e ocultar a senha
 * 
 * @acessibilidade
 * O atributo data-eye pode ser usado para estilizar o ícone via CSS:
 * [data-eye="open"] { ... }  // Senha visível
 * [data-eye="closed"] { ... } // Senha oculta
 */
function togglePassword(id, iconId) {
    // Busca os elementos pelo ID
    const inp = qs('#' + id);      // Input de senha
    const ic = qs('#' + iconId);   // Ícone do olho
    
    // Se o input não existir, sai da função
    if (!inp) return;
    
    // Alterna o tipo do input
    if (inp.type === 'password') {
        // Estava oculto, mostra
        inp.type = 'text';
        // Atualiza atributo do ícone para 'open' (senha visível)
        ic.setAttribute('data-eye', 'open');
    } else {
        // Estava visível, oculta
        inp.type = 'password';
        // Atualiza atributo do ícone para 'closed' (senha oculta)
        ic.setAttribute('data-eye', 'closed');
    }
}

// ═══════════════════════════════════════════════════════════════════════════
// SEÇÃO: FUNÇÕES DE NAVEGAÇÃO (LEGADO)
// ═══════════════════════════════════════════════════════════════════════════

/**
 * submitSwitchOrg() — Submete formulário para trocar de organização
 * 
 * @deprecated Esta função é legada. O sistema atual usa AJAX via header.php
 * 
 * Cria e submete um formulário POST para trocar a organização/empresa ativa
 * 
 * @param {HTMLSelectElement} sel - Elemento select com as organizações
 * 
 * @fluxo
 * 1. Verifica se o select existe e tem valor
 * 2. Cria um formulário invisível
 * 3. Adiciona input hidden com o organization_id
 * 4. Submete o formulário
 * 
 * @nota
 * Esta função usa 'switch_org.php' que pode não existir mais
 * O sistema atual usa 'switch_company.php' com AJAX
 * Mantida para compatibilidade com código legado
 * 
 * @exemplo
 * <select onchange="submitSwitchOrg(this)">
 *   <option value="1">Empresa A</option>
 *   <option value="2">Empresa B</option>
 * </select>
 */
function submitSwitchOrg(sel) {
    // Validação: verifica se select existe e tem valor selecionado
    if (!sel || !sel.value) return;
    
    // Cria um formulário dinamicamente
    const form = document.createElement('form');
    form.method = 'POST';  // Método POST (mais seguro que GET)
    
    // Constrói a URL usando BASE_URL global
    // window.BASE_URL é definido no PHP (ex: '/MINDPULSE' ou '')
    form.action = (window.BASE_URL || '') + '/auth/switch_org.php';
    
    // Cria input hidden para enviar o ID da organização
    const input = document.createElement('input');
    input.type = 'hidden';
    input.name = 'organization_id';  // Nome do campo esperado pelo backend
    input.value = sel.value;         // Valor selecionado no dropdown
    
    // Monta e submete o formulário
    form.appendChild(input);
    document.body.appendChild(form);  // Precisa estar no DOM para submeter
    form.submit();
}

// ═══════════════════════════════════════════════════════════════════════════
// SEÇÃO: FUNÇÕES FUTURAS / ESPAÇO PARA EXPANSÃO
// ═══════════════════════════════════════════════════════════════════════════

/**
 * Este arquivo pode ser expandido com funções como:
 * 
 * - postJSON(url, data) — Requisições AJAX padronizadas
 * - showToast(message, type) — Notificações toast
 * - confirmAction(message) — Diálogos de confirmação
 * - formatDate(date) — Formatação de datas
 * - debounce(fn, delay) — Debounce para inputs
 * - setLoading(element, isLoading) — Estados de loading
 * 
 * Ao adicionar novas funções, documentar:
 * - Propósito
 * - Parâmetros
 * - Retorno
 * - Exemplo de uso
 * - Onde é utilizada
 */
