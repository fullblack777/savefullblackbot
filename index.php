<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Site em Manutenção - @cybersecofc</title>
    <!-- Fontes e ícones -->
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
        body {
            margin: 0;
            overflow: hidden;
            font-family: 'Poppins', sans-serif;
            color: white;
        }
        #canvas-container {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 0;
        }
        .content {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 10;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            pointer-events: none;
        }
        .content * {
            pointer-events: auto;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            text-shadow: 0 0 20px rgba(0,255,255,0.7);
        }
        h1 {
            font-size: 5rem;
            margin: 0;
            font-weight: 900;
            font-family: 'Orbitron', sans-serif;
            text-transform: uppercase;
            background: linear-gradient(45deg, #fff, #00ffff, #ff00ff);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            animation: glitch 3s infinite;
        }
        @keyframes glitch {
            0%, 100% { transform: skew(0deg, 0deg); opacity: 1; }
            95% { transform: skew(0deg, 0deg); opacity: 1; }
            96% { transform: skew(5deg, 2deg); opacity: 0.8; }
            97% { transform: skew(-5deg, -2deg); opacity: 0.9; }
            98% { transform: skew(3deg, 1deg); opacity: 0.8; }
        }
        .tag {
            font-size: 2rem;
            color: #ffd700;
            font-weight: 600;
            text-shadow: 0 0 15px gold;
            letter-spacing: 4px;
        }
        .clock-container {
            background: rgba(0,0,0,0.6);
            backdrop-filter: blur(10px);
            border: 2px solid rgba(0,255,255,0.5);
            border-radius: 60px;
            padding: 20px 50px;
            margin: 30px 0;
            box-shadow: 0 0 50px rgba(0,255,255,0.3);
        }
        #relogio {
            font-family: 'Orbitron', monospace;
            font-size: 5rem;
            font-weight: 700;
            color: #0ff;
            text-shadow: 0 0 20px cyan;
            letter-spacing: 10px;
        }
        .data {
            font-size: 1.5rem;
            color: rgba(255,255,255,0.9);
            margin-top: 10px;
            font-weight: 300;
        }
        .video-section {
            display: flex;
            gap: 40px;
            margin-top: 50px;
            flex-wrap: wrap;
            justify-content: center;
        }
        .video-card {
            background: rgba(20,20,40,0.8);
            backdrop-filter: blur(15px);
            border: 2px solid rgba(255,215,0,0.3);
            border-radius: 30px;
            overflow: hidden;
            width: 400px;
            transition: transform 0.4s, box-shadow 0.4s;
            box-shadow: 0 20px 40px rgba(0,0,0,0.5);
        }
        .video-card:hover {
            transform: translateY(-20px) scale(1.02);
            border-color: #ffd700;
            box-shadow: 0 30px 60px rgba(255,215,0,0.3);
        }
        .card-header {
            background: linear-gradient(90deg, #1e3c72, #2a5298, #1e3c72);
            padding: 15px;
            font-size: 1.3rem;
            font-weight: 600;
            color: white;
            text-align: center;
            border-bottom: 2px solid #ffd700;
        }
        .card-video {
            width: 100%;
            height: 250px;
        }
        .card-video iframe {
            width: 100%;
            height: 100%;
            border: none;
        }
        .card-footer {
            padding: 10px;
            background: rgba(0,0,0,0.5);
            text-align: center;
            font-size: 1rem;
            color: #ccc;
        }
        footer {
            position: absolute;
            bottom: 20px;
            left: 0;
            width: 100%;
            text-align: center;
            color: rgba(255,255,255,0.5);
            font-size: 0.9rem;
            z-index: 20;
            pointer-events: none;
        }
        @media (max-width: 768px) {
            h1 { font-size: 3rem; }
            .tag { font-size: 1.5rem; }
            #relogio { font-size: 3rem; letter-spacing: 5px; }
            .clock-container { padding: 15px 25px; }
            .video-card { width: 90%; }
        }
    </style>
</head>
<body>
    <div id="canvas-container"></div>

    <div class="content">
        <div class="header">
            <h1>🚧 EM MANUTENÇÃO 🚧</h1>
            <div class="tag">@cybersecofc</div>
        </div>

        <div class="clock-container">
            <div id="relogio"><?php echo date('H:i:s'); ?></div>
            <div class="data" id="data"><?php setlocale(LC_TIME, 'pt_BR.utf8', 'pt_BR', 'portuguese'); echo strftime('%A, %d de %B de %Y'); ?></div>
        </div>

        <div class="video-section">
            <div class="video-card">
                <div class="card-header">🎬 Vídeo 1 - @cybersecofc</div>
                <div class="card-video">
                    <iframe src="https://www.youtube.com/embed/U_R6QIc2twI" allowfullscreen></iframe>
                </div>
                <div class="card-footer">Clique para assistir • Inscreva-se!</div>
            </div>
            <div class="video-card">
                <div class="card-header">🎬 Vídeo 2 - @cybersecofc</div>
                <div class="card-video">
                    <iframe src="https://www.youtube.com/embed/0Fv-6ILbbZo" allowfullscreen></iframe>
                </div>
                <div class="card-footer">Clique para assistir • Inscreva-se!</div>
            </div>
        </div>
    </div>

    <footer>
        © <?php echo date('Y'); ?> @cybersecofc - Todos os direitos reservados | 🌍 Terra 3D com Three.js
    </footer>

    <!-- Three.js e OrbitControls -->
    <script type="importmap">
        {
            "imports": {
                "three": "https://unpkg.com/three@0.128.0/build/three.module.js",
                "three/addons/": "https://unpkg.com/three@0.128.0/examples/jsm/"
            }
        }
    </script>

    <script type="module">
        import * as THREE from 'three';
        import { OrbitControls } from 'three/addons/controls/OrbitControls.js';

        // Configuração da cena
        const scene = new THREE.Scene();
        scene.background = new THREE.Color(0x050510);

        const camera = new THREE.PerspectiveCamera(45, window.innerWidth / window.innerHeight, 0.1, 1000);
        camera.position.set(0, 0, 15);

        const renderer = new THREE.WebGLRenderer({ antialias: true, alpha: false });
        renderer.setSize(window.innerWidth, window.innerHeight);
        renderer.setPixelRatio(window.devicePixelRatio);
        document.getElementById('canvas-container').appendChild(renderer.domElement);

        // Controles para permitir interação (opcional, mas pode ser removido se quiser fixo)
        const controls = new OrbitControls(camera, renderer.domElement);
        controls.enableDamping = true;
        controls.autoRotate = true;
        controls.autoRotateSpeed = 0.5;
        controls.enableZoom = true;
        controls.enablePan = false;
        controls.maxPolarAngle = Math.PI / 2;
        controls.minDistance = 8;
        controls.maxDistance = 20;

        // Luzes
        const ambientLight = new THREE.AmbientLight(0x404060);
        scene.add(ambientLight);

        const dirLight = new THREE.DirectionalLight(0xffffff, 1);
        dirLight.position.set(1, 1, 1);
        scene.add(dirLight);

        const pointLight = new THREE.PointLight(0x4488ff, 1, 30);
        pointLight.position.set(2, 3, 4);
        scene.add(pointLight);

        // Estrelas de fundo (partículas)
        const starsGeometry = new THREE.BufferGeometry();
        const starsCount = 2000;
        const starsPositions = new Float32Array(starsCount * 3);
        for (let i = 0; i < starsCount * 3; i += 3) {
            starsPositions[i] = (Math.random() - 0.5) * 200;
            starsPositions[i+1] = (Math.random() - 0.5) * 200;
            starsPositions[i+2] = (Math.random() - 0.5) * 200;
        }
        starsGeometry.setAttribute('position', new THREE.BufferAttribute(starsPositions, 3));
        const starsMaterial = new THREE.PointsMaterial({ color: 0xffffff, size: 0.2, transparent: true });
        const stars = new THREE.Points(starsGeometry, starsMaterial);
        scene.add(stars);

        // Texturas da Terra
        const textureLoader = new THREE.TextureLoader();
        const earthMap = textureLoader.load('https://threejs.org/examples/textures/planets/earth_atmos_2048.jpg');
        const earthCloudMap = textureLoader.load('https://threejs.org/examples/textures/planets/earth_clouds_1024.png');
        const earthNormalMap = textureLoader.load('https://threejs.org/examples/textures/planets/earth_normal_2048.jpg');
        const earthSpecularMap = textureLoader.load('https://threejs.org/examples/textures/planets/earth_specular_2048.jpg');

        // Esfera da Terra
        const earthGeometry = new THREE.SphereGeometry(3, 64, 64);
        const earthMaterial = new THREE.MeshPhongMaterial({
            map: earthMap,
            normalMap: earthNormalMap,
            specularMap: earthSpecularMap,
            specular: new THREE.Color('grey'),
            shininess: 5
        });
        const earth = new THREE.Mesh(earthGeometry, earthMaterial);
        scene.add(earth);

        // Camada de nuvens (translúcida)
        const cloudGeometry = new THREE.SphereGeometry(3.01, 64, 64);
        const cloudMaterial = new THREE.MeshPhongMaterial({
            map: earthCloudMap,
            transparent: true,
            opacity: 0.4,
            blending: THREE.AdditiveBlending,
            side: THREE.DoubleSide
        });
        const clouds = new THREE.Mesh(cloudGeometry, cloudMaterial);
        scene.add(clouds);

        // Anéis orbitais (decorativos)
        const ringGeometry = new THREE.TorusGeometry(4.2, 0.05, 16, 100);
        const ringMaterial = new THREE.MeshStandardMaterial({ color: 0x44aaff, emissive: 0x114488, transparent: true, opacity: 0.2 });
        const ring = new THREE.Mesh(ringGeometry, ringMaterial);
        ring.rotation.x = Math.PI / 2;
        ring.rotation.z = 0.3;
        scene.add(ring);

        const ring2 = new THREE.TorusGeometry(4.5, 0.03, 16, 100);
        const ring2Material = new THREE.MeshStandardMaterial({ color: 0xffaa44, emissive: 0x441100, transparent: true, opacity: 0.15 });
        const ring2Obj = new THREE.Mesh(ring2, ring2Material);
        ring2Obj.rotation.x = Math.PI / 2;
        ring2Obj.rotation.z = -0.2;
        scene.add(ring2Obj);

        // Partículas ao redor (como satélites)
        const particlesGeo = new THREE.BufferGeometry();
        const particlesCount = 200;
        const particlesPos = new Float32Array(particlesCount * 3);
        for (let i = 0; i < particlesCount; i++) {
            const angle = (i / particlesCount) * Math.PI * 2;
            const radius = 5 + Math.random() * 1.5;
            const x = Math.cos(angle) * radius;
            const z = Math.sin(angle) * radius;
            const y = (Math.random() - 0.5) * 2;
            particlesPos[i*3] = x;
            particlesPos[i*3+1] = y;
            particlesPos[i*3+2] = z;
        }
        particlesGeo.setAttribute('position', new THREE.BufferAttribute(particlesPos, 3));
        const particlesMat = new THREE.PointsMaterial({ color: 0x88aaff, size: 0.05, transparent: true });
        const particles = new THREE.Points(particlesGeo, particlesMat);
        scene.add(particles);

        // Animação
        let clock = new THREE.Clock();

        function animate() {
            const delta = clock.getDelta();
            const elapsedTime = performance.now() / 1000;

            // Rotação da Terra e nuvens (em velocidades ligeiramente diferentes)
            earth.rotation.y += 0.0005;
            clouds.rotation.y += 0.0007;

            // Rotacionar estrelas lentamente para dar sensação de movimento
            stars.rotation.y += 0.0001;

            // Rotacionar anéis
            ring.rotation.z += 0.0002;
            ring2Obj.rotation.z -= 0.0003;

            // Partículas orbitam
            particles.rotation.y += 0.001;

            // Atualizar controles
            controls.update();

            renderer.render(scene, camera);
            requestAnimationFrame(animate);
        }
        animate();

        // Redimensionar janela
        window.addEventListener('resize', onWindowResize, false);
        function onWindowResize() {
            camera.aspect = window.innerWidth / window.innerHeight;
            camera.updateProjectionMatrix();
            renderer.setSize(window.innerWidth, window.innerHeight);
        }

        // Atualizar relógio em tempo real (JS)
        function atualizarRelogio() {
            const agora = new Date();
            const horas = agora.getHours().toString().padStart(2, '0');
            const minutos = agora.getMinutes().toString().padStart(2, '0');
            const segundos = agora.getSeconds().toString().padStart(2, '0');
            document.getElementById('relogio').textContent = `${horas}:${minutos}:${segundos}`;

            const dias = ['Domingo', 'Segunda-feira', 'Terça-feira', 'Quarta-feira', 'Quinta-feira', 'Sexta-feira', 'Sábado'];
            const meses = ['janeiro', 'fevereiro', 'março', 'abril', 'maio', 'junho', 'julho', 'agosto', 'setembro', 'outubro', 'novembro', 'dezembro'];
            const diaSemana = dias[agora.getDay()];
            const dia = agora.getDate().toString().padStart(2, '0');
            const mes = meses[agora.getMonth()];
            const ano = agora.getFullYear();
            document.getElementById('data').textContent = `${diaSemana}, ${dia} de ${mes} de ${ano}`;
        }
        setInterval(atualizarRelogio, 1000);
    </script>
</body>
</html>
