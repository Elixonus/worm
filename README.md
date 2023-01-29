# worm 🐍
A slither.io-like game made in javascript with the canvas API.

![Wallpaper](wallpaper.png)

## Controls

```A``` - Turn the player snake left.

```D``` - Turn the player snake right.

```M``` - Toggle minimap overlay.

```G``` - Toggle graphics performance mode.

```(COMMA_SIGN)``` - Slow down the global time scale of the game.

```(DOT_SIGN)``` - Speed up the global time scale of the game.

```(LEFT_CLICK)``` - Cycle the camera to the previous worm.

```(RIGHT_CLICK)``` - Cycle the camera to the next worm.

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

* Derivative (second) of rotation of ```Node Number``` ```0``` is either controlled by player input or an AI algorithm.
* ```Node Number``` ```0``` moves with a certain speed in the direction of the worm.
* For each node except the head node, in ascending order, the movement is as follows:
* ```Node Number``` ```n + 1``` moves in the relative direction of Node Number ```n``` while keeping a constant distance.

## Challenges

Preventing the nodes from getting too close together and forming a knot after turning for a while.

Maintaining a playable framerate solved by rendering objects only that are close to the camera.
