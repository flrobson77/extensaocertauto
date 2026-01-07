<?php
/**
 * EXTENSAO_SUAP - Configurações do Sistema
 * Versão: 0.1
 * 
 * Arquivo de configuração principal do sistema de envio de certificados.
 * Ajuste as configurações de acordo com seu ambiente de hospedagem.
 */

// ========================================
// CONFIGURAÇÕES GERAIS
// ========================================

// Timezone do sistema
define('TIMEZONE', 'America/Sao_Paulo');
date_default_timezone_set(TIMEZONE);

// Versão do sistema
define('SYSTEM_VERSION', '0.1');
define('SYSTEM_NAME', 'EXTENSAO_SUAP - Envio de Certificados');

// ========================================
// CONFIGURAÇÕES DE CAMINHOS
// ========================================

// Diretório raiz do sistema
define('ROOT_PATH', dirname(__DIR__));

// Diretório de dados
define('DATA_PATH', ROOT_PATH . '/data');
define('CERTIFICADOS_PATH', DATA_PATH . '/certificados');
define('CSV_FILE', DATA_PATH . '/estudantes.csv');

// Diretório de logs
define('LOGS_PATH', ROOT_PATH . '/logs');
define('LOG_FILE', LOGS_PATH . '/envios.log');

// Diretório de protocolos
define('PROTOCOLOS_PATH', ROOT_PATH . '/protocolos');

// ========================================
// CONFIGURAÇÕES DE E-MAIL
// ========================================

// Remetente do e-mail
define('MAIL_FROM', 'extensao@sua-instituicao.edu.br');
define('MAIL_FROM_NAME', 'Coordenação de Extensão');

// Assunto do e-mail
define('MAIL_SUBJECT', 'Certificado de Participação - SUAP Extensão');

// Corpo do e-mail (HTML)
define('MAIL_BODY_HTML', '
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background-color: #0066cc; color: white; padding: 20px; text-align: center; }
        .content { padding: 20px; background-color: #f9f9f9; }
        .footer { padding: 20px; text-align: center; font-size: 12px; color: #666; }
        .button { background-color: #0066cc; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; margin: 10px 0; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Certificado de Participação</h1>
        </div>
        <div class="content">
            <p>Prezado(a) estudante,</p>
            <p>Seu certificado de participação está disponível em anexo neste e-mail.</p>
            <p><strong>Prontuário:</strong> {{PRONTUARIO}}</p>
            <p><strong>Protocolo de envio:</strong> {{PROTOCOLO}}</p>
            <p>Guarde este e-mail como comprovante de recebimento do certificado.</p>
            <p>Em caso de dúvidas, entre em contato com a Coordenação de Extensão.</p>
        </div>
        <div class="footer">
            <p>Este é um e-mail automático. Por favor, não responda.</p>
            <p>&copy; ' . date('Y') . ' - Sistema de Extensão SUAP</p>
        </div>
    </div>
</body>
</html>
');

// Corpo do e-mail (texto plano - fallback)
define('MAIL_BODY_TEXT', '
Prezado(a) estudante,

Seu certificado de participação está disponível em anexo neste e-mail.

Prontuário: {{PRONTUARIO}}
Protocolo de envio: {{PROTOCOLO}}

Guarde este e-mail como comprovante de recebimento do certificado.

Em caso de dúvidas, entre em contato com a Coordenação de Extensão.

---
Este é um e-mail automático. Por favor, não responda.
© ' . date('Y') . ' - Sistema de Extensão SUAP
');

// ========================================
// CONFIGURAÇÕES DE VALIDAÇÃO
// ========================================

// Formato do prontuário (regex)
define('PRONTUARIO_PATTERN', '/^GU\d{7}[0-9X]$/');

// Extensões permitidas para certificados
define('ALLOWED_EXTENSIONS', ['pdf']);

// Tamanho máximo do arquivo em bytes (10MB)
define('MAX_FILE_SIZE', 10 * 1024 * 1024);

// ========================================
// CONFIGURAÇÕES DE SEGURANÇA
// ========================================

// Ativar logs detalhados (true/false)
define('ENABLE_DETAILED_LOGS', true);

// Ativar modo de teste (não envia e-mails reais, apenas simula)
define('TEST_MODE', false);

// ========================================
// CONFIGURAÇÕES DE INTERFACE
// ========================================

// Título da página
define('PAGE_TITLE', 'Sistema de Envio de Certificados');

// Mensagens do sistema
define('MSG_SUCCESS', 'Certificados enviados com sucesso!');
define('MSG_ERROR', 'Ocorreu um erro durante o envio. Verifique os logs.');
define('MSG_NO_STUDENTS', 'Nenhum estudante encontrado no arquivo CSV.');
define('MSG_NO_CERTIFICATES', 'Nenhum certificado encontrado para envio.');

// ========================================
// FUNÇÕES AUXILIARES DE CONFIGURAÇÃO
// ========================================

/**
 * Verifica se os diretórios necessários existem e têm permissão de escrita
 * 
 * @return array Array com status de cada diretório
 */
function checkDirectories() {
    $directories = [
        'logs' => LOGS_PATH,
        'protocolos' => PROTOCOLOS_PATH,
        'data' => DATA_PATH,
        'certificados' => CERTIFICADOS_PATH
    ];
    
    $status = [];
    foreach ($directories as $name => $path) {
        $status[$name] = [
            'exists' => is_dir($path),
            'writable' => is_writable($path)
        ];
    }
    
    return $status;
}

/**
 * Verifica se o arquivo CSV existe
 * 
 * @return bool
 */
function checkCSVFile() {
    return file_exists(CSV_FILE) && is_readable(CSV_FILE);
}

/**
 * Retorna informações sobre a configuração do sistema
 * 
 * @return array
 */
function getSystemInfo() {
    return [
        'version' => SYSTEM_VERSION,
        'name' => SYSTEM_NAME,
        'php_version' => PHP_VERSION,
        'timezone' => TIMEZONE,
        'test_mode' => TEST_MODE,
        'mail_from' => MAIL_FROM
    ];
}