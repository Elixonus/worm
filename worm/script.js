//----------------------------
//----- CLASS DEFINITIONS ----
//----------------------------

function point(x, y) {
    return {x: x, y: y};
}

function line(p1, p2) {
    return {p1: p1, p2: p2};
}

function circle(center, radius) {
    return {center: center, radius: radius};
}

function rectangle(center, width, height) {
    return {center: center, width: width, height: height};
}

class Filmable {
    constructor(camera) {
        // Filmable extension class should apply to any game object that may be followed by the camera.
        this.camera = camera;
    }

    follow() {
        // Used to set the common camera instance to point to this object which extends this class.
        this.camera.filmedObject = this;
        this.updateCameraTarget();
    }

    unfollow() {
        this.camera.filmedObject = null;
    }

    updateCameraTarget() {

    }
}

class Worm extends Filmable {
    constructor(camera) {
        // Worm class used to create a worm object that has properties like blink used as storage for rendering a 
        // realistic blinking effect, and happiness which increases with worm interaction is also rendered separately.
        // The property controllable describes whether the user has keyboard input control over the worm (many worms
        // can be set to be controlled by the player due to the nature of class). Important: the nodes array contains
        // a list of the node objects the worm is made of. Each node object contains a position, rotation, rotation
        // velocity, as well as additional properties to smoothen the worm growth transition. The type property is
        // used to distinguish worms of different appearances and the turn property is basically the normalized angular
        // velocity of the head node.
        super(camera);
        this.blink = 0;
        this.blinkDirection = 0;
        this.blinkWait = Math.round(Math.random() * 250) + 10;
        this.controllable = true;
        this.dead = false;
        this.happiness = 0;
        this.happinessAchieved = 0;
        this.happinessDirection = -1;
        this.happinessWait = 0;
        this.hue = 0;
        this.nodes = [];
        this.type = 1;
        this.turn = 0;

        // The first node is initialized with a random position and rotation within the bounds of the map.
        let tempRotation = 2 * Math.PI * Math.random();
        let tempRadius = 0.9 * WORLD_RADIUS * Math.sqrt(Math.random());
        this.nodes.push(
            {
                active: true,
                activeTime: 0,
                x: tempRadius * Math.cos(tempRotation),
                y: tempRadius * Math.sin(tempRotation),
                r: 2 * Math.PI * Math.random(),
                rs: 0
            });
    }

    setControllable(controllable = true) {
        // Make the worm be controllable by the player with keyboard interaction.
        if (controllable !== this.controllable) {
            if (controllable && this.controllable === false) {
                // Delete unnecessary AI properties not needed for player controlled worm if needed.
                if (this.hasOwnProperty("controllable")) {
                    delete this.botWait;
                    delete this.botDesiredDirection;
                    delete this.botEnergy;
                }
            } else if (!controllable) {
                // Add necessary AI properties to the worm object. More specifically, the bot wait value is used to control
                // the speed of the thinking loop; when it reaches 0, the bot makes a decision with the sensory input at that
                // time and resets the counter, repeating the process.
                this.botWait = 0;
                this.botDesiredDirection = this.r;
                this.botEnergy = null;
            }

            this.controllable = controllable;
        }
    }

    setType(type) {
        this.type = type;
    }

    setRandomType(minimumType, maximumType) {
        this.setType(Math.round(Math.random() * (maximumType - minimumType) + minimumType));
    }

    setLength(length) {
        // Set the configuration length of the worm to the given parameter without breaking the space properties
        // of the nodes. This function changes the length instantly, not smoothly.
        let lengthDifference = Math.abs(length - this.nodes.length);

        if (length < this.nodes.length) {
            this.subtractNode(lengthDifference);
        } else if (length > this.nodes.length) {
            this.addNode(lengthDifference);
        }
    }

    setRandomLength(minimumLength, maximumLength) {
        this.setLength(Math.round(Math.random() * (maximumLength - minimumLength) + minimumLength));
    }

    setHue(hue) {
        this.hue = hue;
    }

    setRandomHue(minimumHue = 0, maximumHue = 359) {
        this.hue = Math.random() * (maximumHue - minimumHue) + minimumHue;
    }

    addNode(count = 1) {
        // Adds a number of nodes to the tail instantly, without breaking the space properties of the nodes.
        // The algorithm works by backtracking from the last node with the separation distance a given number
        // of times.
        for (let n = 0; n < count; n++) {
            let tempLastNode = this.nodes[this.nodes.length - 1];
            this.nodes.push(
                {
                    active: true,
                    activeTime: 1,
                    x: tempLastNode.x - 20 * Math.cos(tempLastNode.r),
                    y: tempLastNode.y - 20 * Math.sin(tempLastNode.r),
                    r: tempLastNode.r
                });
        }
    }

    addNodeSmooth(count = 1) {
        // Adds a number of nodes to the tail smoothly, and without breaking the space properties of the nodes.
        // The system responsible for making the growth transition smooth works by starting a node at the tail
        // and incrementally increasing the separation distance closer to the normal separation distance.
        for (let n = 0; n < count; n++) {
            let tempLastNode = this.nodes[this.nodes.length - 1];
            this.nodes.push(
                {
                    active: false,
                    activeTime: 0,
                    x: tempLastNode.x,
                    y: tempLastNode.y,
                    r: tempLastNode.r
                });
        }
    }

    subtractNode(count) {
        // Works like adding a node but instead a number of nodes is removed from the tail, instantly.
        if (count === undefined) {
            count = 1;
        }

        this.nodes.splice(this.nodes.length - count, count);
    }

    increaseHappiness(value) {
        if (this.happiness < 1) {
            this.happiness += value;

            if (this.happiness > 1) {
                this.happiness = 1;
            }
        }
    }

    decreaseHappiness(value) {
        if (this.happiness > 0) {
            this.happiness -= value;

            if (this.happiness < 0) {
                this.happiness = 0;
            }
        }
    }

    die() {
        this.dead = true;
    }

