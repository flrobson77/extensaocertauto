<<<<<<< HEAD
<?php
/**
 * EXTENSAO_SUAP - Funções Auxiliares
 * Versão: 0.1
 * 
 * Funções utilitárias para o sistema de envio de certificados.
 */

require_once __DIR__ . '/../config/config.php';

/**
 * Registra uma entrada no arquivo de log
 * 
 * @param string $message Mensagem a ser registrada
 * @param string $level Nível do log (INFO, ERROR, WARNING, SUCCESS)
 * @return bool Retorna true se gravou com sucesso
 */
function logMessage($message, $level = 'INFO') {
    $timestamp = date('Y-m-d H:i:s');
    $logEntry = "[{$timestamp}] [{$level}] {$message}" . PHP_EOL;
    
    // Garante que o diretório de logs existe
    if (!is_dir(LOGS_PATH)) {
        mkdir(LOGS_PATH, 0777, true);
    }
    
    return file_put_contents(LOG_FILE, $logEntry, FILE_APPEND) !== false;
}

/**
 * Gera um protocolo único para o envio
 * 
 * @return string Protocolo no formato PROTOCOLO-YYYYMMDD-HHMMSS-{hash}
 */
function gerarProtocolo() {
    $timestamp = date('Ymd-His');
    $hash = substr(md5(uniqid(rand(), true)), 0, 8);
    return "PROTOCOLO-{$timestamp}-{$hash}";
}

/**
 * Salva o protocolo de envio em arquivo
 * 
 * @param string $protocolo Número do protocolo
 * @param array $dados Dados do envio (prontuario, email, status, etc)
 * @return bool Retorna true se salvou com sucesso
 */
function salvarProtocolo($protocolo, $dados) {
    // Garante que o diretório existe
    if (!is_dir(PROTOCOLOS_PATH)) {
        mkdir(PROTOCOLOS_PATH, 0777, true);
    }
    
    $filename = PROTOCOLOS_PATH . "/{$protocolo}.txt";
    
    // Monta o conteúdo do protocolo
    $conteudo = "========================================" . PHP_EOL;
    $conteudo .= "PROTOCOLO DE ENVIO DE CERTIFICADO" . PHP_EOL;
    $conteudo .= "========================================" . PHP_EOL;
    $conteudo .= "Protocolo: {$protocolo}" . PHP_EOL;
    $conteudo .= "Data/Hora: " . date('d/m/Y H:i:s') . PHP_EOL;
    $conteudo .= "========================================" . PHP_EOL;
    $conteudo .= "DADOS DO ESTUDANTE" . PHP_EOL;
    $conteudo .= "========================================" . PHP_EOL;
    $conteudo .= "Prontuário: {$dados['prontuario']}" . PHP_EOL;
    $conteudo .= "E-mail: {$dados['email']}" . PHP_EOL;
    $conteudo .= "========================================" . PHP_EOL;
    $conteudo .= "DADOS DO ENVIO" . PHP_EOL;
    $conteudo .= "========================================" . PHP_EOL;
    $conteudo .= "Status: {$dados['status']}" . PHP_EOL;
    $conteudo .= "Certificado: {$dados['certificado']}" . PHP_EOL;
    $conteudo .= "Hash MD5: {$dados['hash']}" . PHP_EOL;
    $conteudo .= "Tamanho: {$dados['tamanho']} bytes" . PHP_EOL;
    $conteudo .= "========================================" . PHP_EOL;
    
    if (isset($dados['erro'])) {
        $conteudo .= "ERRO: {$dados['erro']}" . PHP_EOL;
        $conteudo .= "========================================" . PHP_EOL;
    }
    
    $conteudo .= PHP_EOL;
    $conteudo .= "Este protocolo comprova a tentativa de envio do certificado." . PHP_EOL;
    $conteudo .= "Guarde este número para referência futura." . PHP_EOL;
    
    return file_put_contents($filename, $conteudo) !== false;
}

/**
 * Lê o arquivo CSV e retorna array de estudantes
 * 
 * @return array Array com estudantes ou false em caso de erro
 */
