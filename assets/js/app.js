<<<<<<< HEAD
/**
 * EXTENSAO_SUAP - JavaScript da Interface
 * Versão: 0.1
 * 
 * Controla a interface e o processo de envio de certificados.
 */

// ========================================
// VARIÁVEIS GLOBAIS
// ========================================

let estudantes = [];
let enviandoCertificados = false;

// ========================================
// INICIALIZAÇÃO
// ========================================

/**
 * Inicializa o sistema quando a página carrega
 */
document.addEventListener('DOMContentLoaded', function() {
    console.log('Sistema EXTENSAO_SUAP inicializado');
    
    // Carrega os estudantes
    carregarEstudantes();
    
    // Adiciona evento ao botão de enviar
    const btnEnviar = document.getElementById('btnEnviar');
    if (btnEnviar) {
        btnEnviar.addEventListener('click', iniciarEnvio);
    }
});

// ========================================
// CARREGAMENTO DE DADOS
// ========================================

/**
 * Carrega lista de estudantes do servidor
 */
function carregarEstudantes() {
    // Busca os dados dos estudantes via AJAX
    fetch('processar.php?acao=listar')
        .then(response => response.json())
        .then(data => {
            if (data.sucesso) {
                estudantes = data.estudantes;
                atualizarInterface(data);
            } else {
                exibirAlerta('error', 'Erro ao carregar estudantes: ' + data.mensagem);
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            exibirAlerta('error', 'Erro ao conectar com o servidor');
        });
}

/**
 * Atualiza a interface com os dados carregados
 * 
 * @param {Object} data Dados retornados do servidor
 */
function atualizarInterface(data) {
    // Atualiza estatísticas
    document.getElementById('totalEstudantes').textContent = data.estatisticas.total;
    document.getElementById('totalComCertificado').textContent = data.estatisticas.com_certificado;
    document.getElementById('totalSemCertificado').textContent = data.estatisticas.sem_certificado;
    document.getElementById('percentualPronto').textContent = data.estatisticas.percentual_pronto + '%';
    
    // Renderiza tabela
    renderizarTabela(data.estudantes);
    
    // Habilita/desabilita botão de envio
    const btnEnviar = document.getElementById('btnEnviar');
    if (data.estatisticas.com_certificado > 0) {
        btnEnviar.disabled = false;
    } else {
        btnEnviar.disabled = true;
    }
}

// ========================================
// RENDERIZAÇÃO DA TABELA
// ========================================

/**
 * Renderiza a tabela de estudantes
 * 
 * @param {Array} estudantes Array com dados dos estudantes
 */
function renderizarTabela(estudantes) {
    const tbody = document.getElementById('tabelaEstudantes');
    tbody.innerHTML = '';
    
    if (estudantes.length === 0) {
        tbody.innerHTML = '<tr><td colspan="4" class="text-center">Nenhum estudante encontrado</td></tr>';
        return;
    }
    
    estudantes.forEach((estudante, index) => {
        const tr = document.createElement('tr');
        
        // Número sequencial
        const tdNumero = document.createElement('td');
        tdNumero.textContent = index + 1;
        tr.appendChild(tdNumero);
        
        // Prontuário
        const tdProntuario = document.createElement('td');
        tdProntuario.textContent = estudante.prontuario;
        tr.appendChild(tdProntuario);
        
        // E-mail
        const tdEmail = document.createElement('td');
        tdEmail.textContent = estudante.email;
        tr.appendChild(tdEmail);
        
        // Status
        const tdStatus = document.createElement('td');
        const badge = document.createElement('span');
        badge.className = 'status-badge';
        
        if (estudante.tem_certificado) {
            badge.classList.add('status-disponivel');
            badge.textContent = '✓ Disponível';
        } else {
            badge.classList.add('status-faltando');
            badge.textContent = '✗ Faltando';
        }
        
        tdStatus.appendChild(badge);
        tr.appendChild(tdStatus);
        
        tbody.appendChild(tr);
    });
}

// ========================================
// PROCESSO DE ENVIO
// ========================================

/**
 * Inicia o processo de envio de certificados
 */
function iniciarEnvio() {
    // Verifica se já está enviando
    if (enviandoCertificados) {
        return;
    }
    
    // Confirma ação com o usuário
    const totalEnviar = estudantes.filter(e => e.tem_certificado).length;
    
    if (!confirm(`Confirma o envio de ${totalEnviar} certificado(s)?`)) {
        return;
    }
    
    // Desabilita botão
    const btnEnviar = document.getElementById('btnEnviar');
    btnEnviar.disabled = true;
    btnEnviar.textContent = 'Enviando...';
    
    enviandoCertificados = true;
    
    // Abre modal de progresso
    abrirModal();
    
    // Inicia o envio
    enviarCertificados();
}

/**
 * Processa o envio dos certificados
 */
function enviarCertificados() {
    // Filtra apenas estudantes que têm certificado
    const estudantesParaEnviar = estudantes.filter(e => e.tem_certificado);
    
    if (estudantesParaEnviar.length === 0) {
        fecharModal();
        exibirAlerta('warning', 'Nenhum certificado disponível para envio');
        enviandoCertificados = false;
        return;
    }
    
    // Prepara dados para envio
    const dados = {
        acao: 'enviar',
        estudantes: estudantesParaEnviar.map(e => ({
            prontuario: e.prontuario,
            email: e.email
        }))
    };
    
    // Envia requisição
    fetch('processar.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(dados)
    })
    .then(response => response.json())
    .then(data => {
        processarResultado(data);
    })
    .catch(error => {
        console.error('Erro:', error);
        exibirAlerta('error', 'Erro ao processar envio');
        fecharModal();
    })
    .finally(() => {
        enviandoCertificados = false;
        const btnEnviar = document.getElementById('btnEnviar');
        btnEnviar.textContent = 'Enviar Certificados';
    });
}

