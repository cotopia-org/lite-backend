<?php

namespace App\Utilities;

use App\Enums\Permission;

class Constants {
    public const ROLE_PERMISSIONS = [
        'member'      => [
            Permission::WS_GET,
            Permission::ROOM_GET,
            Permission::WS_ADD_JOB,
            Permission::JOB_GET,
            Permission::JOB_INVITE_MEMBER,
        ],
        'admin'       => [
            Permission::WS_GET,
            Permission::WS_ADD_MEMBER,
            Permission::WS_REMOVE_MEMBER,
            Permission::WS_ADD_ROOMS,
            Permission::ROOMS_CHANGE_POSITION,
            Permission::ROOM_GET,
            Permission::ROOM_UPDATE,
            Permission::ROOM_DELETE,
            Permission::ROOM_UPDATE_MESSAGES,
        ],
        'super-admin' => [
            Permission::ALL,
        ],
    ];
    const DIRECT = 'direct';
    const GROUP = 'group';
    const CHANNEL = 'channel';
    const JOINED = 'participant_joined';
    const LEFT = 'participant_left';

    const BASE_DATE_FORMAT = 'H:i:s';
    const SCHEDULE_DATE_FORMAT = 'H:i:s';
    const ONLINE = 'online';
    const OFFLINE = 'offline';
    const GHOST = 'ghost';

    const API_SUCCESS_MSG = 'success';
    const API_FAILED_MSG = 'failed';

    const IN_PROGRESS = 'in_progress';
    const PAUSED = 'paused';
    const COMPLETED = 'joinedRoom';
    const userUpdated = 'userUpdated';
    const workspaceUpdated = 'workspaceUpdated';
    const roomCreated = 'roomCreated';
    const roomUpdated = 'roomUpdated';
    const roomMessages = 'roomMessages';
    const directMessages = 'directMessages';
    const messageUpdated = 'messageUpdated';
    const messageDeleted = 'messageDeleted';
    const messagePinned = 'messagePinned';
    const messageUnPinned = 'messageUnPinned';
    const messageSeen = 'messageSeen';
    const workspaceRoomUpdated = 'workspaceRoomUpdated';
    const talkCreated = 'talkCreated';
    const  talkResponded = 'talkResponded';
    const  talkExpired = 'talkExpired';
    const  ACCEPTED = 'accepted';
    const  REJECTED = 'rejected';
    const  LATER = 'later';
    const  NO_RESPONSE = 'no_response';

    const userLeftFromRoom = 'userLeftFromRoom';
    const userJoinedToRoom = 'userJoinedToRoom';
    const roomBackgroundChanged = 'roomBackgroundChanged';


    const livekitDisconnected = 'livekitDisconnected';


}
