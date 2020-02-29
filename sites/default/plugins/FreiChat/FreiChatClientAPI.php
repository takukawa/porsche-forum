<?php


namespace com\codologic\codoforum\plugins\FreiChat;

use CODOF\User\CurrentUser\CurrentUser;
use com\codologic\codoforum\plugins\FreiChat\dto\FreiChatConversation;
use com\codologic\codoforum\plugins\FreiChat\dto\FreiChatResponse;

// require_once "dto/FreiChatResponse.php";

/**
 * Sometimes its better to let the client where FreiChat is loaded to directly serve the data instead of requesting
 * the SAAS server. One of the use cases is when dealing with sensitive user data. This ensures data is protected
 * and always resides within the customer database as nothing is transferred to the SAAS servers
 *
 * Including this class with proper route and definition will let FreiChat plugin use these APIs.
 *
 * Class FreiChatClientAPI
 * @package com\codologic\codoforum\plugins
 */
class FreiChatClientAPI
{
    /**
     * Search for users in the database based on a search term.
     * @param $term
     * @return FreiChatResponse
     */
    public function searchUsers($term)
    {
        $users = \DB::table(PREFIX . "codo_users")
            ->join(PREFIX . 'codo_user_roles', 'codo_users.id', '=', 'codo_user_roles.uid')
            ->where(function ($query) use ($term) {
                $query->where('username', 'like', "%$term%")
                    ->orWhere('name', 'like', "%$term%");
            })
            ->where('user_status', '=', 1)
            ->where('rid', '<>', ROLE_BANNED)
            ->where('is_primary', '=', 1)
            ->where('id', '<>', CurrentUser::id())
            ->get();

        $freichatUsers = [];
        foreach ($users as $user) {
            $photo = \CODOF\Util::get_avatar_path($user['avatar'], $user['id']);
            array_push($freichatUsers, new FreiChatUser($user['id'], $user['name'], $photo));
        }
        return FreiChatResponse::withUsers($freichatUsers);
    }

    /**
     * Gets user by given codoforum user id. Used when 'Send message' button is clicked from user profile
     * @param $uid
     * @return FreiChatResponse
     */
    public function getUser($uid)
    {
        $user = \CODOF\User\User::get($uid);

        if (!$user) return FreiChatResponse::withError("Error: Conversation not found for user $uid");

        $users = [];
        array_push($users, new FreiChatUser($user->id, $user->name, $user->getAvatar()));

        return FreiChatResponse::withUsers($users);
    }
}

class FreiChatUser
{

    public $id;
    public $name;
    public $photoUrl;

    // Data types removed becomes production gives a string userid and local gives an int userid
    // TODO: Find cause between difference in data types
    public function __construct($userid, $username, $photoUrl)
    {
        $this->id = $userid;
        $this->name = $username;
        $this->photoUrl = $photoUrl;
    }
}


//--------------------------------- Routes for calling above API methods ---------------------------------------------//

// Codoforum uses dispatch_get for routes
dispatch_post("fc.client/users", function () {

    $term = filter_input(INPUT_POST, "query", FILTER_SANITIZE_MAGIC_QUOTES);
    $api = new FreiChatClientAPI();
    echo json_encode($api->searchUsers($term));
});
dispatch_get("fc.client/user/:uid", function ($uid) {

    $id = (int) $uid;
    $api = new FreiChatClientAPI();
    echo json_encode($api->getUser($id));
});

// This will tell FreiChat plugin that the routes are usable
$js = <<<EOD
            window.FreiChatClient = {
                baseUrl: codo_defs.url,
                searchUsersApi: codo_defs.url + "fc.client/users/", // Must be absolute url
                getUserApi:     codo_defs.url + "fc.client/user/:uid" // Must be absolute url [:uid is hardcoded, do not change]         
            }
EOD;

add_js("freichat_plugin", array(
    'data' => $js,
    'type' => 'inline_module'
));
