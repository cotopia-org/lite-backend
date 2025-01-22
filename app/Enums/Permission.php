<?php

namespace App\Enums;

enum Permission: string {
    case ALL = '*';

    case CALENDAR_VIEW = 'calendar_view';
    case CALENDAR_CREATE = 'calendar_create';
    case CALENDAR_UPDATE = 'calendar_update';
    case CALENDAR_DELETE = 'calendar_delete';

    case SCHEDULE_VIEW = 'schedule_view';
    case SCHEDULE_CREATE = 'schedule_create';
    case SCHEDULE_UPDATE = 'schedule_update';
    case SCHEDULE_DELETE = 'schedule_delete';

    case WS_GET = 'get-ws';
    case WS_ADD_MEMBER = 'add-member-to-ws';
    case WS_REMOVE_MEMBER = 'remove-member-from-ws';
    case WS_UPDATE = 'update-ws';
    case WS_DELETE = 'delete-ws';
    case WS_CREATE_CHAT = 'create-ws-chat';
    case WS_DELETE_CHAT = 'delete-ws-chat';
    case WS_ADD_ROOMS = 'add-rooms-to-ws';
    case WS_ADD_JOB = 'add-job-to-ws';
    case WS_REMOVE_TAG = 'remove-tag-from-ws';
    case WS_ADD_TAG = 'add-tag-to-ws';


    case DELETE_CONTRACT = 'delete-contract';


    case HARDMUTE = 'hardmute';


    case ROOMS_CHANGE_POSITION = 'change-rooms-position';
    case ROOM_GET = 'get-room';
    case ROOM_KICK_USER = 'kick-user-from-room';
    case ROOM_UPDATE = 'update-room';
    case ROOM_DELETE = 'delete-room';
    case ROOM_UPDATE_USER = 'update-user-in-room';         //mute,unmute,stop screen,change position of user.
    case ROOM_UPDATE_MESSAGES = 'update-messages-in-room'; //delete message,pin message,edit message

    case JOB_GET = 'get-job';
    case JOB_INVITE_MEMBER = 'invite-member-to-job';
    case JOB_REMOVE_MEMBER = 'remove-member-from-job';
    case JOB_UPDATE = 'update-job';
    case JOB_DELETE = 'delete-job';
}
