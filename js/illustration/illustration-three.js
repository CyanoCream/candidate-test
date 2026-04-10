import * as THREE from 'three';
import { FBXLoader } from 'three/addons/loaders/FBXLoader.js';
import { OrbitControls } from 'three/addons/controls/OrbitControls.js';

// Setup Container
const container = document.getElementById('illustration-3d-container');
const width = container.clientWidth || window.innerWidth;
const height = window.innerHeight; 

// Scene setup
const scene = new THREE.Scene();
scene.background = new THREE.Color(0x000000);

// Camera setup — isometric-like view matching expected-result.jpg
const camera = new THREE.PerspectiveCamera(45, width / height, 0.1, 1000);
camera.position.set(-3.5, 2.5, -2.5);

// Renderer setup
const renderer = new THREE.WebGLRenderer({ antialias: true });
renderer.setSize(width, height);
renderer.setPixelRatio(window.devicePixelRatio);
container.appendChild(renderer.domElement);

// Controls setup
const controls = new OrbitControls(camera, renderer.domElement);
controls.enableDamping = true;

// Lighting setup
const ambientLight = new THREE.AmbientLight(0xffffff, 0.6);
scene.add(ambientLight);

const directionalLight = new THREE.DirectionalLight(0xffffff, 1.2);
directionalLight.position.set(10, 20, 15);
scene.add(directionalLight);

// Axis Helper (RGB: X=Red, Y=Green, Z=Blue)
const axesHelper = new THREE.AxesHelper(5);
scene.add(axesHelper);

