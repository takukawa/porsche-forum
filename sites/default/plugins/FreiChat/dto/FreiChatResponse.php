<?php

namespace com\codologic\codoforum\plugins\FreiChat\dto;

/**
 * FreiChat plugin expects JSON response of this type
 * Class FreiChatResponse
 * @package com\codologic\codoforum\plugins\dto
 */
class FreiChatResponse
{
    public $data;
    public $errorMsg;
    public $success = true;

    public static function withUsers($users)
    {
        $resp = new FreiChatResponse();
        $conversations = [];
        $i = 0;
        foreach ($users as $user) {
            array_push($conversations, new FreiChatConversation($user, ++$i));
        }

        $resp->data = new FreiChatConversationData($conversations);
        return $resp;
    }

    public static function withError($error)
    {
        $resp = new FreiChatResponse();
        $resp->errorMsg = $error;
        $resp->success = false;
        return $resp;
    }
}