function lerEstudantesCSV() {
    if (!file_exists(CSV_FILE)) {
        logMessage("Arquivo CSV não encontrado: " . CSV_FILE, 'ERROR');
        return false;
    }
    
    $estudantes = [];
    $handle = fopen(CSV_FILE, 'r');
    
    if ($handle === false) {
        logMessage("Erro ao abrir arquivo CSV", 'ERROR');
        return false;
    }
    
    // Pula a primeira linha (cabeçalho)
    $header = fgetcsv($handle);
    
    // Lê os dados
    $linha = 1;
    while (($data = fgetcsv($handle)) !== false) {
        $linha++;
        
        if (count($data) < 2) {
            logMessage("Linha {$linha} do CSV inválida (dados insuficientes)", 'WARNING');
            continue;
        }
        
        $prontuario = trim($data[0]);
        $email = trim($data[1]);
        
        // Valida prontuário
        if (!validarProntuario($prontuario)) {
            logMessage("Prontuário inválido na linha {$linha}: {$prontuario}", 'WARNING');
            continue;
        }
        
        // Valida e-mail
        if (!validarEmail($email)) {
            logMessage("E-mail inválido na linha {$linha}: {$email}", 'WARNING');
            continue;
        }
        
        $estudantes[] = [
            'prontuario' => $prontuario,
            'email' => $email,
            'linha' => $linha
        ];
    }
    
    fclose($handle);
    
    logMessage("Total de " . count($estudantes) . " estudantes carregados do CSV", 'INFO');
    return $estudantes;
}

/**
 * Valida formato do prontuário
 * 
 * @param string $prontuario Prontuário a ser validado
 * @return bool Retorna true se válido
 */
function validarProntuario($prontuario) {
    return preg_match(PRONTUARIO_PATTERN, $prontuario) === 1;
}

/**
 * Valida formato do e-mail
 * 
 * @param string $email E-mail a ser validado
 * @return bool Retorna true se válido
 */
function validarEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Verifica se existe certificado para o prontuário
 * 
 * @param string $prontuario Prontuário do estudante
 * @return string|false Retorna caminho do certificado ou false
 */
function buscarCertificado($prontuario) {
    $filename = CERTIFICADOS_PATH . "/{$prontuario}.pdf";
    
    if (file_exists($filename) && is_readable($filename)) {
        return $filename;
    }
    
    return false;
}

/**
 * Obtém informações sobre o certificado
 * 
 * @param string $caminho Caminho do arquivo
 * @return array Array com informações do arquivo
 */
function obterInfoCertificado($caminho) {
    if (!file_exists($caminho)) {
        return false;
    }
    
    return [
        'caminho' => $caminho,
        'nome' => basename($caminho),
        'tamanho' => filesize($caminho),
        'hash' => md5_file($caminho),
        'mime' => 'application/pdf'
    ];
}

/**
 * Sanitiza string para prevenir XSS
 * 
 * @param string $string String a ser sanitizada
 * @return string String sanitizada
 */