    tick(wormCollection) {
        if (!this.controllable) {
            //--------- AI CODE ----------

            this.botWait -= timeScale;

            if (this.botWait <= 0) {
                let closestEnergy = null;
                let closestDistance = null;

                for (let n = 0; n < energies.length; n++) {
                    const energy = energies[n];
                    let currentDistance = distance(this.nodes[0], energy);

                    if (((closestEnergy === null && currentDistance < 500) || currentDistance < (0.3 + 0.3 * Math.random()) * closestDistance) && !energy.isDestroyed && (energy.botWorm === null || energy.botWorm === this)) {
                        closestEnergy = energy;
                        closestDistance = currentDistance;
                    }
                }

                this.botEnergy = closestEnergy;

                if (closestEnergy !== null) {
                    this.botDesiredDirection = (Math.atan2(this.nodes[0].y - closestEnergy.y, this.nodes[0].x - closestEnergy.x) + 2 * Math.PI) % (2 * Math.PI);
                    closestEnergy.botWorm = this;
                } else {
                    this.botDesiredDirection += 0.25 * Math.PI * Math.sign(2 * Math.random() - 1);
                    this.botDesiredDirection %= 2 * Math.PI;
                }

                let angleDifference = calculateAngleDifference(this.nodes[0].r, this.botDesiredDirection);

                if (angleDifference < 0) {
                    this.turn = -1;
                } else if (angleDifference > 0) {
                    this.turn = 1;
                } else {
                    this.turn = 0;
                }

                this.botWait = Math.round(Math.random() * 20 + 30);
            }
        }

        let wormIndex = wormCollection.indexOf(this);
        let tempFirstNode = this.nodes[0];

        if (this.turn === -1) {
            tempFirstNode.rs = clampMax(tempFirstNode.rs + Math.PI / 360, Math.PI / 90);
        } else if (this.turn === 1) {
            tempFirstNode.rs = clampMin(tempFirstNode.rs - Math.PI / 360, -Math.PI / 90);
        } else {
            if (tempFirstNode.rs < 0) {
                tempFirstNode.rs += Math.PI / 1440;

                if (tempFirstNode.rs > 0) {
                    tempFirstNode.rs = 0;
                }
            }

            if (tempFirstNode.rs > 0) {
                tempFirstNode.rs -= Math.PI / 1440;

                if (tempFirstNode.rs < 0) {
                    tempFirstNode.rs = 0;
                }
            }
        }

        // Move the first node in its direction.
        tempFirstNode.r += tempFirstNode.rs * timeScale;
        tempFirstNode.r %= (2 * Math.PI);
        tempFirstNode.x += 3 * Math.cos(tempFirstNode.r) * timeScale;
        tempFirstNode.y += 3 * Math.sin(tempFirstNode.r) * timeScale;

        // Move the rest of the nodes.
        for (let n = 1; n < this.nodes.length; n++) {
            let tempCurrentNode = this.nodes[n];
            let tempPreviousNode = this.nodes[n - 1];

            if (!tempCurrentNode.active) {
                if (distance(tempCurrentNode, tempPreviousNode) >= 5) {
                    tempCurrentNode.active = true;
                }
            }

            if (tempCurrentNode.active) {
                if (tempCurrentNode.activeTime < 1) {
                    tempCurrentNode.activeTime += 0.05 * timeScale;

                    if (tempCurrentNode.activeTime > 1) {
                        tempCurrentNode.activeTime = 1;
                    }
                }

                // Keep the subsequent nodes close to the leading nodes.
                tempCurrentNode.r = Math.atan2(tempPreviousNode.y - tempCurrentNode.y, tempPreviousNode.x - tempCurrentNode.x);
                tempCurrentNode.x = tempPreviousNode.x - 5 * Math.cos(tempCurrentNode.r);
                tempCurrentNode.y = tempPreviousNode.y - 5 * Math.sin(tempCurrentNode.r);
            }
        }

        // Change the smile of the worm based on surrounding worms.
        let foundHappiness = false;

        for (let n = 0; n < wormCollection.length; n++) {
            if (wormIndex !== n) {
                if (distance(this.nodes[0], wormCollection[n].nodes[0]) < 150) {
                    foundHappiness = true;
                }
            }
        }

        // Happiness control loop.
        if (foundHappiness) {
            if (this.happinessWait < 50) {
                this.happinessWait += timeScale;

                if (this.happinessWait > 50) {
                    this.happinessWait = 50;
                }
            }
        } else {
            if (this.happinessWait > 0) {
                this.happinessWait -= timeScale;

                if (this.happinessWait < 0) {
                    this.happinessWait = 0;
                }
            }
        }

        if (this.happinessWait === 0) {
            this.happinessDirection = -1;
        } else if (this.happinessWait === 50) {
            if (this.happinessDirection === -1) {
                this.happinessAchieved++;
            }

            this.happinessDirection = 1;
        }

        if (this.happinessDirection === -1) {
            this.decreaseHappiness(1 / 10 * timeScale);
        } else if (this.happinessDirection === 1) {
            this.increaseHappiness(1 / 10 * timeScale);
        }

        // Blink control loop.
        this.blinkWait -= timeScale;

        if (this.blinkWait <= 0) {
            // Blink faster if happy.
            if (this.happiness < 0.5) {
                this.blinkWait = Math.round(Math.random() * 250) + 100;
            } else {
                this.blinkWait = Math.round(Math.random() * 100) + 10;
            }

            this.blinkDirection = 1;
        }

        this.blink += 1 / 10 * this.blinkDirection * timeScale;

        if (this.blink > 1) {
            this.blink = 2 - this.blink;
            this.blinkDirection = -1;
        }

        if (this.blink < 0) {
            this.blink = 0;
            this.blinkDirection = 0;
        }

        // Update shared camera instance to follow the position of current worm if following this worm instance.
        this.updateCameraTarget();
    }

    moveTo(p) {
        // Move the worm instantly and abruptly with the head node teleporting to the given parameter point.
        for (let n = 1; n < this.nodes.length; n++) {
            this.nodes[n].x += (p.x - this.nodes[0].x);
            this.nodes[n].y += (p.y - this.nodes[0].y);
        }

        this.nodes[0].x = p.x;
        this.nodes[0].y = p.y;
    }

    inGame(camera) {
        // Returns whether the worm should be rendered on the main game view. Works by checking to see
        // if at least one node is in the viewport box. Calculation accounts for the maximum size shape
        // of the worm and the glow radius.
        let tempShapePadding = 80;
        let tempGlowPadding = clampMin(20 * camera.zoom, 20);
        let tempPadding = tempShapePadding * camera.zoom + tempGlowPadding;

        for (let n = 0; n < this.nodes.length; n++) {
            if (pointInRectangle(point(this.nodes[n].x - camera.x, this.nodes[n].y - camera.y), rectangle(point(0, 0), gameWidth / camera.zoom, gameHeight / camera.zoom), tempPadding)) {
                return true;
            }
        }

        return false;
    }

    inMinimap(camera, expanded) {
        // Returns whether the worm should be rendered on the minimap view. Works the same way as the
        // previous method except calculates within a minimap box instead of main box. If the expanded
        // parameter is set to true the algorithm checks within an expanded minimap box.
        let tempShapePadding = 80;
        let tempGlowPadding = clampMin(20 * camera.zoom, 20);
        let tempPadding = tempShapePadding * camera.zoom * minimapZoom + tempGlowPadding;
        let tempWidth = minimapWidth;
        let tempHeight = minimapHeight;

        if (expanded) {
            tempWidth = gameWidth;
            tempHeight = gameHeight;
        }

        for (let n = 0; n < this.nodes.length; n++) {
            if (pointInRectangle(point(this.nodes[n].x - camera.x, this.nodes[n].y - camera.y), rectangle(point(0, 0), tempWidth / (camera.zoom * minimapZoom), tempHeight / (camera.zoom * minimapZoom)), tempPadding)) {
                return true;
            }
        }

        return false;
    }

    updateCameraTarget() {
        // Set the position of the camera to the position of the head node if this worm instance is
        // being filmed.
        if (this.camera.filmedObject === this) {
            this.camera.setTarget(this.nodes[0]);
        }
    }
}

class Energy extends Filmable {
    constructor(camera) {
        super(camera);
        let tempRotation = 2 * Math.PI * Math.random();
        let tempRadius = WORLD_RADIUS * Math.sqrt(Math.random());
        this.decayFunc = null;
        this.isDestroyed = false;
        this.destroyFunc = null;
        this.isDecaying = false;
        this.opacity = 1;
        this.phase = 2 * Math.PI * Math.random();
        this.r = this.rStatic + 0.2 * Math.sin(this.phase);
        this.rStatic = 2 * Math.PI * Math.random();
        this.type = Math.round(2 * Math.random() + 1);
        this.x = tempRadius * Math.cos(tempRotation);
        this.y = tempRadius * Math.sin(tempRotation);
        this.botWorm = null;
    }

