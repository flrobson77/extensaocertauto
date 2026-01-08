<<<<<<< HEAD
<?php
/**
 * EXTENSAO_SUAP - Funções de E-mail
 * Versão: 0.1
 * 
 * Funções para envio de e-mails com certificados anexados.
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/functions.php';

/**
 * Envia e-mail com certificado anexado
 * 
 * @param string $destinatario E-mail do destinatário
 * @param string $prontuario Prontuário do estudante
 * @param string $caminhoCertificado Caminho completo do certificado
 * @param string $protocolo Número do protocolo de envio
 * @return array Resultado do envio com status e mensagem
 */
function enviarCertificado($destinatario, $prontuario, $caminhoCertificado, $protocolo) {
    $resultado = [
        'sucesso' => false,
        'mensagem' => '',
        'protocolo' => $protocolo
    ];
    
    // Verifica se está em modo de teste
    if (TEST_MODE) {
        logMessage("MODO TESTE: Simulando envio para {$destinatario} (Prontuário: {$prontuario})", 'INFO');
        $resultado['sucesso'] = true;
        $resultado['mensagem'] = 'Envio simulado com sucesso (modo teste)';
        return $resultado;
    }
    
    // Valida e-mail
    if (!validarEmail($destinatario)) {
        $resultado['mensagem'] = 'E-mail inválido';
        logMessage("Tentativa de envio para e-mail inválido: {$destinatario}", 'ERROR');
        return $resultado;
    }
    
    // Verifica se o certificado existe
    if (!file_exists($caminhoCertificado)) {
        $resultado['mensagem'] = 'Certificado não encontrado';
        logMessage("Certificado não encontrado: {$caminhoCertificado}", 'ERROR');
        return $resultado;
    }
    
    try {
        // Lê o conteúdo do certificado
        $conteudoCertificado = file_get_contents($caminhoCertificado);
        if ($conteudoCertificado === false) {
            throw new Exception('Erro ao ler arquivo do certificado');
        }
        
        // Prepara o corpo do e-mail substituindo variáveis
        $corpoHTML = str_replace(
            ['{{PRONTUARIO}}', '{{PROTOCOLO}}'],
            [$prontuario, $protocolo],
            MAIL_BODY_HTML
        );
        
        $corpoTexto = str_replace(
            ['{{PRONTUARIO}}', '{{PROTOCOLO}}'],
            [$prontuario, $protocolo],
            MAIL_BODY_TEXT
        );
        
        // Envia o e-mail com anexo
        $enviado = enviarEmailComAnexo(
            $destinatario,
            MAIL_SUBJECT,
            $corpoHTML,
            $corpoTexto,
            $caminhoCertificado,
            "{$prontuario}.pdf"
        );
        
        if ($enviado) {
            $resultado['sucesso'] = true;
            $resultado['mensagem'] = 'E-mail enviado com sucesso';
            logMessage("Certificado enviado para {$destinatario} (Prontuário: {$prontuario}) - Protocolo: {$protocolo}", 'SUCCESS');
        } else {
            $resultado['mensagem'] = 'Falha ao enviar e-mail';
            logMessage("Falha ao enviar e-mail para {$destinatario} (Prontuário: {$prontuario})", 'ERROR');
        }
        
    } catch (Exception $e) {
        $resultado['mensagem'] = 'Erro: ' . $e->getMessage();
        logMessage("Erro ao enviar e-mail para {$destinatario}: " . $e->getMessage(), 'ERROR');
    }
    
    return $resultado;
}

/**
 * Envia e-mail com anexo usando a função mail() do PHP
 * 
 * @param string $para E-mail do destinatário
 * @param string $assunto Assunto do e-mail
 * @param string $mensagemHTML Corpo do e-mail em HTML
 * @param string $mensagemTexto Corpo do e-mail em texto plano
 * @param string $caminhoArquivo Caminho do arquivo a anexar
 * @param string $nomeArquivo Nome do arquivo no anexo
 * @return bool Retorna true se enviou com sucesso
 */
