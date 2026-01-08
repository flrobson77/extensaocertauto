<?php
/**
 * EXTENSAO_SUAP - Processador de Requisições
 * Versão: 0.1
 * 
 * Processa requisições AJAX para listar estudantes e enviar certificados.
 */

// Configurações de erro e headers
error_reporting(E_ALL);
ini_set('display_errors', 0); // Desabilita exibição de erros em produção

header('Content-Type: application/json; charset=utf-8');

// Carrega dependências
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/email.php';

// ========================================
// PROCESSA REQUISIÇÃO
// ========================================

// Determina o tipo de requisição
$metodo = $_SERVER['REQUEST_METHOD'];

if ($metodo === 'GET') {
    // Requisição GET - listar estudantes
    processarListagem();
} elseif ($metodo === 'POST') {
    // Requisição POST - enviar certificados
    processarEnvio();
} else {
    // Método não suportado
    responderErro('Método não suportado', 405);
}

// ========================================
// FUNÇÕES DE PROCESSAMENTO
// ========================================

/**
 * Processa listagem de estudantes
 */
function processarListagem() {
    try {
        // Verifica parâmetro de ação
        $acao = isset($_GET['acao']) ? $_GET['acao'] : '';
        
        if ($acao !== 'listar') {
            responderErro('Ação inválida');
            return;
        }
        
        // Lê estudantes do CSV
        $estudantes = lerEstudantesCSV();
        
        if ($estudantes === false) {
            responderErro('Erro ao ler arquivo de estudantes');
            return;
        }
        
        // Enriquece dados com informações de certificados
        $estudantesComCertificados = [];
        foreach ($estudantes as $estudante) {
            $certificado = buscarCertificado($estudante['prontuario']);
            
            $estudantesComCertificados[] = [
                'prontuario' => $estudante['prontuario'],
                'email' => $estudante['email'],
                'tem_certificado' => $certificado !== false,
                'certificado' => $certificado ? basename($certificado) : null
            ];
        }
        
        // Obtém estatísticas
        $estatisticas = obterEstatisticas($estudantes);
        
        // Responde com sucesso
        responderSucesso([
            'estudantes' => $estudantesComCertificados,
            'estatisticas' => $estatisticas
        ]);
        
    } catch (Exception $e) {
        logMessage("Erro ao processar listagem: " . $e->getMessage(), 'ERROR');
        responderErro('Erro interno do servidor: ' . $e->getMessage());
    }
}

/**
 * Processa envio de certificados
 */
function processarEnvio() {
    try {
        // Lê dados JSON do corpo da requisição
        $json = file_get_contents('php://input');
        $dados = json_decode($json, true);
        
        // Valida dados recebidos
        if (!isset($dados['acao']) || $dados['acao'] !== 'enviar') {
            responderErro('Ação inválida');
            return;
        }
        
        if (!isset($dados['estudantes']) || !is_array($dados['estudantes'])) {
            responderErro('Dados de estudantes inválidos');
            return;
        }
        
        // Valida cada estudante
        $estudantesValidados = [];
        foreach ($dados['estudantes'] as $estudante) {
            if (!isset($estudante['prontuario']) || !isset($estudante['email'])) {
                logMessage("Estudante com dados incompletos ignorado", 'WARNING');
                continue;
            }
            
            // Valida prontuário
            if (!validarProntuario($estudante['prontuario'])) {
                logMessage("Prontuário inválido: " . $estudante['prontuario'], 'WARNING');
                continue;
            }
            
            // Valida e-mail
            if (!validarEmail($estudante['email'])) {
                logMessage("E-mail inválido: " . $estudante['email'], 'WARNING');
                continue;
            }
            
            $estudantesValidados[] = [
                'prontuario' => $estudante['prontuario'],
                'email' => $estudante['email']
            ];
        }
        
        if (count($estudantesValidados) === 0) {
            responderErro('Nenhum estudante válido para envio');
            return;
        }
        
        // Log de início do processamento
        logMessage("Iniciando envio de certificados para " . count($estudantesValidados) . " estudante(s)", 'INFO');
        
        // Processa envio em lote
        $resultado = processarEnvioLote($estudantesValidados);
        
        // Log de finalização
        logMessage("Envio finalizado: {$resultado['enviados']} sucesso, {$resultado['erros']} erro(s)", 'INFO');
        
        // Responde com resultado
        responderSucesso($resultado);
        
    } catch (Exception $e) {
        logMessage("Erro ao processar envio: " . $e->getMessage(), 'ERROR');
        responderErro('Erro interno do servidor: ' . $e->getMessage());
    }
}

// ========================================
// FUNÇÕES DE RESPOSTA
// ========================================

/**
 * Responde com sucesso
 * 
 * @param mixed $dados Dados a serem retornados
 */
function responderSucesso($dados = null) {
    $resposta = [
        'sucesso' => true,
        'timestamp' => date('Y-m-d H:i:s')
    ];
    
    if ($dados !== null) {
        $resposta = array_merge($resposta, $dados);
    }
    
    echo json_encode($resposta, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

/**
 * Responde com erro
 * 
 * @param string $mensagem Mensagem de erro
 * @param int $httpCode Código HTTP (opcional)
 */
function responderErro($mensagem, $httpCode = 400) {
    http_response_code($httpCode);
    
    $resposta = [
        'sucesso' => false,
        'mensagem' => $mensagem,
        'timestamp' => date('Y-m-d H:i:s')
    ];
    
    echo json_encode($resposta, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

// ========================================
// TRATAMENTO DE ERROS GLOBAL
// ========================================

/**
 * Handler global de erros
 */
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    $mensagem = "Erro PHP [$errno]: $errstr em $errfile na linha $errline";
    logMessage($mensagem, 'ERROR');
    
    // Em produção, não expõe detalhes do erro
    if (ini_get('display_errors')) {
        responderErro($mensagem, 500);
    } else {
        responderErro('Erro interno do servidor', 500);
    }
});

/**
 * Handler global de exceções
 */
set_exception_handler(function($exception) {
    $mensagem = "Exceção não capturada: " . $exception->getMessage();
    logMessage($mensagem, 'ERROR');
    
    // Em produção, não expõe detalhes da exceção
    if (ini_get('display_errors')) {
        responderErro($mensagem, 500);
    } else {
        responderErro('Erro interno do servidor', 500);
    }
});