    moveTo(p) {
        this.x = p.x;
        this.y = p.y;
    }

    decay() {
        this.isDecaying = true;

        if (this.decayFunc !== null) {
            this.decayFunc();
        }
    }

    onDecay(func) {
        this.decayFunc = func;
    }

    destroy(energyCollection) {
        this.isDestroyed = true;
        this.isDecaying = false;
        this.botWorm = null;

        let index = energyCollection.indexOf(this);
        energyCollection.splice(index, 1);

        if (this.destroyFunc !== null) {
            this.destroyFunc();
        }
    }

    onDestroy(func) {
        this.destroyFunc = func;
    }

    tick(energyCollection) {
        // Energy decay logic.
        if (this.isDecaying) {
            this.opacity -= 0.05 * timeScale;

            if (this.opacity <= 0) {
                this.destroy(energyCollection);
                return;
            }
        }

        this.phase += 0.02 * timeScale;
        this.r = this.rStatic + 0.2 * Math.sin(this.phase);
        this.updateCameraTarget();
    }

    updateCameraTarget() {
        if (this.camera.filmedObject === this) {
            this.camera.setTarget(this);
        }
    }

    inGame(camera) {
        // Returns whether the energy should be rendered on the main view.
        let tempShapePadding = 80;
        let tempGlowPadding = clampMin(20 * camera.zoom, 20);
        let tempPadding = tempShapePadding * camera.zoom + tempGlowPadding;
        return pointInRectangle(point(this.x - camera.x, this.y - camera.y), rectangle(point(0, 0), gameWidth / camera.zoom, gameHeight / camera.zoom), tempPadding);
    }

    inMinimap(camera, expanded) {
        // Returns whether the energy should be rendered on the minimap.
        let tempShapePadding = 80;
        let tempGlowPadding = clampMin(20 * camera.zoom, 20);
        let tempPadding = tempShapePadding * camera.zoom * minimapZoom + tempGlowPadding;
        let tempWidth = minimapWidth;
        let tempHeight = minimapHeight;

        if (expanded) {
            tempWidth = gameWidth;
            tempHeight = gameHeight;
        }

        return pointInRectangle(point(this.x - camera.x, this.y - camera.y), rectangle(point(0, 0), tempWidth / (camera.zoom * minimapZoom), tempHeight / (camera.zoom * minimapZoom)), tempPadding);
    }
}

class Camera {
    constructor(p = point(0, 0), zoom = 1) {
        // Camera instance used to store the position and zoom of the current view. Currently, one
        // camera per game instance is allowed but abstractly, multiple camera instances can be used
        // to create a split screen effect if the rendering code allows.
        this.filmedObject = null;
        this.x = p.x;
        this.y = p.y;
        this.targetX = this.x;
        this.targetY = this.y;
        this.zoom = zoom;
        this.MAX_SPEED = 1000;
    }

    setTarget(p) {
        this.targetX = p.x;
        this.targetY = p.y
    }

    moveTo(p) {
        this.x = p.x;
        this.y = p.y;
    }

    moveToSmooth(p) {
        // Perform continuous linear interpolation from current point to target point. The outcome is that
        // the camera movement has an exponentially decaying ease-out transition.
        this.x = interpolateLinear(this.x, p.x, 0.2);
        this.y = interpolateLinear(this.y, p.y, 0.2);
    }

    tick() {
        // Calls camera movement logic used to set the camera properties to the target.
        this.moveToSmooth(point(this.targetX, this.targetY));
    }
}

//----------------------------
//--- FUNCTION DEFINITIONS ---
//----------------------------

function getShadows() {
    // Returns the shadow blur multiplier used for rendering based on user settings.
    if (shadows) {
        return clampMin(20 * camera.zoom, 20);
    } else {
        return 0;
    }
}

function hueString(hue) {
    // Creates a color string of a given hue and maximum perceived color intensity.
    return ("hsl(" + hue + ", 100%, 50%)");
}

function updateTurn(eventKey) {
    if (eventKey === "ArrowLeft" || eventKey.toUpperCase() === "A" || eventKey === "ArrowRight" || eventKey.toUpperCase() === "D") {
        for (let n = 0; n < worms.length; n++) {
            const worm = worms[n];

            if (!worm.dead && worm.controllable) {
                worm.turn = 0;

                if (keysPressed.includes("ArrowLeft") || keysPressed.includes("a") || keysPressed.includes("A")) {
                    worm.turn -= 1;
                }

                if (keysPressed.includes("ArrowRight") || keysPressed.includes("d") || keysPressed.includes("D")) {
                    worm.turn += 1;
                }
            }
        }
    }
}

function resize() {
    // Resize the fixed canvas dimensions with code to maximize view area while keeping aspect ratio 16:9.
    const windowWidth = window.innerWidth;
    const windowHeight = window.innerHeight;

    // Check whether the aspect ratio of the user screen is greater than the aspect ratio of the game.
    if (windowWidth / windowHeight > gameWidth / gameHeight) {
        canvas.style.width = `${(windowHeight / windowWidth) * (gameWidth / gameHeight) * 100}%`;
        canvas.style.height = "100%";
    } else {
        canvas.style.width = "100%";
        canvas.style.height = `${(windowWidth / windowHeight) * (gameHeight / gameWidth) * 100}%`;
    }
}

function mousedown(event) {
    // Register a mouse down event.
    // Left click pans the camera to the previous worm.
    if (event.button === 0 && camera.filmedObject != null) {
        if (camera.filmedObject.constructor.name === "Worm") {
            filmedWormIndex--;
        }
    }

    // Right click pans the camera to the next worm.
    if (event.button === 2 && camera.filmedObject != null) {
        if (camera.filmedObject.constructor.name === "Worm") {
            filmedWormIndex++;
        }
    }

    // Middle click unfollows the current worm.
    if (worms.length > 0) {
        filmedWormIndex = clamp(filmedWormIndex, 0, worms.length - 1);

        if (event.button !== 1) {
            worms[filmedWormIndex].follow();
        } else {
            worms[filmedWormIndex].unfollow();
        }
    }
}

function keydown(event) {
    // Register a key down event and add the pressed key value to a stack.
    let eventKey = event.key;

    if (keysPressed.includes(eventKey) === false) {
        // Keep a record of all the keys that are pressed down.
        keysPressed.push(eventKey);
        updateTurn(eventKey);

        // Open the minimap for expanded view.
        if (eventKey.toUpperCase() === "M") {
            minimapExpanded = !minimapExpanded;
        }

        // Toggle the shadows for performance and quality tradeoff.
        if (eventKey.toUpperCase() === "G") {
            shadows = !shadows;
        }

        if (eventKey.toUpperCase() === "I") {
            inspect = !inspect;
        }
    }
}

function keyup(event) {
    // Register a key up event and remove the lifted key value from the stack.
    let eventKey = event.key;

    keysPressed.splice(keysPressed.indexOf(eventKey), 1);
    updateTurn(eventKey);
}