function enviarEmailComAnexo($para, $assunto, $mensagemHTML, $mensagemTexto, $caminhoArquivo, $nomeArquivo) {
    // Boundary único para separar as partes do e-mail
    $boundary = md5(uniqid(time()));
    
    // Headers do e-mail
    $headers = [];
    $headers[] = "From: " . MAIL_FROM_NAME . " <" . MAIL_FROM . ">";
    $headers[] = "Reply-To: " . MAIL_FROM;
    $headers[] = "MIME-Version: 1.0";
    $headers[] = "Content-Type: multipart/mixed; boundary=\"{$boundary}\"";
    $headers[] = "X-Mailer: PHP/" . phpversion();
    
    // Corpo do e-mail - Parte 1: Versões texto e HTML
    $message = "--{$boundary}\n";
    $message .= "Content-Type: multipart/alternative; boundary=\"alt-{$boundary}\"\n\n";
    
    // Versão texto plano
    $message .= "--alt-{$boundary}\n";
    $message .= "Content-Type: text/plain; charset=UTF-8\n";
    $message .= "Content-Transfer-Encoding: 7bit\n\n";
    $message .= $mensagemTexto . "\n\n";
    
    // Versão HTML
    $message .= "--alt-{$boundary}\n";
    $message .= "Content-Type: text/html; charset=UTF-8\n";
    $message .= "Content-Transfer-Encoding: 7bit\n\n";
    $message .= $mensagemHTML . "\n\n";
    
    $message .= "--alt-{$boundary}--\n\n";
    
    // Corpo do e-mail - Parte 2: Anexo
    if (file_exists($caminhoArquivo)) {
        $conteudoArquivo = chunk_split(base64_encode(file_get_contents($caminhoArquivo)));
        
        $message .= "--{$boundary}\n";
        $message .= "Content-Type: application/pdf; name=\"{$nomeArquivo}\"\n";
        $message .= "Content-Transfer-Encoding: base64\n";
        $message .= "Content-Disposition: attachment; filename=\"{$nomeArquivo}\"\n\n";
        $message .= $conteudoArquivo . "\n\n";
    }
    
    $message .= "--{$boundary}--";
    
    // Envia o e-mail
    return mail($para, $assunto, $message, implode("\r\n", $headers));
}

/**
 * Processa envio em lote de certificados
 * 
 * @param array $estudantes Array com dados dos estudantes
 * @return array Resultado do processamento com estatísticas
 */
function processarEnvioLote($estudantes) {
    $resultado = [
        'total' => count($estudantes),
        'enviados' => 0,
        'erros' => 0,
        'detalhes' => []
    ];
    
    logMessage("Iniciando envio em lote para " . count($estudantes) . " estudantes", 'INFO');
    
    foreach ($estudantes as $estudante) {
        $prontuario = $estudante['prontuario'];
        $email = $estudante['email'];
        
        // Busca o certificado
        $certificado = buscarCertificado($prontuario);
        
        if (!$certificado) {
            $resultado['erros']++;
            $resultado['detalhes'][] = [
                'prontuario' => $prontuario,
                'email' => $email,
                'sucesso' => false,
                'mensagem' => 'Certificado não encontrado'
            ];
            
            logMessage("Certificado não encontrado para prontuário {$prontuario}", 'WARNING');
            continue;
        }
        
        // Gera protocolo único
        $protocolo = gerarProtocolo();
        
        // Obtém informações do certificado
        $infoCertificado = obterInfoCertificado($certificado);
        
        // Envia o e-mail
        $envio = enviarCertificado($email, $prontuario, $certificado, $protocolo);
        
        // Prepara dados para salvar no protocolo
        $dadosProtocolo = [
            'prontuario' => $prontuario,
            'email' => $email,
            'status' => $envio['sucesso'] ? 'ENVIADO' : 'ERRO',
            'certificado' => $infoCertificado['nome'],
            'hash' => $infoCertificado['hash'],
            'tamanho' => $infoCertificado['tamanho']
        ];
        
        if (!$envio['sucesso']) {
            $dadosProtocolo['erro'] = $envio['mensagem'];
        }
        
        // Salva o protocolo
        salvarProtocolo($protocolo, $dadosProtocolo);
        
        // Atualiza estatísticas
        if ($envio['sucesso']) {
            $resultado['enviados']++;
        } else {
            $resultado['erros']++;
        }
        
        // Adiciona detalhes ao resultado
        $resultado['detalhes'][] = [
            'prontuario' => $prontuario,
            'email' => $email,
            'sucesso' => $envio['sucesso'],
            'mensagem' => $envio['mensagem'],
            'protocolo' => $protocolo
        ];
        
        // Pequeno delay entre envios para evitar sobrecarga
        usleep(100000); // 0.1 segundo
    }
    
    logMessage("Envio em lote finalizado: {$resultado['enviados']} enviados, {$resultado['erros']} erros", 'INFO');
    
    return $resultado;
}

