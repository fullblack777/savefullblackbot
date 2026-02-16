<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Site em Manutenção - @cybersecofc</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap');
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background: radial-gradient(ellipse at bottom, #1B2735 0%, #090A0F 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            color: white;
            text-align: center;
            padding: 20px;
            position: relative;
            overflow-x: hidden;
        }
        
        /* Efeito de estrelas */
        body::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: transparent url('data:image/svg+xml;utf8,<svg width="100%" height="100%" xmlns="http://www.w3.org/2000/svg"><defs><pattern id="stars" x="0" y="0" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="10" cy="10" r="1" fill="white" opacity="0.8"/><circle cx="50" cy="30" r="1.5" fill="white" opacity="0.6"/><circle cx="80" cy="70" r="1" fill="white" opacity="0.9"/><circle cx="30" cy="80" r="1.2" fill="white" opacity="0.5"/><circle cx="90" cy="20" r="1.8" fill="white" opacity="0.7"/><circle cx="40" cy="40" r="1" fill="white" opacity="0.8"/><circle cx="70" cy="90" r="1.3" fill="white" opacity="0.6"/></pattern></defs><rect width="100%" height="100%" fill="url(%23stars)"/></svg>') repeat;
            pointer-events: none;
            z-index: 0;
        }
        
        .container {
            background: rgba(20, 30, 50, 0.6);
            backdrop-filter: blur(15px);
            padding: 40px;
            border-radius: 40px;
            box-shadow: 0 30px 60px rgba(0, 0, 0, 0.5), 0 0 0 2px rgba(255, 255, 255, 0.1) inset;
            max-width: 1200px;
            width: 100%;
            border: 1px solid rgba(255, 255, 255, 0.2);
            position: relative;
            z-index: 1;
        }
        
        /* Globo terrestre real */
        .terra {
            width: 250px;
            height: 250px;
            margin: 0 auto 20px;
            border-radius: 50%;
            box-shadow: 0 0 50px rgba(66, 140, 200, 0.5), 0 0 100px rgba(66, 140, 200, 0.3);
            animation: girar 30s linear infinite;
            background: url('https://upload.wikimedia.org/wikipedia/commons/thumb/9/97/The_earth_at_night.jpg/1024px-The_earth_at_night.jpg') center/cover no-repeat;
            border: 3px solid rgba(255, 255, 255, 0.3);
        }
        
        @keyframes girar {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        
        h1 {
            font-size: 3.5em;
            margin-bottom: 15px;
            text-shadow: 0 0 20px rgba(0, 160, 255, 0.5);
            background: linear-gradient(45deg, #fff, #aaddff);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            font-weight: 700;
        }
        
        .tag {
            font-size: 1.3em;
            margin-bottom: 20px;
            color: #ffd700;
            font-weight: 600;
            text-shadow: 0 0 10px rgba(255, 215, 0, 0.5);
        }
        
        .relogio {
            font-size: 4em;
            font-weight: 700;
            font-family: 'Courier New', monospace;
            background: rgba(0, 0, 0, 0.4);
            padding: 20px 40px;
            border-radius: 60px;
            display: inline-block;
            margin: 20px 0;
            letter-spacing: 8px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            box-shadow: 0 0 30px rgba(0, 160, 255, 0.3);
            color: #0ff;
            text-shadow: 0 0 15px cyan;
        }
        
        .data {
            font-size: 1.4em;
            margin-top: 10px;
            color: rgba(255, 255, 255, 0.9);
            background: rgba(0, 0, 0, 0.2);
            padding: 10px 20px;
            border-radius: 40px;
            display: inline-block;
            backdrop-filter: blur(5px);
        }
        
        .mensagem {
            font-size: 1.2em;
            margin: 30px 0 40px;
            font-style: italic;
            background: rgba(255, 255, 255, 0.1);
            padding: 20px;
            border-radius: 20px;
            border-left: 5px solid #ffd700;
            text-align: left;
            box-shadow: 0 5px 20px rgba(0,0,0,0.3);
        }
        
        .cameras-titulo {
            font-size: 2.2em;
            margin: 40px 0 30px;
            color: #ffd700;
            text-shadow: 0 0 15px rgba(255, 215, 0, 0.3);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        
        .cameras-titulo::before, .cameras-titulo::after {
            content: "📹";
            font-size: 1.2em;
            opacity: 0.7;
        }
        
        .cameras-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 25px;
            margin-top: 20px;
        }
        
        .camera-card {
            background: rgba(0, 0, 0, 0.5);
            border-radius: 25px;
            overflow: hidden;
            border: 2px solid rgba(255, 255, 255, 0.2);
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
        }
        
        .camera-card:hover {
            transform: translateY(-10px);
            border-color: #ffd700;
            box-shadow: 0 20px 40px rgba(255, 215, 0, 0.2);
        }
        
        .camera-header {
            background: linear-gradient(45deg, #1e3c72, #2a5298);
            padding: 15px;
            font-weight: 600;
            font-size: 1.2em;
            border-bottom: 2px solid #ffd700;
        }
        
        .camera-header i {
            margin-right: 8px;
            color: #ffd700;
        }
        
        .camera-frame {
            width: 100%;
            height: 220px;
            background: #000;
            position: relative;
        }
        
        .camera-frame iframe {
            width: 100%;
            height: 100%;
            border: none;
        }
        
        .camera-footer {
            padding: 10px;
            background: rgba(0,0,0,0.3);
            font-size: 0.9em;
            color: #ccc;
        }
        
        footer {
            margin-top: 50px;
            font-size: 1em;
            color: rgba(255, 255, 255, 0.5);
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            padding-top: 20px;
        }
        
        @media (max-width: 768px) {
            .container { padding: 20px; }
            h1 { font-size: 2.5em; }
            .relogio { font-size: 2.8em; letter-spacing: 4px; }
            .terra { width: 180px; height: 180px; }
            .cameras-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Globo terrestre real com animação -->
        <div class="terra"></div>
        
        <h1>🚧 SITE EM MANUTENÇÃO 🚧</h1>
        
        <div class="tag">@cybersecofc</div>
        
        <div class="relogio" id="relogio">
            <?php 
                date_default_timezone_set('America/Sao_Paulo');
                echo date('H:i:s');
            ?>
        </div>
        
        <div class="data" id="data">
            <?php 
                setlocale(LC_TIME, 'pt_BR.utf8', 'pt_BR', 'portuguese');
                echo strftime('%A, %d de %B de %Y');
            ?>
        </div>
        
        <div class="mensagem">
            <p>🌟 Estamos trabalhando duro para trazer novidades incríveis!</p>
            <p>🔧 Enquanto isso, você pode acompanhar algumas câmeras ao vivo pelo mundo abaixo.</p>
            <p>💪 Em breve estaremos de volta com tudo!</p>
        </div>
        
        <!-- Seção de câmeras ao vivo -->
        <div class="cameras-titulo">
            <span>Câmeras Abertas pelo Mundo</span>
        </div>
        
        <div class="cameras-grid">
            <!-- Câmera 1: Times Square, NY -->
            <div class="camera-card">
                <div class="camera-header">
                    <i>📡</i> Times Square - Nova York
                </div>
                <div class="camera-frame">
                    <iframe src="https://www.youtube.com/embed/5_XSYlAfJZM?autoplay=1&mute=1" allowfullscreen></iframe>
                </div>
                <div class="camera-footer">
                    🔴 Ao vivo • Atualizado em tempo real
                </div>
            </div>
            
            <!-- Câmera 2: Praia de Copacabana, RJ -->
            <div class="camera-card">
                <div class="camera-header">
                    <i>🏖️</i> Praia de Copacabana - Rio
                </div>
                <div class="camera-frame">
                    <iframe src="https://www.youtube.com/embed/qaIHLymXb48?autoplay=1&mute=1" allowfullscreen></iframe>
                </div>
                <div class="camera-footer">
                    🔴 Ao vivo • Webcam oficial
                </div>
            </div>
            
            <!-- Câmera 3: Torre Eiffel, Paris -->
            <div class="camera-card">
                <div class="camera-header">
                    <i>🗼</i> Torre Eiffel - Paris
                </div>
                <div class="camera-frame">
                    <iframe src="https://www.youtube.com/embed/3Mx_4pG7U8o?autoplay=1&mute=1" allowfullscreen></iframe>
                </div>
                <div class="camera-footer">
                    🔴 Ao vivo • Vista incrível
                </div>
            </div>
            
            <!-- Câmera 4: Shibuya, Tóquio -->
            <div class="camera-card">
                <div class="camera-header">
                    <i>🗼</i> Shibuya Crossing - Tóquio
                </div>
                <div class="camera-frame">
                    <iframe src="https://www.youtube.com/embed/bCgLa25fFHc?autoplay=1&mute=1" allowfullscreen></iframe>
                </div>
                <div class="camera-footer">
                    🔴 Ao vivo • O cruzamento mais famoso
                </div>
            </div>
        </div>
        
        <footer>
            <p>&copy; <?php echo date('Y'); ?> @cybersecofc - Todos os direitos reservados</p>
            <p style="font-size: 0.8em; margin-top: 5px;">🌍 Imagem da Terra: NASA / Wikimedia</p>
        </footer>
    </div>
    
    <script>
        function atualizarRelogio() {
            const agora = new Date();
            
            // Formatar hora
            const horas = agora.getHours().toString().padStart(2, '0');
            const minutos = agora.getMinutes().toString().padStart(2, '0');
            const segundos = agora.getSeconds().toString().padStart(2, '0');
            const horaFormatada = `${horas}:${minutos}:${segundos}`;
            
            // Formatar data em português
            const dias = ['Domingo', 'Segunda-feira', 'Terça-feira', 'Quarta-feira', 'Quinta-feira', 'Sexta-feira', 'Sábado'];
            const meses = ['janeiro', 'fevereiro', 'março', 'abril', 'maio', 'junho', 'julho', 'agosto', 'setembro', 'outubro', 'novembro', 'dezembro'];
            
            const diaSemana = dias[agora.getDay()];
            const dia = agora.getDate().toString().padStart(2, '0');
            const mes = meses[agora.getMonth()];
            const ano = agora.getFullYear();
            
            const dataFormatada = `${diaSemana}, ${dia} de ${mes} de ${ano}`;
            
            // Atualizar elementos
            document.getElementById('relogio').textContent = horaFormatada;
            document.getElementById('data').textContent = dataFormatada;
        }
        
        // Atualizar a cada segundo
        setInterval(atualizarRelogio, 1000);
    </script>
</body>
</html>