// Model Loading and Assembly
const fbxLoader = new FBXLoader();
fbxLoader.load(
    'model/wood/wood.fbx',
    (baseObject) => {
        const woodMesh = baseObject.children.find(child => child.isMesh);
        if (!woodMesh) return;

        // The FBX model has a -90° rotation on X axis (from 3D software export).
        // This causes the model's Z-up to become Y-up, making the model stand vertical.
        // The model's apparent world size with this rotation:
        //   X = 2m (width), Y = 3m (was depth, now vertical), Z = 0.2m (was height, now shallow)
        //
        // To lay the model flat (horizontal), we need to counter this rotation
        // by resetting the mesh rotation and using the correct axis mapping.
        //
        // Base dimensions in the MODEL's local space (before rotation):
        //   model-X = 2 (width), model-Y = 3 (length), model-Z = 0.2 (thickness)
        //
        // After the FBX's built-in -90° X rotation:
        //   scene-X = model-X = 2 (width)
        //   scene-Y = model-Z = 0.2 (now the vertical height — very thin)
        //   scene-Z = -model-Y = -3 (depth going backward)
        //
        // But the model appears tall because model-Y=3 maps to scene Y.
        // So the effective world size is: X=2, Y=3 (tall!), Z=0.2 (thin).
        //
        // FIX: Reset the child's rotation and scale to match our desired world coords.
        // We want: target X=width, Y=height(thin), Z=depth(long)
        // The geometry in local space (before FBX rotation) has:
        //   geo-X = width extent (=2)
        //   geo-Y = depth/length extent (=3)  
        //   geo-Z = thickness extent (=0.2)
        //
        // So after resetting rotation, model-Y still points UP in the geometry.
        // We need:
        //   scale.x = targetWidth / geoBasisX  
        //   scale.y = targetDepth / geoBasisY  (model-Y will be scene-Y after rotation fix)
        //   scale.z = targetHeight / geoBasisZ  (model-Z = thickness)
        //
        // And then we use rotation.x = -PI/2 on the model to lay depth along Z and height along Y.

        // But actually, simpler approach: just wrap in a container group that 
        // corrects the orientation.
        
        // Get the base object's effective world size by computing bounding box
        // The baseObject applies scale and rotation from FBX
        const tempScene = new THREE.Scene();
        const tempClone = baseObject.clone();
        tempScene.add(tempClone);
        const bbox = new THREE.Box3().setFromObject(tempClone);
        const worldSize = new THREE.Vector3();
        bbox.getSize(worldSize);
        tempScene.remove(tempClone);

        // worldSize gives us the actual rendered dimensions
        // Based on original code analysis: X=2, Y=?, Z=3 (with Y being height 0.2)
        // But FBX rotation makes it: X=2, Y=3(tall), Z=0.2(thin)
        //
        // We track the world axes:
        const worldX = worldSize.x; // 2 (width)
        const worldY = worldSize.y; // likely 3 (the "depth" rotated to vertical) 
        const worldZ = worldSize.z; // likely 0.2 (thickness)

        const assembly = new THREE.Group();
        
        /**
         * Create a wood panel with target dimensions in world space.
         * The model's axes after FBX rotation: X=width, Y=depth(vertical), Z=height(thin)
         * We need to lay it flat: targetWidth→X, targetHeight→Y, targetDepth→Z
         * So we scale differently to account for axis swapping.
         *
         * @param {number} w - target width (X) in meters
         * @param {number} h - target height (Y) in meters (typically 0.2m)
         * @param {number} d - target depth (Z) in meters
         * @param {number} px - position X (center)
         * @param {number} py - position Y (center)
         * @param {number} pz - position Z (center)
         * @param {boolean} rotateGrain - rotate 90° for cross-lamination
         */
        const makePanel = (w, h, d, px, py, pz, rotateGrain = false) => {
            // Instead of cloning just the mesh, clone the entire baseObject
            // to preserve the FBX transformations, then adjust scale on top
            const panelGroup = baseObject.clone();
            
            // The baseObject renders at worldSize (worldX, worldY, worldZ)
            // We want it to render at (w, h, d) in world space
            // Since the FBX rotation maps: model height(0.2) → appears as Y=tall
            // Let me just scale the whole thing relative to worldSize
            
            // With the FBX rotation, the model appears as:
            //   X = worldX (width of plank)
            //   Y = worldY (length of plank, standing up!)
            //   Z = worldZ (thickness of plank)
            //
            // We want the panel to be:
            //   X = w (width)
            //   Y = h (height/thickness, small)
            //   Z = d (depth/length)
            //
            // So to remap: we want model-X → scene-X (w), model-Y → scene-Z (d), model-Z → scene-Y (h)
            // This requires a +90° rotation around X to "lay it flat"
            //
            // Scale before rotation:
            //   panelGroup.scale.x *= w / worldX  (width mapping)
            //   panelGroup.scale.y *= d / worldY  (model-Y → scene-Z after rotation)
            //   panelGroup.scale.z *= h / worldZ  (model-Z → scene-Y after rotation)
            
            const sx = w / worldX;
            const sy = d / worldY;  
            const sz = h / worldZ;
            
            panelGroup.scale.set(
                panelGroup.scale.x * sx,
                panelGroup.scale.y * sy,
                panelGroup.scale.z * sz
            );
            
            // Rotate 90° around X to lay the panel flat (model-Y goes to -Z, model-Z goes to Y)
            panelGroup.rotation.x += Math.PI / 2;
            
            if (rotateGrain) {
                // Additional 90° around Y for cross-laminated grain direction
                panelGroup.rotation.y = Math.PI / 2;
            }
            
            panelGroup.position.set(px, py, pz);
            assembly.add(panelGroup);
            return panelGroup;
        };

        // ================================================================
        // CLT Assembly matching expected-result.jpg
        // ================================================================

        // Layer 1 (Bottom): 1.54m × 0.2m × 3.0m — full base slab
        makePanel(1.54, 0.2, 3.0, 0.77, 0.1, 1.5, false);

        // Layer 2 (Middle, cross-laminated): 0.54m × 0.2m × 2.0m
        makePanel(0.54, 0.2, 2.0, 0.29, 0.3, 1.0, true);

        // Layer 3 (Top): 0.4m × 0.2m × 2.0m
        makePanel(0.4, 0.2, 2.0, 0.22, 0.5, 1.0, false);

        scene.add(assembly);
        
        // Add dimension labels
        addDetailedDimensions(scene);
        
        // Point camera at assembly center
        controls.target.set(0.77, 0.2, 1.2);
        camera.lookAt(0.77, 0.2, 1.2);
    },
    undefined,
    (error) => console.error(error)
);

// Create text sprite for dimension labels
function createTextSprite(text) {
    const canvas = document.createElement('canvas');
    const context = canvas.getContext('2d');
    canvas.width = 256;
    canvas.height = 64;
    context.clearRect(0, 0, canvas.width, canvas.height);
    context.fillStyle = 'white';
    context.font = 'bold 28px Arial';
    context.textAlign = 'center';
    context.textBaseline = 'middle';
    context.fillText(text, 128, 32);
    const texture = new THREE.CanvasTexture(canvas);
    texture.minFilter = THREE.LinearFilter;
    const sprite = new THREE.Sprite(new THREE.SpriteMaterial({ 
        map: texture, 
        depthTest: false,
        transparent: true
    }));
    sprite.scale.set(0.4, 0.1, 1);
    sprite.renderOrder = 999;
    return sprite;
}

