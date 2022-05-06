# wormJS :snake:
A slither.io-like game made in javascript with the canvas API.

## Controls
A - to turn the player snake left.

D - to turn the player snake right.

M - to toggle minimap overlay.

G - to toggle graphics performance mode.

(minus) - to slow down the global time scale of the game.

(plus) - to speed up the global time scale of the game.

(left click) - to cycle the camera to the previous worm.

(right click) - to cycle the camera to the next worm.

## Designed features

When a worm eats a food particle, the tail grows slowly in length.

The worms blink randomly from time to time.

When worms are closer to each other, they become happier.

When worms pass over the end of the map, they die and change appearance.

## Algorithm

| Node Number | Node Order | Node Significance |
| ----------- | ---------- | ----------------- |
| 0           | First      | Head              |
| 1           | Second     | Body              |
| 2           | Third      | Body              |
| 3           | Fourth     | Body              |
| 4           | Fifth      | Body              |

The direction of node ```0``` is either controlled by the player input or simple AI algorithm.

Node ```0``` moves by a certain speed into the direction that is going.

For each node except the head node in ascending order, the movement is as follows:

Node ```n + 1``` moves in the relative direction of node ```n``` while keeping a constant distance.

## Challenges

Preventing the nodes from getting too close together and forming a knot after turning for a while.

Maintaining a playable framerate solved by rendering objects only that are close to the camera.
