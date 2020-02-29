<?php


namespace com\codologic\codoforum\plugins\FreiChat\dto;


class FreiChatConversation
{
    public $photo;
    public $status = "OFFLINE"; //TODO: Grab status
    public $title;
    public $userId;
    public $conversationHash;

    public function __construct($user, $i)
    {
        $this->title = $user->name;
        $this->userId = $user->id;
        $this->photo = $user->photoUrl;
        $this->conversationHash = uniqid() . "_$i";
    }
}