/**
 * Envia e-mail de teste para verificar configuração
 * 
 * @param string $emailTeste E-mail para teste
 * @return array Resultado do teste
 */
function enviarEmailTeste($emailTeste) {
    $resultado = [
        'sucesso' => false,
        'mensagem' => ''
    ];
    
    if (!validarEmail($emailTeste)) {
        $resultado['mensagem'] = 'E-mail inválido';
        return $resultado;
    }
    
    $assunto = "Teste - " . MAIL_SUBJECT;
    $mensagem = "Este é um e-mail de teste do sistema de envio de certificados.\n\n";
    $mensagem .= "Se você recebeu esta mensagem, o sistema está configurado corretamente.\n\n";
    $mensagem .= "Data/Hora: " . date('d/m/Y H:i:s') . "\n";
    $mensagem .= "Sistema: " . SYSTEM_NAME . " v" . SYSTEM_VERSION;
    
    $headers = "From: " . MAIL_FROM_NAME . " <" . MAIL_FROM . ">";
    
    if (mail($emailTeste, $assunto, $mensagem, $headers)) {
        $resultado['sucesso'] = true;
        $resultado['mensagem'] = 'E-mail de teste enviado com sucesso';
        logMessage("E-mail de teste enviado para {$emailTeste}", 'SUCCESS');
    } else {
        $resultado['mensagem'] = 'Falha ao enviar e-mail de teste';
        logMessage("Falha ao enviar e-mail de teste para {$emailTeste}", 'ERROR');
    }
    
    return $resultado;
=======
<?php
/**
 * EXTENSAO_SUAP - Funções de E-mail
 * Versão: 0.1
 * 
 * Funções para envio de e-mails com certificados anexados.
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/functions.php';

/**
 * Envia e-mail com certificado anexado
 * 
 * @param string $destinatario E-mail do destinatário
 * @param string $prontuario Prontuário do estudante
 * @param string $caminhoCertificado Caminho completo do certificado
 * @param string $protocolo Número do protocolo de envio
 * @return array Resultado do envio com status e mensagem
 */
function enviarCertificado($destinatario, $prontuario, $caminhoCertificado, $protocolo) {
    $resultado = [
        'sucesso' => false,
        'mensagem' => '',
        'protocolo' => $protocolo
    ];
    
    // Verifica se está em modo de teste
    if (TEST_MODE) {
        logMessage("MODO TESTE: Simulando envio para {$destinatario} (Prontuário: {$prontuario})", 'INFO');
        $resultado['sucesso'] = true;
        $resultado['mensagem'] = 'Envio simulado com sucesso (modo teste)';
        return $resultado;
    }
    
    // Valida e-mail
    if (!validarEmail($destinatario)) {
        $resultado['mensagem'] = 'E-mail inválido';
        logMessage("Tentativa de envio para e-mail inválido: {$destinatario}", 'ERROR');
        return $resultado;
    }
    
    // Verifica se o certificado existe
    if (!file_exists($caminhoCertificado)) {
        $resultado['mensagem'] = 'Certificado não encontrado';
        logMessage("Certificado não encontrado: {$caminhoCertificado}", 'ERROR');
        return $resultado;
    }
    
    try {
        // Lê o conteúdo do certificado
        $conteudoCertificado = file_get_contents($caminhoCertificado);
        if ($conteudoCertificado === false) {
            throw new Exception('Erro ao ler arquivo do certificado');
        }
        
        // Prepara o corpo do e-mail substituindo variáveis
        $corpoHTML = str_replace(
            ['{{PRONTUARIO}}', '{{PROTOCOLO}}'],
            [$prontuario, $protocolo],
            MAIL_BODY_HTML
        );
        
        $corpoTexto = str_replace(
            ['{{PRONTUARIO}}', '{{PROTOCOLO}}'],
            [$prontuario, $protocolo],
            MAIL_BODY_TEXT
        );
        
        // Envia o e-mail com anexo
        $enviado = enviarEmailComAnexo(
            $destinatario,
            MAIL_SUBJECT,
            $corpoHTML,
            $corpoTexto,
            $caminhoCertificado,
            "{$prontuario}.pdf"
        );
        
        if ($enviado) {
            $resultado['sucesso'] = true;
            $resultado['mensagem'] = 'E-mail enviado com sucesso';
            logMessage("Certificado enviado para {$destinatario} (Prontuário: {$prontuario}) - Protocolo: {$protocolo}", 'SUCCESS');
        } else {
            $resultado['mensagem'] = 'Falha ao enviar e-mail';
            logMessage("Falha ao enviar e-mail para {$destinatario} (Prontuário: {$prontuario})", 'ERROR');
        }
        
    } catch (Exception $e) {
        $resultado['mensagem'] = 'Erro: ' . $e->getMessage();
        logMessage("Erro ao enviar e-mail para {$destinatario}: " . $e->getMessage(), 'ERROR');
    }
    
    return $resultado;
}

/**
 * Envia e-mail com anexo usando a função mail() do PHP
 * 
 * @param string $para E-mail do destinatário
 * @param string $assunto Assunto do e-mail
 * @param string $mensagemHTML Corpo do e-mail em HTML
 * @param string $mensagemTexto Corpo do e-mail em texto plano
 * @param string $caminhoArquivo Caminho do arquivo a anexar
 * @param string $nomeArquivo Nome do arquivo no anexo
 * @return bool Retorna true se enviou com sucesso
 */
function enviarEmailComAnexo($para, $assunto, $mensagemHTML, $mensagemTexto, $caminhoArquivo, $nomeArquivo) {
    // Boundary único para separar as partes do e-mail
    $boundary = md5(uniqid(time()));
    
    // Headers do e-mail
    $headers = [];
    $headers[] = "From: " . MAIL_FROM_NAME . " <" . MAIL_FROM . ">";
    $headers[] = "Reply-To: " . MAIL_FROM;
    $headers[] = "MIME-Version: 1.0";
    $headers[] = "Content-Type: multipart/mixed; boundary=\"{$boundary}\"";
    $headers[] = "X-Mailer: PHP/" . phpversion();
    
    // Corpo do e-mail - Parte 1: Versões texto e HTML
    $message = "--{$boundary}\n";
    $message .= "Content-Type: multipart/alternative; boundary=\"alt-{$boundary}\"\n\n";
    
    // Versão texto plano
    $message .= "--alt-{$boundary}\n";
    $message .= "Content-Type: text/plain; charset=UTF-8\n";
    $message .= "Content-Transfer-Encoding: 7bit\n\n";
    $message .= $mensagemTexto . "\n\n";
    
    // Versão HTML
    $message .= "--alt-{$boundary}\n";
    $message .= "Content-Type: text/html; charset=UTF-8\n";
    $message .= "Content-Transfer-Encoding: 7bit\n\n";
    $message .= $mensagemHTML . "\n\n";
    
    $message .= "--alt-{$boundary}--\n\n";
    
    // Corpo do e-mail - Parte 2: Anexo
    if (file_exists($caminhoArquivo)) {
        $conteudoArquivo = chunk_split(base64_encode(file_get_contents($caminhoArquivo)));
        
        $message .= "--{$boundary}\n";
        $message .= "Content-Type: application/pdf; name=\"{$nomeArquivo}\"\n";
        $message .= "Content-Transfer-Encoding: base64\n";
        $message .= "Content-Disposition: attachment; filename=\"{$nomeArquivo}\"\n\n";
        $message .= $conteudoArquivo . "\n\n";
    }
    
    $message .= "--{$boundary}--";
    
    // Envia o e-mail
    return mail($para, $assunto, $message, implode("\r\n", $headers));
}

/**
 * Processa envio em lote de certificados
 * 
 * @param array $estudantes Array com dados dos estudantes
 * @return array Resultado do processamento com estatísticas
 */
function processarEnvioLote($estudantes) {
    $resultado = [
        'total' => count($estudantes),
        'enviados' => 0,
        'erros' => 0,
        'detalhes' => []
    ];
    
    logMessage("Iniciando envio em lote para " . count($estudantes) . " estudantes", 'INFO');
    
    foreach ($estudantes as $estudante) {
        $prontuario = $estudante['prontuario'];
        $email = $estudante['email'];
        
        // Busca o certificado
        $certificado = buscarCertificado($prontuario);
        
        if (!$certificado) {
            $resultado['erros']++;
            $resultado['detalhes'][] = [
                'prontuario' => $prontuario,
                'email' => $email,
                'sucesso' => false,
                'mensagem' => 'Certificado não encontrado'
            ];
            
            logMessage("Certificado não encontrado para prontuário {$prontuario}", 'WARNING');
            continue;
        }
        
        // Gera protocolo único
        $protocolo = gerarProtocolo();
        
        // Obtém informações do certificado
        $infoCertificado = obterInfoCertificado($certificado);
        
        // Envia o e-mail
        $envio = enviarCertificado($email, $prontuario, $certificado, $protocolo);
        
        // Prepara dados para salvar no protocolo
        $dadosProtocolo = [
            'prontuario' => $prontuario,
            'email' => $email,
            'status' => $envio['sucesso'] ? 'ENVIADO' : 'ERRO',
            'certificado' => $infoCertificado['nome'],
            'hash' => $infoCertificado['hash'],
            'tamanho' => $infoCertificado['tamanho']
        ];
        
        if (!$envio['sucesso']) {
            $dadosProtocolo['erro'] = $envio['mensagem'];
        }
        
        // Salva o protocolo
        salvarProtocolo($protocolo, $dadosProtocolo);
        
        // Atualiza estatísticas
        if ($envio['sucesso']) {
            $resultado['enviados']++;
        } else {
            $resultado['erros']++;
        }
        
        // Adiciona detalhes ao resultado
        $resultado['detalhes'][] = [
            'prontuario' => $prontuario,
            'email' => $email,
            'sucesso' => $envio['sucesso'],
            'mensagem' => $envio['mensagem'],
            'protocolo' => $protocolo
        ];
        
        // Pequeno delay entre envios para evitar sobrecarga
        usleep(100000); // 0.1 segundo
    }
    
    logMessage("Envio em lote finalizado: {$resultado['enviados']} enviados, {$resultado['erros']} erros", 'INFO');
    
    return $resultado;
}

/**
 * Envia e-mail de teste para verificar configuração
 * 
 * @param string $emailTeste E-mail para teste
 * @return array Resultado do teste
 */
function enviarEmailTeste($emailTeste) {
    $resultado = [
        'sucesso' => false,
        'mensagem' => ''
    ];
    
    if (!validarEmail($emailTeste)) {
        $resultado['mensagem'] = 'E-mail inválido';
        return $resultado;
    }
    
    $assunto = "Teste - " . MAIL_SUBJECT;
    $mensagem = "Este é um e-mail de teste do sistema de envio de certificados.\n\n";
    $mensagem .= "Se você recebeu esta mensagem, o sistema está configurado corretamente.\n\n";
    $mensagem .= "Data/Hora: " . date('d/m/Y H:i:s') . "\n";
    $mensagem .= "Sistema: " . SYSTEM_NAME . " v" . SYSTEM_VERSION;
    
    $headers = "From: " . MAIL_FROM_NAME . " <" . MAIL_FROM . ">";
    
    if (mail($emailTeste, $assunto, $mensagem, $headers)) {
        $resultado['sucesso'] = true;
        $resultado['mensagem'] = 'E-mail de teste enviado com sucesso';
        logMessage("E-mail de teste enviado para {$emailTeste}", 'SUCCESS');
    } else {
        $resultado['mensagem'] = 'Falha ao enviar e-mail de teste';
        logMessage("Falha ao enviar e-mail de teste para {$emailTeste}", 'ERROR');
    }
    
    return $resultado;
>>>>>>> 02be3d6e93f4b7aa59e02c72bb147de6dcd9d180
}