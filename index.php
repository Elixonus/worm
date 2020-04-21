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
            
            class Worm
            {
                controllable;
                nodes = [];
                color = "hsl(" + Math.round(Math.random() * 360) + ", 100%, 50%)";
                camera = new Camera();
                
                constructor(type, count)
                {
                    if(type === "Human")
                    {
                        this.controllable = true;
                    }
                    
                    if(type === "Bot")
                    {
                        this.controllable = false;
                    }
                    
                    this.addNode(count);
                }
                
                addNode(count)
                {
                    if(count === undefined)
                    {
                        count = 1;
                    }
                    
                    var temp_length = this.nodes.length;
                    
                    for(var n = 0; n < count; n++)
                    {
                        if(temp_length === 0)
                        {
                            var temp_rotation = 2 * Math.PI * Math.random();
                            var temp_radius = world_radius * Math.sqrt(Math.random());
                            this.nodes.push(
                            {
                                x: temp_radius * Math.cos(temp_rotation),
                                y: temp_radius * Math.sin(temp_rotation),
                                r: 2 * Math.PI * Math.random()
                            });
                        }
                        
                        else
                        {
                            var temp_last_node = this.nodes[temp_length - 1];
                            this.nodes.push(
                            {
                                x: temp_last_node.x - 5 * Math.cos(temp_last_node.r),
                                y: temp_last_node.y + 5 * Math.sin(temp_last_node.r),
                                r: temp_last_node.r
                            });
                        }
                        
                        temp_length++;
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
                    if(this.nodes.length > 0)
                    {
                        var temp_first_node = this.nodes[0];
                        temp_first_node.x += 3 * Math.cos(temp_first_node.r);
                        temp_first_node.y -= 3 * Math.sin(temp_first_node.r);
                        
                        for(var n = 1; n < this.nodes.length; n++)
                        {
                            var temp_current_node = this.nodes[n];
                            var temp_next_node = this.nodes[n - 1];
                            temp_current_node.r = Math.PI - Math.atan2(temp_current_node.y - temp_next_node.y, temp_current_node.x - temp_next_node.x);
                            temp_current_node.x = temp_next_node.x - 5 * Math.cos(temp_current_node.r);
                            temp_current_node.y = temp_next_node.y + 5 * Math.sin(temp_current_node.r);
                        }
                        
                        this.camera.x = temp_first_node.x;
                        this.camera.y = temp_first_node.y;
                    }
                }
            }
            
            class Energy
            {
                constructor()
                {
                    this.type = Math.round(Math.random());
                    var temp_rotation = 2 * Math.PI * Math.random();
                    var temp_radius = world_radius * Math.sqrt(Math.random());
                    this.x = temp_radius * Math.cos(temp_rotation);
                    this.y = temp_radius * Math.sin(temp_rotation);
                    this.r = 2 * Math.PI * Math.random();
                }
            }
            
            class Camera
            {
                constructor()
                {
                    this.x = null;
                    this.y = null;
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
            
            document.onkeydown = keydown;
            document.onkeyup = keyup;
            
            var keys = [];
            var world_radius = 1000;
            var grid_size = 100;
            var worm_bot_count = 10;
            var energy_count = 100;
            var camera = new Camera();
            var worms = [new Worm("Human", Math.round(Math.random() * 50 + 20))];
            var energies = [];
            
            for(var n = 0; n < worm_bot_count; n++)
            {
                worms.push(new Worm("Bot", Math.round(Math.random() * 50 + 20)));
            }
            
            for(var n = 0; n < energy_count; n++)
            {
                energies.push(new Energy());
            }
            
            var canvas = document.getElementById("canvas");
            var ctx = canvas.getContext("2d");
            var fps = null;
            var previous_time = new Date();
            
            function render()
            {
                for(var n = 0; n < worms.length; n++)
                {
                    var worm = worms[n];
                    
                    if(worm.controllable)
                    {
                        if(worm.nodes.length > 0)
                        {
                            if(keys.includes(65) || keys.includes(37))
                            {
                                worm.nodes[n].r += Math.PI / 60;
                                
                                if(worm.nodes[n].r >= 2 * Math.PI)
                                {
                                    worm.nodes[n].r -= 2 * Math.PI;
                                }
                            }
                            
                            if(keys.includes(68) || keys.includes(39))
                            {
                                worm.nodes[n].r -= Math.PI / 60;
                                
                                if(worm.nodes[n].r < 0)
                                {
                                    worm.nodes[n].r += 2 * Math.PI;
                                }
                            }
                        }
                    }
                    
                    else
                    {
                        //AI code
                    }
                    
                    worm.move();
                }
                
                camera.x = worms[0].camera.x - canvas.width / 2;
                camera.y = worms[0].camera.y - canvas.height / 2;
                
                ctx.fillStyle = "#000000";
                ctx.shadowBlur = 0;
                ctx.globalAlpha = 1;
                ctx.fillRect(0, 0, canvas.width, canvas.height);
                ctx.strokeStyle = "#141414";
                ctx.lineWidth = 1;
                for(var n = 1; n < 2 * world_radius / grid_size; n++)
                {
                    ctx.beginPath();
                    ctx.moveTo(n * grid_size - world_radius - camera.x, 0 - world_radius - camera.y);
                    ctx.lineTo(n * grid_size - world_radius - camera.x, 2 * world_radius - world_radius - camera.y);
                    ctx.stroke();
                    ctx.closePath();
                    ctx.beginPath();
                    ctx.moveTo(0 - world_radius - camera.x, n * grid_size - world_radius - camera.y);
                    ctx.lineTo(2 * world_radius - world_radius - camera.x, n * grid_size - world_radius - camera.y);
                    ctx.stroke();
                    ctx.closePath();
                }
                ctx.beginPath();
                ctx.arc(0 - camera.x, 0 - camera.y, world_radius, Math.PI, 0);
                ctx.lineTo(world_radius - camera.x, -world_radius - camera.y);
                ctx.lineTo(-world_radius - camera.x, -world_radius - camera.y);
                ctx.closePath();
                ctx.fill();
                ctx.beginPath();
                ctx.arc(0 - camera.x, 0 - camera.y, world_radius, 0, -Math.PI);
                ctx.lineTo(-world_radius - camera.x, world_radius - camera.y);
                ctx.lineTo(world_radius - camera.x, world_radius - camera.y);
                ctx.closePath();
                ctx.fill();
                ctx.strokeStyle = "#141414";
                ctx.lineWidth = 3;
                ctx.beginPath();
                ctx.arc(0 - camera.x, 0 - camera.y, world_radius, 0, 2 * Math.PI);
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
                    }
                }
                ctx.shadowBlur = 0;
                ctx.globalAlpha = 0.25;
                for(var n = 1; n < canvas.height / 4; n++)
                {
                    ctx.fillRect(0, n * 4 - 1, canvas.width, 2);
                }
                
                var current_time = new Date();
                fps = 1000 / (current_time - previous_time);
                previous_time = current_time;
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