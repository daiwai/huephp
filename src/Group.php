<?php


namespace Hue;

use stdClass;

class Group
{
    const TYPE_ROOM = 'Room';

    private $id;
    private $name;
    private array $lights = [];
    private $type;
    private $state;
    private $action;


    public function __construct($id, $name, $lights, $type, $state, $action)
    {
        $this->id = $id;
        $this->name = $name;
        $this->lights = $lights;
        $this->type = $type;
        $this->state = $state;
        $this->action = $action;
    }

    public static function createFromJson($id, stdClass $json)
    {
        return new static(
            $id,
            $json->name,
            $json->lights,
            $json->type,
            $json->state,
            $json->action
        );
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return mixed
     */
    public function getType()
    {
        return $this->type;
    }

    public function getLights() : array
    {
        return $this->lights;
    }

    public function allLightsOn() : bool
    {
        return $this->state->all_on;
    }

    public function anyLightsOn() : bool
    {
        return $this->state->any_on;
    }
}