function touchstart(event) {
    let rect = canvas.getBoundingClientRect();
    let touch = point(gameWidth * (event.touches[0].clientX - rect.left) / (rect.right - rect.left), gameHeight * (event.touches[0].clientY - rect.top) / (rect.bottom - rect.top));

    if (touch.x >= 0 && touch.y >= 0 && touch.x <= gameWidth && touch.y <= gameHeight) {
        if (touch.x >= gameWidth - minimapWidth - 10 && touch.y >= gameHeight - minimapHeight - 10) {
            minimapExpanded = !minimapExpanded;
        } else {
            for(let n = 0; n < worms.length; n++) {
                const worm = worms[n];
    
                if (!worm.dead && worm.controllable) {
                    if (touch.x > gameHalfWidth) {
                        worm.turn = 1;
                    } else {
                        worm.turn = -1;
                    }
                }
            }
        }
    }
}

function touchend(event) {
    for(let n = 0; n < worms.length; n++) {
        const worm = worms[n];

        if(!worm.dead && worm.controllable) {
            worm.turn = 0;
        }
    }
}

//----------------------------
//---------- EVENTS ----------
//----------------------------

window.addEventListener("resize", resize);
window.addEventListener("mousedown", mousedown);
window.addEventListener("keydown", keydown);
window.addEventListener("keyup", keyup);
window.addEventListener("touchstart", touchstart);
window.addEventListener("touchend", touchend);
window.oncontextmenu = (event) => event.preventDefault();
// Prevent right click showing menu.

//-----------------------------------
//--- GLOBAL VARIABLE DEFINITIONS ---
//-----------------------------------

let request;
const requestAnimationFrame = window.requestAnimationFrame;
const cancelAnimationFrame = window.cancelAnimationFrame;
const canvas = document.getElementById("canvas");
const ctx = canvas.getContext("2d", {alpha: false});
const gameWidth = 1920;
const gameHeight = 1080;
const gameHalfWidth = 960;
const gameHalfHeight = 540;
const minimapWidth = 250;
const minimapHeight = 200;
const minimapHalfWidth = 125;
const minimapHalfHeight = 100;
let minimapZoom;
let minimapExpanded;
let shadows = true;
let inspect = false;
let timeScale;
const keysPressed = [];
let camera;
const WORLD_RADIUS = 10000;
const WORLD_CIRCLE = circle(point(0, 0), WORLD_RADIUS);
const GRID_SIZE = 100;
const WORM_BOT_COUNT = 20;
const ENERGY_COUNT = 500;
const worms = [];
const energies = [];
let filmedWormIndex;
resize();
start();

function start() {
    timeScale = 1;
    camera = new Camera();
    worms.length = 0;
    energies.length = 0;
    minimapZoom = 0.1;
    minimapExpanded = false;
    filmedWormIndex = 0;

    // Generate all the worms.
    for (let n = 0; n < WORM_BOT_COUNT + 1; n++) {
        let worm = new Worm(camera);
        // Generate the worm player.
        if (n === 0) {
            camera.moveTo(worm.nodes[0]);
            worm.follow();
            worm.setControllable(true);
            worm.setType(1);
            worm.setHue(120);
            worm.setRandomLength(5, 50);
        }
        // Generate the AI worms.
        else {
            worm.setControllable(false);
            worm.setRandomType(1, 4);
            worm.setRandomHue(240, 340);
            worm.setRandomLength(5, 50);
        }

        worms.push(worm);
    }

    // Generate the energies.
    for (let n = 0; n < ENERGY_COUNT; n++) {
        let energy = new Energy(camera);
        energies.push(energy);
    }

    request = requestAnimationFrame(render);
}

function reset() {
    cancelAnimationFrame(request);
    start();
}

