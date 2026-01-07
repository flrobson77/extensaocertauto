<?php
/**
 * EXTENSAO_SUAP - Interface Principal
 * Versão: 0.1
 * 
 * Interface web para gerenciar o envio de certificados.
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/functions.php';

// Verifica status do sistema
$statusSistema = verificarSistema();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Sistema de envio automático de certificados - SUAP Extensão">
    <meta name="author" content="SUAP - Sistema Unificado de Administração Pública">
    <title><?php echo PAGE_TITLE; ?> - v<?php echo SYSTEM_VERSION; ?></title>
    
    <!-- CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
    
    <!-- Favicon (opcional) -->
    <!-- <link rel="icon" type="image/png" href="assets/images/favicon.png"> -->
</head>
<body>
    <!-- Container Principal -->
    <div class="container">
        
        <!-- Cabeçalho -->
        <header class="header">
            <h1><?php echo SYSTEM_NAME; ?></h1>
            <p>Sistema automatizado para envio de certificados por e-mail</p>
            <span class="version-badge">Versão <?php echo SYSTEM_VERSION; ?></span>
        </header>
        
        <!-- Conteúdo -->
        <main class="content">
            
            <?php if (!$statusSistema['ok']): ?>
                <!-- Alertas de erro do sistema -->
                <div class="alert alert-error">
                    <strong>Erros de Configuração:</strong>
                    <ul style="margin-top: 10px;">
                        <?php foreach ($statusSistema['erros'] as $erro): ?>
                            <li><?php echo sanitizar($erro); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <?php if (count($statusSistema['avisos']) > 0): ?>
                <!-- Avisos do sistema -->
                <div class="alert alert-warning">
                    <strong>Avisos:</strong>
                    <ul style="margin-top: 10px;">
                        <?php foreach ($statusSistema['avisos'] as $aviso): ?>
                            <li><?php echo sanitizar($aviso); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <!-- Painel de Informações -->
            <div class="info-panel">
                <h2>📊 Estatísticas</h2>
                <p>Resumo dos certificados disponíveis para envio</p>
                
                <div class="info-grid">
                    <div class="info-item">
                        <label>Total de Estudantes</label>
                        <div class="value" id="totalEstudantes">0</div>
                    </div>
                    
                    <div class="info-item">
                        <label>Com Certificado</label>
                        <div class="value" style="color: #28a745;" id="totalComCertificado">0</div>
                    </div>
                    
                    <div class="info-item">
                        <label>Sem Certificado</label>
                        <div class="value" style="color: #ffc107;" id="totalSemCertificado">0</div>
                    </div>
                    
                    <div class="info-item">
                        <label>Progresso</label>
                        <div class="value" id="percentualPronto">0%</div>
                    </div>
                </div>
            </div>
            
            <!-- Informações sobre o processamento -->
            <div class="alert alert-info">
                <strong>ℹ️ Informações Importantes:</strong>
                <ul style="margin-top: 10px; margin-left: 20px;">
                    <li>O sistema enviará certificados apenas para estudantes que possuem arquivo PDF correspondente</li>
                    <li>Cada envio gerará um protocolo único para rastreamento</li>
                    <li>Os logs de envio serão salvos no diretório <code>logs/</code></li>
                    <li>Os protocolos serão salvos no diretório <code>protocolos/</code></li>
                </ul>
            </div>
            
            <!-- Tabela de Estudantes -->
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th style="width: 60px;">#</th>
                            <th>Prontuário</th>
                            <th>E-mail</th>
                            <th style="width: 150px;">Status</th>
                        </tr>
                    </thead>
                    <tbody id="tabelaEstudantes">
                        <tr>
                            <td colspan="4" class="text-center">
                                <div class="spinner"></div>
                                Carregando dados...
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <!-- Botão de Envio -->
            <div class="button-container">
                <button id="btnEnviar" disabled>
                    📧 Enviar Certificados
                </button>
            </div>
            
        </main>
        
        <!-- Rodapé -->
        <footer class="footer">
            <p>
                <strong><?php echo SYSTEM_NAME; ?></strong> - Versão <?php echo SYSTEM_VERSION; ?><br>
                Desenvolvido para facilitar o processo de distribuição de certificados<br>
                &copy; <?php echo date('Y'); ?> - Todos os direitos reservados
            </p>
        </footer>
        
    </div>
    
    <!-- Modal de Progresso -->
    <div id="modalProgresso" class="modal">
        <div class="modal-content">
            <h3>⏳ Enviando Certificados...</h3>
            <p>Por favor, aguarde enquanto os certificados são enviados.</p>
            
            <!-- Barra de Progresso -->
            <div class="progress-container">
                <div id="progressBar" class="progress-bar" style="width: 0%;">0%</div>
            </div>
            
            <!-- Log de Envios -->
            <div class="log-container" id="logEnvios">
                <!-- Entradas de log serão adicionadas aqui via JavaScript -->
            </div>
        </div>
    </div>
    
    <!-- JavaScript -->
    <script src="assets/js/app.js"></script>
</body>
</html>