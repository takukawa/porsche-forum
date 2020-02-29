<?php
namespace com\codologic\codoforum\plugins;

/*
 * @CODOLICENSE
 */

/**
 *
 * There is no restriction whether to use OOP or procedural
 *
 * preferred pattern
 * assets/ your static resources
 *         js/  your javascript
 *         css/ your css files
 *         img/ your images
 *         tpl/ your .tpl files
 *
 * you are free to follow your own style.
 */
/**
 * All files should include below defined or die line
 *
 */
defined('IN_CODOF') or die();


//dispatch('abc', function() {echo "hi";});
/**
 * you can define your own routes using dispatch_t(get/post)
 * wildcards can be used
 * files must end with .tpl and no php is allowed inside template files
 * not even using the smarty php tags by default
 * to use any variables use the smarty assign function
 *
 * All .tpl files in a plugin must follow the below layout
 *
 * {* Smarty *}
 * {extends file='layout.tpl'}
 *
 * {block name=body}
 *
 *  YOUR PLUGIN HTML
 * {/block}
 *
 * if you want to remove the header and footer comment the {extends... } line
 *
 *
 * How to load your template file ?
 *
 * You can load your smarty tpl file for eg. my_blog.tpl using
 * \CODOF\Plugin::tpl('my_blog')
 * do not include .tpl at the end
 *
 */

use CODOF\DTO\Error;
use CODOF\DTO\Response;
use CODOF\User\User;
use CODOF\Util;

\CODOF\Hook::add('before_reply_insert', function () {
    //checking the post fields is already done at this stage.
    $messageToCheck = $_POST['input_txt'];
    AntiSpamRules::checkMessage($messageToCheck);
});

\CODOF\Hook::add('before_topic_edit', function ($args) {
    //checking the post fields is already done at this stage.
    $title = $args[0];
    $message = $args[1];
    $tagArray = $args[2];
    $tagsString = implode(" ",$tagArray);
    $messageToCheck = "$title $message $tagsString";
    AntiSpamRules::checkMessage($messageToCheck, true);
});

\CODOF\Hook::add('before_post_edit', function ($args) {
    //checking the post fields is already done at this stage.
    $title = $args[0];
    $message = isset($args[1]) ? $args[1] : "";
    $tagArray = isset($args[2]) ? $args[2] : [];
    $tagsString = implode(" ",$tagArray);
    $messageToCheck = "$title $message $tagsString";
    AntiSpamRules::checkMessage($messageToCheck, true);

});

\CODOF\Hook::add('before_topic_insert', function ($args) {
    //checking the post fields is already done at this stage.
    $title = $args[0];
    $message = $args[1];
    $tagArray = $args[2];
    $tagsString = implode(" ",$tagArray);
    $messageToCheck = "$title $message $tagsString";
    AntiSpamRules::checkMessage($messageToCheck);

});

class AntiSpamRules{


    /**
     * @param string $message
     * @param $isEdit
     */
    public static function checkMessage($message, $isEdit = false){
        $user = User::get();
        $bannedWordsFound = AntiSpamRules::checkMessageForNewUser($user,$message, $isEdit);
        if(count($bannedWordsFound)>0){
            $errorMessage = _t("You do not have enough posts to use the following words : ")
                .implode(",",$bannedWordsFound);
            $response = new Response();
            $response->setError(new Error(400,$errorMessage));
            Util::halt(400,json_encode($response)); //this calls an exit();
        }

        $bannedWordsFound = AntiSpamRules::checkMessageForAllUsers($message);
        if(count($bannedWordsFound)>0){
            $errorMessage = _t("The following words are banned: ")
                .implode(",",$bannedWordsFound);
            $response = new Response();
            $response->setError(new Error(400,$errorMessage));
            Util::halt(400,json_encode($response)); //this calls an exit();
        }


    }


    /**
     * @param string $message
     * @return string[]
     */
    public static function checkMessageForAllUsers($message) {
        $message = strtolower($message);
        $bannedWordsFoundForAllUsers = [];
        $bannedWordsStr = strtolower(Util::get_opt('ASR_WORDS_NOT_ALLOWED_EVER'));
        $bannedWords = explode(",",$bannedWordsStr);

        foreach ($bannedWords as $bannedWord){
            if($bannedWord !== "" && strpos($message,$bannedWord)!==false){
                $bannedWordsFoundForAllUsers[] = $bannedWord;
            }
        }

        return $bannedWordsFoundForAllUsers;

    }

    /**
     * @param $user
     * @param string $message
     * @return string[]
     */
    public static function checkMessageForNewUser($user, $message, $isEdit) {
        $message = strtolower($message);
        $bannedWordsFoundForNewUsers = [];
        $user_num_posts = (int) $user->no_posts;
        $should_have_posts = (int) Util::get_opt('ASR_BLOCK_TILL_POST_COUNT');
        $blockTillPostCountWordsStr = strtolower(Util::get_opt('ASR_BLOCK_TILL_POST_COUNT_WORDS'));
        $blockTillPostCountWords = explode(",",$blockTillPostCountWordsStr);

        if($isEdit) $should_have_posts++;

        if($user_num_posts < $should_have_posts){
            foreach ($blockTillPostCountWords as $blockTillPostCountWord){
                if($blockTillPostCountWord != "" && strpos($message,$blockTillPostCountWord)!==false){
                    $bannedWordsFoundForNewUsers[] = $blockTillPostCountWord;
                }
            }
        }

        return $bannedWordsFoundForNewUsers;

    }


}