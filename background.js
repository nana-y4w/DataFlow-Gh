// Three.js 3D Background with Data Flow Theme
// Creates floating geometric shapes with Ghana flag and network colors

document.addEventListener('DOMContentLoaded', function() {
    const scene = new THREE.Scene();
    scene.background = new THREE.Color(0x0a0a0a);

    const camera = new THREE.PerspectiveCamera(75, window.innerWidth / window.innerHeight, 0.1, 1000);
    camera.position.z = 30;

    const renderer = new THREE.WebGLRenderer({ antialias: true, alpha: false });
    renderer.setSize(window.innerWidth, window.innerHeight);
    renderer.setPixelRatio(window.devicePixelRatio);
    document.getElementById('canvas-container').appendChild(renderer.domElement);

    // Network and Ghana flag colors
    const colors = [0xffcc00, 0xe30613, 0xed1c24, 0x006b3f, 0xf4d112];

    // Create floating data cubes
    const cubes = [];
    for (let i = 0; i < 50; i++) {
        const geometry = new THREE.BoxGeometry(0.5, 0.5, 0.5);
        const color = colors[Math.floor(Math.random() * colors.length)];
        const material = new THREE.MeshPhongMaterial({
            color: color,
            emissive: color,
            emissiveIntensity: 0.2,
            transparent: true,
            opacity: 0.3
        });

        const cube = new THREE.Mesh(geometry, material);
        
        cube.position.x = (Math.random() - 0.5) * 60;
        cube.position.y = (Math.random() - 0.5) * 40;
        cube.position.z = (Math.random() - 0.5) * 30;
        
        cube.userData = {
            speedX: (Math.random() - 0.5) * 0.02,
            speedY: (Math.random() - 0.5) * 0.02,
            speedZ: (Math.random() - 0.5) * 0.02
        };
        
        scene.add(cube);
        cubes.push(cube);
    }

    // Create floating spheres (data packets)
    const spheres = [];
    for (let i = 0; i < 30; i++) {
        const geometry = new THREE.SphereGeometry(0.3, 16, 16);
        const color = colors[Math.floor(Math.random() * colors.length)];
        const material = new THREE.MeshPhongMaterial({
            color: color,
            emissive: color,
            emissiveIntensity: 0.3,
            transparent: true,
            opacity: 0.4
        });

        const sphere = new THREE.Mesh(geometry, material);
        
        sphere.position.x = (Math.random() - 0.5) * 50;
        sphere.position.y = (Math.random() - 0.5) * 30;
        sphere.position.z = (Math.random() - 0.5) * 40;
        
        scene.add(sphere);
        spheres.push(sphere);
    }

    // Create network connection lines
    const lineMaterial = new THREE.LineBasicMaterial({ color: 0xffcc00, opacity: 0.1, transparent: true });
    for (let i = 0; i < 20; i++) {
        const points = [];
        points.push(new THREE.Vector3((Math.random() - 0.5) * 40, (Math.random() - 0.5) * 30, (Math.random() - 0.5) * 20));
        points.push(new THREE.Vector3((Math.random() - 0.5) * 40, (Math.random() - 0.5) * 30, (Math.random() - 0.5) * 20));
        
        const geometry = new THREE.BufferGeometry().setFromPoints(points);
        const line = new THREE.Line(geometry, lineMaterial);
        scene.add(line);
    }

    // Lighting
    const ambientLight = new THREE.AmbientLight(0x404040);
    scene.add(ambientLight);

    const lights = [];
    const lightColors = [0xffcc00, 0xe30613, 0xed1c24];
    
    for (let i = 0; i < 3; i++) {
        const light = new THREE.PointLight(lightColors[i], 1, 50);
        light.position.set(
            (i - 1) * 15,
            (i - 1) * 10,
            20
        );
        scene.add(light);
        lights.push(light);
    }

    // Particle system for data stream
    const particleGeometry = new THREE.BufferGeometry();
    const particleCount = 500;
    const posArray = new Float32Array(particleCount * 3);
    
    for (let i = 0; i < particleCount * 3; i += 3) {
        posArray[i] = (Math.random() - 0.5) * 100;
        posArray[i + 1] = (Math.random() - 0.5) * 100;
        posArray[i + 2] = (Math.random() - 0.5) * 100;
    }
    
    particleGeometry.setAttribute('position', new THREE.BufferAttribute(posArray, 3));
    
    const particleMaterial = new THREE.PointsMaterial({
        color: 0xffcc00,
        size: 0.1,
        transparent: true,
        opacity: 0.3
    });
    
    const particles = new THREE.Points(particleGeometry, particleMaterial);
    scene.add(particles);

    // Animation
    function animate() {
        requestAnimationFrame(animate);

        // Rotate cubes
        cubes.forEach(cube => {
            cube.rotation.x += cube.userData.speedX;
            cube.rotation.y += cube.userData.speedY;
            cube.rotation.z += cube.userData.speedZ;
        });

        // Animate spheres in floating motion
        const time = Date.now() * 0.001;
        spheres.forEach((sphere, index) => {
            sphere.position.y += Math.sin(time + index) * 0.005;
        });

        // Move lights
        lights.forEach((light, index) => {
            light.position.x = 15 * Math.sin(time * 0.5 + index);
            light.position.y = 10 * Math.cos(time * 0.3 + index);
        });

        // Rotate particles
        particles.rotation.y += 0.0005;

        renderer.render(scene, camera);
    }

    animate();

    // Handle window resize
    window.addEventListener('resize', () => {
        camera.aspect = window.innerWidth / window.innerHeight;
        camera.updateProjectionMatrix();
        renderer.setSize(window.innerWidth, window.innerHeight);
    });
});
