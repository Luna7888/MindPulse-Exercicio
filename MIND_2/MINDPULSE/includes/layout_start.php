<?php
/**
 * LAYOUT_START.PHP — Início do Template Base de Todas as Páginas
 * ---------------------------------------------------------------------------
 * Objetivo: Fornecer estrutura HTML comum para todas as páginas protegidas.
 * Dependências: auth.php, db.php, header.php, sidebar.php
 */

// 1. AUTENTICAÇÃO E BANCO
// Inclui funções de auth e exige login imediato.
// Se o usuário não estiver logado, ele será redirecionado aqui mesmo.
require_once __DIR__ . '/auth.php'; 
requireLogin();

// Conecta ao banco de dados ($pdo fica disponível globalmente)
require_once __DIR__ . '/db.php';
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1"/>
    <title>Mindhub — Painel</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;900&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="<?= url_for('/assets/css/styles.css') ?>"/>

    <script>window.BASE_URL="<?= htmlspecialchars(BASE_URL, ENT_QUOTES) ?>";</script>
    <script src="<?= url_for('/assets/js/app.js') ?>" defer></script>

    <style>
        :root { --mh-header-h: 64px; }
        
        /* Reset básico e cores do tema dark */
        body { 
            background: #0f1117; 
            color: #e8edf7; 
            font-family: Inter, system-ui, sans-serif; 
            margin: 0;
        }

        /* Container principal */
        .app-shell { 
            min-height: 100dvh; 
            display: block !important; 
        }

        /* Área de conteúdo ajustada para Header e Sidebar */
        .mh-content {
            width: 100%;
            padding: 16px;
            padding-top: calc(var(--mh-header-h) + 12px);
        }

        /* Desktop: Abre espaço para a sidebar fixa */
        @media (min-width: 981px) {
            .mh-content { padding-left: 276px; }
        }

        /* Resets de utilitários */
        .content { width: 100% !important; max-width: none !important; margin: 0 !important; padding: 0 !important; }
        .card { width: 100%; }
        [class*="container"], [class*="wrapper"] { max-width: none !important; }
    </style>
</head>

<body>
    <div class="app-shell">

        <?php include __DIR__ . '/header.php'; ?>
        
        <?php include __DIR__ . '/sidebar.php'; ?>

        <main class="mh-content">
            <div class="content">
                ```