/**
 * Processa o resultado do envio
 * 
 * @param {Object} resultado Resultado retornado do servidor
 */
function processarResultado(resultado) {
    // Atualiza progresso
    atualizarProgresso(100);
    
    // Exibe log de envios
    resultado.detalhes.forEach(detalhe => {
        adicionarLogEntry(
            detalhe.prontuario,
            detalhe.email,
            detalhe.sucesso,
            detalhe.mensagem,
            detalhe.protocolo
        );
    });
    
    // Aguarda um pouco antes de fechar o modal
    setTimeout(() => {
        fecharModal();
        
        // Exibe mensagem de resultado
        if (resultado.erros > 0) {
            exibirAlerta('warning', 
                `Envio concluído: ${resultado.enviados} sucesso, ${resultado.erros} erro(s). Verifique os logs.`
            );
        } else {
            exibirAlerta('success', 
                `Todos os ${resultado.enviados} certificado(s) foram enviados com sucesso!`
            );
        }
    }, 2000);
}

// ========================================
// CONTROLE DO MODAL
// ========================================

/**
 * Abre o modal de progresso
 */
function abrirModal() {
    const modal = document.getElementById('modalProgresso');
    modal.classList.add('active');
    
    // Limpa o log
    const logContainer = document.getElementById('logEnvios');
    logContainer.innerHTML = '';
    
    // Reseta progresso
    atualizarProgresso(0);
}

/**
 * Fecha o modal de progresso
 */
function fecharModal() {
    const modal = document.getElementById('modalProgresso');
    modal.classList.remove('active');
}

/**
 * Atualiza a barra de progresso
 * 
 * @param {Number} percentual Percentual de progresso (0-100)
 */
function atualizarProgresso(percentual) {
    const progressBar = document.getElementById('progressBar');
    progressBar.style.width = percentual + '%';
    progressBar.textContent = Math.round(percentual) + '%';
}

/**
 * Adiciona entrada no log de envios
 * 
 * @param {String} prontuario Prontuário do estudante
 * @param {String} email E-mail do estudante
 * @param {Boolean} sucesso Se o envio foi bem-sucedido
 * @param {String} mensagem Mensagem do resultado
 * @param {String} protocolo Número do protocolo
 */
