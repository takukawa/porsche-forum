<?php


namespace com\codologic\codoforum\plugins\FreiChat\dto;


class FreiChatConversationData
{
    public $conversations = [];

    public function __construct($conversations)
    {
        $this->conversations = $conversations;
    }
}