function addLabel(scene, start, end, text, offset = new THREE.Vector3(0, 0, 0)) {
    const geometry = new THREE.BufferGeometry().setFromPoints([start, end]);
    const line = new THREE.Line(geometry, new THREE.LineBasicMaterial({ color: 0xffffff }));
    scene.add(line);

    const midPoint = new THREE.Vector3().addVectors(start, end).multiplyScalar(0.5).add(offset);
    const label = createTextSprite(text);
    label.position.copy(midPoint);
    scene.add(label);

    const dotGeom = new THREE.SphereGeometry(0.02, 8, 8);
    const dotMat = new THREE.MeshBasicMaterial({ color: 0xffffff });
    [start, end].forEach(pos => {
        const dot = new THREE.Mesh(dotGeom, dotMat);
        dot.position.copy(pos);
        scene.add(dot);
    });
}

function addDetailedDimensions(scene) {
    // Bottom front edge X-axis segments (at Y=0, Z=3)
    addLabel(scene, 
        new THREE.Vector3(0, 0, 3.0), 
        new THREE.Vector3(0.5, 0, 3.0), 
        '0.5m', new THREE.Vector3(0, -0.08, 0.08));
    
    addLabel(scene, 
        new THREE.Vector3(0.5, 0, 3.0), 
        new THREE.Vector3(1.04, 0, 3.0), 
        '0.54m', new THREE.Vector3(0, -0.08, 0.08));
    
    addLabel(scene, 
        new THREE.Vector3(1.04, 0, 3.0), 
        new THREE.Vector3(1.54, 0, 3.0), 
        '0.5m', new THREE.Vector3(0, -0.08, 0.08));

    // Right side 3m length
    addLabel(scene, 
        new THREE.Vector3(1.54, 0, 0), 
        new THREE.Vector3(1.54, 0, 3.0), 
        '3m', new THREE.Vector3(0.15, 0.05, 0));

    // Right front 0.2m height
    addLabel(scene, 
        new THREE.Vector3(1.54, 0, 3.0), 
        new THREE.Vector3(1.54, 0.2, 3.0), 
        '0.2m', new THREE.Vector3(0.15, 0, 0));

    // Left side 2m lengths
    addLabel(scene, 
        new THREE.Vector3(0, 0.2, 0), 
        new THREE.Vector3(0, 0.2, 2.0), 
        '2m', new THREE.Vector3(-0.12, 0.05, 0));

    addLabel(scene, 
        new THREE.Vector3(0, 0.6, 0), 
        new THREE.Vector3(0, 0.6, 2.0), 
        '2m', new THREE.Vector3(-0.12, 0.05, 0));

    // Left front 0.2m heights
    addLabel(scene, 
        new THREE.Vector3(0, 0, 2.0), 
        new THREE.Vector3(0, 0.2, 2.0), 
        '0.2m', new THREE.Vector3(-0.12, 0, 0));

    addLabel(scene,
        new THREE.Vector3(0, 0.4, 2.0),
        new THREE.Vector3(0, 0.6, 2.0),
        '0.2m', new THREE.Vector3(-0.12, 0, 0));

    // Top face 0.4m width
    addLabel(scene, 
        new THREE.Vector3(0.02, 0.6, 0), 
        new THREE.Vector3(0.42, 0.6, 0), 
        '0.4m', new THREE.Vector3(0, 0.06, -0.05));

    // 0.02m gap
    addLabel(scene, 
        new THREE.Vector3(0, 0.4, 0), 
        new THREE.Vector3(0.02, 0.4, 0), 
        '0.02m', new THREE.Vector3(0, 0.1, -0.05));
}

function animate() {
    requestAnimationFrame(animate);
    controls.update();
    renderer.render(scene, camera);
}
animate();

window.addEventListener('resize', () => {
    const newWidth = container.clientWidth || window.innerWidth;
    const newHeight = window.innerHeight;
    camera.aspect = newWidth / newHeight;
    camera.updateProjectionMatrix();
    renderer.setSize(newWidth, newHeight);
});