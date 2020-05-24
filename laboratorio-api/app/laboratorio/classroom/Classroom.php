<?php

namespace App\laboratorio\classroom;

use App\laboratorio\RemoteRepositoryResolver;
use App\laboratorio\util\http\ErrorResponse;

class Classroom {
    public $name;
    public $description;
    public $members;
    public $external_id;

    public function __construct(string $name, string $description, ?array $members, string $external_id) {
        $this->name = $name;
        $this->description = $description;
        $this->members = $members;
        $this->external_id = $external_id;
    }

    public static function create(string $provider_access_token, string $name, string $description, array $members) {
        $group_id = self::createGroup($provider_access_token, $name, $description);

        if(!empty($members)){
            $members_added = self::addMembersToGroup($members, $group_id, $provider_access_token);
        }

        return new self($name, $description, $members_added, $group_id);
    }

    public static function addMembers(string $provider_access_token, string $external_id, array $members): array {
        $members_added = self::addMembersToGroup($members, $external_id, $provider_access_token);

        if(!$members_added) {
            throw new ClassroomException("Could not find any members to add");
        }

        return $members_added;
    }

    public static function list(string $provider_access_token): array {
        $groups = RemoteRepositoryResolver::resolve()->getGroups($provider_access_token);
        $classrooms = array_map(function($group) use ($provider_access_token) {
            $members = self::getGroupMembers($group, $provider_access_token);
            return new self($group->name, $group->description, $members, $group->id);
        }, $groups->data);

        return $classrooms;
    }

    private static function createGroup(string $provider_access_token, string $name, string $description): string {
        $response = RemoteRepositoryResolver::resolve()->createGroup($name, $description, $provider_access_token);
        if($response instanceof ErrorResponse) {
            throw new ClassroomException($response->data['error_message']);
        }

        return $response->data->id;
    }

    private static function addMembersToGroup(array $members, string $group_id, string $provider_access_token): ?array {
        $users = self::getUsers($members);
        if(empty($users)) {
            return null;
        }

        RemoteRepositoryResolver::resolve()->addMembersToGroup($users, $group_id, $provider_access_token);

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

    private static function getGroupMembers($group, string $provider_access_token): array {
        $group_members = RemoteRepositoryResolver::resolve()->getGroupMembers($provider_access_token, $group->id);
        $members = array_map(function($member) {
            return $member->name;
        }, $group_members->data);

        return $members;
    }

}