function adicionarLogEntry(prontuario, email, sucesso, mensagem, protocolo) {
    const logContainer = document.getElementById('logEnvios');
    
    const entry = document.createElement('div');
    entry.className = 'log-entry ' + (sucesso ? 'success' : 'error');
    
    const icon = sucesso ? '✓' : '✗';
    const protocoloTexto = sucesso ? ` | Protocolo: ${protocolo}` : '';
    
    entry.textContent = `${icon} ${prontuario} (${email}): ${mensagem}${protocoloTexto}`;
    
    logContainer.appendChild(entry);
    
    // Scroll automático para o final
    logContainer.scrollTop = logContainer.scrollHeight;
}

// ========================================
// ALERTAS E MENSAGENS
// ========================================

/**
 * Exibe alerta na interface
 * 
 * @param {String} tipo Tipo do alerta (success, error, warning, info)
 * @param {String} mensagem Mensagem a ser exibida
 */
function exibirAlerta(tipo, mensagem) {
    // Remove alertas anteriores
    const alertasAntigos = document.querySelectorAll('.alert');
    alertasAntigos.forEach(alerta => alerta.remove());
    
    // Cria novo alerta
    const alerta = document.createElement('div');
    alerta.className = `alert alert-${tipo}`;
    alerta.innerHTML = `<strong>${mensagem}</strong>`;
    
    // Insere no início do conteúdo
    const content = document.querySelector('.content');
    content.insertBefore(alerta, content.firstChild);
    
    // Remove automaticamente após 5 segundos
    setTimeout(() => {
        alerta.remove();
    }, 5000);
}

// ========================================
// UTILITÁRIOS
// ========================================

/**
 * Formata número com separador de milhares
 * 
 * @param {Number} numero Número a ser formatado
 * @return {String} Número formatado
 */
function formatarNumero(numero) {
    return numero.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
}

/**
 * Valida formato de e-mail
 * 
 * @param {String} email E-mail a ser validado
 * @return {Boolean} True se válido
 */
function validarEmail(email) {
    const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return regex.test(email);
}

/**
 * Log para debug (apenas em desenvolvimento)
 * 
 * @param {String} mensagem Mensagem de debug
 */
