<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🤖 Chatbot Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        .chat-container {
            height: 600px;
            overflow-y: auto;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            border-radius: 10px;
            padding: 15px;
        }
        
        .chat-message {
            margin-bottom: 15px;
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
        
        @keyframes pulse {
            0%, 100% {
                transform: scale(1);
            }
            50% {
                transform: scale(1.1);
            }
        }
        
        .mensaje-usuario {
            text-align: right;
        }
        
        .mensaje-usuario .bubble {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px 15px 0 15px;
            padding: 10px 15px;
            max-width: 70%;
            display: inline-block;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
            word-wrap: break-word;
        }
        
        .mensaje-bot {
            text-align: left;
        }
        
        .mensaje-bot .bubble {
            background: white;
            color: #333;
            border-radius: 15px 15px 15px 0;
            padding: 10px 15px;
            max-width: 80%;
            display: inline-block;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            word-wrap: break-word;
        }
        
        .mensaje-bot .icono {
            font-size: 24px;
            margin-right: 10px;
            animation: slideIn 0.3s ease-out;
        }
        
        .input-group-chatbot {
            border: 2px solid #dee2e6;
            border-radius: 25px;
            padding: 5px;
            display: flex;
            align-items: center;
            background: white;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }
        
        .input-group-chatbot input {
            border: none;
            outline: none;
            flex: 1;
            padding: 10px 15px;
            font-size: 14px;
        }
        
        .input-group-chatbot input::placeholder {
            color: #aaa;
        }
        
        .btn-voice {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            border: none;
            padding: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 5px;
            transition: all 0.3s ease;
        }
        
        .btn-voice:hover {
            transform: scale(1.1);
        }
        
        .btn-voice.escuchando {
            background: #dc3545;
            animation: pulse 1s infinite;
        }
        
        .transcripcion {
            font-size: 12px;
            color: #6c757d;
            margin-top: 5px;
            font-style: italic;
            min-height: 20px;
        }
        
        .status-badge {
            font-size: 12px;
            padding: 3px 8px;
            border-radius: 12px;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        
        .status-escuchando {
            background: #ffc107;
            color: #000;
        }
        
        .status-hablando {
            background: #17a2b8;
            color: white;
        }
        
        .pregunta {
            font-size: 13px !important;
            transition: all 0.3s ease;
        }
        
        .pregunta:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }
    </style>
</head>
<body>
    <div class="container-fluid py-4">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                
                <!-- Encabezado -->
                <div class="card border-0 shadow mb-3">
                    <div class="card-header bg-gradient" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                        <div class="d-flex align-items-center justify-content-between text-white">
                            <h4 class="mb-0">
                                <i class="bi bi-chat-dots"></i> Asistente Inteligente
                            </h4>
                            <div>
                                <span class="status-badge" id="status-badge">
                                    <i class="bi bi-circle-fill"></i> Listo
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Área de Chat -->
                <div class="card border-0 shadow mb-3">
                    <div class="card-body">
                        <div class="chat-container" id="chatContainer">
                            <div class="chat-message mensaje-bot">
                                <div class="d-flex align-items-start">
                                    <span class="icono">🤖</span>
                                    <div class="bubble">
                                        <strong>¡Hola!</strong> Soy tu asistente. Puedo ayudarte con consultas sobre:
                                        <br><br>
                                        📦 Paquetes<br>
                                        👥 Clientes<br>
                                        🚚 Repartidores<br>
                                        💰 Ingresos<br>
                                        📊 Reportes
                                        <br><br>
                                        ¿En qué puedo ayudarte?
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Input de Chat -->
                <div class="card border-0 shadow">
                    <div class="card-body">
                        
                        <!-- Ejemplos de preguntas -->
                        <div class="mb-3">
                            <small class="text-muted">⚡ Comandos rápidos:</small>
                            <div class="d-flex flex-wrap gap-2 mt-2">
                                <button class="btn btn-sm btn-outline-primary pregunta" data-pregunta="¿Cuántos paquetes hay?">
                                    📦 Total
                                </button>
                                <button class="btn btn-sm btn-outline-danger pregunta" data-pregunta="Paquetes pendientes">
                                    ⏳ Pendientes
                                </button>
                                <button class="btn btn-sm btn-outline-success pregunta" data-pregunta="Paquetes entregados">
                                    ✅ Entregados
                                </button>
                                <button class="btn btn-sm btn-outline-info pregunta" data-pregunta="Dame un resumen">
                                    📊 Resumen
                                </button>
                                <button class="btn btn-sm btn-outline-warning pregunta" data-pregunta="¿Cuánto ganamos hoy?">
                                    💰 Ingresos
                                </button>
                                <button class="btn btn-sm btn-outline-secondary pregunta" data-pregunta="Entregas fallidas">
                                    ⚠️ Problemas
                                </button>
                            </div>
                        </div>

                        <!-- Input con voz -->
                        <form id="chatForm" class="mt-3">
                            <div class="input-group-chatbot">
                                <input 
                                    type="text" 
                                    id="chatInput" 
                                    placeholder="Escribe tu pregunta o usa el micrófono..." 
                                    class="form-control"
                                    autocomplete="off"
                                >
                                <button type="button" id="btnVoz" class="btn btn-voice btn-primary" title="Hablar">
                                    <i class="bi bi-mic"></i>
                                </button>
                                <button type="button" id="btnSonido" class="btn btn-voice btn-info" title="Sonido">
                                    <i class="bi bi-volume-up"></i>
                                </button>
                                <button type="submit" class="btn btn-voice btn-success" title="Enviar">
                                    <i class="bi bi-send"></i>
                                </button>
                            </div>
                            <div class="transcripcion" id="transcripcion"></div>
                        </form>

                        <!-- Indicador de estado -->
                        <div class="mt-3">
                            <div id="indicador" class="spinner-border spinner-border-sm d-none" role="status">
                                <span class="visually-hidden">Cargando...</span>
                            </div>
                            <small id="statusText" class="text-muted ms-2"></small>
                        </div>

                    </div>
                </div>

                <!-- Controles de voz -->
                <div class="card border-0 shadow mt-3 bg-light">
                    <div class="card-body text-center">
                        <small class="text-muted">
                            <i class="bi bi-info-circle"></i>
                            Usa el micrófono para hablar. El chatbot responderá por texto y puede hablar también.
                        </small>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Variables globales
        const recognition = new (window.SpeechRecognition || window.webkitSpeechRecognition)();
        const synth = window.speechSynthesis;
        let escuchando = false;
        let hablando = false;

        // Configurar reconocimiento de voz
        recognition.continuous = false;
        recognition.interimResults = true;
        recognition.lang = 'es-ES';

        // Elementos del DOM
        const chatContainer = document.getElementById('chatContainer');
        const chatInput = document.getElementById('chatInput');
        const chatForm = document.getElementById('chatForm');
        const btnVoz = document.getElementById('btnVoz');
        const btnSonido = document.getElementById('btnSonido');
        const transcripcion = document.getElementById('transcripcion');
        const statusBadge = document.getElementById('status-badge');
        const indicador = document.getElementById('indicador');
        const statusText = document.getElementById('statusText');

        // Botones de preguntas rápidas
        document.querySelectorAll('.pregunta').forEach(btn => {
            btn.addEventListener('click', () => {
                chatInput.value = btn.dataset.pregunta;
                chatForm.dispatchEvent(new Event('submit'));
            });
        });

        // Enviar mensaje
        chatForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const mensaje = chatInput.value.trim();
            
            if (!mensaje) return;

            // Agregar mensaje del usuario
            agregarMensaje(mensaje, 'usuario');
            chatInput.value = '';
            transcripcion.textContent = '';

            // Procesar en servidor
            const respuesta = await procesarPregunta(mensaje);
            
            // Agregar respuesta del bot
            agregarMensaje(respuesta.respuesta, 'bot', respuesta.icono || '🤖');

            // Reproducir respuesta en voz si está activo
            if (sonoActivado) {
                hablarRespuesta(respuesta.respuesta);
            }
        });

        // Botón de voz
        btnVoz.addEventListener('click', () => {
            if (!escuchando) {
                escuchando = true;
                btnVoz.classList.add('escuchando');
                btnVoz.innerHTML = '<i class="bi bi-mic-fill"></i>';
                statusBadge.innerHTML = '<span class="status-badge status-escuchando"><i class="bi bi-circle-fill"></i> Escuchando...</span>';
                statusText.textContent = 'Micrófono activo...';
                
                recognition.start();
            } else {
                escuchando = false;
                btnVoz.classList.remove('escuchando');
                btnVoz.innerHTML = '<i class="bi bi-mic"></i>';
                recognition.stop();
            }
        });

        // Eventos de reconocimiento de voz
        recognition.onstart = () => {
            transcripcion.textContent = '🎙️ Escuchando...';
        };

        recognition.onresult = (event) => {
            let interimTranscript = '';
            
            for (let i = event.resultIndex; i < event.results.length; i++) {
                const transcript = event.results[i][0].transcript;
                
                if (event.results[i].isFinal) {
                    chatInput.value = transcript;
                    transcripcion.textContent = '✓ ' + transcript;
                    // Enviar automáticamente después de 500ms
                    setTimeout(() => {
                        chatForm.dispatchEvent(new Event('submit'));
                    }, 500);
                } else {
                    interimTranscript += transcript;
                }
            }
            
            if (interimTranscript && !transcripcion.textContent.startsWith('✓')) {
                transcripcion.textContent = '📝 ' + interimTranscript;
            }
        };

        recognition.onend = () => {
            escuchando = false;
            btnVoz.classList.remove('escuchando');
            btnVoz.innerHTML = '<i class="bi bi-mic"></i>';
            statusBadge.innerHTML = '<span class="status-badge"><i class="bi bi-circle-fill"></i> Listo</span>';
            statusText.textContent = '';
        };

        recognition.onerror = (event) => {
            let mensajeError = event.error;
            if (event.error === 'network') mensajeError = 'Error de red';
            if (event.error === 'no-speech') mensajeError = 'No se detectó voz';
            if (event.error === 'audio-capture') mensajeError = 'Micrófono no disponible';
            
            statusText.textContent = '❌ Error: ' + mensajeError;
            console.error('Error de voz:', event.error);
            escuchando = false;
            btnVoz.classList.remove('escuchando');
            btnVoz.innerHTML = '<i class="bi bi-mic"></i>';
        };

        // Botón de sonido (alternar audio)
        let sonoActivado = true;
        btnSonido.addEventListener('click', () => {
            sonoActivado = !sonoActivado;
            btnSonido.classList.toggle('active');
            
            if (sonoActivado) {
                btnSonido.style.opacity = '1';
                statusText.textContent = '🔊 Sonido activado';
            } else {
                btnSonido.style.opacity = '0.5';
                statusText.textContent = '🔇 Sonido desactivado';
                window.speechSynthesis.cancel();
            }
        });
        
        // Estado inicial del sonido
        btnSonido.classList.add('active');

        // Procesar pregunta en servidor
        async function procesarPregunta(pregunta) {
            indicador.classList.remove('d-none');
            statusText.textContent = 'Procesando...';

            try {
                const response = await fetch('api_chatbot.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: 'action=chat&input=' + encodeURIComponent(pregunta)
                });

                const data = await response.json();
                indicador.classList.add('d-none');
                statusText.textContent = '';

                return data;
            } catch (error) {
                indicador.classList.add('d-none');
                statusText.textContent = '❌ Error al conectar';
                console.error(error);
                return {
                    tipo: 'error',
                    respuesta: 'Error al procesar la consulta',
                    icono: '❌'
                };
            }
        }

        // Agregar mensaje al chat
        function agregarMensaje(texto, tipo = 'bot', icono = '🤖') {
            const div = document.createElement('div');
            div.className = 'chat-message ' + (tipo === 'usuario' ? 'mensaje-usuario' : 'mensaje-bot');

            if (tipo === 'usuario') {
                div.innerHTML = `<div class="bubble">${escape(texto)}</div>`;
            } else {
                // Formatear respuesta del bot (convertir ** a negrita)
                const textFormateado = texto
                    .replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>')
                    .replace(/\n/g, '<br>');
                
                div.innerHTML = `
                    <div class="d-flex align-items-start">
                        <span class="icono">${icono}</span>
                        <div class="bubble">${textFormateado}</div>
                    </div>
                `;
            }

            chatContainer.appendChild(div);
            chatContainer.scrollTop = chatContainer.scrollHeight;
        }

        // Hablar respuesta
        function hablarRespuesta(texto) {
            // Limpiar texto de markdown y caracteres especiales
            const textoLimpio = texto
                .replace(/\*\*(.+?)\*\*/g, '$1')
                .replace(/\n/g, '. ')
                .replace(/[👤📦✅⏳📅🚚💚📍💰📈📊⚠️❌🤔🤖💬🎙️🔊🟢🏆💉↩️⚡🔇]/g, '')
                .trim();

            if (!textoLimpio) return;

            const utterance = new SpeechSynthesisUtterance(textoLimpio);
            utterance.lang = 'es-ES';
            utterance.rate = 0.9;
            utterance.pitch = 1;

            utterance.onstart = () => {
                hablando = true;
                statusBadge.innerHTML = '<span class="status-badge status-hablando"><i class="bi bi-circle-fill"></i> Hablando...</span>';
            };

            utterance.onend = () => {
                hablando = false;
                statusBadge.innerHTML = '<span class="status-badge"><i class="bi bi-circle-fill"></i> Listo</span>';
            };

            synth.speak(utterance);
        }

        // Helper para escapar HTML
        function escape(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        // Evitar enviar con Enter si hay micrófono en uso
        chatInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter' && !escuchando) {
                e.preventDefault();
                chatForm.dispatchEvent(new Event('submit'));
            }
        });

        console.log('✅ Chatbot v2.0 iniciado correctamente');
        console.log('💡 Características: Reconocimiento de voz, síntesis de voz, patrones avanzados');
    </script>
</body>
</html>
