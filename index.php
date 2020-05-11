<?php

if(isset($_SERVER['REMOTE_ADDR']))
{
    file_put_contents('./data.txt', $_SERVER['REMOTE_ADDR']."\n", FILE_APPEND | LOCK_EX);
}

?>

<!DOCTYPE HTML>
<html>
    <head>
        <title>Worm</title>
        <link rel="shortcut icon" type="image/png" href="/favicon.ico"/>
        <script src="/libraries/lodash/lodash.js"></script>
        <script src="/libraries/stats/stats.js"></script>
    </head>
    
    <body>
        <canvas id="canvas" width="1920" height="1080"></canvas>
        
        <style>
            
            body
            {
                background-color: black;
                overflow: hidden;
                margin: 0;
                padding: 0;
            }
            
            #canvas
            {
                position: absolute;
                top: 50%;
                left: 50%;
                transform: translate(-50%, -50%);
            }
            
        </style>
        
        <script>
        
            //----------------------------
            //----- CLASS DEFINITIONS ----
            //----------------------------
        
            function point(x, y)
            {
                return {
                    x: x,
                    y: y
                };
            }
            
            function line(p1, p2)
            {
                return {
                    p1: p1,
                    p2: p2
                };
            }
            
            function circle(center, radius)
            {
                return {
                    center: center,
                    radius: radius
                };
            }
        
            class Filmable
            {
                constructor(camera)
                {
                    this.camera = camera;
                }

                follow()
                {
                    this.camera.filmedObject = this;
                }
                
                unfollow()
                {
                    this.camera.filmedObject = null;
                }
            }
            
            class Worm extends Filmable
            {
                constructor(controllable, type, length, hue, camera)
                {
                    super(camera);
                    this.controllable = controllable;
                    this.dead = false;
                    this.type = type;
                    this.hue = hue;
                    this.turn = 0;
                    this.nodes = [];
                    this.happiness = 0;
                    this.happinessCounter = 0;
                    this.happinessDirection = -1;
                    this.addNode(length);
                    
                    if(!this.controllable)
                    {
                        this.botWait = Math.round(Math.random() * 20 + 10);
                        this.botDesiredDirection = this.nodes[0].r;
                    }
                }
                
                setHue(hue)
                {
                    this.hue = hue;
                }
                
                setRandomHue(startingHue, endingHue)
                {
                    if(startingHue === undefined || endingHue === undefined)
                    {
                        this.hue = Math.round(Math.random() * 359);
                        return;
                    }
                    
                    this.hue = Math.round(Math.random() * (endingHue - startingHue) + startingHue);
                }
                
                addNode(count)
                {
                    if(count === undefined)
                    {
                        count = 1;
                    }
                    
                    var tempLength = this.nodes.length;
                    
                    for(var n = 0; n < count; n++)
                    {
                        if(tempLength === 0)
                        {
                            var tempRotation = 2 * Math.PI * Math.random();
                            var tempRadius = WORLD_RADIUS * Math.sqrt(Math.random());
                            this.nodes.push(
                            {
                                active: true,
                                activeCounter: 1,
                                x: tempRadius * Math.cos(tempRotation),
                                y: tempRadius * Math.sin(tempRotation),
                                r: 2 * Math.PI * Math.random()
                            });
                        }
                        
                        else
                        {
                            var tempLastNode = this.nodes[tempLength - 1];
                            this.nodes.push(
                            {
                                active: true,
                                activeCounter: 1,
                                x: tempLastNode.x - 20 * Math.cos(tempLastNode.r),
                                y: tempLastNode.y + 20 * Math.sin(tempLastNode.r),
                                r: tempLastNode.r
                            });
                        }
                        
                        tempLength++;
                    }
                }
                
                addNodeSmooth(count)
                {
                    if(count === undefined)
                    {
                        count = 1;
                    }
                    
                    var tempLength = this.nodes.length;
                    
                    for(var n = 0; n < count; n++)
                    {
                        if(tempLength === 0)
                        {
                            var tempRotation = 2 * Math.PI * Math.random();
                            var tempRadius = WORLD_RADIUS * Math.sqrt(Math.random());
                            this.nodes.push(
                            {
                                active: true,
                                activeCounter: 1,
                                x: tempRadius * Math.cos(tempRotation),
                                y: tempRadius * Math.sin(tempRotation),
                                r: 2 * Math.PI * Math.random()
                            });
                        }
                        
                        else
                        {
                            var tempLastNode = this.nodes[tempLength - 1];
                            this.nodes.push(
                            {
                                active: false,
                                activeCounter: 0,
                                x: tempLastNode.x,
                                y: tempLastNode.y,
                                r: tempLastNode.r
                            });
                        }
                        
                        tempLength++;
                    }
                }
                
                subtractNode(count)
                {
                    if(count === undefined)
                    {
                        count = 1;
                    }
                    
                    this.nodes.splice(this.nodes.length - count, count);
                }
                
                increaseHappiness(value)
                {
                    if(this.happiness < 1)
                    {
                        this.happiness += value;
                        
                        if(this.happiness > 1)
                        {
                            this.happiness = 1;
                        }
                    }
                }
                
                decreaseHappiness(value)
                {
                    if(this.happiness > 0)
                    {
                        this.happiness -= value;
                        
                        if(this.happiness < 0)
                        {
                            this.happiness = 0;
                        }
                    }
                }
                
                move()
                {
                    var tempFirstNode = this.nodes[0];
                    
                    if(this.turn === -1)
                    {
                        tempFirstNode.r += Math.PI / 90 * deltaTimeMultiplier * timeScale;

                        if(tempFirstNode.r >= 2 * Math.PI)
                        {
                            tempFirstNode.r -= 2 * Math.PI;
                        }
                    }

                    if(this.turn === 1)
                    {
                        tempFirstNode.r -= Math.PI / 90 * deltaTimeMultiplier * timeScale;

                        if(tempFirstNode.r < 0)
                        {
                            tempFirstNode.r += 2 * Math.PI;
                        }
                    }
                    
                    tempFirstNode.x += 3 * Math.cos(tempFirstNode.r) * deltaTimeMultiplier * timeScale;
                    tempFirstNode.y -= 3 * Math.sin(tempFirstNode.r) * deltaTimeMultiplier * timeScale;
                    
                    for(var n = 1; n < this.nodes.length; n++)
                    {
                        var tempCurrentNode = this.nodes[n];
                        var tempPreviousNode = this.nodes[n - 1];
                        
                        if(tempCurrentNode.active === false)
                        {
                            if(distance(tempCurrentNode, tempPreviousNode) > 5)
                            {
                                tempCurrentNode.active = true;
                            }
                        }
                        
                        if(tempCurrentNode.active === true)
                        {
                            if(tempCurrentNode.activeCounter < 1)
                            {
                                tempCurrentNode.activeCounter += 0.05 * deltaTimeMultiplier * timeScale;
                                
                                if(tempCurrentNode.activeCounter > 1)
                                {
                                    tempCurrentNode.activeCounter = 1;
                                }
                            }
                            
                            tempCurrentNode.r = Math.PI - Math.atan2(tempCurrentNode.y - tempPreviousNode.y, tempCurrentNode.x - tempPreviousNode.x);
                            tempCurrentNode.x = tempPreviousNode.x - 5 * Math.cos(tempCurrentNode.r);
                            tempCurrentNode.y = tempPreviousNode.y + 5 * Math.sin(tempCurrentNode.r);
                            
                            if(n > 1)
                            {
                                var tempPreviousPreviousNode = this.nodes[n - 2];
                                var nodeDistance = distance(tempCurrentNode, tempPreviousPreviousNode);
                                
                                if(nodeDistance < 9.993)
                                {
                                    var circle1 = circle(tempPreviousNode, 5);
                                    var circle2 = circle(tempPreviousPreviousNode, 9.993);
                                    var intersections = intersectCircleCircle(circle1, circle2);
                                    
                                    if(distance(tempCurrentNode, intersections[0]) < distance(tempCurrentNode, intersections[1]))
                                    {
                                        tempCurrentNode.x = intersections[0].x;
                                        tempCurrentNode.y = intersections[0].y;
                                    }
                                    else
                                    {
                                        tempCurrentNode.x = intersections[1].x;
                                        tempCurrentNode.y = intersections[1].y;
                                    }
                                }
                                
                                tempCurrentNode.r = Math.PI - Math.atan2(tempCurrentNode.y - tempPreviousNode.y, tempCurrentNode.x - tempPreviousNode.x);
                            }
                        }
                    }
                    
                    this.updateCamera();
                }
                
                moveTo(p)
                {
                    for(var n = 1; n < this.nodes.length; n++)
                    {
                        this.nodes[n].x += (p.x - this.nodes[0].x);
                        this.nodes[n].y += (p.y - this.nodes[0].y);
                    }
                    
                    this.nodes[0].x = p.x;
                    this.nodes[0].y = p.y;
                    this.updateCamera();
                }

                updateCamera()
                {
                    if(this.camera.filmedObject == this)
                    {
                        this.camera.moveToSmooth(this.nodes[0]);
                    }
                }
                
                inCanvas(camera)
                {
                    var tempPadding = 80 + 20;
                    
                    for(var n = 0; n < this.nodes.length; n++)
                    {
                        if(this.nodes[n].x - camera.x > -(canvasHalfWidth + tempPadding) && this.nodes[n].x - camera.x < canvasHalfWidth + tempPadding && this.nodes[n].y - camera.y > -(canvasHalfHeight + tempPadding) && this.nodes[n].y - camera.y < canvasHalfHeight + tempPadding)
                        {
                            return true;
                        }
                    }
                    
                    return false;
                }
                
                inMinimap(camera)
                {
                    var tempPadding = 80 * minimapZoom + 20;
                    
                    for(var n = 0; n < this.nodes.length; n++)
                    {
                        if(minimapZoom * (this.nodes[n].x - camera.x) + tempPadding > -minimapHalfWidth && minimapZoom * (this.nodes[n].x - camera.x) - tempPadding < minimapHalfWidth && minimapZoom * (this.nodes[n].y - camera.y) + tempPadding > -minimapHalfHeight && minimapZoom * (this.nodes[n].y - camera.y) - tempPadding < minimapHalfHeight)
                        {
                            return true;
                        }
                    }
                    
                    return false;
                }
            }
            
            class Energy extends Filmable
            {
                constructor(camera)
                {
                    super(camera);
                    this.type = Math.round(2 * Math.random());
                    this.opacity = 1;
                    this.phase = 2 * Math.PI * Math.random();
                    var tempRotation = 2 * Math.PI * Math.random();
                    var tempRadius = WORLD_RADIUS * Math.sqrt(Math.random());
                    this.x = tempRadius * Math.cos(tempRotation);
                    this.y = tempRadius * Math.sin(tempRotation);
                    this.rStatic = 2 * Math.PI * Math.random();
                    this.r = this.rStatic + 0.2 * Math.sin(this.phase);
                }
                
                move()
                {
                    this.phase += 0.02 * deltaTimeMultiplier * timeScale;
                    this.r = this.rStatic + 0.2 * Math.sin(this.phase);
                    this.updateCamera();
                }
                
                updateCamera()
                {
                    if(this.camera.filmedObject === this)
                    {
                        this.camera.moveToSmooth(this);
                    }
                }
                
                inCanvas(camera)
                {
                    var tempPadding = 80 + 20;
                    
                    if(this.x - camera.x > -(canvasHalfWidth + tempPadding) && this.x - camera.x < canvasHalfWidth + tempPadding && this.y - camera.y > -(canvasHalfHeight + tempPadding) && this.y - camera.y < canvasHalfHeight + tempPadding)
                    {
                        return true;
                    }
                    
                    return false;
                }
                
                inMinimap(camera)
                {
                    var tempPadding = 80 * minimapZoom + 20;
                    
                    if(minimapZoom * (this.x - camera.x) + tempPadding > -minimapHalfWidth && minimapZoom * (this.x - camera.x) - tempPadding < minimapHalfWidth && minimapZoom * (this.y - camera.y) + tempPadding > -minimapHalfHeight && minimapZoom * (this.y - camera.y) - tempPadding < minimapHalfHeight)
                    {
                        return true;
                    }
                    
                    return false;
                }
            }
            
            class Camera
            {
                constructor()
                {
                    this.filmedObject = null;
                    this.x = 0;
                    this.y = 0;
                    this.maxSpeed = 1000;
                }
                
                moveTo(p)
                {
                    this.x = p.x;
                    this.y = p.y;
                }
                
                moveToSmooth(p)
                {
                    var tempActualSpeed = this.maxSpeed * deltaTimeMultiplier;
                    var tempAngle = Math.atan2(p.y - this.y, p.x - this.x);
                    var tempCosine = Math.abs(tempActualSpeed * Math.cos(tempAngle));
                    var tempSine = Math.abs(tempActualSpeed * Math.sin(tempAngle));
                    this.x = interpolateLinear(this.x, clamp(p.x, this.x - tempCosine, this.x + tempCosine), 0.2);
                    this.y = interpolateLinear(this.y, clamp(p.y, this.y - tempSine, this.y + tempSine), 0.2);
                }
            }
            
            //----------------------------
            //--- FUNCTION DEFINITIONS ---
            //----------------------------
            
            function hueString(hue)
            {
                return ("hsl(" + hue + ", 100%, 50%)");
            }
            
            function killWorm(worm)
            {
                var index = worms.indexOf(worm);
                var tempFilmed = false;
                
                if(worms[index].camera.filmedObject === worms[index])
                {
                    tempFilmed = true;
                }
                var deadWorm = worms[index];
                worms.splice(index, 1);
                deadWorms.push(deadWorm);
                if(tempFilmed === true)
                {
                    if(worms.length !== 0)
                    {
                        worms[clampMax(index, worms.length - 1)].follow();
                    }
                }
            }
            
            function destroyEnergy(energy)
            {
                var index = energies.indexOf(energy);
                var tempFilmed = false;
                
                if(energies[index].camera.filmedObject === energies[index])
                {
                    tempFilmed = true;
                }
                energies.splice(index, 1);
                if(tempFilmed === true)
                {
                    if(energies.length !== 0)
                    {
                        energies[clampMax(index, energies.length - 1)].follow();
                    }
                    
                    else if(worms.length !== 0)
                    {
                        worms[Math.round(Math.random() * worms.length - 1)].follow();
                    }
                }
            }
            
            function resize()
            {
                const windowWidth = window.innerWidth;
                const windowHeight = window.innerHeight;
                
                if(windowWidth / windowHeight > canvasWidth / canvasHeight)
                {
                    canvas.style.width = `${(windowHeight / windowWidth) * ( canvasWidth / canvasHeight) * 100}%`;
                    canvas.style.height = "100%";
                }
                
                else
                {
                    canvas.style.width = "100%";
                    canvas.style.height = `${(windowWidth / windowHeight) * (canvasHeight / canvasWidth) * 100}%`;
                }
            }

            function mousedown(event)
            {
                if(!event)
                {
                    event = window.event;
                }

                if(event.button === 0 && camera.filmedObject != null)
                {
                    if(camera.filmedObject.constructor.name === "Worm")
                    {
                        activeWorm--;
                    }
                }

                if(event.button === 2 && camera.filmedObject != null)
                {
                    if(camera.filmedObject.constructor.name === "Worm")
                    {
                        activeWorm++;
                    }
                }
                
                if(worms.length > 0)
                {
                    activeWorm = clamp(activeWorm, 0, worms.length - 1);
                    
                    if(event.button !== 1)
                    {
                        worms[activeWorm].follow();
                    }
                    
                    else
                    {
                        worms[activeWorm].unfollow();
                    }
                }
            }

            function keydown(event)
            {
                if(!event)
                {
                    event = window.event;
                }
                
                var eventKey = event.key;
                
                if(keys.includes(eventKey) === false)
                {
                    keys.push(eventKey);
                    
                    if(eventKey.toUpperCase() === "M" && !minimapFired)
                    {
                        minimapFired = true;
                        
                        if(minimapExpanded === false)
                        {
                            minimapExpanded = true;
                        }
                        
                        else
                        {
                            minimapExpanded = false;
                        }
                    }
                }
            }
            
            function keyup(event)
            {
                if(!event)
                {
                    event = window.event;
                }
                
                var eventKey = event.key;
                
                keys.splice(keys.indexOf(eventKey), 1);
                
                if(eventKey.toUpperCase() === "M")
                {
                    minimapFired = false;
                }
            }
            
            //----------------------------
            //---------- EVENTS ----------
            //----------------------------
            
            window.onresize = resize;
            window.onmousedown = mousedown;
            window.onkeydown = keydown;
            window.onkeyup = keyup;
            window.onload = function() { requestAnimationFrame(render); };
            window.oncontextmenu = function(event) { event.preventDefault(); };
            
            //-----------------------------------
            //--- GLOBAL VARIABLE DEFINITIONS ---
            //-----------------------------------
            
            var time = [];
            var timeScale = 1;
            const keys = [];
            const camera = new Camera();
            const WORLD_RADIUS = 10000;
            const GRID_SIZE = 100;
            const WORM_BOT_COUNT = 100;
            const ENERGY_COUNT = 1000;
            const worms = [new Worm(true, 1, Math.round(Math.random() * 50 + 5), 120, camera)];
            const deadWorms = [];
            const energies = [];
            const minimapWidth = 250;
            const minimapHeight = 200;
            const minimapHalfWidth = minimapWidth / 2;
            const minimapHalfHeight = minimapHeight / 2;
            var minimapZoom = 0.1;
            var minimapFired = false;
            var minimapExpanded = false;
            
            worms[0].follow();
            worms[0].camera.x = worms[0].nodes[0].x;
            worms[0].camera.y = worms[0].nodes[0].y;
            
            for(var n = 0; n < WORM_BOT_COUNT; n++)
            {
                worms.push(new Worm(false, Math.round(Math.random() * 2), Math.round(Math.random() * 50 + 5), Math.random() * 100 + 260, camera));
            }
            
            for(var n = 0; n < ENERGY_COUNT; n++)
            {
                energies.push(new Energy(camera));
            }
            
            const canvas = document.getElementById("canvas");
            const canvasWidth = canvas.width;
            const canvasHeight = canvas.height;
            const canvasHalfWidth = canvasWidth / 2;
            const canvasHalfHeight = canvasHeight / 2;
            const ctx = canvas.getContext("2d", {alpha: false});
            var activeWorm = 0;
            var previousTime;
            var currentTime = new Date();
            var fps;
            var deltaTimeMultiplier = 1;
            
            var stats = new Stats();
            stats.showPanel(0);
            document.body.appendChild(stats.dom);
            
            resize();
            
            const pointOrigin = point(0, 0), pointCanvasCenter = point(canvasHalfWidth, canvasHalfHeight);
            const distanceOriginCenter = distance(pointOrigin, pointCanvasCenter);

            function render()
            {
                stats.update();
                
                previousTime = currentTime;
                currentTime = new Date();
                fps = 1000 / (currentTime - previousTime);
                deltaTimeMultiplier = interpolateLinear(deltaTimeMultiplier, 60 / clamp(fps, 20, 60), 0.5);
                
                //----------------------------
                //-------- MOVEMENT ----------
                //----------------------------
                
                for(var n = 0; n < worms.length; n++)
                {
                    const worm = worms[n];
                    worm.turn = 0;
                    
                    if(worm.controllable)
                    {
                        if(keys.includes("ArrowLeft") || keys.includes("a") || keys.includes("A"))
                        {
                            worm.turn -= 1;
                        }
                        
                        if(keys.includes("ArrowRight") || keys.includes("d") || keys.includes("D"))
                        {
                            worm.turn += 1;
                        }
                        
                        if(keys.includes("-") || keys.includes(","))
                        {
                            timeScale -= 0.01;
                            
                            if(timeScale < 0)
                            {
                                timeScale = 0;
                            }
                        }
                        
                        if(keys.includes("+") || keys.includes("."))
                        {
                            timeScale += 0.01;
                        }
                    }
                    
                    else if(!worm.controllable)
                    {
                        worm.botWait--;
                        
                        if(worm.botWait === 0)
                        {
                            worm.botDesiredDirection += (Math.random() - 0.5) * Math.PI;
                            worm.botWait = Math.round(Math.random() * 20 + 10);
                        }
                        
                        var angleDifference = calculateAngleDifference(worm.nodes[0].r, worm.botDesiredDirection);
                        if(angleDifference < 0)
                        {
                            worm.turn = 1;
                        }
                        
                        else if(angleDifference > 0)
                        {
                            worm.turn = -1;
                        }
                    }
                    
                    var oldX = worm.nodes[0].x;
                    var oldY = worm.nodes[0].y;
                    
                    worm.move();
                    
                    if(distance(pointOrigin, worm.nodes[0]) > WORLD_RADIUS)
                    {
                        var newX = worm.nodes[0].x;
                        var newY = worm.nodes[0].y;
                        var intersection = intersectCircleLineSegment(circle(pointOrigin, WORLD_RADIUS), line(point(oldX, oldY), point(newX, newY)));
                        worm.moveTo(intersection[0]);
                        killWorm(worms[n]);
                        n--;
                    }
                }
                
                for(var n = 0; n < worms.length; n++)
                {
                    var foundHappiness = false;
                    
                    for(var m = 0; m < worms.length; m++)
                    {
                        if(n !== m)
                        {
                            if(distance(worms[n].nodes[0], worms[m].nodes[0]) < 150)
                            {
                                foundHappiness = true;
                            }
                        }
                    }
                    
                    if(foundHappiness)
                    {
                        if(worms[n].happinessCounter < 1)
                        {
                            worms[n].happinessCounter += 1 / 50 * deltaTimeMultiplier * timeScale;
                            
                            if(worms[n].happinessCounter > 1)
                            {
                                worms[n].happinessCounter = 1;
                            }
                        }
                    }
                    
                    else
                    {
                        if(worms[n].happinessCounter > 0)
                        {
                            worms[n].happinessCounter -= 1 / 50 * deltaTimeMultiplier * timeScale;
                            
                            if(worms[n].happinessCounter < 0)
                            {
                                worms[n].happinessCounter = 0;
                            }
                        }
                    }
                    
                    if(worms[n].happinessCounter === 0)
                    {
                        worms[n].happinessDirection = -1;
                    }
                    
                    else if(worms[n].happinessCounter === 1)
                    {
                        worms[n].happinessDirection = 1;
                    }
                    
                    if(worms[n].happinessDirection === -1)
                    {
                        worms[n].decreaseHappiness(1 / 10 * deltaTimeMultiplier * timeScale);
                    }
                    
                    else if(worms[n].happinessDirection === 1)
                    {
                        worms[n].increaseHappiness(1 / 10 * deltaTimeMultiplier * timeScale);
                    }
                }
                
                for(var n = 0; n < energies.length; n++)
                {
                    const energy = energies[n];
                    energy.move();
                    var tempClosestWorm;
                    var tempMinimumDistance = -1;
                    
                    for(var m = 0; m < worms.length; m++)
                    {
                        var tempDistance = distanceManhattan(energies[n], worms[m].nodes[0]);
                        
                        if(tempDistance < tempMinimumDistance || tempMinimumDistance === -1)
                        {
                            tempMinimumDistance = tempDistance;
                            tempClosestWorm = m;
                        }
                    }
                    
                    if(energy.opacity < 1)
                    {
                        energy.opacity -= 0.1 * deltaTimeMultiplier * timeScale;
                        
                        if(energy.opacity <= 0)
                        {
                            destroyEnergy(energies[n]);
                            worms[tempClosestWorm].addNodeSmooth(5);
                            n--;
                        }
                        
                        continue;
                    }
                    
                    if(tempMinimumDistance < 50 && tempMinimumDistance !== -1)
                    {
                        if(energy.opacity === 1)
                        {
                            energy.opacity -= 0.1 * deltaTimeMultiplier * timeScale;
                        }
                    }
                }
                
                //time.push(_.cloneDeep(worms));
                
                //----------------------------
                //-------- RENDERING ---------
                //----------------------------
                
                //------ WORLD RENDERING -----
                
                ctx.fillStyle = "#000000";
                ctx.lineCap = "butt";
                ctx.shadowBlur = 0;
                ctx.globalAlpha = 1;
                ctx.fillRect(0, 0, canvasWidth, canvasHeight);
                ctx.strokeStyle = "#141414";
                ctx.lineWidth = 1;
                
                ctx.translate(canvasHalfWidth - camera.x, canvasHalfHeight - camera.y);
                
                for(var n = 1; n < 2 * WORLD_RADIUS / GRID_SIZE; n++)
                {
                    ctx.beginPath();
                    ctx.moveTo(n * GRID_SIZE - WORLD_RADIUS, 0 - WORLD_RADIUS);
                    ctx.lineTo(n * GRID_SIZE - WORLD_RADIUS, 2 * WORLD_RADIUS - WORLD_RADIUS);
                    ctx.stroke();
                    ctx.beginPath();
                    ctx.moveTo(0 - WORLD_RADIUS, n * GRID_SIZE - WORLD_RADIUS);
                    ctx.lineTo(2 * WORLD_RADIUS - WORLD_RADIUS, n * GRID_SIZE - WORLD_RADIUS);
                    ctx.stroke();
                }
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
                ctx.lineWidth = 3;
                ctx.beginPath();
                ctx.arc(0, 0, WORLD_RADIUS, 0, 2 * Math.PI);
                ctx.stroke();
                
                //----- ENERGY RENDERING -----
                
                ctx.shadowBlur = 20;
                for(var n = 0; n < energies.length; n++)
                {
                    const energy = energies[n];
                    
                    if(energy.inCanvas(camera))
                    {
                        ctx.globalAlpha = energy.opacity;
                        ctx.translate(energy.x, energy.y);
                        
                        if(energy.type === 0)
                        {
                            ctx.strokeStyle = "#ff0000";
                            ctx.beginPath();
                            ctx.shadowColor = ctx.strokeStyle;
                            ctx.moveTo(25 * Math.cos(energy.r), 0 - 25 * Math.sin(energy.r));
                            ctx.lineTo(25 * Math.cos(energy.r + 2 * Math.PI / 3), 0 - 25 * Math.sin(energy.r + 2 * Math.PI / 3));
                            ctx.lineTo(25 * Math.cos(energy.r + 4 * Math.PI / 3), 0 - 25 * Math.sin(energy.r + 4 * Math.PI / 3));
                            ctx.closePath();
                            ctx.stroke();
                        }
                        
                        if(energy.type === 1)
                        {
                            ctx.strokeStyle = "#00e5ff";
                            ctx.beginPath();
                            ctx.shadowColor = ctx.strokeStyle;
                            ctx.moveTo(25 * Math.cos(energy.r), 0 - 25 * Math.sin(energy.r));
                            ctx.lineTo(25 * Math.cos(energy.r + Math.PI / 2), 0 - 25 * Math.sin(energy.r + Math.PI / 2));
                            ctx.lineTo(25 * Math.cos(energy.r + Math.PI), 0 - 25 * Math.sin(energy.r + Math.PI));
                            ctx.lineTo(25 * Math.cos(energy.r + 3 * Math.PI / 2), 0 - 25 * Math.sin(energy.r + 3 * Math.PI / 2));
                            ctx.closePath();
                            ctx.stroke();
                        }
                        
                        if(energy.type === 2)
                        {
                            ctx.strokeStyle = "#ff9100";
                            ctx.shadowColor = ctx.strokeStyle;
                            
                            ctx.beginPath();
                            ctx.arc(0, 0, 25, energy.r - Math.PI / 2, energy.r + Math.PI / 2);
                            ctx.closePath();
                            ctx.stroke();
                        }
                        
                        ctx.translate(-energy.x, -energy.y);
                    }
                }
                
                //--- DEAD WORMS RENDERING ---
                
                ctx.shadowBlur = 0;
                ctx.globalAlpha = 1;
                
                for(var n = 0; n < deadWorms.length; n++)
                {
                    var deadWorm = deadWorms[n];
                    
                    if(deadWorm.inCanvas(camera))
                    {
                        ctx.lineWidth = 3;
                        ctx.strokeStyle = "#171717";
                        
                        ctx.beginPath();
                        ctx.arc(deadWorm.nodes[0].x, deadWorm.nodes[0].y, 25, -(deadWorm.nodes[0].r + Math.PI / 2), -(deadWorm.nodes[0].r - Math.PI / 2));
                        for(var m = 1; m < deadWorm.nodes.length - 1; m++)
                        {
                            ctx.lineTo(deadWorm.nodes[m].x + 25 * Math.cos(deadWorm.nodes[m].r - Math.PI / 2), deadWorm.nodes[m].y - 25 * Math.sin(deadWorm.nodes[m].r - Math.PI / 2));
                        }
                        ctx.arc(deadWorm.nodes[deadWorm.nodes.length - 1].x, deadWorm.nodes[deadWorm.nodes.length - 1].y, 25, -(deadWorm.nodes[deadWorm.nodes.length - 1].r - Math.PI / 2), -(deadWorm.nodes[deadWorm.nodes.length - 1].r + Math.PI / 2));
                        for(var m = deadWorm.nodes.length - 2; m > 0; m--)
                        {
                            ctx.lineTo(deadWorm.nodes[m].x + 25 * Math.cos(deadWorm.nodes[m].r + Math.PI / 2), deadWorm.nodes[m].y - 25 * Math.sin(deadWorm.nodes[m].r + Math.PI / 2));
                        }
                        ctx.closePath();
                        ctx.stroke();
                        
                        ctx.translate(deadWorm.nodes[0].x, deadWorm.nodes[0].y);
                        ctx.rotate(-deadWorm.nodes[0].r);
                        
                        ctx.lineWidth = 2;
                        
                        ctx.beginPath();
                        ctx.arc(19, 0, 13, Math.PI - Math.PI / 3, Math.PI + Math.PI / 3);
                        ctx.stroke();
                        
                        ctx.beginPath();
                        ctx.moveTo(-2.5, -12.5);
                        ctx.lineTo(2.5, -7.5);
                        ctx.stroke();
                        
                        ctx.beginPath();
                        ctx.moveTo(-2.5, -7.5);
                        ctx.lineTo(2.5, -12.5);
                        ctx.stroke();
                        
                        ctx.beginPath();
                        ctx.moveTo(-2.5, 12.5);
                        ctx.lineTo(2.5, 7.5);
                        ctx.stroke();
                        
                        ctx.beginPath();
                        ctx.moveTo(-2.5, 7.5);
                        ctx.lineTo(2.5, 12.5);
                        ctx.stroke();
                            
                        ctx.rotate(deadWorm.nodes[0].r);
                        ctx.translate(-deadWorm.nodes[0].x, -deadWorm.nodes[0].y);
                    }
                }
                
                //------ WORM RENDERING ------
                
                for(var n = 0; n < worms.length; n++)
                {
                    const worm = worms[n];
                    var color = hueString(worm.hue);
                    
                    if(worm.inCanvas(camera))
                    {
                        ctx.lineWidth = 3;
                        ctx.strokeStyle = color;
                        ctx.fillStyle = color;
                        ctx.shadowBlur = 20;
                        ctx.shadowColor = color;
                        
                        switch(worm.type)
                        {
                            case 0:
                                ctx.beginPath();
                                ctx.arc(worm.nodes[0].x, worm.nodes[0].y, 25, -(worm.nodes[0].r + Math.PI / 2), -(worm.nodes[0].r - Math.PI / 2));
                                for(var m = 1; m < worm.nodes.length - 1; m += 1)
                                {
                                    ctx.translate(worm.nodes[m].x, worm.nodes[m].y);
                                    ctx.rotate(-worm.nodes[m].r);
                                    ctx.lineTo(0, 25);
                                    ctx.rotate(worm.nodes[m].r);
                                    ctx.translate(-worm.nodes[m].x, -worm.nodes[m].y);
                                }
                                ctx.arc(worm.nodes[worm.nodes.length - 1].x, worm.nodes[worm.nodes.length - 1].y, 25, -(worm.nodes[worm.nodes.length - 1].r - Math.PI / 2), -(worm.nodes[worm.nodes.length - 1].r + Math.PI / 2));
                                for(var m = worm.nodes.length - 2; m > 0; m -= 1)
                                {
                                    ctx.translate(worm.nodes[m].x, worm.nodes[m].y);
                                    ctx.rotate(-worm.nodes[m].r);
                                    ctx.lineTo(0, -25);
                                    ctx.rotate(worm.nodes[m].r);
                                    ctx.translate(-worm.nodes[m].x, -worm.nodes[m].y);
                                }
                                ctx.closePath();
                                ctx.stroke();
                                var interpolation1 = interpolateQuadratic(12, 6, worm.happiness);
                                var interpolation2 = interpolateQuadratic(-11, -15, worm.happiness);
                                var interpolation3 = interpolateQuadratic(4, 19, worm.happiness);
                                ctx.translate(worm.nodes[0].x, worm.nodes[0].y);
                                ctx.rotate(-worm.nodes[0].r);
                                ctx.lineWidth = 2;
                                ctx.beginPath();
                                ctx.moveTo(interpolation1, interpolation2);
                                ctx.bezierCurveTo(interpolation3, -7, interpolation3, 7, interpolation1, -interpolation2);
                                ctx.stroke();
                                ctx.beginPath();
                                ctx.arc(0, -5, 2, 0, 2 * Math.PI);
                                ctx.fill();
                                ctx.beginPath();
                                ctx.arc(0, 5, 2, 0, 2 * Math.PI);
                                ctx.fill();
                                ctx.rotate(worm.nodes[0].r);
                                ctx.translate(-worm.nodes[0].x, -worm.nodes[0].y);
                                break;
                            case 1:
                                ctx.beginPath();
                                ctx.arc(worm.nodes[0].x, worm.nodes[0].y, 25, -(worm.nodes[0].r + Math.PI / 2), -(worm.nodes[0].r - Math.PI / 2));
                                for(var m = 1; m < worm.nodes.length - 1; m += 1)
                                {
                                    ctx.translate(worm.nodes[m].x, worm.nodes[m].y);
                                    ctx.rotate(-worm.nodes[m].r);
                                    
                                    switch(true)
                                    {
                                        case m % 4 === 0:
                                            ctx.lineTo(3, 25);
                                            break;
                                        case m % 4 === 1:
                                            ctx.lineTo(-3, 25);
                                            break;
                                        case m % 4 > 1:
                                            ctx.lineTo(0, 25 - 5 * worm.nodes[m].activeCounter);
                                            break;
                                    }
                                    
                                    ctx.rotate(worm.nodes[m].r);
                                    ctx.translate(-worm.nodes[m].x, -worm.nodes[m].y);
                                }
                                ctx.arc(worm.nodes[worm.nodes.length - 1].x, worm.nodes[worm.nodes.length - 1].y, 25, -(worm.nodes[worm.nodes.length - 1].r - Math.PI / 2), -(worm.nodes[worm.nodes.length - 1].r + Math.PI / 2));
                                for(var m = worm.nodes.length - 2; m > 0; m -= 1)
                                {
                                    ctx.translate(worm.nodes[m].x, worm.nodes[m].y);
                                    ctx.rotate(-worm.nodes[m].r);
                                    
                                    switch(true)
                                    {
                                        case m % 4 === 0:
                                            ctx.lineTo(3, -25);
                                            break;
                                        case m % 4 === 1:
                                            ctx.lineTo(-3, -25);
                                            break;
                                        case m % 4 > 1:
                                            ctx.lineTo(0, -25 + 5 * worm.nodes[m].activeCounter);
                                            break;
                                    }
                                    
                                    ctx.rotate(worm.nodes[m].r);
                                    ctx.translate(-worm.nodes[m].x, -worm.nodes[m].y);
                                }
                                ctx.closePath();
                                ctx.stroke();
                                var interpolation1 = interpolateQuadratic(12, 6, worm.happiness);
                                var interpolation2 = interpolateQuadratic(-11, -15, worm.happiness);
                                var interpolation3 = interpolateQuadratic(4, 19, worm.happiness);
                                ctx.translate(worm.nodes[0].x, worm.nodes[0].y);
                                ctx.rotate(-worm.nodes[0].r);
                                ctx.lineWidth = 2;
                                ctx.beginPath();
                                ctx.moveTo(interpolation1, interpolation2);
                                ctx.bezierCurveTo(interpolation3, -7, interpolation3, 7, interpolation1, -interpolation2);
                                ctx.stroke();
                                ctx.beginPath();
                                ctx.arc(0, -5, 2, 0, 2 * Math.PI);
                                ctx.fill();
                                ctx.beginPath();
                                ctx.arc(0, 5, 2, 0, 2 * Math.PI);
                                ctx.fill();
                                ctx.rotate(worm.nodes[0].r);
                                ctx.translate(-worm.nodes[0].x, -worm.nodes[0].y);
                                break;
                            case 2:
                                ctx.beginPath();
                                ctx.arc(worm.nodes[0].x, worm.nodes[0].y, 25, -(worm.nodes[0].r + Math.PI / 2), -(worm.nodes[0].r - Math.PI / 2));
                                for(var m = 1; m < worm.nodes.length - 1; m += 1)
                                {
                                    ctx.translate(worm.nodes[m].x, worm.nodes[m].y);
                                    ctx.rotate(-worm.nodes[m].r);
                                    ctx.lineTo(0, 25);
                                    ctx.rotate(worm.nodes[m].r);
                                    ctx.translate(-worm.nodes[m].x, -worm.nodes[m].y);
                                }
                                ctx.arc(worm.nodes[worm.nodes.length - 1].x, worm.nodes[worm.nodes.length - 1].y, 25, -(worm.nodes[worm.nodes.length - 1].r - Math.PI / 2), -(worm.nodes[worm.nodes.length - 1].r + Math.PI / 2));
                                for(var m = worm.nodes.length - 2; m > 0; m -= 1)
                                {
                                    ctx.translate(worm.nodes[m].x, worm.nodes[m].y);
                                    ctx.rotate(-worm.nodes[m].r);
                                    ctx.lineTo(0, -25);
                                    ctx.rotate(worm.nodes[m].r);
                                    ctx.translate(-worm.nodes[m].x, -worm.nodes[m].y);
                                }
                                ctx.closePath();
                                ctx.stroke();
                                
                                var interpolation = interpolateQuadratic(Math.PI / 4, Math.PI / 6, worm.happiness);
                                ctx.translate(worm.nodes[0].x, worm.nodes[0].y);
                                ctx.rotate(-worm.nodes[0].r);
                                ctx.beginPath();
                                ctx.moveTo(25 * Math.cos(-interpolation), 25 * Math.sin(-interpolation));
                                ctx.lineTo(50 * Math.cos(-interpolation), 50 * Math.sin(-interpolation));
                                ctx.stroke();
                                ctx.beginPath();
                                ctx.moveTo(25 * Math.cos(interpolation), 25 * Math.sin(interpolation));
                                ctx.lineTo(50 * Math.cos(interpolation), 50 * Math.sin(interpolation));
                                ctx.stroke();
                                ctx.beginPath();
                                ctx.arc(50 * Math.cos(-interpolation), 50 * Math.sin(-interpolation), 5, 0, 2 * Math.PI);
                                ctx.fill();
                                ctx.beginPath();
                                ctx.arc(50 * Math.cos(interpolation), 50 * Math.sin(interpolation), 5, 0, 2 * Math.PI);
                                ctx.fill();
                                ctx.rotate(worm.nodes[0].r);
                                ctx.translate(-worm.nodes[0].x, -worm.nodes[0].y);
                                break;
                        }
                    }
                }
                
                //----- MINIMAP RENDERING ----
                
                ctx.translate(camera.x - canvasHalfWidth, camera.y - canvasHalfHeight);
                ctx.shadowBlur = 0;
                ctx.globalAlpha = 0.5;
                
                if(!minimapExpanded)
                {
                    var region = new Path2D();
                    region.rect(canvasWidth - minimapWidth - 10, canvasHeight - minimapHeight - 10, minimapWidth, minimapHeight);
                    ctx.save();
                    ctx.clip(region, "nonzero");
                    ctx.fillStyle = "#020202";
                    ctx.fillRect(canvasWidth - minimapWidth - 10, canvasHeight - minimapHeight - 10, minimapWidth, minimapHeight);
                    ctx.translate(canvasWidth - 10 - minimapWidth / 2, canvasHeight - 10 - minimapHeight / 2);
                }
                
                if(minimapExpanded)
                {
                    ctx.fillStyle = "#000000";
                    ctx.shadowColor = "#000000";
                    ctx.globalAlpha = 0.8;
                    ctx.fillRect(0, 0, canvasWidth, canvasHeight);
                    ctx.translate(canvasHalfWidth, canvasHalfHeight);
                }
                
                ctx.strokeStyle = "#171717";
                ctx.lineWidth = 20 * minimapZoom;
                ctx.beginPath();
                ctx.arc(minimapZoom * (0 - camera.x), minimapZoom * (0 - camera.y), WORLD_RADIUS * minimapZoom, 0, 2 * Math.PI);
                ctx.stroke();
                ctx.shadowBlur = 20;
                
                for(var n = 0; n < energies.length; n++)
                {
                    const energy = energies[n];
                    
                    if(minimapExpanded || energy.inMinimap(camera))
                    {
                        ctx.globalAlpha = energy.opacity;
                        
                        if(energy.type === 0)
                        {
                            ctx.fillStyle = "#ff0000";
                            ctx.shadowColor = "#ff0000";
                        }
                        
                        if(energy.type === 1)
                        {
                            ctx.fillStyle = "#00e5ff";
                            ctx.shadowColor = "#00e5ff";
                        }
                        
                        if(energy.type === 2)
                        {
                            ctx.fillStyle = "#ff9100";
                            ctx.shadowColor = "#ff9100";
                        }
                        
                        ctx.beginPath();
                        ctx.arc(minimapZoom * (energy.x - camera.x), minimapZoom * (energy.y - camera.y), 20 * minimapZoom, 0, 2 * Math.PI);
                        ctx.fill();
                    }
                }
                
                ctx.lineWidth = 40 * minimapZoom;
                ctx.lineCap = "round";
                ctx.globalAlpha = 1;
                
                for(var n = 0; n < worms.length; n++)
                {
                    const worm = worms[n];
                    var color = hueString(worm.hue);
                    
                    if(minimapExpanded || worm.inMinimap(camera))
                    {
                        ctx.strokeStyle = color;
                        ctx.shadowColor = color;
                        ctx.beginPath();
                        ctx.moveTo(minimapZoom * (worm.nodes[0].x - camera.x), minimapZoom * (worm.nodes[0].y - camera.y));
                        
                        for(var m = 1; m < worm.nodes.length; m++)
                        {
                            ctx.lineTo(minimapZoom * (worm.nodes[m].x - camera.x), minimapZoom * (worm.nodes[m].y - camera.y));
                        }
                        
                        ctx.stroke();
                    }
                }
                
                if(!minimapExpanded)
                {
                    ctx.translate(-canvasWidth + 10 + minimapWidth / 2, -canvasHeight + 10 + minimapHeight / 2);
                    ctx.restore();
                }
                
                if(minimapExpanded)
                {
                    ctx.translate(-canvasHalfWidth, -canvasHalfHeight);
                }
                
                requestAnimationFrame(render);
            }
            
            //----------------------------
            //---------- MAFFS -----------
            //----------------------------
            
            function distance(p1, p2)
            {
                return Math.hypot(p1.x - p2.x, p1.y - p2.y);
            }
            
            function distanceSquared(p1, p2)
            {
                return Math.pow(p1.x - p2.x, 2) + Math.pow(p1.y - p2.y, 2);
            }
            
            function distanceManhattan(p1, p2)
            {
                return Math.abs(p1.x - p2.x) + Math.abs(p1.y - p2.y);
            }
            
            function interpolateLinear(startingValue, endingValue, t)
            {
                return (startingValue + (endingValue - startingValue) * t);
            }
            
            function interpolateQuadratic(startingValue, endingValue, t)
            {
                return interpolateLinear(startingValue, endingValue, t < 0.5 ? 2 * t * t : 1 - Math.pow(-2 * t + 2, 2) / 2);
            }
            
            function calculateAngleDifference(a1, a2)
            {
                var difference = a2 - a1;
                while(difference < -Math.PI)
                    difference += 2 * Math.PI;
                while(difference > Math.PI)
                    difference -= 2 * Math.PI;
                return difference;
            }
            
            function intersectCircleLineSegment(circle, line)
            {
                var a, b, c, d, u1, u2, ret, retP1, retP2, v1, v2;
                v1 = {};
                v2 = {};
                v1.x = line.p2.x - line.p1.x;
                v1.y = line.p2.y - line.p1.y;
                v2.x = line.p1.x - circle.center.x;
                v2.y = line.p1.y - circle.center.y;
                b = (v1.x * v2.x + v1.y * v2.y);
                c = 2 * (v1.x * v1.x + v1.y * v1.y);
                b *= -2;
                d = Math.sqrt(b * b - 2 * c * (v2.x * v2.x + v2.y * v2.y - circle.radius * circle.radius));
                if(isNaN(d))
                {
                    return [];
                }
                u1 = (b - d) / c;
                u2 = (b + d) / c;    
                retP1 = {};
                retP2 = {}  
                ret = [];
                if(u1 <= 1 && u1 >= 0)
                {
                    retP1.x = line.p1.x + v1.x * u1;
                    retP1.y = line.p1.y + v1.y * u1;
                    ret[0] = retP1;
                }
                if(u2 <= 1 && u2 >= 0)
                {
                    retP2.x = line.p1.x + v1.x * u2;
                    retP2.y = line.p1.y + v1.y * u2;
                    ret[ret.length] = retP2;
                }       
                return ret;
            }
            
            function intersectCircleCircle(circle1, circle2)
            {
                var a, dx, dy, d, h, rx, ry;
                var x0 = circle1.center.x, y0 = circle1.center.y, r0 = circle1.radius, x1 = circle2.center.x, y1 = circle2.center.y, r1 = circle2.radius, x2, y2;
                dx = x1 - x0;
                dy = y1 - y0;
                d = Math.sqrt((dy*dy) + (dx*dx));
                if (d > (r0 + r1)) {
                    return false;
                }
                if (d < Math.abs(r0 - r1)) {
                    return false;
                }
                a = ((r0*r0) - (r1*r1) + (d*d)) / (2.0 * d) ;
                x2 = x0 + (dx * a/d);
                y2 = y0 + (dy * a/d);
                h = Math.sqrt((r0*r0) - (a*a));
                rx = -dy * (h/d);
                ry = dx * (h/d);
                var xi = x2 + rx;
                var xi_prime = x2 - rx;
                var yi = y2 + ry;
                var yi_prime = y2 - ry;
                return [point(xi, yi), point(xi_prime, yi_prime)];
            }
            
            function clampMin(num, min)
            {
                return num < min ? min : num;
            }
            
            function clampMax(num, max)
            {
                return num > max ? max : num;
            }
            
            function clamp(num, min, max)
            {
                return num < min ? min : num > max ? max : num;
            }
            
        </script>
    </body>
</html>