function render() {
    //----------------------------
    //-------- MOVEMENT ----------
    //----------------------------

    // Slow down time.
    if (keysPressed.includes("-") || keysPressed.includes(",")) {
        timeScale -= 0.01;

        if (timeScale < 0.5) {
            timeScale = 0.5;
        }
    }

    // Speed up time.
    if (keysPressed.includes("+") || keysPressed.includes(".")) {
        timeScale += 0.01;

        if (timeScale > 5) {
            timeScale = 5;
        }
    }

    // High level worm movement logic.
    for (let n = 0; n < worms.length; n++) {
        const worm = worms[n];

        if (!worm.dead) {
            worm.tick(worms);

            // Check if any worm falls off the circle map.
            if (!pointInCircle(worm.nodes[0], WORLD_CIRCLE)) {
                worm.die();
            }
        }
    }

    // High level energy decay and worm eat logic.
    for (let n = 0; n < energies.length; n++) {
        let energy = energies[n];
        let closestWorm = undefined;
        let distanceManhattanToClosestWorm = undefined;

        for (let m = 0; m < worms.length; m++) {
            let worm = worms[m];

            if (!worm.dead) {
                let distanceManhattanToWorm = distanceManhattan(energy, worm.nodes[0]);

                if (distanceManhattanToWorm < distanceManhattanToClosestWorm || distanceManhattanToClosestWorm === undefined) {
                    distanceManhattanToClosestWorm = distanceManhattanToWorm;
                    closestWorm = worm;
                }
            }
        }

        if (distanceManhattanToClosestWorm <= 100) {
            if (!energy.isDecaying) {
                energy.decay();
                closestWorm.addNodeSmooth(5);
            }
        }

        energy.tick(energies);
    }

    camera.tick();

    //----------------------------
    //-------- RENDERING ---------
    //----------------------------

    //------ WORLD RENDERING -----

    ctx.fillStyle = "#000000";
    ctx.fillRect(0, 0, gameWidth, gameHeight);

    ctx.save();
    // Transform into the game space dependent on camera properties.
    ctx.translate(gameHalfWidth, gameHalfHeight);
    ctx.scale(1, -1);
    ctx.scale(camera.zoom, camera.zoom);
    ctx.translate(-camera.x, -camera.y);
    // GAME SPACE

    ctx.save();
    ctx.strokeStyle = "#333333";
    ctx.lineWidth = 2;

    // Render the interior grid of the circle map.
    for (let n = 1; n < 2 * WORLD_RADIUS / GRID_SIZE; n++) {
        ctx.beginPath();
        ctx.moveTo(n * GRID_SIZE - WORLD_RADIUS, 0 - WORLD_RADIUS);
        ctx.lineTo(n * GRID_SIZE - WORLD_RADIUS, 2 * WORLD_RADIUS - WORLD_RADIUS);
        ctx.stroke();
        ctx.beginPath();
        ctx.moveTo(0 - WORLD_RADIUS, n * GRID_SIZE - WORLD_RADIUS);
        ctx.lineTo(2 * WORLD_RADIUS - WORLD_RADIUS, n * GRID_SIZE - WORLD_RADIUS);
        ctx.stroke();
    }

    ctx.fillStyle = "#000000";
    ctx.strokeStyle = "#333333";
    ctx.lineWidth = 3;

    // Render the outline of the circle map.
    ctx.beginPath();
    ctx.arc(0, 0, WORLD_RADIUS, Math.PI, 0);
    ctx.lineTo(WORLD_RADIUS, -WORLD_RADIUS);
    ctx.lineTo(-WORLD_RADIUS, -WORLD_RADIUS);
    ctx.closePath();
    ctx.fill();
    ctx.beginPath();
    ctx.arc(0, 0, WORLD_RADIUS, 0, -Math.PI);
    ctx.lineTo(-WORLD_RADIUS, WORLD_RADIUS);
    ctx.lineTo(WORLD_RADIUS, WORLD_RADIUS);
    ctx.closePath();
    ctx.fill();
    ctx.beginPath();
    ctx.arc(0, 0, WORLD_RADIUS, 0, 2 * Math.PI);
    ctx.stroke();

    ctx.restore();

    //--- DEAD WORM RENDERING ---
    
    ctx.save();

    for (let n = 0; n < worms.length; n++) {
        let worm = worms[n];

        if (worm.dead) {
            if (worm.inGame(camera)) {
                ctx.beginPath();
                ctx.arc(worm.nodes[0].x, worm.nodes[0].y, 25, worm.nodes[0].r - Math.PI / 2, worm.nodes[0].r + Math.PI / 2);

                for (let m = 1; m < worm.nodes.length - 1; m++) {
                    ctx.save();
                    ctx.translate(worm.nodes[m].x, worm.nodes[m].y);
                    ctx.rotate(worm.nodes[m].r);
                    ctx.lineTo(0, 25);
                    ctx.restore();
                }

                ctx.arc(worm.nodes[worm.nodes.length - 1].x, worm.nodes[worm.nodes.length - 1].y, 25, worm.nodes[worm.nodes.length - 1].r + Math.PI / 2, worm.nodes[worm.nodes.length - 1].r - Math.PI / 2);

                for (let m = worm.nodes.length - 2; m > 0; m--) {
                    ctx.save();
                    ctx.translate(worm.nodes[m].x, worm.nodes[m].y);
                    ctx.rotate(worm.nodes[m].r);
                    ctx.lineTo(0, -25);
                    ctx.restore();
                }

                ctx.closePath();
                ctx.fillStyle = "#000000";
                ctx.fill();
                ctx.lineWidth = 30;
                ctx.strokeStyle = "#000000";
                ctx.stroke();
                ctx.lineWidth = 3;
                ctx.strokeStyle = "#575757";
                ctx.shadowBlur = 0;
                ctx.stroke();

                ctx.save();
                ctx.translate(worm.nodes[0].x, worm.nodes[0].y);
                ctx.rotate(worm.nodes[0].r);

                ctx.lineWidth = 2;

                ctx.beginPath();
                ctx.arc(19, 0, 13, Math.PI - Math.PI / 3, Math.PI + Math.PI / 3);
                ctx.stroke();

                ctx.beginPath();
                ctx.moveTo(-3, -13);
                ctx.lineTo(3, -7);
                ctx.stroke();

                ctx.beginPath();
                ctx.moveTo(-3, -7);
                ctx.lineTo(3, -13);
                ctx.stroke();

                ctx.beginPath();
                ctx.moveTo(-3, 13);
                ctx.lineTo(3, 7);
                ctx.stroke();

                ctx.beginPath();
                ctx.moveTo(-3, 7);
                ctx.lineTo(3, 13);
                ctx.stroke();

                ctx.restore();
            }
        }
    }

    ctx.restore();

    //----- ENERGY RENDERING -----

    ctx.save();
    ctx.shadowBlur = getShadows(clampMin(20 * camera.zoom, 20));
    for (let n = 0; n < energies.length; n++) {
        const energy = energies[n];

        if (energy.inGame(camera)) {
            ctx.globalAlpha = energy.opacity;
            ctx.save();
            ctx.translate(energy.x, energy.y);
            ctx.rotate(energy.r);

            switch (energy.type) {
                // Render the red triangle energy.
                case 1:
                    ctx.beginPath();
                    ctx.moveTo(0, 25);
                    ctx.lineTo(-21.65, -12.5);
                    ctx.lineTo(21.65, -12.5);
                    ctx.closePath();
                    ctx.fillStyle = "#000000";
                    ctx.shadowBlur = 0;
                    ctx.fill();
                    ctx.lineWidth = 3;
                    ctx.strokeStyle = "#ff0000";
                    ctx.shadowColor = "#ff0000";
                    ctx.stroke();
                    break;
                // Render the blue square energy.
                case 2:
                    ctx.beginPath();
                    ctx.moveTo(20, 20);
                    ctx.lineTo(20, -20);
                    ctx.lineTo(-20, -20);
                    ctx.lineTo(-20, 20);
                    ctx.closePath();
                    ctx.fillStyle = "#000000";
                    ctx.shadowBlur = 0;
                    ctx.fill();
                    ctx.lineWidth = 3;
                    ctx.strokeStyle = "#00e5ff";
                    ctx.shadowColor = "#00e5ff";
                    ctx.stroke();
                    break;
                // Render the orange semicircle energy.
                case 3:
                    ctx.beginPath();
                    ctx.arc(0, 0, 25, 0, Math.PI);
                    ctx.closePath();
                    ctx.fillStyle = "#000000";
                    ctx.shadowBlur = 0;
                    ctx.fill();
                    ctx.lineWidth = 3;
                    ctx.strokeStyle = "#ff9100";
                    ctx.shadowColor = "#ff9100";
                    ctx.stroke();
                    break;
            }

            ctx.restore();
        }
    }

    ctx.restore();

    //------ WORM RENDERING ------

    ctx.save();
    ctx.shadowBlur = getShadows();

    for (let n = 0; n < worms.length; n++) {
        const worm = worms[n];

        if (!worm.dead) {
            let color = hueString(worm.hue);

            // Render the worm only if seen by the camera.
            if (worm.inGame(camera)) {
                if (!inspect) {
                    let interpolation1 = 0;
                    let interpolation2 = 0;
                    let interpolation3 = 0;

                    ctx.lineWidth = 3;
                    ctx.strokeStyle = color;
                    ctx.fillStyle = color;
                    ctx.shadowColor = color;

                    switch (worm.type) {
                        // Render the normal worm.
                        case 1:
                            ctx.beginPath();
                            ctx.arc(worm.nodes[0].x, worm.nodes[0].y, 25, worm.nodes[0].r - Math.PI / 2, worm.nodes[0].r + Math.PI / 2);
                            for (let m = 1; m < worm.nodes.length - 1; m++) {
                                ctx.save();
                                ctx.translate(worm.nodes[m].x, worm.nodes[m].y);
                                ctx.rotate(worm.nodes[m].r);
                                ctx.lineTo(0, 25);
                                ctx.restore();
                            }
                            ctx.arc(worm.nodes[worm.nodes.length - 1].x, worm.nodes[worm.nodes.length - 1].y, 25, worm.nodes[worm.nodes.length - 1].r + Math.PI / 2, worm.nodes[worm.nodes.length - 1].r - Math.PI / 2);
                            for (let m = worm.nodes.length - 2; m > 0; m--) {
                                ctx.save();
                                ctx.translate(worm.nodes[m].x, worm.nodes[m].y);
                                ctx.rotate(worm.nodes[m].r);
                                ctx.lineTo(0, -25);
                                ctx.restore();
                            }
                            ctx.closePath();
                            ctx.fillStyle = "#000000";
                            ctx.shadowBlur = 0;
                            ctx.fill();
                            ctx.lineWidth = 20;
                            ctx.strokeStyle = "#000000";
                            ctx.shadowBlur = 0;
                            ctx.stroke();
                            ctx.lineWidth = 3;
                            ctx.strokeStyle = color;
                            ctx.shadowBlur = getShadows();
                            ctx.shadowColor = color;
                            ctx.stroke();
                            interpolation1 = interpolateQuadratic(12, 6, worm.happiness);
                            interpolation2 = interpolateQuadratic(-11, -15, worm.happiness);
                            interpolation3 = interpolateQuadratic(4, 19, worm.happiness);
                            ctx.save();
                            ctx.translate(worm.nodes[0].x, worm.nodes[0].y);
                            ctx.rotate(worm.nodes[0].r);
                            ctx.beginPath();
                            ctx.moveTo(interpolation1, interpolation2);
                            ctx.bezierCurveTo(interpolation3, -7, interpolation3, 7, interpolation1, -interpolation2);
                            ctx.lineWidth = 2;
                            ctx.strokeStyle = color;
                            ctx.shadowBlur = getShadows();
                            ctx.shadowColor = color;
                            ctx.stroke();
                            ctx.beginPath();
                            ctx.ellipse(0, -5, 2 * (1 - worm.blink), 2, 0, 0, 2 * Math.PI);
                            ctx.fillStyle = color;
                            ctx.shadowBlur = getShadows();
                            ctx.shadowColor = color;
                            ctx.fill();
                            ctx.beginPath();
                            ctx.ellipse(0, 5, 2 * (1 - worm.blink), 2, 0, 0, 2 * Math.PI);
                            ctx.fillStyle = color;
                            ctx.shadowBlur = getShadows();
                            ctx.shadowColor = color;
                            ctx.fill();
                            ctx.restore();
                            break;
                        // Render the mechanical worm.
                        case 2:
                            ctx.beginPath();
                            ctx.arc(worm.nodes[0].x, worm.nodes[0].y, 25, worm.nodes[0].r - Math.PI / 2, worm.nodes[0].r + Math.PI / 2);
                            for (let m = 1; m < worm.nodes.length - 1; m++) {
                                ctx.save();
                                ctx.translate(worm.nodes[m].x, worm.nodes[m].y);
                                ctx.rotate(worm.nodes[m].r);

                                switch (true) {
                                    case (m - 1) % 4 === 0:
                                        ctx.lineTo(-3, 25);
                                        break;
                                    case (m - 1) % 4 === 1 || (m - 1) % 4 === 2:
                                        ctx.lineTo(0, 25 - 5 * worm.nodes[m + 1].activeTime);
                                        break;
                                    case (m - 1) % 4 === 3:
                                        ctx.lineTo(3, 25);
                                        break;
                                }

                                ctx.restore();
                            }
                            ctx.arc(worm.nodes[worm.nodes.length - 1].x, worm.nodes[worm.nodes.length - 1].y, 25, worm.nodes[worm.nodes.length - 1].r + Math.PI / 2, worm.nodes[worm.nodes.length - 1].r - Math.PI / 2);
                            for (let m = worm.nodes.length - 2; m > 0; m--) {
                                ctx.save();
                                ctx.translate(worm.nodes[m].x, worm.nodes[m].y);
                                ctx.rotate(worm.nodes[m].r);

                                switch (true) {
                                    case (m - 1) % 4 === 0:
                                        ctx.lineTo(-3, -25);
                                        break;
                                    case ((m - 1) % 4 === 1 || (m - 1) % 4 === 2):
                                        ctx.lineTo(0, -25 + 5 * worm.nodes[m].activeTime);
                                        break;
                                    case (m - 1) % 4 === 3:
                                        ctx.lineTo(3, -25);
                                        break;
                                }

                                ctx.restore();
                            }
                            ctx.closePath();
                            ctx.fillStyle = "#000000";
                            ctx.shadowBlur = 0;
                            ctx.fill();
                            ctx.lineWidth = 20;
                            ctx.strokeStyle = "#000000";
                            ctx.shadowBlur = 0;
                            ctx.stroke();
                            ctx.lineWidth = 2;
                            ctx.strokeStyle = color;
                            ctx.shadowBlur = getShadows();
                            ctx.shadowColor = color;
                            ctx.stroke();
                            interpolation1 = interpolateQuadratic(12, 6, worm.happiness);
                            interpolation2 = interpolateQuadratic(-11, -15, worm.happiness);
                            interpolation3 = interpolateQuadratic(4, 19, worm.happiness);
                            ctx.save();
                            ctx.translate(worm.nodes[0].x, worm.nodes[0].y);
                            ctx.rotate(worm.nodes[0].r);
                            ctx.beginPath();
                            ctx.moveTo(interpolation1, interpolation2);
                            ctx.bezierCurveTo(interpolation3, -7, interpolation3, 7, interpolation1, -interpolation2);
                            ctx.lineWidth = 2;
                            ctx.strokeStyle = color;
                            ctx.shadowBlur = getShadows();
                            ctx.shadowColor = color;
                            ctx.stroke();
                            ctx.beginPath();
                            ctx.ellipse(0, -5, 2 * (1 - worm.blink), 2, 0, 0, 2 * Math.PI);
                            ctx.fillStyle = color;
                            ctx.shadowBlur = getShadows();
                            ctx.shadowColor = color;
                            ctx.fill();
                            ctx.beginPath();
                            ctx.ellipse(0, 5, 2 * (1 - worm.blink), 2, 0, 0, 2 * Math.PI);
                            ctx.fillStyle = color;
                            ctx.shadowBlur = getShadows();
                            ctx.shadowColor = color;
                            ctx.fill();
                            ctx.restore();
                            break;
                        // Render the alien worm.
                        case 3:
                            ctx.beginPath();
                            ctx.arc(worm.nodes[0].x, worm.nodes[0].y, 25, worm.nodes[0].r - Math.PI / 2, worm.nodes[0].r + Math.PI / 2);
                            for (let m = 1; m < worm.nodes.length - 1; m++) {
                                ctx.save();
                                ctx.translate(worm.nodes[m].x, worm.nodes[m].y);
                                ctx.rotate(worm.nodes[m].r);
                                ctx.lineTo(0, 25);
                                ctx.restore();
                            }
                            ctx.arc(worm.nodes[worm.nodes.length - 1].x, worm.nodes[worm.nodes.length - 1].y, 25, worm.nodes[worm.nodes.length - 1].r + Math.PI / 2, worm.nodes[worm.nodes.length - 1].r - Math.PI / 2);
                            for (let m = worm.nodes.length - 2; m > 0; m--) {
                                ctx.save();
                                ctx.translate(worm.nodes[m].x, worm.nodes[m].y);
                                ctx.rotate(worm.nodes[m].r);
                                ctx.lineTo(0, -25);
                                ctx.restore();
                            }
                            ctx.closePath();
                            ctx.fillStyle = "#000000";
                            ctx.shadowBlur = 0;
                            ctx.fill();
                            ctx.lineWidth = 20;
                            ctx.strokeStyle = "#000000";
                            ctx.shadowBlur = 0;
                            ctx.stroke();
                            ctx.lineWidth = 2;
                            ctx.strokeStyle = color;
                            ctx.shadowBlur = getShadows();
                            ctx.shadowColor = color;
                            ctx.stroke();

                            let interpolation = interpolateQuadratic(Math.PI / 4, Math.PI / 6, worm.happiness);
                            ctx.save();
                            ctx.translate(worm.nodes[0].x, worm.nodes[0].y);
                            ctx.rotate(worm.nodes[0].r);
                            ctx.beginPath();
                            ctx.moveTo(25 * Math.cos(-interpolation), 25 * Math.sin(-interpolation));
                            ctx.lineTo(50 * Math.cos(-interpolation), 50 * Math.sin(-interpolation));
                            ctx.lineWidth = 20;
                            ctx.strokeStyle = "#000000";
                            ctx.shadowBlur = 0;
                            ctx.stroke();
                            ctx.lineWidth = 2;
                            ctx.strokeStyle = color;
                            ctx.shadowBlur = getShadows();
                            ctx.shadowColor = color;
                            ctx.stroke();
                            ctx.beginPath();
                            ctx.moveTo(25 * Math.cos(interpolation), 25 * Math.sin(interpolation));
                            ctx.lineTo(50 * Math.cos(interpolation), 50 * Math.sin(interpolation));
                            ctx.lineWidth = 20;
                            ctx.strokeStyle = "#000000";
                            ctx.shadowBlur = 0;
                            ctx.stroke();
                            ctx.lineWidth = 2;
                            ctx.strokeStyle = color;
                            ctx.shadowBlur = getShadows();
                            ctx.shadowColor = color;
                            ctx.stroke();
                            ctx.beginPath();
                            ctx.arc(50 * Math.cos(-interpolation), 50 * Math.sin(-interpolation), 5, 0, 2 * Math.PI);
                            ctx.lineWidth = 20;
                            ctx.strokeStyle = "#000000";
                            ctx.shadowBlur = 0;
                            ctx.stroke();
                            ctx.fillStyle = color;
                            ctx.shadowBlur = getShadows();
                            ctx.shadowColor = color;
                            ctx.fill();
                            ctx.beginPath();
                            ctx.arc(50 * Math.cos(interpolation), 50 * Math.sin(interpolation), 5, 0, 2 * Math.PI);
                            ctx.lineWidth = 20;
                            ctx.strokeStyle = "#000000";
                            ctx.shadowBlur = 0;
                            ctx.stroke();
                            ctx.fillStyle = color;
                            ctx.shadowBlur = getShadows();
                            ctx.shadowColor = color;
                            ctx.fill();
                            ctx.restore();
                            break;
                        // Render the flag worm.
                        case 4:
                            ctx.beginPath();
                            ctx.arc(worm.nodes[0].x, worm.nodes[0].y, 25, worm.nodes[0].r - Math.PI / 2, worm.nodes[0].r + Math.PI / 2);
                            for (let m = 1; m < worm.nodes.length - 1; m++) {
                                ctx.save();
                                ctx.translate(worm.nodes[m].x, worm.nodes[m].y);
                                ctx.rotate(worm.nodes[m].r);

                                switch (true) {
                                    case (m - 1) % 20 < 19:
                                        ctx.lineTo(0, 25);
                                        break;
                                    case (m - 1) % 20 === 19:
                                        ctx.lineTo(0, 25);
                                        ctx.lineTo(0, 25 + 40 * worm.nodes[m + 1].activeTime);
                                        ctx.lineTo(20 * worm.nodes[m + 1].activeTime, 25 + 30 * worm.nodes[m + 1].activeTime);
                                        ctx.lineTo(0, 25 + 20 * worm.nodes[m + 1].activeTime);
                                        ctx.lineTo(0, 25);
                                        break;
                                }

                                ctx.restore();
                            }
                            ctx.arc(worm.nodes[worm.nodes.length - 1].x, worm.nodes[worm.nodes.length - 1].y, 25, worm.nodes[worm.nodes.length - 1].r + Math.PI / 2, worm.nodes[worm.nodes.length - 1].r - Math.PI / 2);
                            for (let m = worm.nodes.length - 2; m > 0; m--) {
                                ctx.save();
                                ctx.translate(worm.nodes[m].x, worm.nodes[m].y);
                                ctx.rotate(worm.nodes[m].r);
                                ctx.lineTo(0, -25);
                                ctx.restore();
                            }
                            ctx.closePath();
                            ctx.fillStyle = "#000000";
                            ctx.shadowBlur = 0;
                            ctx.fill();
                            ctx.lineWidth = 20;
                            ctx.strokeStyle = "#000000";
                            ctx.shadowBlur = 0;
                            ctx.stroke();
                            ctx.lineWidth = 2;
                            ctx.strokeStyle = color;
                            ctx.shadowBlur = getShadows();
                            ctx.shadowColor = color;
                            ctx.stroke();
                            interpolation1 = interpolateQuadratic(12, 6, worm.happiness);
                            interpolation2 = interpolateQuadratic(-11, -15, worm.happiness);
                            interpolation3 = interpolateQuadratic(4, 19, worm.happiness);
                            ctx.save();
                            ctx.translate(worm.nodes[0].x, worm.nodes[0].y);
                            ctx.rotate(worm.nodes[0].r);
                            ctx.beginPath();
                            ctx.moveTo(interpolation1, interpolation2);
                            ctx.bezierCurveTo(interpolation3, -7, interpolation3, 7, interpolation1, -interpolation2);
                            ctx.lineWidth = 2;
                            ctx.strokeStyle = color;
                            ctx.shadowBlur = getShadows();
                            ctx.shadowColor = color;
                            ctx.stroke();
                            ctx.beginPath();
                            ctx.ellipse(0, -5, 2 * (1 - worm.blink), 2, 0, 0, 2 * Math.PI);
                            ctx.fillStyle = color;
                            ctx.shadowBlur = getShadows();
                            ctx.shadowColor = color;
                            ctx.fill();
                            ctx.beginPath();
                            ctx.ellipse(0, 5, 2 * (1 - worm.blink), 2, 0, 0, 2 * Math.PI);
                            ctx.fillStyle = color;
                            ctx.shadowBlur = getShadows();
                            ctx.shadowColor = color;
                            ctx.fill();
                            ctx.restore();
                            break;

                        // Additional worm types can be added here for rendering.
                    }
                }

                if (inspect) {
                    for (let m = 0; m < worm.nodes.length; m += 5) {
                        ctx.save();
                        ctx.translate(worm.nodes[m].x, worm.nodes[m].y);
                        ctx.beginPath();
                        ctx.arc(0, 0, 25, 0, 2 * Math.PI);
                        ctx.lineWidth = 2;
                        ctx.strokeStyle = "#0000ff";
                        ctx.shadowBlur = 0;
                        ctx.stroke();
                        ctx.rotate(worm.nodes[m].r);
                        ctx.beginPath();
                        ctx.moveTo(0, 0);
                        ctx.lineTo(50, 0);
                        ctx.arc(50, 0, 5, 0, 2 * Math.PI);
                        ctx.lineWidth = 2;
                        ctx.strokeStyle = "#ff0000";
                        ctx.shadowBlur = 0;
                        ctx.stroke();
                        ctx.restore();
                    }

                    if (!worm.controllable) {
                        if (worm.botEnergy !== null && !worm.botEnergy.isDestroyed) {
                            ctx.beginPath();
                            ctx.moveTo(worm.botEnergy.x, worm.botEnergy.y);
                            ctx.lineTo(worm.nodes[0].x, worm.nodes[0].y);
                            ctx.lineWidth = 3;
                            ctx.strokeStyle = "#00ff00";
                            ctx.setLineDash([10, 10]);
                            ctx.shadowBlur = 0;
                            ctx.stroke();
                            ctx.setLineDash([]);
                            ctx.strokeRect(worm.botEnergy.x - 30, worm.botEnergy.y - 30, 60, 60);
                        }

                        ctx.save();
                        ctx.translate(worm.nodes[0].x, worm.nodes[0].y);
                        ctx.rotate(worm.botDesiredDirection);
                        ctx.beginPath();
                        ctx.moveTo(0, 0);
                        ctx.lineTo(-40, 0);
                        ctx.lineWidth = 3;
                        ctx.strokeStyle = "#00ff00";
                        ctx.shadowBlur = 0;
                        ctx.stroke();
                        ctx.beginPath();
                        ctx.moveTo(-40, 10);
                        ctx.lineTo(-40, -10);
                        ctx.lineTo(-50, 0);
                        ctx.closePath();
                        ctx.fillStyle = "#00ff00";
                        ctx.shadowBlur = 0;
                        ctx.fill();
                        ctx.restore();
                    }
                }
            }
        }
    }

    ctx.restore();

    //----- MINIMAP RENDERING ----

    ctx.setTransform(1, 0, 0, 1, 0, 0);
    ctx.save();

    // Switch the context to rendering the minimized minimap.
    if (!minimapExpanded) {
        ctx.translate(gameWidth - minimapHalfWidth - 10, gameHeight - minimapHalfHeight - 10);
        ctx.scale(1, -1);

        ctx.beginPath();

        if (!inspect) {
            ctx.rect(-minimapHalfWidth, -minimapHalfHeight, minimapWidth, minimapHeight);
        } else {
            ctx.rect(-minimapHalfWidth - 100, -minimapHalfHeight - 10, minimapWidth + 110, minimapHeight + 110);
        }

        ctx.clip();

        ctx.fillStyle = "#000022";
        ctx.globalAlpha = 0.8;
        ctx.fillRect(-minimapHalfWidth, -minimapHalfHeight, minimapWidth, minimapHeight);
    }
    // Switch the context to rendering the maximized minimap.
    else {
        ctx.translate(gameHalfWidth, gameHalfHeight);
        ctx.scale(1, -1);
        ctx.fillStyle = "#000022";
        ctx.globalAlpha = 0.8;
        ctx.fillRect(-gameHalfWidth, -gameHalfHeight, gameWidth, gameHeight);
    }

    ctx.scale(camera.zoom * minimapZoom, camera.zoom * minimapZoom);
    ctx.translate(-camera.x, -camera.y);
    // MINIMAP SPACE

    // Rendering the world either in the minimized or maximized minimap.
    ctx.strokeStyle = "#333333";
    ctx.lineWidth = 25;
    ctx.beginPath();
    ctx.arc(0, 0, WORLD_RADIUS, 0, 2 * Math.PI);
    ctx.stroke();

    // Rendering the energies on the minimap as colored dots with their respective colors filled in.
    ctx.shadowBlur = getShadows(clampMin(20 * camera.zoom, 20));

    for (let n = 0; n < energies.length; n++) {
        const energy = energies[n];

        if (energy.inMinimap(camera, minimapExpanded)) {
            ctx.globalAlpha = energy.opacity;

            switch (energy.type) {
                case 1:
                    ctx.fillStyle = "#ff0000";
                    ctx.shadowColor = "#ff0000";
                    break;
                case 2:
                    ctx.fillStyle = "#00e5ff";
                    ctx.shadowColor = "#00e5ff";
                    break;
                case 3:
                    ctx.fillStyle = "#ff9100";
                    ctx.shadowColor = "#ff9100";
                    break;
            }

            ctx.beginPath();
            ctx.arc(energy.x, energy.y, 25, 0, 2 * Math.PI);
            ctx.fill();
        } else if (inspect) {
            ctx.globalAlpha = energy.opacity;
            ctx.lineWidth = 20;
            ctx.strokeStyle = "#ff0000";
            ctx.shadowBlur = 0;
            ctx.save();
            ctx.translate(energy.x, energy.y);
            ctx.beginPath();
            ctx.moveTo(-50, -50);
            ctx.lineTo(50, 50);
            ctx.lineTo(0, 0);
            ctx.lineTo(-50, 50);
            ctx.lineTo(50, -50);
            ctx.stroke();
            ctx.restore();
        }
    }

    // Rendering the worms on the minimap as colored paths with their respective colors used for the stroke call.

    ctx.lineCap = "round";
    ctx.globalAlpha = 1;

    for (let n = 0; n < worms.length; n++) {
        const worm = worms[n];

        if (!worm.dead) {
            let color = hueString(worm.hue);

            if (worm.inMinimap(camera, minimapExpanded)) {
                ctx.lineWidth = 50;
                ctx.strokeStyle = color;
                ctx.shadowBlur = getShadows(clampMin(20 * camera.zoom, 20));
                ctx.shadowColor = color;
                ctx.beginPath();

                for (let m = 0; m < worm.nodes.length; m++) {
                    ctx.lineTo(worm.nodes[m].x, worm.nodes[m].y);
                }

                ctx.stroke();

                if (inspect && !worm.controllable) {
                    if (worm.botEnergy !== null && !worm.botEnergy.isDestroyed) {
                        ctx.beginPath();
                        ctx.moveTo(worm.botEnergy.x, worm.botEnergy.y);
                        ctx.lineTo(worm.nodes[0].x, worm.nodes[0].y);
                        ctx.lineWidth = 20;
                        ctx.strokeStyle = "#00ff00";
                        ctx.shadowBlur = 0;
                        ctx.stroke();
                    }
                }
            }
        }
    }

    ctx.restore();

    // Rendering the minimized minimap border for better styling.
    if (!minimapExpanded) {
        ctx.beginPath();

        // Checking whether the rounded rectangle function is supported on the client's browser.
        ctx.rect(gameWidth - minimapWidth - 10, gameHeight - minimapHeight - 10, minimapWidth, minimapHeight);

        ctx.lineWidth = 5;
        ctx.strokeStyle = "#222222";
        ctx.stroke();
    }

    ctx.restore();

    // Repeating the draw loop and storing the request.
    request = requestAnimationFrame(render);
}

