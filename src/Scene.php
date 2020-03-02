<?php

declare(strict_types=1);

namespace Hue;

use stdClass;

class Scene
{
    const TYPE_LIGHTS = 'GroupScene';
    const TYPE_GROUP = 'LightScene';

    private string $id;
    private string $name;
    private string $type;
    private ?string $group;
    private array $lights;
    private string $owner;
    private bool $recycle;
    private bool $locked;
    private string $appdata;
    private string $picture;
    private string $lastupdated;
    private int $version;

    private function __construct($id, $name, $lights, $owner, bool $recycle, bool $locked, $picture, $lastupdated, $version, $type = self::TYPE_LIGHTS, $group = null)
    {
        $this->id = $id;
        $this->name = $name;
        $this->lights = $lights;
        $this->type = $type;
        $this->group = $group;
        $this->owner = $owner;
        $this->recycle = $recycle;
        $this->locked = $locked;
        $this->picture = $picture;
        $this->lastupdated = $lastupdated;
        $this->version = $version;
    }

    public static function createFromJson(string $id, stdClass $spec) : Scene
    {
        return new static(
            $id,
            $spec->name,
            $spec->lights,
            $spec->owner,
            $spec->recycle,
            $spec->locked,
            $spec->picture,
            $spec->lastupdated,
            $spec->version
        );
    }

    /**
     * @return string
     */
    public function getId() : string
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getName() : string
    {
        return $this->name;
    }

    public function belongsToRoom(Group $room)
    {
        return count(array_diff($room->getLights(), $this->lights)) === 0;
    }
}
