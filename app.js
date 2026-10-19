/**
 * ============================================================
 * THE SOCIALS — 3D Engine & Frontend Logic
 * Three.js Scene + GSAP ScrollTrigger + AJAX Join Handler
 * ============================================================
 */

(function () {
  'use strict';

  /* ─────────────────────────────────────────────────────────────
     § 1. BRAND PALETTE (synced with CSS tokens)
     ───────────────────────────────────────────────────────────── */
  const PALETTE = {
    bgDark:    0x1A2610,  // Deep dark olive — scene background
    green:     0x32471C,  // Brand olive green
    parchment: 0xEAD8C0,  // Light beige
    copper:    0xA37752,  // Warm brown accent
    ivory:     0xFAF6F0,  // Near-white highlight
  };


  /* ─────────────────────────────────────────────────────────────
     § 2. THREE.JS SCENE INITIALIZATION
     ───────────────────────────────────────────────────────────── */
  const canvas = document.getElementById('bg');
  const scene  = new THREE.Scene();
  scene.background = new THREE.Color(PALETTE.bgDark);
  scene.fog = new THREE.FogExp2(PALETTE.bgDark, 0.035);

  // ── Camera ────────────────────────────────────────────────
  const camera = new THREE.PerspectiveCamera(
    60,
    window.innerWidth / window.innerHeight,
    0.1,
    200
  );
  camera.position.set(0, 0, 28);

  // ── Renderer ──────────────────────────────────────────────
  const renderer = new THREE.WebGLRenderer({
    canvas,
    antialias: true,
    alpha: false,
    powerPreference: 'high-performance',
  });
  renderer.setSize(window.innerWidth, window.innerHeight);
  renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));


  /* ─────────────────────────────────────────────────────────────
     § 3. LIGHTING
     ───────────────────────────────────────────────────────────── */
  const ambientLight = new THREE.AmbientLight(PALETTE.parchment, 0.15);
  scene.add(ambientLight);

  const pointLight1 = new THREE.PointLight(PALETTE.copper, 1.2, 60);
  pointLight1.position.set(8, 10, 10);
  scene.add(pointLight1);

  const pointLight2 = new THREE.PointLight(PALETTE.parchment, 0.6, 50);
  pointLight2.position.set(-10, -5, 5);
  scene.add(pointLight2);


  /* ─────────────────────────────────────────────────────────────
     § 4. CENTRAL WIREFRAME MONOLITH
     A slowly rotating icosahedron with visible edges
     ───────────────────────────────────────────────────────────── */
  const monolithGroup = new THREE.Group();

  // Outer wireframe — large icosahedron
  const icoGeo  = new THREE.IcosahedronGeometry(5, 1);
  const icoWire = new THREE.LineSegments(
    new THREE.WireframeGeometry(icoGeo),
    new THREE.LineBasicMaterial({
      color: PALETTE.copper,
      transparent: true,
      opacity: 0.35,
    })
  );
  monolithGroup.add(icoWire);

  // Inner wireframe — smaller octahedron for depth
  const octGeo  = new THREE.OctahedronGeometry(2.5, 0);
  const octWire = new THREE.LineSegments(
    new THREE.WireframeGeometry(octGeo),
    new THREE.LineBasicMaterial({
      color: PALETTE.parchment,
      transparent: true,
      opacity: 0.25,
    })
  );
  monolithGroup.add(octWire);

  // Core — tiny solid sphere glow
  const coreGeo = new THREE.SphereGeometry(0.3, 16, 16);
  const coreMat = new THREE.MeshBasicMaterial({
    color: PALETTE.copper,
    transparent: true,
    opacity: 0.7,
  });
  const coreMesh = new THREE.Mesh(coreGeo, coreMat);
  monolithGroup.add(coreMesh);

  scene.add(monolithGroup);


  /* ─────────────────────────────────────────────────────────────
     § 5. FLOATING ORBITAL RINGS
     Decorative wireframe torus rings orbiting the monolith
     ───────────────────────────────────────────────────────────── */
  const rings = [];

  function createRing(radius, tubeRadius, rotX, rotZ, color, opacity) {
    const geo  = new THREE.TorusGeometry(radius, tubeRadius, 8, 80);
    const wire = new THREE.LineSegments(
      new THREE.WireframeGeometry(geo),
      new THREE.LineBasicMaterial({
        color,
        transparent: true,
        opacity,
      })
    );
    wire.rotation.x = rotX;
    wire.rotation.z = rotZ;
    scene.add(wire);
    rings.push(wire);
    return wire;
  }

  createRing(8,  0.02, Math.PI * 0.45, 0.3,  PALETTE.parchment, 0.12);
  createRing(10, 0.02, Math.PI * 0.6,  -0.5, PALETTE.copper,    0.08);
  createRing(12, 0.02, Math.PI * 0.3,  0.8,  PALETTE.parchment, 0.06);


  /* ─────────────────────────────────────────────────────────────
     § 6. PARTICLE FIELD
     Scattered points filling the depth of the scene
     ───────────────────────────────────────────────────────────── */
  const isMobile     = window.innerWidth < 768;
  const PARTICLE_COUNT = isMobile ? 600 : 1500;

  const particleGeo  = new THREE.BufferGeometry();
  const positions    = new Float32Array(PARTICLE_COUNT * 3);
  const colors       = new Float32Array(PARTICLE_COUNT * 3);

  const colorParchment = new THREE.Color(PALETTE.parchment);
  const colorCopper    = new THREE.Color(PALETTE.copper);
  const colorIvory     = new THREE.Color(PALETTE.ivory);
  const particleColors = [colorParchment, colorCopper, colorIvory];

  for (let i = 0; i < PARTICLE_COUNT; i++) {
    const i3 = i * 3;
    // Spread particles in a cylindrical volume around the camera path
    positions[i3]     = (Math.random() - 0.5) * 60;     // x
    positions[i3 + 1] = (Math.random() - 0.5) * 60;     // y
    positions[i3 + 2] = (Math.random() - 0.5) * 120 - 20; // z (depth)

    const c = particleColors[Math.floor(Math.random() * particleColors.length)];
    colors[i3]     = c.r;
    colors[i3 + 1] = c.g;
    colors[i3 + 2] = c.b;
  }

  particleGeo.setAttribute('position', new THREE.BufferAttribute(positions, 3));
  particleGeo.setAttribute('color',    new THREE.BufferAttribute(colors, 3));

  const particleMat = new THREE.PointsMaterial({
    size: isMobile ? 0.12 : 0.08,
    vertexColors: true,
    transparent: true,
    opacity: 0.6,
    sizeAttenuation: true,
    depthWrite: false,
  });

  const particles = new THREE.Points(particleGeo, particleMat);
  scene.add(particles);


  /* ─────────────────────────────────────────────────────────────
     § 7. GSAP SCROLL TRIGGER — Camera Depth Animation
     ───────────────────────────────────────────────────────────── */
  gsap.registerPlugin(ScrollTrigger);

  // Camera moves forward into the scene as user scrolls
  gsap.to(camera.position, {
    z: -30,
    ease: 'none',
    scrollTrigger: {
      trigger: '#scroll-spacer',
      start: 'top top',
      end: 'bottom bottom',
      scrub: 1.5,
    },
  });

  // Monolith scales up and rotates as camera approaches
  gsap.to(monolithGroup.rotation, {
    y: Math.PI * 2,
    ease: 'none',
    scrollTrigger: {
      trigger: '#scroll-spacer',
      start: 'top top',
      end: 'bottom bottom',
      scrub: 2,
    },
  });

  gsap.to(monolithGroup.scale, {
    x: 1.6,
    y: 1.6,
    z: 1.6,
    ease: 'none',
    scrollTrigger: {
      trigger: '#scroll-spacer',
      start: 'top top',
      end: 'bottom bottom',
      scrub: 2,
    },
  });

  // UI layer fades out as user scrolls past hero
  gsap.to('#ui-layer', {
    opacity: 0,
    ease: 'none',
    scrollTrigger: {
      trigger: '#scroll-spacer',
      start: 'top top',
      end: '30% top',
      scrub: 1,
    },
  });


  /* ─────────────────────────────────────────────────────────────
     § 8. NAVBAR SCROLL EFFECT
     ───────────────────────────────────────────────────────────── */
  const navbar = document.getElementById('navbar');
  ScrollTrigger.create({
    start: 80,
    onUpdate: (self) => {
      if (self.scroll() > 80) {
        navbar.classList.add('nav--scrolled');
      } else {
        navbar.classList.remove('nav--scrolled');
      }
    },
  });


  /* ─────────────────────────────────────────────────────────────
     § 9. RENDER LOOP (Optimized)
     ───────────────────────────────────────────────────────────── */
  const clock = new THREE.Clock();

  function animate() {
    requestAnimationFrame(animate);

    const elapsed = clock.getElapsedTime();
    const delta   = clock.getDelta();

    // Slow idle rotation on the monolith
    monolithGroup.rotation.x = Math.sin(elapsed * 0.15) * 0.3;
    monolithGroup.rotation.z = Math.cos(elapsed * 0.1)  * 0.15;

    // Inner octahedron counter-rotates
    octWire.rotation.y = -elapsed * 0.3;
    octWire.rotation.x = elapsed * 0.2;

    // Core pulse
    const pulse = 0.7 + Math.sin(elapsed * 1.5) * 0.3;
    coreMesh.material.opacity = pulse;
    coreMesh.scale.setScalar(0.8 + Math.sin(elapsed * 2) * 0.2);

    // Rings slow rotation
    rings.forEach((ring, i) => {
      ring.rotation.z += 0.0005 * (i % 2 === 0 ? 1 : -1);
      ring.rotation.y += 0.0003;
    });

    // Subtle light movement
    pointLight1.position.x = 8 + Math.sin(elapsed * 0.5) * 3;
    pointLight1.position.y = 10 + Math.cos(elapsed * 0.4) * 2;

    renderer.render(scene, camera);
  }

  animate();


  /* ─────────────────────────────────────────────────────────────
     § 10. WINDOW RESIZE HANDLER
     ───────────────────────────────────────────────────────────── */
  let resizeTimeout;
  window.addEventListener('resize', () => {
    clearTimeout(resizeTimeout);
    resizeTimeout = setTimeout(() => {
      camera.aspect = window.innerWidth / window.innerHeight;
      camera.updateProjectionMatrix();
      renderer.setSize(window.innerWidth, window.innerHeight);
      renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
    }, 100);
  });


  /* ─────────────────────────────────────────────────────────────
     § 11. AJAX — JOIN CLUB FORM HANDLER
     ───────────────────────────────────────────────────────────── */
  const joinForm   = document.getElementById('join-form');
  const joinBtn    = document.getElementById('join-btn');
  const joinInput  = document.getElementById('join-code');
  const statusToast = document.getElementById('status-toast');

  /**
   * Display a status toast message
   * @param {'success'|'error'} type
   * @param {string} message
   */
  function showToast(type, message) {
    statusToast.textContent = message;
    statusToast.className = 'status-toast status-toast--visible status-toast--' + type;

    // Auto-dismiss after 5 seconds
    setTimeout(() => {
      statusToast.classList.remove('status-toast--visible');
    }, 5000);
  }

  /**
   * Toggle the loading state of the submit button
   * @param {boolean} loading
   */
  function setLoading(loading) {
    if (loading) {
      joinBtn.classList.add('btn--loading');
      joinBtn.disabled = true;
    } else {
      joinBtn.classList.remove('btn--loading');
      joinBtn.disabled = false;
    }
  }

  joinForm.addEventListener('submit', async function (e) {
    e.preventDefault();

    const joinCode = joinInput.value.trim();
    if (!joinCode) {
      showToast('error', 'Please enter an invitation code.');
      joinInput.focus();
      return;
    }

    setLoading(true);

    // NOTE: In production, user_id would come from a session/JWT.
    // For this demo, we use a placeholder user_id = 1.
    const payload = {
      join_code: joinCode,
      user_id: 1,
    };

    try {
      const response = await fetch('join_club.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload),
      });

      const data = await response.json();

      if (data.status === 'success') {
        showToast('success', data.message);
        joinInput.value = '';

        // Celebration: pulse the monolith
        gsap.to(monolithGroup.scale, {
          x: 2.2, y: 2.2, z: 2.2,
          duration: 0.4,
          yoyo: true,
          repeat: 1,
          ease: 'elastic.out(1, 0.5)',
        });
        gsap.to(coreMesh.material, {
          opacity: 1,
          duration: 0.6,
          yoyo: true,
          repeat: 1,
        });
      } else {
        showToast('error', data.message);
      }
    } catch (err) {
      console.error('Join request failed:', err);
      showToast('error', 'Connection error. Please try again.');
    } finally {
      setLoading(false);
    }
  });

})();