function debugLog(mensagem) {
    if (window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1') {
        console.log('[DEBUG]', mensagem);
    }
=======
/**
 * EXTENSAO_SUAP - JavaScript da Interface
 * Versão: 0.1
 * 
 * Controla a interface e o processo de envio de certificados.
 */

// ========================================
// VARIÁVEIS GLOBAIS
// ========================================

let estudantes = [];
let enviandoCertificados = false;

// ========================================
// INICIALIZAÇÃO
// ========================================

/**
 * Inicializa o sistema quando a página carrega
 */
document.addEventListener('DOMContentLoaded', function() {
    console.log('Sistema EXTENSAO_SUAP inicializado');
    
    // Carrega os estudantes
    carregarEstudantes();
    
    // Adiciona evento ao botão de enviar
    const btnEnviar = document.getElementById('btnEnviar');
    if (btnEnviar) {
        btnEnviar.addEventListener('click', iniciarEnvio);
    }
});

// ========================================
// CARREGAMENTO DE DADOS
// ========================================

/**
 * Carrega lista de estudantes do servidor
 */
function carregarEstudantes() {
    // Busca os dados dos estudantes via AJAX
    fetch('processar.php?acao=listar')
        .then(response => response.json())
        .then(data => {
            if (data.sucesso) {
                estudantes = data.estudantes;
                atualizarInterface(data);
            } else {
                exibirAlerta('error', 'Erro ao carregar estudantes: ' + data.mensagem);
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            exibirAlerta('error', 'Erro ao conectar com o servidor');
        });
}

/**
 * Atualiza a interface com os dados carregados
 * 
 * @param {Object} data Dados retornados do servidor
 */
function atualizarInterface(data) {
    // Atualiza estatísticas
    document.getElementById('totalEstudantes').textContent = data.estatisticas.total;
    document.getElementById('totalComCertificado').textContent = data.estatisticas.com_certificado;
    document.getElementById('totalSemCertificado').textContent = data.estatisticas.sem_certificado;
    document.getElementById('percentualPronto').textContent = data.estatisticas.percentual_pronto + '%';
    
    // Renderiza tabela
    renderizarTabela(data.estudantes);
    
    // Habilita/desabilita botão de envio
    const btnEnviar = document.getElementById('btnEnviar');
    if (data.estatisticas.com_certificado > 0) {
        btnEnviar.disabled = false;
    } else {
        btnEnviar.disabled = true;
    }
}

// ========================================
// RENDERIZAÇÃO DA TABELA
// ========================================

/**
 * Renderiza a tabela de estudantes
 * 
 * @param {Array} estudantes Array com dados dos estudantes
 */
function renderizarTabela(estudantes) {
    const tbody = document.getElementById('tabelaEstudantes');
    tbody.innerHTML = '';
    
    if (estudantes.length === 0) {
        tbody.innerHTML = '<tr><td colspan="4" class="text-center">Nenhum estudante encontrado</td></tr>';
        return;
    }
    
    estudantes.forEach((estudante, index) => {
        const tr = document.createElement('tr');
        
        // Número sequencial
        const tdNumero = document.createElement('td');
        tdNumero.textContent = index + 1;
        tr.appendChild(tdNumero);
        
        // Prontuário
        const tdProntuario = document.createElement('td');
        tdProntuario.textContent = estudante.prontuario;
        tr.appendChild(tdProntuario);
        
        // E-mail
        const tdEmail = document.createElement('td');
        tdEmail.textContent = estudante.email;
        tr.appendChild(tdEmail);
        
        // Status
        const tdStatus = document.createElement('td');
        const badge = document.createElement('span');
        badge.className = 'status-badge';
        
        if (estudante.tem_certificado) {
            badge.classList.add('status-disponivel');
            badge.textContent = '✓ Disponível';
        } else {
            badge.classList.add('status-faltando');
            badge.textContent = '✗ Faltando';
        }
        
        tdStatus.appendChild(badge);
        tr.appendChild(tdStatus);
        
        tbody.appendChild(tr);
    });
}

// ========================================
// PROCESSO DE ENVIO
// ========================================

/**
 * Inicia o processo de envio de certificados
 */
function iniciarEnvio() {
    // Verifica se já está enviando
    if (enviandoCertificados) {
        return;
    }
    
    // Confirma ação com o usuário
    const totalEnviar = estudantes.filter(e => e.tem_certificado).length;
    
    if (!confirm(`Confirma o envio de ${totalEnviar} certificado(s)?`)) {
        return;
    }
    
    // Desabilita botão
    const btnEnviar = document.getElementById('btnEnviar');
    btnEnviar.disabled = true;
    btnEnviar.textContent = 'Enviando...';
    
    enviandoCertificados = true;
    
    // Abre modal de progresso
    abrirModal();
    
    // Inicia o envio
    enviarCertificados();
}

/**
 * Processa o envio dos certificados
 */
function enviarCertificados() {
    // Filtra apenas estudantes que têm certificado
    const estudantesParaEnviar = estudantes.filter(e => e.tem_certificado);
    
    if (estudantesParaEnviar.length === 0) {
        fecharModal();
        exibirAlerta('warning', 'Nenhum certificado disponível para envio');
        enviandoCertificados = false;
        return;
    }
    
    // Prepara dados para envio
    const dados = {
        acao: 'enviar',
        estudantes: estudantesParaEnviar.map(e => ({
            prontuario: e.prontuario,
            email: e.email
        }))
    };
    
    // Envia requisição
    fetch('processar.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(dados)
    })
    .then(response => response.json())
    .then(data => {
        processarResultado(data);
    })
    .catch(error => {
        console.error('Erro:', error);
        exibirAlerta('error', 'Erro ao processar envio');
        fecharModal();
    })
    .finally(() => {
        enviandoCertificados = false;
        const btnEnviar = document.getElementById('btnEnviar');
        btnEnviar.textContent = 'Enviar Certificados';
    });
}

/**
 * Processa o resultado do envio
 * 
 * @param {Object} resultado Resultado retornado do servidor
 */
function processarResultado(resultado) {
    // Atualiza progresso
    atualizarProgresso(100);
    
    // Exibe log de envios
    resultado.detalhes.forEach(detalhe => {
        adicionarLogEntry(
            detalhe.prontuario,
            detalhe.email,
            detalhe.sucesso,
            detalhe.mensagem,
            detalhe.protocolo
        );
    });
    
    // Aguarda um pouco antes de fechar o modal
    setTimeout(() => {
        fecharModal();
        
        // Exibe mensagem de resultado
        if (resultado.erros > 0) {
            exibirAlerta('warning', 
                `Envio concluído: ${resultado.enviados} sucesso, ${resultado.erros} erro(s). Verifique os logs.`
            );
        } else {
            exibirAlerta('success', 
                `Todos os ${resultado.enviados} certificado(s) foram enviados com sucesso!`
            );
        }
    }, 2000);
}

// ========================================
// CONTROLE DO MODAL
// ========================================

/**
 * Abre o modal de progresso
 */
function abrirModal() {
    const modal = document.getElementById('modalProgresso');
    modal.classList.add('active');
    
    // Limpa o log
    const logContainer = document.getElementById('logEnvios');
    logContainer.innerHTML = '';
    
    // Reseta progresso
    atualizarProgresso(0);
}

/**
 * Fecha o modal de progresso
 */
function fecharModal() {
    const modal = document.getElementById('modalProgresso');
    modal.classList.remove('active');
}

/**
 * Atualiza a barra de progresso
 * 
 * @param {Number} percentual Percentual de progresso (0-100)
 */
function atualizarProgresso(percentual) {
    const progressBar = document.getElementById('progressBar');
    progressBar.style.width = percentual + '%';
    progressBar.textContent = Math.round(percentual) + '%';
}

/**
 * Adiciona entrada no log de envios
 * 
 * @param {String} prontuario Prontuário do estudante
 * @param {String} email E-mail do estudante
 * @param {Boolean} sucesso Se o envio foi bem-sucedido
 * @param {String} mensagem Mensagem do resultado
 * @param {String} protocolo Número do protocolo
 */
function adicionarLogEntry(prontuario, email, sucesso, mensagem, protocolo) {
    const logContainer = document.getElementById('logEnvios');
    
    const entry = document.createElement('div');
    entry.className = 'log-entry ' + (sucesso ? 'success' : 'error');
    
    const icon = sucesso ? '✓' : '✗';
    const protocoloTexto = sucesso ? ` | Protocolo: ${protocolo}` : '';
    
    entry.textContent = `${icon} ${prontuario} (${email}): ${mensagem}${protocoloTexto}`;
    
    logContainer.appendChild(entry);
    
    // Scroll automático para o final
    logContainer.scrollTop = logContainer.scrollHeight;
}

// ========================================
// ALERTAS E MENSAGENS
// ========================================

/**
 * Exibe alerta na interface
 * 
 * @param {String} tipo Tipo do alerta (success, error, warning, info)
 * @param {String} mensagem Mensagem a ser exibida
 */
function exibirAlerta(tipo, mensagem) {
    // Remove alertas anteriores
    const alertasAntigos = document.querySelectorAll('.alert');
    alertasAntigos.forEach(alerta => alerta.remove());
    
    // Cria novo alerta
    const alerta = document.createElement('div');
    alerta.className = `alert alert-${tipo}`;
    alerta.innerHTML = `<strong>${mensagem}</strong>`;
    
    // Insere no início do conteúdo
    const content = document.querySelector('.content');
    content.insertBefore(alerta, content.firstChild);
    
    // Remove automaticamente após 5 segundos
    setTimeout(() => {
        alerta.remove();
    }, 5000);
}

// ========================================
// UTILITÁRIOS
// ========================================

/**
 * Formata número com separador de milhares
 * 
 * @param {Number} numero Número a ser formatado
 * @return {String} Número formatado
 */
function formatarNumero(numero) {
    return numero.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
}

/**
 * Valida formato de e-mail
 * 
 * @param {String} email E-mail a ser validado
 * @return {Boolean} True se válido
 */
function validarEmail(email) {
    const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return regex.test(email);
}

/**
 * Log para debug (apenas em desenvolvimento)
 * 
 * @param {String} mensagem Mensagem de debug
 */
function debugLog(mensagem) {
    if (window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1') {
        console.log('[DEBUG]', mensagem);
    }
>>>>>>> 02be3d6e93f4b7aa59e02c72bb147de6dcd9d180
}