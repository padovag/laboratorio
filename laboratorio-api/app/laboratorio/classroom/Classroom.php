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
            $members = self::getGroupMembers($group->id, $provider_access_token);
            $details = self::getGroupDetails($group->id, $provider_access_token);

            return new self($group->name, $group->description, $members, $group->id, $details->avatar_url);
        }, $groups->data);

        return $classrooms;
    }

    public static function get(string $provider_access_token, string $external_id) {
        $members = self::getGroupMembers($external_id, $provider_access_token);
        $group_details = self::getGroupDetails($external_id, $provider_access_token);

        return new self($group_details->name, $group_details->description, $members, $group_details->id, $group_details->avatar_url);
    }

    private static function createGroup(string $provider_access_token, string $name, string $description): string {
        $response = RemoteRepositoryResolver::resolve()->createGroup($provider_access_token, $name, $description);
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

    private static function getGroupMembers($group_id, string $provider_access_token): array {
        $group_members = RemoteRepositoryResolver::resolve()->getGroupMembers($provider_access_token, $group_id);
        $members = array_map(function($member) {
            return $member->name;
        }, $group_members->data);

        return $members;
    }

    private static function getGroupDetails($group_id, string $provider_access_token) {
        $details = RemoteRepositoryResolver::resolve()->getGroupDetails($provider_access_token, $group_id);

        return $details->data;
    }

}
