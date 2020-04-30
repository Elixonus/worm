<!DOCTYPE HTML>
<html>
    <head>
        <title>Worm</title>
        <link rel="shortcut icon" type="image/png" href="/favicon.ico"/>
    </head>
    
    <body onresize="resize();">
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
        
            class Filmable
            {
                constructor(camera)
                {
                    this.camera = camera;
                }

                setCameraOn()
                {
                    this.camera.FilmedObject = this;
                    this.updateCameraPosition();
                }
                
                removeCameraFrom()
                {
                    this.camera.FilmedObject = null;
                    this.updateCameraPosition();
                }
            }

            class Point extends Filmable
            {
                constructor(x, y, camera)
                {
                    super(camera);
                    this.x = x;
                    this.y = y;
                }

                updateCameraPosition()
                {
                    if(this.camera.FilmedObject == this)
                    {
                        this.camera.x = this.x;
                        this.camera.y = this.y;
                    }
                }
            }
            
            class Line
            {
                constructor(p1, p2)
                {
                    this.p1 = p1;
                    this.p2 = p2;
                }
            }
            
            class Circle
            {
                constructor(center, radius)
                {
                    this.center = center;
                    this.radius = radius;
                }
            }

            class World extends Filmable
            {
                constructor(camera, x = 0, y = 0)
                {
                    super(camera);
                    this.x = x;
                    this.y = y;
                    this.worldRadius = 10000;
                    this.gridSize = 100;
                    this.wormBotCount = 100;
                    this.energyCount = 1000;
                }

                updateCameraPosition()
                {
                    if (this.camera.FilmedObject == this)
                    {
                        this.camera.x = this.x;
                        this.camera.y = this.y;
                    }
                }
            }
            
            class Worm extends Filmable
            {
                constructor(type, count, camera)
                {
                    super(camera);
                    
                    if(type === "Human")
                    {
                        this.controllable = true;
                        this.color = "#00ff00";
                    }
                    
                    if(type === "Bot")
                    {
                        this.controllable = false;
                        this.color = "hsl(" + Math.round(Math.random() * 90 + 270) + ", 100%, 50%)";
                    }
                    this.nodes = [];
                    this.turn = 0;
                    this.addNode(count);
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
                            var tempRadius = worldRadius * Math.sqrt(Math.random());
                            this.nodes.push(
                            {
                                active: true,
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
                                x: tempLastNode.x - 5 * Math.cos(tempLastNode.r),
                                y: tempLastNode.y + 5 * Math.sin(tempLastNode.r),
                                r: tempLastNode.r
                            });
                        }
                        
                        tempLength++;
                    }
                }
                
                addSmoothNode(count)
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
                            var tempRadius = worldRadius * Math.sqrt(Math.random());
                            this.nodes.push(
                            {
                                active: true,
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
                
                move()
                {
                    if(this.turn === -1)
                    {
                        this.nodes[0].r += Math.PI / 60 * 60 / clampMin(fps, 20);

                        if(this.nodes[0].r >= 2 * Math.PI)
                        {
                            this.nodes[0].r -= 2 * Math.PI;
                        }
                    }

                    if(this.turn === 1)
                    {
                        this.nodes[0].r -= Math.PI / 60 * 60 / clampMin(fps, 20);

                        if(this.nodes[0].r < 0)
                        {
                            this.nodes[0].r += 2 * Math.PI;
                        }
                    }
                    
                    this.nodes[0].x += 3 * Math.cos(this.nodes[0].r) * 60 / clampMin(fps, 20);
                    this.nodes[0].y -= 3 * Math.sin(this.nodes[0].r) * 60 / clampMin(fps, 20);
                    
                    for(var n = 1; n < this.nodes.length; n++)
                    {
                        var tempCurrentNode = this.nodes[n];
                        var tempNextNode = this.nodes[n - 1];
                        
                        if(tempCurrentNode.active === false)
                        {
                            var tempDistance = d(tempCurrentNode, tempNextNode);
                            
                            if(tempDistance > 5)
                            {
                                tempCurrentNode.active = true;
                            }
                        }
                        
                        if(tempCurrentNode.active === true)
                        {
                            tempCurrentNode.r = Math.PI - Math.atan2(tempCurrentNode.y - tempNextNode.y, tempCurrentNode.x - tempNextNode.x);
                            
                            tempCurrentNode.x = tempNextNode.x - 5 * Math.cos(tempCurrentNode.r);
                            tempCurrentNode.y = tempNextNode.y + 5 * Math.sin(tempCurrentNode.r);
                        }
                    }
                    
                    this.updateCameraPosition();
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
                }

                updateCameraPosition()
                {
                    if (this.camera.FilmedObject == this)
                    {
                        this.camera.x = this.nodes[0].x;
                        this.camera.y = this.nodes[0].y;
                    }
                }
            }
            
            class Energy extends Filmable
            {
                constructor(camera)
                {
                    super(camera);
                    this.type = Math.round(2 * Math.random());
                    var tempRotation = 2 * Math.PI * Math.random();
                    var tempRadius = worldRadius * Math.sqrt(Math.random());
                    this.x = tempRadius * Math.cos(tempRotation);
                    this.y = tempRadius * Math.sin(tempRotation);
                    this.r = 2 * Math.PI * Math.random();
                }
                
                move()
                {

                }

                updateCameraPosition()
                {
                    if (this.camera.FilmedObject == this)
                    {
                        this.camera.x = this.x;
                        this.camera.y = this.y;
                    }
                }
            }
            
            class Camera
            {
                constructor()
                {
                    this.x = null;
                    this.y = null;
                    this.FilmedObject = null;
                }
            }
            
            function killWorm(n)
            {
                var temp = worms[n];
                worms.splice(n, 1);
                deadWorms.push(temp);
            }
            
            function resize()
            {
                if(window.innerWidth / window.innerHeight > canvas.width / canvas.height)
                {
                    canvas.style.width = window.innerHeight / window.innerWidth * canvas.width / canvas.height * 100 + "%";
                    canvas.style.height = "100%";
                }
                
                else
                {
                    canvas.style.width = "100%";
                    canvas.style.height = window.innerWidth / window.innerHeight * canvas.height / canvas.width * 100 + "%";
                }
            }

            function mousedown(event)
            {
                if(!event)
                {
                    event = window.event;
                }

                if(event.button === 0)
                {
                    activeWorm--;
                }
                
                if(event.button === 1)
                {
                    worms[activeWorm].removeCameraFrom();
                }

                if(event.button === 2)
                {
                    activeWorm++;
                }

                if(activeWorm < 0)
                {
                    activeWorm = 0;
                }
                
                if(activeWorm > worms.length - 1)
                {
                    activeWorm = worms.length - 1;
                }
                
                if(event.button !== 1)
                {
                    worms[activeWorm].setCameraOn();
                }
            }

            function keydown(event)
            {
                if(!event)
                {
                    event = window.event;
                }
                
                if(keys.includes(event.keyCode) === false)
                {
                    keys.push(event.keyCode);
                    
                    if(event.keyCode === 77 && !minimap.fired)
                    {
                        minimap.fired = true;
                        
                        if(minimap.expanded === false)
                        {
                            minimap.expanded = true;
                        }
                        
                        else
                        {
                            minimap.expanded = false;
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
                
                keys.splice(keys.indexOf(event.keyCode), 1);
                
                if(event.keyCode === 77)
                {
                    minimap.fired = false;
                }
            }
            
            window.addEventListener("contextmenu", event => event.preventDefault());
            window.onmousedown = mousedown;
            window.onkeydown = keydown;
            window.onkeyup = keyup;
            window.onload = render;
            
            var keys = [];
            var camera = new Camera();
            var world = new World(camera);
            var worldRadius = world.worldRadius;
            var gridSize = world.gridSize;
            var wormBotCount = world.wormBotCount;
            var energyCount = world.energyCount;
            var worms = [new Worm("Human", Math.round(Math.random() * 50 + 20), camera)];
            var deadWorms = [];
            var energies = [];
            
            for(var n = 0; n < wormBotCount; n++)
            {
                worms.push(new Worm("Bot", Math.round(Math.random() * 50 + 20), camera));
            }
            
            for(var n = 0; n < energyCount; n++)
            {
                energies.push(new Energy(camera));
            }
            
            var minimap =
            {
                width: 250,
                height: 200,
                zoom: 0.1,
                fired: false,
                expanded: false
            };
            
            var activeWorm = 0;
            var canvas = document.getElementById("canvas");
            var ctx = canvas.getContext("2d", {alpha: false});
            var fps = 60;
            var previousTime;
            var currentTime = new Date();
            worms[0].setCameraOn();

            function render()
            {
                for(var n = 0; n < worms.length; n++)
                {
                    var worm = worms[n];
                    
                    if(worm.controllable)
                    {
                        worm.turn = 0;

                        if(keys.includes(37) || keys.includes(65))
                        {
                            worm.turn -= 1;
                        }
                        
                        if(keys.includes(39) || keys.includes(68))
                        {
                            worm.turn += 1;
                        }
                    }
                    
                    else
                    {
                        //AI code


                    }
                    
                    var oldX = worm.nodes[0].x;
                    var oldY = worm.nodes[0].y;
                    
                    worm.move();
                    
                    if(d(new Point(0, 0), worm.nodes[0]) > worldRadius)
                    {
                        var newX = worm.nodes[0].x;
                        var newY = worm.nodes[0].y;
                        var intersection = interceptCircleLineSegment(new Circle(new Point(0, 0), worldRadius), new Line(new Point(oldX, oldY), new Point(newX, newY)));
                        worm.moveTo(intersection[0]);
                        killWorm(n);
                        n--;
                    }
                }
                
                for(var n = 0; n < energies.length; n++)
                {
                    var energy = energies[n];
                    energy.move();
                    var tempClosestWorm;
                    var tempMinimumDistance = false;
                    
                    for(var m = 0; m < worms.length; m++)
                    {
                        var tempDistance = d(energies[n], worms[m].nodes[0]);
                        
                        if(tempDistance < tempMinimumDistance || tempMinimumDistance === false)
                        {
                            tempMinimumDistance = tempDistance;
                            tempClosestWorm = m;
                        }
                    }
                    
                    if(tempMinimumDistance < 50 && tempMinimumDistance !== false)
                    {
                        energies.splice(n, 1);
                        worms[tempClosestWorm].addSmoothNode(5);
                        n--;
                    }
                }
                
                ctx.fillStyle = "#000000";
                ctx.lineCap = "butt";
                ctx.shadowBlur = 0;
                ctx.globalAlpha = 1;
                ctx.fillRect(0, 0, canvas.width, canvas.height);
                ctx.strokeStyle = "#141414";
                ctx.lineWidth = 1;
                
                ctx.translate(canvas.width / 2 - camera.x, canvas.height / 2 - camera.y);
                
                for(var n = 1; n < 2 * worldRadius / gridSize; n++)
                {
                    ctx.beginPath();
                    ctx.moveTo(n * gridSize - worldRadius, 0 - worldRadius);
                    ctx.lineTo(n * gridSize - worldRadius, 2 * worldRadius - worldRadius);
                    ctx.stroke();
                    ctx.beginPath();
                    ctx.moveTo(0 - worldRadius, n * gridSize - worldRadius);
                    ctx.lineTo(2 * worldRadius - worldRadius, n * gridSize - worldRadius);
                    ctx.stroke();
                }
                ctx.beginPath();
                ctx.arc(0, 0, worldRadius, Math.PI, 0);
                ctx.lineTo(worldRadius, -worldRadius);
                ctx.lineTo(-worldRadius, -worldRadius);
                ctx.closePath();
                ctx.fill();
                ctx.beginPath();
                ctx.arc(0, 0, worldRadius, 0, -Math.PI);
                ctx.lineTo(-worldRadius, worldRadius);
                ctx.lineTo(worldRadius, worldRadius);
                ctx.closePath();
                ctx.fill();
                ctx.strokeStyle = "#141414";
                ctx.lineWidth = 3;
                ctx.beginPath();
                ctx.arc(0, 0, worldRadius, 0, 2 * Math.PI);
                ctx.stroke();
                ctx.lineWidth = 3;
                ctx.shadowBlur = 20;
                for(var n = 0; n < energies.length; n++)
                {
                    var energy = energies[n];
                    
                    if(d(new Point(0, 0), new Point(energy.x - camera.x, energy.y - camera.y)) < d(new Point(0, 0), new Point(canvas.width / 2, canvas.height / 2)) + 100)
                    {
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
                
                ctx.shadowBlur = 0;
                
                for(var n = 0; n < deadWorms.length; n++)
                {
                    var deadWorm = deadWorms[n];
                    
                    if(d(new Point(0, 0), new Point(deadWorm.nodes[0].x - camera.x, deadWorm.nodes[0].y - camera.y)) < d(new Point(0, 0), new Point(canvas.width / 2, canvas.height / 2)) + 5 * deadWorm.nodes.length + 100)
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
                
                for(var n = 0; n < worms.length; n++)
                {
                    var worm = worms[n];
                    
                    if(d(new Point(0, 0), new Point(worm.nodes[0].x - camera.x, worm.nodes[0].y - camera.y)) < d(new Point(0, 0), new Point(canvas.width / 2, canvas.height / 2)) + 5 * worm.nodes.length + 100)
                    {
                        ctx.lineWidth = 3;
                        ctx.strokeStyle = worm.color;
                        ctx.fillStyle = worm.color;
                        ctx.shadowBlur = 20;
                        ctx.shadowColor = worm.color;
                        
                        ctx.beginPath();
                        ctx.arc(worm.nodes[0].x, worm.nodes[0].y, 25, -(worm.nodes[0].r + Math.PI / 2), -(worm.nodes[0].r - Math.PI / 2));
                        for(var m = 1; m < worm.nodes.length - 1; m++)
                        {
                            ctx.lineTo(worm.nodes[m].x + 25 * Math.cos(worm.nodes[m].r - Math.PI / 2), worm.nodes[m].y - 25 * Math.sin(worm.nodes[m].r - Math.PI / 2));
                        }
                        ctx.arc(worm.nodes[worm.nodes.length - 1].x, worm.nodes[worm.nodes.length - 1].y, 25, -(worm.nodes[worm.nodes.length - 1].r - Math.PI / 2), -(worm.nodes[worm.nodes.length - 1].r + Math.PI / 2));
                        for(var m = worm.nodes.length - 2; m > 0; m--)
                        {
                            ctx.lineTo(worm.nodes[m].x + 25 * Math.cos(worm.nodes[m].r + Math.PI / 2), worm.nodes[m].y - 25 * Math.sin(worm.nodes[m].r + Math.PI / 2));
                        }
                        ctx.closePath();
                        ctx.stroke();
                        
                        ctx.translate(worm.nodes[0].x, worm.nodes[0].y);
                        ctx.rotate(-worm.nodes[0].r);
                        
                        ctx.lineWidth = 2;
                        ctx.beginPath();
                        ctx.arc(0, 0, 15, -Math.PI / 3, Math.PI / 3);
                        ctx.stroke();
                        
                        ctx.beginPath();
                        ctx.arc(0, -5, 2, 0, 2 * Math.PI);
                        ctx.fill();
                        
                        ctx.beginPath();
                        ctx.arc(0, 5, 2, 0, 2 * Math.PI);
                        ctx.fill();
                        
                        ctx.rotate(worm.nodes[0].r);
                        ctx.translate(-worm.nodes[0].x, -worm.nodes[0].y);
                    }
                }
                
                ctx.translate(camera.x - canvas.width / 2, camera.y - canvas.height / 2);
                
                ctx.shadowBlur = 0;
                ctx.globalAlpha = 0.5;
                
                if(!minimap.expanded)
                {
                    var region = new Path2D();
                    region.rect(canvas.width - minimap.width - 10, canvas.height - minimap.height - 10, minimap.width, minimap.height);
                    ctx.save();
                    ctx.clip(region, "nonzero");
                    ctx.fillStyle = "#050505";
                    ctx.shadowColor = "#050505";
                    ctx.fillRect(canvas.width - minimap.width - 10, canvas.height - minimap.height - 10, minimap.width, minimap.height);
                    
                    ctx.translate(canvas.width - 10 - minimap.width / 2, canvas.height - 10 - minimap.height / 2);
                }
                
                if(minimap.expanded)
                {
                    ctx.fillStyle = "#000000";
                    ctx.shadowColor = "#000000";
                    ctx.globalAlpha = 0.8;
                    ctx.fillRect(0, 0, canvas.width, canvas.height);
                    
                    ctx.translate(canvas.width / 2, canvas.height / 2);
                }
                
                ctx.strokeStyle = "#171717";
                ctx.beginPath();
                ctx.arc(minimap.zoom * (0 - camera.x), minimap.zoom * (0 - camera.y), minimap.zoom * worldRadius, 0, 2 * Math.PI);
                ctx.stroke();
                
                ctx.shadowBlur = 20;
                
                for(var n = 0; n < energies.length; n++)
                {
                    if(d(new Point(0, 0), new Point(minimap.zoom * (energies[n].x - camera.x), minimap.zoom * (energies[n].y - camera.y))) < d(new Point(0, 0), new Point(minimap.width / 2, minimap.height / 2)) + 100 * minimap.zoom || minimap.expanded)
                    {
                        if(energies[n].type === 0)
                        {
                            ctx.fillStyle = "#ff0000";
                            ctx.shadowColor = "#ff0000";
                        }
                        
                        if(energies[n].type === 1)
                        {
                            ctx.fillStyle = "#00e5ff";
                            ctx.shadowColor = "#00e5ff";
                        }
                        
                        if(energies[n].type === 2)
                        {
                            ctx.fillStyle = "#ff9100";
                            ctx.shadowColor = "#ff9100";
                        }
                        
                        ctx.beginPath();
                        ctx.arc(minimap.zoom * (energies[n].x - camera.x), minimap.zoom * (energies[n].y - camera.y), 2, 0, 2 * Math.PI);
                        ctx.fill();
                    }
                }
                
                ctx.lineWidth = 4;
                ctx.lineCap = "round";
                
                for(var n = 0; n < worms.length; n++)
                {
                    if(d(new Point(0, 0), new Point(minimap.zoom * (worms[n].nodes[0].x - camera.x), minimap.zoom * (worms[n].nodes[0].y - camera.y))) < d(new Point(0, 0), new Point(minimap.width / 2, minimap.height / 2)) + (5 * worms[n].nodes.length + 100) * minimap.zoom || minimap.expanded)
                    {
                        ctx.strokeStyle = worms[n].color;
                        ctx.shadowColor = worms[n].color;
                        ctx.beginPath();
                        ctx.moveTo(minimap.zoom * (worms[n].nodes[0].x - camera.x), minimap.zoom * (worms[n].nodes[0].y - camera.y));
                        
                        for(var m = 1; m < worms[n].nodes.length; m++)
                        {
                            ctx.lineTo(minimap.zoom * (worms[n].nodes[m].x - camera.x), minimap.zoom * (worms[n].nodes[m].y - camera.y));
                        }
                        ctx.stroke();
                    }
                }
                
                if(!minimap.expanded)
                {
                    ctx.translate(-canvas.width + 10 + minimap.width / 2, -canvas.height + 10 + minimap.height / 2);
                    ctx.restore();
                }
                
                if(minimap.expanded)
                {
                    ctx.translate(-canvas.width / 2, -canvas.height / 2);
                }
                
                ctx.fillStyle = "#000000";
                ctx.shadowBlur = 0;
                ctx.globalAlpha = 0.25;
                for(var n = 1; n < canvas.height / 4; n++)
                {
                    ctx.fillRect(0, 4 * n - 1, canvas.width, 2);
                }
                
                previousTime = currentTime;
                currentTime = new Date();
                fps = 1000 / (currentTime - previousTime);
                
                ctx.globalAlpha = 1;
                ctx.fillStyle = "#00ff00";
                ctx.font = "30px Verdana";
                ctx.fillText(Math.round(fps), 10, 30);
                
                window.requestAnimationFrame(render);
            }
            
            resize();
            
            function d(p1, p2)
            {
                return Math.sqrt(Math.pow(p1.x - p2.x, 2) + Math.pow(p1.y - p2.y, 2));
            }
            
            function interceptCircleLineSegment(circle, line)
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
                if(isNaN(d)){ // no intercept
                    return [];
                }
                u1 = (b - d) / c;  // these represent the unit distance of point one and two on the line
                u2 = (b + d) / c;    
                retP1 = {};   // return points
                retP2 = {}  
                ret = []; // return array
                if(u1 <= 1 && u1 >= 0){  // add point if on the line segment
                    retP1.x = line.p1.x + v1.x * u1;
                    retP1.y = line.p1.y + v1.y * u1;
                    ret[0] = retP1;
                }
                if(u2 <= 1 && u2 >= 0){  // second add point if on the line segment
                    retP2.x = line.p1.x + v1.x * u2;
                    retP2.y = line.p1.y + v1.y * u2;
                    ret[ret.length] = retP2;
                }       
                return ret;
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