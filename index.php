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
            var world_radius = 10000;
            var grid_size = 100;
            var worm_length = 100;
            var energy_count = 100;
            var camera =
            {
                x: null,
                y: null,
            };
            var worm =
            {
                nodes: [],
            };
            var angle = 2 * Math.PI * Math.random();
            var radius = world_radius * Math.sqrt(Math.random());
            worm.nodes.push({x: radius * Math.cos(angle), y: radius * Math.sin(angle), r: 2 * Math.PI * Math.random()});
            for(var n = 0; n < worm_length - 1; n++)
            {
                addNode();
            }
            var energy = [];
            for(var n = 0; n < energy_count; n++)
            {
                var angle = 2 * Math.PI * Math.random();
                var radius = world_radius * Math.sqrt(Math.random());
                energy.push({type: Math.round(Math.random() * 1), x: radius * Math.cos(angle), y: radius * Math.sin(angle), r: 2 * Math.PI * Math.random()});
            }
            
            function addNode()
            {
                worm.nodes.push({x: worm.nodes[worm.nodes.length - 1].x - 5 * Math.cos(worm.nodes[worm.nodes.length - 1].r), y: worm.nodes[worm.nodes.length - 1].y + 5 * Math.sin(worm.nodes[worm.nodes.length - 1].r), r: worm.nodes[worm.nodes.length - 1].r});
            }
            
            var canvas = document.getElementById("canvas");
            var ctx = canvas.getContext("2d");
            var fps = null;
            var previous_time = new Date();
            
            function render()
            {
                if(keys.includes(65) || keys.includes(37))
                {
                    worm.nodes[0].r += Math.PI / 60;
                    
                    if(worm.nodes[0].r >= 2 * Math.PI)
                    {
                        worm.nodes[0].r -= 2 * Math.PI;
                    }
                }
                
                if(keys.includes(68) || keys.includes(39))
                {
                    worm.nodes[0].r -= Math.PI / 60;
                    
                    if(worm.nodes[0].r < 0)
                    {
                        worm.nodes[0].r += 2 * Math.PI;
                    }
                }
                
                //movement goes here
                
                worm.nodes[0].x += 3 * Math.cos(worm.nodes[0].r);
                worm.nodes[0].y -= 3 * Math.sin(worm.nodes[0].r);
                for(var n = 1; n < worm.nodes.length; n++)
                {
                    worm.nodes[n].r = Math.PI - Math.atan2(worm.nodes[n].y - worm.nodes[n - 1].y, worm.nodes[n].x - worm.nodes[n - 1].x);
                    worm.nodes[n].x = worm.nodes[n - 1].x - 5 * Math.cos(worm.nodes[n].r);
                    worm.nodes[n].y = worm.nodes[n - 1].y + 5 * Math.sin(worm.nodes[n].r);
                }
                for(var n = 0; n < energy.length; n++)
                {
                    if(d(worm.nodes[0], energy[n]) < 50)
                    {
                        energy.splice(n, 1);
                        addNode();
                        n--;
                    }
                }
                camera.x = worm.nodes[0].x - canvas.width / 2;
                camera.y = worm.nodes[0].y - canvas.height / 2;
                
                ctx.clearRect(0, 0, canvas.width, canvas.height);
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
                for(var n = 0; n < energy.length; n++)
                {
                    ctx.beginPath();
                    
                    if(energy[n].type === 0)
                    {
                        ctx.strokeStyle = "#ff0000";
                        ctx.shadowColor = ctx.strokeStyle;
                        ctx.moveTo(energy[n].x + 25 * Math.cos(energy[n].r) - camera.x, energy[n].y + 25 * Math.sin(energy[n].r) - camera.y);
                        ctx.lineTo(energy[n].x + 25 * Math.cos(energy[n].r + 2 * Math.PI / 3) - camera.x, energy[n].y + 25 * Math.sin(energy[n].r + 2 * Math.PI / 3) - camera.y);
                        ctx.lineTo(energy[n].x + 25 * Math.cos(energy[n].r + 4 * Math.PI / 3) - camera.x, energy[n].y + 25 * Math.sin(energy[n].r + 4 * Math.PI / 3) - camera.y);
                    }
                    
                    if(energy[n].type === 1)
                    {
                        ctx.strokeStyle = "#00e5ff";
                        ctx.shadowColor = ctx.strokeStyle;
                        ctx.moveTo(energy[n].x + 25 * Math.cos(energy[n].r) - camera.x, energy[n].y + 25 * Math.sin(energy[n].r) - camera.y);
                        ctx.lineTo(energy[n].x + 25 * Math.cos(energy[n].r + Math.PI / 2) - camera.x, energy[n].y + 25 * Math.sin(energy[n].r + Math.PI / 2) - camera.y);
                        ctx.lineTo(energy[n].x + 25 * Math.cos(energy[n].r + Math.PI) - camera.x, energy[n].y + 25 * Math.sin(energy[n].r + Math.PI) - camera.y);
                        ctx.lineTo(energy[n].x + 25 * Math.cos(energy[n].r + 3 * Math.PI / 2) - camera.x, energy[n].y + 25 * Math.sin(energy[n].r + 3 * Math.PI / 2) - camera.y);
                    }
                    
                    ctx.closePath();
                    ctx.stroke();
                }
                
                ctx.strokeStyle = "#00ff00";
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
                
                ctx.shadowBlur = 0;
                ctx.globalAlpha = 0.25;
                for(var n = 1; n < 216; n++)
                {
                    ctx.fillRect(0, n * canvas.height / 216 - 1, canvas.width, 2);
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