function sanitizar($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

/**
 * Formata tamanho de arquivo em formato legível
 * 
 * @param int $bytes Tamanho em bytes
 * @return string Tamanho formatado
 */
function formatarTamanho($bytes) {
    $units = ['B', 'KB', 'MB', 'GB'];
    $i = 0;
    
    while ($bytes >= 1024 && $i < count($units) - 1) {
        $bytes /= 1024;
        $i++;
    }
    
    return round($bytes, 2) . ' ' . $units[$i];
}

/**
 * Verifica status do sistema antes de iniciar envios
 * 
 * @return array Array com verificações do sistema
 */
function verificarSistema() {
    $status = [
        'ok' => true,
        'erros' => [],
        'avisos' => []
    ];
    
    // Verifica diretórios
    $dirs = checkDirectories();
    foreach ($dirs as $nome => $info) {
        if (!$info['exists']) {
            $status['erros'][] = "Diretório '{$nome}' não existe";
            $status['ok'] = false;
        } elseif (!$info['writable']) {
            $status['erros'][] = "Diretório '{$nome}' sem permissão de escrita";
            $status['ok'] = false;
        }
    }
    
    // Verifica arquivo CSV
    if (!checkCSVFile()) {
        $status['erros'][] = "Arquivo CSV não encontrado ou não legível";
        $status['ok'] = false;
    }
    
    // Verifica função mail()
    if (!function_exists('mail')) {
        $status['erros'][] = "Função mail() não disponível no PHP";
        $status['ok'] = false;
    }
    
    // Verifica modo de teste
    if (TEST_MODE) {
        $status['avisos'][] = "Sistema em MODO DE TESTE - e-mails não serão enviados";
    }
    
    return $status;
}

/**
 * Prepara dados para envio em formato JSON
 * 
 * @param array $estudantes Array de estudantes
 * @return string JSON com dados
 */
function prepararDadosJSON($estudantes) {
    $dados = [];
    
    foreach ($estudantes as $estudante) {
        $certificado = buscarCertificado($estudante['prontuario']);
        
        $dados[] = [
            'prontuario' => $estudante['prontuario'],
            'email' => $estudante['email'],
            'tem_certificado' => $certificado !== false,
            'certificado' => $certificado ? basename($certificado) : null
        ];
    }
    
    return json_encode($dados);
}

/**
 * Retorna estatísticas dos certificados disponíveis
 * 
 * @param array $estudantes Array de estudantes
 * @return array Estatísticas
 */
function obterEstatisticas($estudantes) {
    $total = count($estudantes);
    $com_certificado = 0;
    $sem_certificado = 0;
    
    foreach ($estudantes as $estudante) {
        if (buscarCertificado($estudante['prontuario'])) {
            $com_certificado++;
        } else {
            $sem_certificado++;
        }
    }
    
    return [
        'total' => $total,
        'com_certificado' => $com_certificado,
        'sem_certificado' => $sem_certificado,
        'percentual_pronto' => $total > 0 ? round(($com_certificado / $total) * 100, 2) : 0
    ];
=======
<?php
/**
 * EXTENSAO_SUAP - Funções Auxiliares
 * Versão: 0.1
 * 
 * Funções utilitárias para o sistema de envio de certificados.
 */

require_once __DIR__ . '/../config/config.php';

/**
 * Registra uma entrada no arquivo de log
 * 
 * @param string $message Mensagem a ser registrada
 * @param string $level Nível do log (INFO, ERROR, WARNING, SUCCESS)
 * @return bool Retorna true se gravou com sucesso
 */
function logMessage($message, $level = 'INFO') {
    $timestamp = date('Y-m-d H:i:s');
    $logEntry = "[{$timestamp}] [{$level}] {$message}" . PHP_EOL;
    
    // Garante que o diretório de logs existe
    if (!is_dir(LOGS_PATH)) {
        mkdir(LOGS_PATH, 0777, true);
    }
    
    return file_put_contents(LOG_FILE, $logEntry, FILE_APPEND) !== false;
}

/**
 * Gera um protocolo único para o envio
 * 
 * @return string Protocolo no formato PROTOCOLO-YYYYMMDD-HHMMSS-{hash}
 */
function gerarProtocolo() {
    $timestamp = date('Ymd-His');
    $hash = substr(md5(uniqid(rand(), true)), 0, 8);
    return "PROTOCOLO-{$timestamp}-{$hash}";
}

/**
 * Salva o protocolo de envio em arquivo
 * 
 * @param string $protocolo Número do protocolo
 * @param array $dados Dados do envio (prontuario, email, status, etc)
 * @return bool Retorna true se salvou com sucesso
 */
function salvarProtocolo($protocolo, $dados) {
    // Garante que o diretório existe
    if (!is_dir(PROTOCOLOS_PATH)) {
        mkdir(PROTOCOLOS_PATH, 0777, true);
    }
    
    $filename = PROTOCOLOS_PATH . "/{$protocolo}.txt";
    
    // Monta o conteúdo do protocolo
    $conteudo = "========================================" . PHP_EOL;
    $conteudo .= "PROTOCOLO DE ENVIO DE CERTIFICADO" . PHP_EOL;
    $conteudo .= "========================================" . PHP_EOL;
    $conteudo .= "Protocolo: {$protocolo}" . PHP_EOL;
    $conteudo .= "Data/Hora: " . date('d/m/Y H:i:s') . PHP_EOL;
    $conteudo .= "========================================" . PHP_EOL;
    $conteudo .= "DADOS DO ESTUDANTE" . PHP_EOL;
    $conteudo .= "========================================" . PHP_EOL;
    $conteudo .= "Prontuário: {$dados['prontuario']}" . PHP_EOL;
    $conteudo .= "E-mail: {$dados['email']}" . PHP_EOL;
    $conteudo .= "========================================" . PHP_EOL;
    $conteudo .= "DADOS DO ENVIO" . PHP_EOL;
    $conteudo .= "========================================" . PHP_EOL;
    $conteudo .= "Status: {$dados['status']}" . PHP_EOL;
    $conteudo .= "Certificado: {$dados['certificado']}" . PHP_EOL;
    $conteudo .= "Hash MD5: {$dados['hash']}" . PHP_EOL;
    $conteudo .= "Tamanho: {$dados['tamanho']} bytes" . PHP_EOL;
    $conteudo .= "========================================" . PHP_EOL;
    
    if (isset($dados['erro'])) {
        $conteudo .= "ERRO: {$dados['erro']}" . PHP_EOL;
        $conteudo .= "========================================" . PHP_EOL;
    }
    
    $conteudo .= PHP_EOL;
    $conteudo .= "Este protocolo comprova a tentativa de envio do certificado." . PHP_EOL;
    $conteudo .= "Guarde este número para referência futura." . PHP_EOL;
    
    return file_put_contents($filename, $conteudo) !== false;
}

/**
 * Lê o arquivo CSV e retorna array de estudantes
 * 
 * @return array Array com estudantes ou false em caso de erro
 */
function lerEstudantesCSV() {
    if (!file_exists(CSV_FILE)) {
        logMessage("Arquivo CSV não encontrado: " . CSV_FILE, 'ERROR');
        return false;
    }
    
    $estudantes = [];
    $handle = fopen(CSV_FILE, 'r');
    
    if ($handle === false) {
        logMessage("Erro ao abrir arquivo CSV", 'ERROR');
        return false;
    }
    
    // Pula a primeira linha (cabeçalho)
    $header = fgetcsv($handle);
    
    // Lê os dados
    $linha = 1;
    while (($data = fgetcsv($handle)) !== false) {
        $linha++;
        
        if (count($data) < 2) {
            logMessage("Linha {$linha} do CSV inválida (dados insuficientes)", 'WARNING');
            continue;
        }
        
        $prontuario = trim($data[0]);
        $email = trim($data[1]);
        
        // Valida prontuário
        if (!validarProntuario($prontuario)) {
            logMessage("Prontuário inválido na linha {$linha}: {$prontuario}", 'WARNING');
            continue;
        }
        
        // Valida e-mail
        if (!validarEmail($email)) {
            logMessage("E-mail inválido na linha {$linha}: {$email}", 'WARNING');
            continue;
        }
        
        $estudantes[] = [
            'prontuario' => $prontuario,
            'email' => $email,
            'linha' => $linha
        ];
    }
    
    fclose($handle);
    
    logMessage("Total de " . count($estudantes) . " estudantes carregados do CSV", 'INFO');
    return $estudantes;
}

/**
 * Valida formato do prontuário
 * 
 * @param string $prontuario Prontuário a ser validado
 * @return bool Retorna true se válido
 */
function validarProntuario($prontuario) {
    return preg_match(PRONTUARIO_PATTERN, $prontuario) === 1;
}

/**
 * Valida formato do e-mail
 * 
 * @param string $email E-mail a ser validado
 * @return bool Retorna true se válido
 */
function validarEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Verifica se existe certificado para o prontuário
 * 
 * @param string $prontuario Prontuário do estudante
 * @return string|false Retorna caminho do certificado ou false
 */
function buscarCertificado($prontuario) {
    $filename = CERTIFICADOS_PATH . "/{$prontuario}.pdf";
    
    if (file_exists($filename) && is_readable($filename)) {
        return $filename;
    }
    
    return false;
}

/**
 * Obtém informações sobre o certificado
 * 
 * @param string $caminho Caminho do arquivo
 * @return array Array com informações do arquivo
 */
function obterInfoCertificado($caminho) {
    if (!file_exists($caminho)) {
        return false;
    }
    
    return [
        'caminho' => $caminho,
        'nome' => basename($caminho),
        'tamanho' => filesize($caminho),
        'hash' => md5_file($caminho),
        'mime' => 'application/pdf'
    ];
}

/**
 * Sanitiza string para prevenir XSS
 * 
 * @param string $string String a ser sanitizada
 * @return string String sanitizada
 */
function sanitizar($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

/**
 * Formata tamanho de arquivo em formato legível
 * 
 * @param int $bytes Tamanho em bytes
 * @return string Tamanho formatado
 */
function formatarTamanho($bytes) {
    $units = ['B', 'KB', 'MB', 'GB'];
    $i = 0;
    
    while ($bytes >= 1024 && $i < count($units) - 1) {
        $bytes /= 1024;
        $i++;
    }
    
    return round($bytes, 2) . ' ' . $units[$i];
}

/**
 * Verifica status do sistema antes de iniciar envios
 * 
 * @return array Array com verificações do sistema
 */
function verificarSistema() {
    $status = [
        'ok' => true,
        'erros' => [],
        'avisos' => []
    ];
    
    // Verifica diretórios
    $dirs = checkDirectories();
    foreach ($dirs as $nome => $info) {
        if (!$info['exists']) {
            $status['erros'][] = "Diretório '{$nome}' não existe";
            $status['ok'] = false;
        } elseif (!$info['writable']) {
            $status['erros'][] = "Diretório '{$nome}' sem permissão de escrita";
            $status['ok'] = false;
        }
    }
    
    // Verifica arquivo CSV
    if (!checkCSVFile()) {
        $status['erros'][] = "Arquivo CSV não encontrado ou não legível";
        $status['ok'] = false;
    }
    
    // Verifica função mail()
    if (!function_exists('mail')) {
        $status['erros'][] = "Função mail() não disponível no PHP";
        $status['ok'] = false;
    }
    
    // Verifica modo de teste
    if (TEST_MODE) {
        $status['avisos'][] = "Sistema em MODO DE TESTE - e-mails não serão enviados";
    }
    
    return $status;
}

/**
 * Prepara dados para envio em formato JSON
 * 
 * @param array $estudantes Array de estudantes
 * @return string JSON com dados
 */
function prepararDadosJSON($estudantes) {
    $dados = [];
    
    foreach ($estudantes as $estudante) {
        $certificado = buscarCertificado($estudante['prontuario']);
        
        $dados[] = [
            'prontuario' => $estudante['prontuario'],
            'email' => $estudante['email'],
            'tem_certificado' => $certificado !== false,
            'certificado' => $certificado ? basename($certificado) : null
        ];
    }
    
    return json_encode($dados);
}

/**
 * Retorna estatísticas dos certificados disponíveis
 * 
 * @param array $estudantes Array de estudantes
 * @return array Estatísticas
 */
function obterEstatisticas($estudantes) {
    $total = count($estudantes);
    $com_certificado = 0;
    $sem_certificado = 0;
    
    foreach ($estudantes as $estudante) {
        if (buscarCertificado($estudante['prontuario'])) {
            $com_certificado++;
        } else {
            $sem_certificado++;
        }
    }
    
    return [
        'total' => $total,
        'com_certificado' => $com_certificado,
        'sem_certificado' => $sem_certificado,
        'percentual_pronto' => $total > 0 ? round(($com_certificado / $total) * 100, 2) : 0
    ];
>>>>>>> 02be3d6e93f4b7aa59e02c72bb147de6dcd9d180
}