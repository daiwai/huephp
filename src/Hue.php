<?php

namespace Hue;

use Pest;

class Hue
{
    private $bridge;
    private $key;
    /** @var Light[] */
    private $lights;
    /** @var Pest */
    private $pest;

    public function __construct( $bridge, $key )
    {
        $this->bridge = $bridge;
        $this->key    = $key;
        $this->pest   = new Pest( "http://" . $this->bridge . "/api/" . $this->key . "/" );

        $this->update();
    }


    public function pest()
    {
        return $this->pest;
    }


    private function makeLightArray( $lightid = false )
    {
        $targets = array();

        if ($lightid === false) {
            $targets = $this->lightIds();
        } else {
            if ( ! is_array( $lightid )) {
                $targets[] = $lightid;
            } else {
                $targets = $lightid;
            }
        }

        return $targets;
    }


    // Registers with a Hue hub
    public function register()
    {
        $pest   = new Pest( "http://" . $this->bridge . "/api" );
        $data   = json_encode( array( 'devicetype' => 'phpHue' ) );
        $result = $pest->post( '', $data );

        return $result;
    }


    public function update( $lightid = false )
    {
        $lights = $this->makeLightArray( $lightid );
        foreach ($lights as $id) {
            $data = $this->pest()->get( "lights/$id" );

            $this->lights[$id] = new Light( $this, $id, $data );
        }
    }


    public function lights()
    {
        return $this->lights;
    }


    // Returns an array of the light numbers in the system
    public function lightIds()
    {
        $result  = json_decode( $this->pest()->get( 'lights' ), true );
        $targets = array_keys( $result );

        return $targets;
    }


    // Gets the full state of the bridge
    public function state()
    {
        return $this->pest()->get( "" );
    }


    public function config()
    {
        return json_decode($this->pest()->get('config'));
    }

    // Gets an array of currently configured schedules
    public function schedules()
    {
        $result = json_decode( $this->pest()->get( "schedules" ), true );

        return $result;
    }

    /**
     *
     * @return array A list of groups
     *
     */
    public function groups() : array
    {
        $response = json_decode($this->pest()->get('groups'));
        $groups = [];
        foreach($response as $id => $spec)
        {
            $groups[] = Group::createFromJson($id, $spec);
        }

        return $groups;
    }

    public function rooms() : array
    {
        $groups = $this->groups();
        $rooms = [];
        foreach($groups as $group)
        {
            if($group->getType() === Group::TYPE_ROOM)
            {
                $rooms[] = $group;
            }
        }

        return $rooms;
    }

    public function switchRoomOn($room_id)
    {
        $data = [
            'on' => true,
        ];
        $this->pest->put('/groups/' . $room_id . '/action', json_encode($data));
    }

    public function switchRoomOff($room_id)
    {
        $data = [
            'on' => false,
        ];
        $this->pest->put('/groups/' . $room_id . '/action', json_encode($data));
    }

    /**
     * Gets a list of all configured scenes
     *
     * @return Scene[] A list of scenes
     *
     */
    public function scenes() : array
    {
        $response = json_decode($this->pest()->get('scenes'));
        $scenes = [];
        foreach($response as $id => $spec)
        {
            $scenes[] = Scene::createFromJson($id, $spec);
        }

        return $scenes;
    }

    public function scene(string $id) : array
    {
        $raw = json_decode($this->pest()->get('scene/' . $id));

        return $raw;
    }

    public function saveScene(string $name, array $lights)
    {
        $data = [
            'name' => $name,
            'recycle' => false,
            'lights' => $lights,
            //'type' => Scene::TYPE_LIGHTS,
        ];

        $raw = $this->pest()->post('scenes', json_encode($data));

        print_r(json_decode($raw));
    }

    public function recallScene(string $id)
    {
        $data = [
            'scene' => $id,
        ];

        $response = $this->pest()->put('groups/0/action', json_encode($data));
    }

    // Gin up a random color
    public function randomColor()
    {
        $return = array();

        $return['hue'] = rand( 0, 65535 );
        $return['sat'] = rand( 0, 254 );
        $return['bri'] = rand( 0, 254 );

        return $return;
    }


    // Gin up a random temp-based white setting
    public function randomWhite()
    {
        $return        = array();
        $return['ct']  = rand( 150, 500 );
        $return['bri'] = rand( 0, 255 );

        return $return;
    }


    // Build a few color commands based on color names
    public function predefinedColors( $colorname )
    {
        $command = array();
        switch ($colorname) {
            case "green":
                $command['hue'] = 182 * 140;
                $command['sat'] = 254;
                $command['bri'] = 254;
                break;

            case "red":
                $command['hue'] = 0;
                $command['sat'] = 254;
                $command['bri'] = 254;
                break;

            case "blue":
                $command['hue'] = 182 * 250;
                $command['sat'] = 254;
                $command['bri'] = 254;
                break;

            case "coolwhite":
                $command['ct']  = 150;
                $command['bri'] = 254;
                break;

            case "warmwhite":
                $command['ct']  = 500;
                $command['bri'] = 254;
                break;

            case "orange":
                $command['hue'] = 182 * 25;
                $command['sat'] = 254;
                $command['bri'] = 254;
                break;

            case "yellow":
                $command['hue'] = 182 * 85;
                $command['sat'] = 254;
                $command['bri'] = 254;
                break;

            case "pink":
                $command['hue'] = 182 * 300;
                $command['sat'] = 254;
                $command['bri'] = 254;
                break;

            case "purple":
                $command['hue'] = 182 * 270;
                $command['sat'] = 254;
                $command['bri'] = 254;
                break;
        }

        return $command;
    }
}