//----------------------------
//---------- MATHS -----------
//----------------------------

function distance(p1, p2 = point(0, 0)) {
    return Math.hypot(p1.x - p2.x, p1.y - p2.y);
}

function distanceSquared(p1, p2 = point(0, 0)) {
    return Math.pow(p1.x - p2.x, 2) + Math.pow(p1.y - p2.y, 2);
}

// For quicker calculation of the distance.
function distanceManhattan(p1, p2 = point(0, 0)) {
    return Math.abs(p1.x - p2.x) + Math.abs(p1.y - p2.y);
}

function interpolateLinear(startingValue, endingValue, t) {
    return (startingValue + (endingValue - startingValue) * t);
}

function interpolateQuadratic(startingValue, endingValue, t) {
    return interpolateLinear(startingValue, endingValue, t < 0.5 ? 2 * t * t : 1 - Math.pow(-2 * t + 2, 2) / 2);
}

function calculateAngleDifference(a1, a2) {
    let difference = a2 - a1;
    while (difference < -Math.PI)
        difference += 2 * Math.PI;
    while (difference > Math.PI)
        difference -= 2 * Math.PI;
    return difference;
}

function pointInCircle(point, circle) {
    return distance(circle.center, point) <= circle.radius;
}

function pointInRectangle(point, rectangle, padding = 0) {
    return point.x >= rectangle.center.x - rectangle.width / 2 - padding && point.x <= rectangle.center.x + rectangle.width / 2 + padding && point.y >= rectangle.center.y - rectangle.height / 2 - padding && point.y <= rectangle.center.y + rectangle.height / 2 + padding;
}

function clampMin(num, min) {
    return Math.max(num, min)
}

function clampMax(num, max) {
    return Math.min(num, max);
}

function clamp(num, min, max) {
    return Math.min(Math.max(num, min), max);
}
