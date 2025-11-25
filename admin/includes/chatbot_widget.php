<!-- Chatbot Widget Flotante -->
<style>
    .chatbot-float-btn {
        position: fixed;
        bottom: 20px;
        right: 20px;
        width: 50px;
        height: 50px;
        border-radius: 50%;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border: none;
        cursor: pointer;
        box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
        z-index: 999;
        transition: all 0.3s ease;
    }
    
    .chatbot-float-btn:hover {
        transform: scale(1.1);
        box-shadow: 0 6px 20px rgba(102, 126, 234, 0.6);
    }
    
    .chatbot-float-btn:active {
        transform: scale(0.95);
    }
    
    .chatbot-modal {
        display: none;
        position: fixed;
        bottom: 85px;
        right: 20px;
        width: 380px;
        height: 500px;
        background: white;
        border-radius: 15px;
        box-shadow: 0 5px 40px rgba(0, 0, 0, 0.16);
        z-index: 998;
        flex-direction: column;
        animation: slideUp 0.3s ease;
    }
    
    .chatbot-modal.active {
        display: flex;
    }
    
    @keyframes slideUp {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    .chatbot-modal-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 15px;
        border-radius: 15px 15px 0 0;
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-weight: bold;
    }
    
    .chatbot-modal-close {
        background: rgba(255, 255, 255, 0.2);
        border: none;
        color: white;
        width: 30px;
        height: 30px;
        border-radius: 50%;
        cursor: pointer;
        font-size: 18px;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.2s ease;
    }
    
    .chatbot-modal-close:hover {
        background: rgba(255, 255, 255, 0.3);
    }
    
    .chatbot-modal-body {
        flex: 1;
        overflow-y: auto;
        padding: 15px;
        background: #f8f9fa;
    }
    
    .chatbot-modal-footer {
        padding: 15px;
        border-top: 1px solid #dee2e6;
        background: white;
        border-radius: 0 0 15px 15px;
    }
    
    .chatbot-modal-input {
        display: flex;
        gap: 10px;
    }
    
    .chatbot-modal-input input {
        flex: 1;
        padding: 10px 15px;
        border: 2px solid #dee2e6;
        border-radius: 20px;
        font-size: 14px;
        transition: border-color 0.2s;
    }
    
    .chatbot-modal-input input:focus {
        outline: none;
        border-color: #667eea;
    }
    
    .chatbot-modal-input button {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border: none;
        padding: 10px 15px;
        border-radius: 50%;
        cursor: pointer;
        width: 40px;
        height: 40px;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.2s;
    }
    
    .chatbot-modal-input button:hover {
        transform: scale(1.05);
    }
    
    .chatbot-mic-btn {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
        color: white;
        border: none;
        padding: 10px 15px;
        border-radius: 50%;
        cursor: pointer;
        width: 40px;
        height: 40px;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.2s;
        font-size: 16px;
    }
    
    .chatbot-mic-btn:hover {
        transform: scale(1.05);
    }
    
    .chatbot-mic-btn.recording {
        background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%) !important;
        animation: pulse 1.5s infinite;
    }
    
    @keyframes pulse {
        0%, 100% {
            box-shadow: 0 0 0 0 rgba(245, 87, 108, 0.7);
        }
        50% {
            box-shadow: 0 0 0 10px rgba(245, 87, 108, 0);
        }
    }
    
    .chat-message {
        margin-bottom: 10px;
        display: flex;
        animation: slideIn 0.3s ease-out;
    }
    
    @keyframes slideIn {
        from {
            opacity: 0;
            transform: translateY(10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    .mensaje-usuario {
        justify-content: flex-end;
        width: 100%;
    }
    
    .mensaje-usuario .bubble {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 8px 12px;
        border-radius: 12px 12px 0 12px;
        max-width: 85%;
        word-wrap: break-word;
        font-size: 13px;
    }
    
    .mensaje-bot {
        justify-content: flex-start;
        width: 100%;
    }
    
    .mensaje-bot .bubble {
        background: white;
        color: #333;
        padding: 8px 12px;
        border-radius: 12px 12px 12px 0;
        max-width: 85%;
        word-wrap: break-word;
        font-size: 13px;
        border: 1px solid #dee2e6;
    }
</style>

<!-- Botón Flotante -->
<button class="chatbot-float-btn" id="chatbotFloatBtn" title="Abrir Chatbot">
    💬
</button>

<!-- Modal del Chatbot -->
<div class="chatbot-modal" id="chatbotModal">
    <div class="chatbot-modal-header">
        🤖 Asistente Inteligente
        <button class="chatbot-modal-close" id="chatbotCloseBtn">✕</button>
    </div>
    <div class="chatbot-modal-body" id="chatbotMessages">
        <div class="chat-message mensaje-bot">
            <div class="bubble">📦 ¡Bienvenido a Hermes Express! Puedo ayudarte con tu operación logística. Pregunta por paquetes, entregas, repartidores o ingresos.</div>
        </div>
    </div>
    <div class="chatbot-modal-footer">
        <form id="chatbotForm" class="chatbot-modal-input">
            <input 
                type="text" 
                id="chatbotInput" 
                placeholder="Escribe o habla..."
                autocomplete="off"
            >
            <button type="button" id="micBtn" class="chatbot-mic-btn" title="Hablar (Micrófono)">🎤</button>
            <button type="submit" title="Enviar">📤</button>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const floatBtn = document.getElementById('chatbotFloatBtn');
    const modal = document.getElementById('chatbotModal');
    const closeBtn = document.getElementById('chatbotCloseBtn');
    const form = document.getElementById('chatbotForm');
    const input = document.getElementById('chatbotInput');
    const micBtn = document.getElementById('micBtn');
    const messagesContainer = document.getElementById('chatbotMessages');
    let recognition = null;
    let isRecording = false;
    
    // Inicializar Speech Recognition
    const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
    if (SpeechRecognition) {
        recognition = new SpeechRecognition();
        recognition.lang = 'es-ES';
        recognition.continuous = false;
        recognition.interimResults = false;
        
        recognition.onstart = function() {
            isRecording = true;
            micBtn.classList.add('recording');
            micBtn.textContent = '🔴';
            input.value = 'Escuchando...';
        };
        
        recognition.onend = function() {
            isRecording = false;
            micBtn.classList.remove('recording');
            micBtn.textContent = '🎤';
        };
        
        recognition.onresult = function(event) {
            let transcript = '';
            for (let i = event.resultIndex; i < event.results.length; i++) {
                transcript += event.results[i][0].transcript;
            }
            if (transcript.trim()) {
                input.value = transcript.trim();
                // Auto-enviar al terminar de grabar
                setTimeout(() => {
                    if (input.value.trim() && input.value !== 'Escuchando...') {
                        form.dispatchEvent(new Event('submit'));
                    }
                }, 500);
            }
        };
        
        recognition.onerror = function(event) {
            console.error('Error de voz:', event.error);
            input.value = '';
            let errorMsg = 'Error: ';
            if (event.error === 'no-speech') {
                errorMsg += 'No se detectó voz';
            } else if (event.error === 'network') {
                errorMsg += 'Error de red';
            } else if (event.error === 'not-allowed') {
                errorMsg += 'Permite el micrófono en tu navegador';
            } else {
                errorMsg += event.error;
            }
            alert(errorMsg);
        };
    } else {
        micBtn.style.display = 'none';
    }
    
    // Botón micrófono
    micBtn.addEventListener('click', function(e) {
        e.preventDefault();
        if (!recognition) {
            alert('El reconocimiento de voz no es soportado en este navegador');
            return;
        }
        
        if (isRecording) {
            recognition.stop();
        } else {
            input.value = '';
            recognition.start();
        }
    });
    
    // Abrir/Cerrar modal con toggle
    floatBtn.addEventListener('click', function() {
        if (modal.classList.contains('active')) {
            modal.classList.remove('active');
        } else {
            modal.classList.add('active');
            input.focus();
        }
    });
    
    // Cerrar modal
    closeBtn.addEventListener('click', function() {
        modal.classList.remove('active');
    });
    
    // Enviar mensaje
    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        const mensaje = input.value.trim();
        
        if (!mensaje) return;
        
        // Mostrar mensaje del usuario
        const userMsgDiv = document.createElement('div');
        userMsgDiv.className = 'chat-message mensaje-usuario';
        userMsgDiv.innerHTML = '<div class="bubble">' + escapeHtml(mensaje) + '</div>';
        messagesContainer.appendChild(userMsgDiv);
        
        input.value = '';
        messagesContainer.scrollTop = messagesContainer.scrollHeight;
        
        try {
            // Enviar a la API
            const response = await fetch('api_chatbot.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: 'action=chat&input=' + encodeURIComponent(mensaje)
            });
            
            if (!response.ok) {
                throw new Error('Error en la solicitud');
            }
            
            const data = await response.json();
            
            // Mostrar respuesta del bot
            const botMsgDiv = document.createElement('div');
            botMsgDiv.className = 'chat-message mensaje-bot';
            let respuesta = (data.respuesta || 'Error procesando');
            // Limpiar respuesta de mensaje de ayuda no deseado
            respuesta = respuesta
                .replace(/❓\s+No entendí eso\..*?📊 \*\*Reportes\*\*/s, 'No entendí, intenta de otra forma')
                .replace(/❌\s+Error de conexión BD/g, 'Error de conexión');
            
            botMsgDiv.innerHTML = '<div class="bubble">' + respuesta.replace(/\n/g, '<br>').replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>') + '</div>';
            messagesContainer.appendChild(botMsgDiv);
            
            messagesContainer.scrollTop = messagesContainer.scrollHeight;
            
            // Reproducir audio de la respuesta
            reproducirAudio(respuesta);
        } catch (error) {
            const errorDiv = document.createElement('div');
            errorDiv.className = 'chat-message mensaje-bot';
            errorDiv.innerHTML = '<div class="bubble">❌ Error: ' + error.message + '</div>';
            messagesContainer.appendChild(errorDiv);
            messagesContainer.scrollTop = messagesContainer.scrollHeight;
        }
    });
    
    // Función para reproducir audio (Text-to-Speech)
    function reproducirAudio(texto) {
        // Limpiar emojis y caracteres especiales para el audio
        let textoLimpio = texto
            .replace(/[\u{1F300}-\u{1F9FF}]/gu, '')
            .replace(/[\u{2600}-\u{27BF}]/gu, '')
            .replace(/[\u{2300}-\u{23FF}]/gu, '')
            .replace(/\*\*/g, '')
            .replace(/\n/g, ' ')
            .replace(/\s+/g, ' ')
            .trim();
        
        if (!textoLimpio) return;
        
        if ('speechSynthesis' in window) {
            // Cancelar cualquier audio anterior
            speechSynthesis.cancel();
            const utterance = new SpeechSynthesisUtterance(textoLimpio);
            utterance.lang = 'es-ES';
            utterance.rate = 1.0;
            utterance.pitch = 1.0;
            speechSynthesis.speak(utterance);
        }
    }
    
    // Función para escapar HTML
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
});
</script>
