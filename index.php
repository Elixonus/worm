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
                camera;

                constructor(camera)
                {
                    this.camera = camera;
                }

                setCameraOn()
                {
                    this.camera.FilmedObject = this;
                    this.updateCameraPosition();
                }
            }

            class Point extends Filmable
            {
                x;
                y;

                constructor(x, y, camera)
                {
                    super(camera);
                    this.x = x;
                    this.y = y;
                }

                updateCameraPosition()
                {
                    if (this.camera.FilmedObject == this)
                    {
                        this.camera.x = this.x - canvas.width / 2;
                        this.camera.y = this.y - canvas.height / 2;
                    }
                }
            }

            class World extends Filmable
            {
                x;
                y;
                worldRadius = 1000;
                gridSize = 100;
                wormBotCount = 10;
                energyCount = 100;

                constructor(camera, x = 0, y = 0)
                {
                    super(camera);
                    this.x = x;
                    this.y = y;


                }

                updateCameraPosition()
                {
                    if (this.camera.FilmedObject == this)
                    {
                        this.camera.x = this.x - canvas.width / 2;
                        this.camera.y = this.y - canvas.height / 2;
                    }
                }
            }
            
            class Worm extends Filmable
            {
                controllable;
                nodes = [];
                turn;
                color;

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
                                x: tempLastNode.x - 5 * Math.cos(tempLastNode.r),
                                y: tempLastNode.y + 5 * Math.sin(tempLastNode.r),
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
                        this.nodes[0].r += Math.PI / 60;

                        if(this.nodes[0].r >= 2 * Math.PI)
                        {
                            this.nodes[0].r -= 2 * Math.PI;
                        }
                    }

                    if(this.turn === 1)
                    {
                        this.nodes[0].r -= Math.PI / 60;

                        if(this.nodes[0].r < 0)
                        {
                            this.nodes[0].r += 2 * Math.PI;
                        }
                    }

                    if(this.turn === 1)
                    {

                    }

                    if(this.nodes.length > 0)
                    {
                        var tempFirstNode = this.nodes[0];
                        tempFirstNode.x += 3 * Math.cos(tempFirstNode.r);
                        tempFirstNode.y -= 3 * Math.sin(tempFirstNode.r);
                        
                        for(var n = 1; n < this.nodes.length; n++)
                        {
                            var tempCurrentNode = this.nodes[n];
                            var tempNextNode = this.nodes[n - 1];
                            tempCurrentNode.r = Math.PI - Math.atan2(tempCurrentNode.y - tempNextNode.y, tempCurrentNode.x - tempNextNode.x);
                            tempCurrentNode.x = tempNextNode.x - 5 * Math.cos(tempCurrentNode.r);
                            tempCurrentNode.y = tempNextNode.y + 5 * Math.sin(tempCurrentNode.r);
                        }
                        
                        this.updateCameraPosition();
                    }
                }

                updateCameraPosition()
                {
                    if (this.camera.FilmedObject == this)
                    {
                        this.camera.x = this.nodes[0].x - canvas.width / 2;
                        this.camera.y = this.nodes[0].y - canvas.height / 2;
                    }
                }
            }
            
            class Energy extends Filmable
            {
                constructor(camera)
                {
                    super(camera);

                    this.type = Math.round(Math.random());
                    var tempRotation = 2 * Math.PI * Math.random();
                    var tempRadius = worldRadius * Math.sqrt(Math.random());
                    this.x = tempRadius * Math.cos(tempRotation);
                    this.y = tempRadius * Math.sin(tempRotation);
                    this.r = 2 * Math.PI * Math.random();
                }
                
                move()
                {
                    for(var n = 0; n < worms.length; n++)
                    {

                    }
                }

                updateCameraPosition()
                {
                    if (this.camera.FilmedObject == this)
                    {
                        this.camera.x = this.x - canvas.width / 2;
                        this.camera.y = this.y - canvas.height / 2;
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
                console.log("test");

                if(!event)
                {
                    event = window.event;
                }

                if(event.button == 0)
                {
                    activeWorm--;
                }

                if(event.button == 2)
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

                worms[activeWorm].setCameraOn();
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
                }
            }
            
            function keyup(event)
            {
                if(!event)
                {
                    event = window.event;
                }
                
                keys.splice(keys.indexOf(event.keyCode), 1);
            }
            
            document.addEventListener("contextmenu", event => event.preventDefault());
            document.onmousedown = mousedown;
            document.onkeydown = keydown;
            document.onkeyup = keyup;
            
            var keys = [];
            var camera = new Camera();
            var world = new World(camera);
            var worldRadius = world.worldRadius;
            var gridSize = world.gridSize;
            var wormBotCount = world.wormBotCount;
            var energyCount = world.energyCount;
            var worms = [new Worm("Human", Math.round(Math.random() * 50 + 20), camera)];
            var energies = [];
            
            for(var n = 0; n < wormBotCount; n++)
            {
                worms.push(new Worm("Bot", Math.round(Math.random() * 50 + 20), camera));
            }
            
            for(var n = 0; n < energyCount; n++)
            {
                energies.push(new Energy(camera));
            }
            
            var activeWorm = 0;
            var canvas = document.getElementById("canvas");
            var ctx = canvas.getContext("2d");
            var fps = null;
            var previousTime = new Date();
            worms[0].setCameraOn();

            function render()
            {
                for(var n = 0; n < worms.length; n++)
                {
                    var worm = worms[n];
                    
                    if(worm.controllable)
                    {
                        if(worm.nodes.length > 0)
                        {
                            worm.turn = 0;

                            if(keys.includes(65) || keys.includes(37))
                            {
                                worm.turn -= 1;
                            }
                            
                            if(keys.includes(68) || keys.includes(39))
                            {
                                worm.turn += 1;
                            }

                            console.log(worm.turn);
                        }
                    }
                    
                    else
                    {
                        //AI code


                    }
                    

                    worm.move();
                    
                    if(d({x: 0, y: 0}, worm.nodes[0]) > worldRadius)
                    {
                        worms.splice(n, 1);
                        n--;
                    }
                }
                
                for(var n = 0; n < energies.length; n++)
                {
                    var energy = energies[n];
                    energy.move();
                }
                
                ctx.fillStyle = "#000000";
                ctx.shadowBlur = 0;
                ctx.globalAlpha = 1;
                ctx.fillRect(0, 0, canvas.width, canvas.height);
                ctx.strokeStyle = "#141414";
                ctx.lineWidth = 1;
                for(var n = 1; n < 2 * worldRadius / gridSize; n++)
                {
                    ctx.beginPath();
                    ctx.moveTo(n * gridSize - worldRadius - camera.x, 0 - worldRadius - camera.y);
                    ctx.lineTo(n * gridSize - worldRadius - camera.x, 2 * worldRadius - worldRadius - camera.y);
                    ctx.stroke();
                    ctx.closePath();
                    ctx.beginPath();
                    ctx.moveTo(0 - worldRadius - camera.x, n * gridSize - worldRadius - camera.y);
                    ctx.lineTo(2 * worldRadius - worldRadius - camera.x, n * gridSize - worldRadius - camera.y);
                    ctx.stroke();
                    ctx.closePath();
                }
                ctx.beginPath();
                ctx.arc(0 - camera.x, 0 - camera.y, worldRadius, Math.PI, 0);
                ctx.lineTo(worldRadius - camera.x, -worldRadius - camera.y);
                ctx.lineTo(-worldRadius - camera.x, -worldRadius - camera.y);
                ctx.closePath();
                ctx.fill();
                ctx.beginPath();
                ctx.arc(0 - camera.x, 0 - camera.y, worldRadius, 0, -Math.PI);
                ctx.lineTo(-worldRadius - camera.x, worldRadius - camera.y);
                ctx.lineTo(worldRadius - camera.x, worldRadius - camera.y);
                ctx.closePath();
                ctx.fill();
                ctx.strokeStyle = "#141414";
                ctx.lineWidth = 3;
                ctx.beginPath();
                ctx.arc(0 - camera.x, 0 - camera.y, worldRadius, 0, 2 * Math.PI);
                ctx.stroke();
                ctx.closePath();
                ctx.lineWidth = 3;
                ctx.shadowBlur = 20;
                for(var n = 0; n < energies.length; n++)
                {
                    var energy = energies[n];
                    ctx.beginPath();
                    if(energy.type === 0)
                    {
                        ctx.strokeStyle = "#ff0000";
                        ctx.shadowColor = ctx.strokeStyle;
                        ctx.moveTo(energy.x + 25 * Math.cos(energy.r) - camera.x, energy.y + 25 * Math.sin(energy.r) - camera.y);
                        ctx.lineTo(energy.x + 25 * Math.cos(energy.r + 2 * Math.PI / 3) - camera.x, energy.y + 25 * Math.sin(energy.r + 2 * Math.PI / 3) - camera.y);
                        ctx.lineTo(energy.x + 25 * Math.cos(energy.r + 4 * Math.PI / 3) - camera.x, energy.y + 25 * Math.sin(energy.r + 4 * Math.PI / 3) - camera.y);
                    }
                    
                    if(energy.type === 1)
                    {
                        ctx.strokeStyle = "#00e5ff";
                        ctx.shadowColor = ctx.strokeStyle;
                        ctx.moveTo(energy.x + 25 * Math.cos(energy.r) - camera.x, energy.y + 25 * Math.sin(energy.r) - camera.y);
                        ctx.lineTo(energy.x + 25 * Math.cos(energy.r + Math.PI / 2) - camera.x, energy.y + 25 * Math.sin(energy.r + Math.PI / 2) - camera.y);
                        ctx.lineTo(energy.x + 25 * Math.cos(energy.r + Math.PI) - camera.x, energy.y + 25 * Math.sin(energy.r + Math.PI) - camera.y);
                        ctx.lineTo(energy.x + 25 * Math.cos(energy.r + 3 * Math.PI / 2) - camera.x, energy.y + 25 * Math.sin(energy.r + 3 * Math.PI / 2) - camera.y);
                    }
                    
                    ctx.closePath();
                    ctx.stroke();
                }
                
                for(var m = 0; m < worms.length; m++)
                {
                    var worm = worms[m];
                    if(worm.nodes.length > 0)
                    {
                        ctx.strokeStyle = worm.color;
                        ctx.fillStyle = worm.color;
                        ctx.shadowColor = ctx.strokeStyle;
                        ctx.beginPath();
                        ctx.arc(worm.nodes[0].x - camera.x, worm.nodes[0].y - camera.y, 25, -(worm.nodes[0].r + Math.PI / 2), -(worm.nodes[0].r - Math.PI / 2));
                        for(var n = 1; n < worm.nodes.length - 1; n++)
                        {
                            ctx.lineTo(worm.nodes[n].x + 25 * Math.cos(worm.nodes[n].r - Math.PI / 2) - camera.x, worm.nodes[n].y - 25 * Math.sin(worm.nodes[n].r - Math.PI / 2) - camera.y);
                        }
                        ctx.arc(worm.nodes[worm.nodes.length - 1].x - camera.x, worm.nodes[worm.nodes.length - 1].y - camera.y, 25, -(worm.nodes[worm.nodes.length - 1].r - Math.PI / 2), -(worm.nodes[worm.nodes.length - 1].r + Math.PI / 2));
                        for(var n = worm.nodes.length - 2; n > 0; n--)
                        {
                            ctx.lineTo(worm.nodes[n].x + 25 * Math.cos(worm.nodes[n].r + Math.PI / 2) - camera.x, worm.nodes[n].y - 25 * Math.sin(worm.nodes[n].r + Math.PI / 2) - camera.y);
                        }
                        ctx.closePath();
                        ctx.stroke();

                        for(var n = 1; n < worm.nodes.length - 1; n++)
                        {
                            ctx.beginPath();
                            ctx.lineTo(worm.nodes[n].x + 25 * Math.cos(worm.nodes[n].r - Math.PI / 2) - camera.x, worm.nodes[n].y - 25 * Math.sin(worm.nodes[n].r - Math.PI / 2) - camera.y);
                            ctx.lineTo(worm.nodes[n].x + 25 * Math.cos(worm.nodes[n].r + Math.PI / 2) - camera.x, worm.nodes[n].y - 25 * Math.sin(worm.nodes[n].r + Math.PI / 2) - camera.y);
                            ctx.stroke();
                            ctx.closePath();
                        }

                        for(var n = -1; n <= 1; n+=2)
                        {
                            ctx.beginPath();
                            ctx.moveTo(worm.nodes[0].x + 25 * Math.cos(worm.nodes[0].r + n * Math.PI / 4) - camera.x, worm.nodes[0].y - 25 * Math.sin(worm.nodes[0].r + n * Math.PI / 4) - camera.y);
                            ctx.lineTo(worm.nodes[0].x + 50 * Math.cos(worm.nodes[0].r + n * Math.PI / 4) - camera.x, worm.nodes[0].y - 50 * Math.sin(worm.nodes[0].r + n * Math.PI / 4) - camera.y);
                            ctx.stroke();
                            ctx.closePath();
                            ctx.beginPath();
                            ctx.arc(worm.nodes[0].x + 50 * Math.cos(worm.nodes[0].r + n * Math.PI / 4) - camera.x, worm.nodes[0].y - 50 * Math.sin(worm.nodes[0].r + n * Math.PI / 4) - camera.y, 5, 0, 2 * Math.PI);
                            ctx.fill();
                            ctx.closePath();
                        }
                    }
                }
                ctx.fillStyle = "#000000";
                ctx.shadowBlur = 0;
                ctx.globalAlpha = 0.25;
                for(var n = 1; n < canvas.height / 4; n++)
                {
                    ctx.fillRect(0, n * 4 - 1, canvas.width, 2);
                }
                
                var currentTime = new Date();
                fps = 1000 / (currentTime - previousTime);
                previousTime = currentTime;
                window.requestAnimationFrame(render);
            }
            
            window.requestAnimationFrame(render);
            
            resize();
            
            function d(p1, p2)
            {
                return Math.sqrt(Math.pow(p1.x - p2.x, 2) + Math.pow(p1.y - p2.y, 2));
            }
            
        </script>
    </body>
</html>