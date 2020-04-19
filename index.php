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
            var camera =
            {
                x: null,
                y: null,
            };
            var worm =
            {
                nodes: [],
            };
            
            var a = 2 * Math.PI * Math.random();
            var r = world_radius * Math.random();
            worm.nodes.push({x: r * Math.cos(a), y: r * Math.sin(a), a: 2 * Math.PI * Math.random()});
            
            for(var n = 0; n < worm_length - 1; n++)
            {
                worm.nodes.push({x: worm.nodes[n].x, y: worm.nodes[n].y, a: worm.nodes[n].a});
            }
            
            var canvas = document.getElementById("canvas");
            var ctx = canvas.getContext("2d");
            
            function render()
            {
                if(keys.includes(65) || keys.includes(37))
                {
                    worm.nodes[0].a -= Math.PI / 60;
                    
                    if(worm.nodes[0].a < 0)
                    {
                        worm.nodes[0].a += 2 * Math.PI;
                    }
                }
                
                if(keys.includes(68) || keys.includes(39))
                {
                    worm.nodes[0].a += Math.PI / 60;
                    
                    if(worm.nodes[0].a >= 2 * Math.PI)
                    {
                        worm.nodes[0].a -= 2 * Math.PI;
                    }
                }
                
                //movement goes here
                
                worm.nodes[0].x += 3 * Math.cos(worm.nodes[0].a);
                worm.nodes[0].y += 3 * Math.sin(worm.nodes[0].a);
                for(var n = 1; n < worm.nodes.length; n++)
                {
                    worm.nodes[n].a = Math.atan2(worm.nodes[n].y - worm.nodes[n - 1].y, worm.nodes[n].x - worm.nodes[n - 1].x);
                    worm.nodes[n].x = worm.nodes[n - 1].x + 5 * Math.cos(worm.nodes[n].a);
                    worm.nodes[n].y = worm.nodes[n - 1].y + 5 * Math.sin(worm.nodes[n].a);
                }
                camera.x = worm.nodes[0].x - canvas.width / 2;
                camera.y = worm.nodes[0].y - canvas.height / 2;
                
                ctx.clearRect(0, 0, canvas.width, canvas.height);
                ctx.strokeStyle = "#000000";
                ctx.shadowBlur = 0;
                ctx.fillRect(0, 0, canvas.width, canvas.height);
                ctx.strokeStyle = "#141414";
                ctx.lineWidth = 1;
                for(var n = 1; n < 2 * world_radius / grid_size; n++)
                {
                    ctx.beginPath();
                    ctx.moveTo(n * grid_size - world_radius - camera.x, 0 - world_radius - camera.y);
                    ctx.lineTo(n * grid_size - world_radius - camera.x, 2 * world_radius - world_radius - camera.y);
                    ctx.closePath();
                    ctx.stroke();
                }
                for(var n = 1; n < 2 * world_radius / grid_size; n++)
                {
                    ctx.beginPath();
                    ctx.moveTo(0 - world_radius - camera.x, n * grid_size - world_radius - camera.y);
                    ctx.lineTo(2 * world_radius - world_radius - camera.x, n * grid_size - world_radius - camera.y);
                    ctx.closePath();
                    ctx.stroke();
                }
                ctx.strokeStyle = "#00ff00";
                ctx.lineWidth = 3;
                ctx.shadowBlur = 20;
                ctx.shadowColor = ctx.strokeStyle;
                ctx.beginPath();
                ctx.arc(worm.nodes[0].x - camera.x, worm.nodes[0].y - camera.y, 25, worm.nodes[0].a - Math.PI / 2, worm.nodes[0].a + Math.PI / 2);
                for(var n = 1; n < worm.nodes.length - 1; n++)
                {
                    ctx.lineTo(worm.nodes[n].x + 25 * Math.cos(worm.nodes[n].a - Math.PI / 2) - camera.x, worm.nodes[n].y + 25 * Math.sin(worm.nodes[n].a - Math.PI / 2) - camera.y);
                }
                ctx.arc(worm.nodes[worm.nodes.length - 1].x - camera.x, worm.nodes[worm.nodes.length - 1].y - camera.y, 25, worm.nodes[worm.nodes.length - 1].a - Math.PI / 2, worm.nodes[worm.nodes.length - 1].a + Math.PI / 2);
                for(var n = worm.nodes.length - 2; n > 0; n--)
                {
                    ctx.lineTo(worm.nodes[n].x + 25 * Math.cos(worm.nodes[n].a + Math.PI / 2) - camera.x, worm.nodes[n].y + 25 * Math.sin(worm.nodes[n].a + Math.PI / 2) - camera.y);
                }
                ctx.closePath();
                ctx.stroke();
                window.requestAnimationFrame(render);
            }
            
            window.requestAnimationFrame(render);
            
            resize();
            
        </script>
    </body>
</html>