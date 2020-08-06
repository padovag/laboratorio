<?php

namespace App\laboratorio\classroom;

use App\laboratorio\RemoteRepositoryResolver;
use App\laboratorio\util\http\ErrorResponse;

class Classroom {
    public $name;
    public $description;
    public $avatar;
    public $members;
    public $external_id;

    public function __construct(string $name, string $description, ?array $members, string $external_id, ?string $avatar = null) {
        $this->name = $name;
        $this->description = $description;
        $this->avatar = $avatar;
        $this->members = $members;
        $this->external_id = $external_id;
    }

    public static function create(string $code, string $name, string $description, array $members) {
        $group_id = self::createGroup($code, $name, $description);

        if(!empty($members)){
            $members_added = self::addMembersToGroup($members, $group_id, $code);
        }

        return new self($name, $description, $members_added, $group_id);
    }

    public static function addMembers(string $code, string $external_id, array $members): array {
        $members_added = self::addMembersToGroup($members, $external_id, $code);

        if(!$members_added) {
            throw new ClassroomException("Could not find any members to add");
        }

        return $members_added;
    }

    public static function list(string $code): array {
        $groups = RemoteRepositoryResolver::resolve()->getGroups($code);
        $classrooms = array_map(function($group) use ($code) {
            $details = self::getGroupDetails($group->id, $code);

            return new self($group->name, $group->description, null, $group->id, $details->avatar_url);
        }, $groups->data);

        return $classrooms;
    }

    public static function get(string $code, string $external_id) {
        $members = self::getGroupMembers($external_id, $code);
        $group_details = self::getGroupDetails($external_id, $code);

        return new self($group_details->name, $group_details->description, $members, $group_details->id, $group_details->avatar_url);
    }

    private static function createGroup(string $code, string $name, string $description): string {
        $response = RemoteRepositoryResolver::resolve()->createGroup($code, $name, $description);
        if($response instanceof ErrorResponse) {
            throw new ClassroomException($response->data['error_message']);
        }

        return $response->data->id;
    }

    private static function addMembersToGroup(array $members, string $group_id, string $code): ?array {
        $users = self::getUsers($members);
        if(empty($users)) {
            return null;
        }

        RemoteRepositoryResolver::resolve()->addMembersToGroup($users, $group_id, $code);

        return array_keys($users);
    }

    private static function getUsers(array $members): array {
        $users = [];
        foreach($members as $member) {
            $user = RemoteRepositoryResolver::resolve()->getUser($member);
            if(empty($user->data)) {
                continue;
            }
            $users[$member] = current($user->data)->id;
        }

        return $users;
    }

    private static function getGroupMembers($group_id, string $code): array {
        $group_members = RemoteRepositoryResolver::resolve()->getGroupMembers($code, $group_id);
        $members = array_map(function($member) {
            return $member->name;
        }, $group_members->data);

        return $members;
    }

    private static function getGroupDetails($group_id, string $code) {
        $details = RemoteRepositoryResolver::resolve()->getGroupDetails($code, $group_id);

        return $details->data;
    }

}
