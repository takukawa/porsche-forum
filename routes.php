<?php

/*
 * @CODOLICENSE
 */

//Limonade -> 230 ms
//display & routing
if (get_magic_quotes_gpc()) {
    $gpc = array(&$_GET, &$_POST, &$_COOKIE, &$_REQUEST);

    array_walk_recursive($gpc, function (&$value) {
        $value = stripslashes($value);
    });
}

use CODOF\Forum\Notification\Mention;
use CODOF\Forum\Notification\Subscriber;
use CODOF\Forum\Poll;
use CODOF\Forum\Tracker;
use CODOF\Smarty\Layout;
use CODOF\Smarty\Single;
use CODOF\Store;
use CODOF\User\User;
use CODOF\Util;
use CODOF\Access\Request;
use Controller\Ajax\forum\post;
use Controller\Ajax\forum\topic;
use Controller\Ajax\forum\topics;
use Controller\Ajax\moderation;
use Controller\forum;

$db = \DB::getPDO();

Util::get_config($db);
\Constants::post_boot('themes/' . Util::get_opt('theme') . "/");

//loads translation system
require DATA_PATH . 'locale/lang.php';

CODOF\Smarty\Single::get_instance();

if (!\CODOF\User\CurrentUser\CurrentUser::loggedIn()) {

    //does he have a remember me cookie ?

    $rm = new \CODOF\User\RememberMe($db);
    $id = $rm->has_cookie();
    if ($id) {

        $u = new CODOF\User\User($db);
        $u->login($id);
    }


    $ck = new CODOF\Cookie();
    $ck->Set('cf', \CODOF\User\CurrentUser\CurrentUser::id());
}

$user = CODOF\User\User::get();

dispatch_get('serve/attachment', function () {

    $serve = new \Controller\Serve();
    $serve->attachment();
});

dispatch_get('serve/attachment/preview', function () {

    $serve = new \Controller\Serve();
    $serve->previewAttachment();
});


/*** ============================ USER CAN VIEW FORUM ============================= **/
if ($user->can("view forum")) {
    dispatch_get('Ajax/history/posts', function () {

        if (Request::valid($_GET['_token'])) {

            $db = \DB::getPDO();
            $post = new \CODOF\Forum\Post($db);
            $post->getHistory($_GET['pid']);
        }
    });

    dispatch_get('Ajax/reputation/:tid/:pid/up', function (int $tid, int $pid) {

        if (Request::valid($_GET['_token'])) {

            $rep = new \CODOF\Forum\Reputation();
            $rep->up($tid, $pid);
        }
    });

    dispatch_get('Ajax/reputation/:tid/:pid/down', function (int $tid, int $pid) {

        if (Request::valid($_GET['_token'])) {

            $rep = new \CODOF\Forum\Reputation();
            $rep->down($tid, $pid);
        }
    });


    dispatch_get('Ajax/category/get_topics', function () {

        if (Request::valid($_GET['token'])) {
            $cat = new Controller\Ajax\forum\category();
            $page = (int)$_GET['page'];
            $catid = $_GET['catid'];
            $topics = $cat->get_topics($catid, $page);
            echo json_encode($topics);
        }
    });

//safe
    dispatch_post('Ajax/topic/create', function () {

        if (Request::valid($_POST['token'])) {
            $topic = new Controller\Ajax\forum\topic();
            $topic->create();
        }
    });

//safe
    dispatch_post('Ajax/topic/edit', function () {

        if (Request::valid($_POST['token'])) {
            $topic = new Controller\Ajax\forum\topic();
            $topic->edit();
        }
    });

//safe
    dispatch_post('Ajax/topic/reply', function () {

        if (Request::valid($_POST['token'])) {
            $nt = new Controller\Ajax\forum\topic();
            $nt->reply();
        }
    });

//safe
    dispatch_get('Ajax/topic/inc_view', function () {

        if (Request::valid($_GET['token'])) {
            $topic = new Controller\Ajax\forum\topic();
            $topic->inc_view();
        }
    });

//TODO: Make it category/topic specific so that permissions may be checked
    dispatch_post('Ajax/topic/upload', function () {

        $tid = (int)$_GET['tid'];
        if (Request::valid($_POST['token'])) {
            $topic = new Controller\Ajax\forum\topic();
            $topic->upload();
        }
    });


//safe
    dispatch_get('Ajax/topic/:tid/:from/get_posts', function (int $tid, int $from) {


        $topic = new \CODOF\Forum\Topic(\DB::getPDO());
        $topic_info = $topic->get_topic_info($tid);

        if ($topic->canViewTopic($topic_info['uid'], $topic_info['cat_id'], $topic_info['topic_id'])) {
            $topics = new Controller\Ajax\forum\topic();
            $posts = $topics->get_posts($tid, $from, $topic_info);
            echo json_encode($posts);
        } else {
            exit('Permission denied');
        }
    });

//safe
    dispatch_post('Ajax/topic/report', function () {

        $tid = (int)$_POST['tid'];
        $topic = new \CODOF\Forum\Topic(\DB::getPDO());
        $topic_info = $topic->get_topic_info($tid);

        if (Request::valid($_POST['token']) && $topic->canViewTopic($topic_info['uid'], $topic_info['cat_id'], $topic_info['topic_id'])) {
            $report = new CODOF\Forum\Report();
            $report->reportTopic($tid, (int)$_POST['type'], $_POST['details']);
        } else {
            exit('Permission denied');
        }
    });

//safe
    dispatch_post('Ajax/moderation/topics/delete', function () {

        if (Request::valid($_POST['token'])) {

            $mod = new Controller\Ajax\moderation();
            $mod->deleteTopics();
        }
    });

//safe
    dispatch_post('Ajax/moderation/replies/approve', function () {

        if (Request::valid($_POST['token'])) {

            $mod = new Controller\Ajax\moderation();
            $mod->approveReplies();
        }
    });

//safe
    dispatch_post('Ajax/moderation/replies/delete', function () {

        if (Request::valid($_POST['token'])) {

            $mod = new Controller\Ajax\moderation();
            $mod->deleteReplies();
        }
    });

//safe
    dispatch_get('Ajax/notifications/all', function () {

        if (Request::valid($_GET['_token']) && \CODOF\User\CurrentUser\CurrentUser::loggedIn()) {

            $notifier = new \CODOF\Forum\Notification\Notifier();

            $offset = 0;
            if (isset($_GET['offset'])) {

                $offset = (int)$_GET['offset'];
            }
            $events = $notifier->get(FALSE, 20, 'desc', $offset);

            echo json_encode($notifier->getFormattedForInline($events));
        }
    });

//safe
    dispatch_get('Ajax/notifications/new', function () {

        if (Request::valid($_GET['_token']) && \CODOF\User\CurrentUser\CurrentUser::loggedIn()) {

            $notifier = new \CODOF\Forum\Notification\Notifier();

            if (isset($_GET['time']) && $_GET['time'] > 0) {

                $time = $_GET['time'];
            } else {

                $time = time();
            }

            $events = $notifier->get(FALSE);

            if (!empty($events)) {

                $lastEventTime = $events[0]['created'];
                $time = $lastEventTime;
            }

            $notifications = $notifier->getFormattedForInline($events);
            echo json_encode(
                array(
                    "events" => $notifications,
                    "time" => $time
                )
            );
        }
    });

//safe
    dispatch_get('Ajax/data/new', function () {

        if (Request::valid($_GET['_token']) && \CODOF\User\CurrentUser\CurrentUser::loggedIn()) {

            $notifier = new \CODOF\Forum\Notification\Notifier();

            if (isset($_GET['time']) && $_GET['time'] > 0) {

                $time = $_GET['time'];
            } else {

                $time = time();
            }

            $events = $notifier->getLatest($time);

            if (!empty($events)) {

                $lastEventTime = $events[0]['created'];
                $time = $lastEventTime;
            }

            $notifications = $notifier->getFormattedForInline($events);

            echo json_encode(
                array(
                    "events" => $notifications,
                    "time" => $time
                )
            );
        }
    });


//safe
    dispatch_post('Ajax/moderation/topics/approve', function () {

        if (Request::valid($_POST['token'])) {

            $mod = new Controller\Ajax\moderation();
            $mod->approveTopics();
        }
    });


    dispatch_get('Ajax/topics/get_topics', function () {

        if (Request::valid($_GET['token'])) {
            $topics = new Controller\Ajax\forum\topics();
            $list = $topics->get_topics($_GET['from'], $_GET['type'], isset($_GET['str']));
            echo json_encode($list);
        }
    });

    dispatch_get('Ajax/topics/mark_read', function () {


        if (Request::valid($_GET['token'])) {

            $tracker = new CODOF\Forum\Tracker();
            $tracker->mark_forum_as_read();
        }
    });

    dispatch_get('Ajax/topics/mark_read/:cid', function (int $cid) {

        if (Request::valid($_GET['token'])) {

            $tracker = new CODOF\Forum\Tracker();
            $tracker->mark_category_as_read($cid);
        }
    });

    dispatch_get('Ajax/topics/mark_read/:cid/:tid', function (int $cid, int $tid) {

        if (Request::valid($_GET['token'])) {

            $tracker = new CODOF\Forum\Tracker();
            $tracker->mark_topic_as_read($cid, $tid);
        }
    });


//safe
    dispatch_post('Ajax/post/edit', function () {

        if (Request::valid($_POST['token'])) {
            $post = new Controller\Ajax\forum\post();
            $post->edit();
        }
    });

//safe
    dispatch_post('Ajax/post/:id/delete', function (int $id) {

        if (Request::valid($_POST['token'])) {
            $post = new Controller\Ajax\forum\post();
            $post->delete($id);
        }
    });

//safe
    dispatch_post('Ajax/post/:id/undelete', function (int $id) {

        if (Request::valid($_POST['token'])) {
            $post = new Controller\Ajax\forum\post();
            $post->undelete($id);
        }
    });

//safe
    dispatch_post('Ajax/topic/:id/delete', function (int $id) {

        if (Request::valid($_POST['token'])) {
            $topic = new Controller\Ajax\forum\topic();
            $topic->delete($id);
        }
    });

    dispatch_post('Ajax/topic/deleteAll', function () {

        if (Request::valid($_POST['token'])) {

            $tids = $_POST['tids'];
            $topic = new Controller\Ajax\forum\topic();

            foreach ($tids as $tid) {

                $id = (int)$tid;
                $topic->delete($id);
            }
        }
    });


    dispatch_post('Ajax/topic/merge', function () {

        if (Request::valid($_POST['token'])) {

            $tids = $_POST['tids'];
            $dest = $_POST['dest'];
            $topic = new Controller\Ajax\forum\topic();
            $topic->merge($tids, $dest);
        }
    });

    dispatch_post('Ajax/topic/move', function () {

        if (Request::valid($_POST['token'])) {

            $tids = $_POST['tids'];
            $dest = $_POST['dest'];
            $topic = new Controller\Ajax\forum\topic();
            $topic->moveTopics($tids, $dest);
        }
    });

    dispatch_post('Ajax/posts/move', function () {

        if (Request::valid($_POST['token'])) {

            $topic = new Controller\Ajax\forum\topic();
            $topic->movePosts($_POST['movedPost']);
        }
    });


    Request::get('Ajax/subscribe/:cid/:level', function (int $cid, $level) {
        $subscribe = new CODOF\Forum\Notification\Subscriber();
        $subscribe->toCategory($cid, $level);
    });

    Request::get('Ajax/subscribe/:cid/:tid/:level', function (int $cid, int $tid, $level) {
        $subscribe = new CODOF\Forum\Notification\Subscriber();
        $subscribe->toTopic($cid, $tid, $level);
    });


    Request::get('Ajax/mentions/validate', function () {

        $mentioner = new CODOF\Forum\Notification\Mention();

        $_mentions = $_GET['mentions'];

        return $mentioner->getValid($_mentions);
    });

    Request::get('Ajax/mentions/mentionable/:cid', function (int $cid) {

        $mentioner = new CODOF\Forum\Notification\Mention();

        return $mentioner->getNotMentionable($cid);
    });


    Request::get('Ajax/mentions/:q/:cid/:tid', function ($q, int $cid = 0, int $tid = 0) {

        $mentioner = new CODOF\Forum\Notification\Mention();

        return $mentioner->find($q, $cid, $tid);
    });


    Request::post('Ajax/poll/vote/:pollId/:optionId', function (int $pollId, int $optionId) {

        CODOF\Forum\Poll::vote((int)$pollId, (int)$optionId);
    });


//-------------FORUM------------------------------------------------------------
//the default homepage
    dispatch_get('/topics', function () {

        $forum = new \Controller\forum();
        $forum->topics(1);
        CODOF\Smarty\Layout::load($forum->view, $forum->css_files, $forum->js_files);
    });

    dispatch_get('/moderation', function () {

        $mod = new \Controller\moderation();
        $user = CODOF\User\User::get();
        if ($user->can('moderate topics')) {
            $mod->showTopicsQueue();
            CODOF\Smarty\Layout::load($mod->view, $mod->css_files, $mod->js_files);
        } else {
            CODOF\Smarty\Layout::access_denied();
        }
    });

    dispatch_get('/moderation/replies', function () {

        $mod = new \Controller\moderation();
        $user = CODOF\User\User::get();
        if ($user->can('moderate posts')) {
            $mod->showRepliesQueue();
            CODOF\Smarty\Layout::load($mod->view, $mod->css_files, $mod->js_files);
        } else {
            CODOF\Smarty\Layout::access_denied();
        }
    });

    dispatch_get('/topics/:page', function ($page) {

        $pageNo = (int)$page;

        if (!$pageNo) {

            CODOF\Smarty\Layout::not_found();
        } else {

            $forum = new \Controller\forum();
            $forum->topics((int)$page);
            CODOF\Smarty\Layout::load($forum->view, $forum->css_files, $forum->js_files);
        }
    });


    dispatch_get('/category', function () {

        $forum = new \Controller\forum();
        $forum->topics(1);
        CODOF\Smarty\Layout::load($forum->view, $forum->css_files, $forum->js_files);
    });

    dispatch_get('/category/:cat_name', function ($cat_name) {

        $forum = new \Controller\forum();
        $forum->category($cat_name, 1);
        CODOF\Smarty\Layout::load($forum->view, $forum->css_files, $forum->js_files);
    });

    dispatch_get('/category/:cat_name/:page', function ($cat_name, $page) {

        $forum = new \Controller\forum();
        $forum->category($cat_name, (int)$page);
        CODOF\Smarty\Layout::load($forum->view, $forum->css_files, $forum->js_files);
    });

    dispatch_get('/topic', 'not_found'); //there is nothing as a default topic

    dispatch_get('/topic/:id/edit', function (int $tid) {

        $forum = new \Controller\forum();
        $forum->manage_topic((int)$tid);
        CODOF\Smarty\Layout::load($forum->view, $forum->css_files, $forum->js_files);
    });


    dispatch_get('/topic/:tid/:tname/:page', function (int $tid, $tname, $page) {

        if ($page == null) {

            $page = 1;
        }
        $forum = new \Controller\forum();
        $forum->topic((int)$tid, $page);
        CODOF\Smarty\Layout::load($forum->view, $forum->css_files, $forum->js_files);
    });


    dispatch_get('/new_topic', function () {

        $forum = new \Controller\forum();
        $forum->manage_topic();
        CODOF\Smarty\Layout::load($forum->view, $forum->css_files, $forum->js_files);
    });


    dispatch_get('/tags/:tag/:page', function ($tag, $page = 1) {

        if (!isset($tag)) {


            return \CODOF\Smarty\Layout::not_found();
        }
        CODOF\Store::set('meta:robots', 'noindex, follow');
        $clean_tag = strip_tags($tag);

        $forum = new Controller\forum();
        $forum->listTaggedTopics($clean_tag, $page);
        CODOF\Smarty\Layout::load($forum->view, $forum->css_files, $forum->js_files);
    });
}
/*** ============================ USER CANNOT VIEW FORUM ============================= **/

dispatch_get('Ajax/user/login/dologin', function () {

    if (Request::valid($_GET['token'])) {
        $user = new Controller\Ajax\user\login();
        $user->dologin();
    }
});


dispatch_get('Ajax/user/login/req_pass', function () {

    if (Request::valid($_GET['token'])) {
        $user = new Controller\Ajax\user\login();
        $user->req_pass();
    }
});
dispatch_post('Ajax/user/login/reset_pass', function () {

    if (Request::valid($_POST['token'])) {

        $user = new Controller\Ajax\user\login();
        $user->reset_pass();
    }
});

dispatch_get('Ajax/user/register/mail_exists', function () {

    if (Request::valid($_GET['token'])) {
        $user = new Controller\Ajax\user\register();
        $user->mailExists();
    }
});

dispatch_get('Ajax/user/register/username_exists', function () {

    if (Request::valid($_GET['token'])) {
        $user = new Controller\Ajax\user\register();
        $user->usernameExists();
    }
});

dispatch_get('Ajax/user/register/resend_mail', function () {

    if (Request::valid($_GET['token'])) {
        $user = new Controller\Ajax\user\register();
        $user->resend_mail();
    }
});

dispatch_post('Ajax/set/lastNotificationRead', function () {

    if (Request::valid($_POST['_token'])) {
        $user = CODOF\User\User::get();
        $user->set(array("last_notification_view_time" => time()));
    }
});

dispatch_get('/user/login', function () {

    $user = new \Controller\user();
    $user->login();

    CODOF\Smarty\Layout::load($user->view, $user->css_files, $user->js_files);
});

dispatch_post('/user/register', function () {

    if (Request::valid($_POST['token'])) {
        $user = new \Controller\user();
        $user->register(true);

        CODOF\Smarty\Layout::load($user->view, $user->css_files, $user->js_files);
    }
});

dispatch_get('/user/register', function () {

    $user = new \Controller\user();
    $user->register(false);

    CODOF\Smarty\Layout::load($user->view, $user->css_files, $user->js_files);
});


dispatch_get('/user/forgot', function () {

    $user = new \Controller\user();
    $user->forgot();

    CODOF\Smarty\Layout::load($user->view, $user->css_files, $user->js_files);
});

dispatch_get('/user/reset', function () {

    $user = new \Controller\user();
    $user->reset();

    CODOF\Smarty\Layout::load($user->view, $user->css_files, $user->js_files);
});

dispatch_get('template/**', function ($template) {

    $paginateTpl = '';
    if ($template == 'forum/topic') {

        $paginateTpl = CODOF\HB\Render::get_template_contents('forum/paginate');
    }
    $tpl = CODOF\HB\Render::get_template_contents($template);
    $data = CODOF\HB\Render::get_template_data($template);

    echo json_encode(array("tpl" => $tpl, "paginateTpl" => $paginateTpl, "data" => $data));
});

Request::post('Ajax/user/profile/update_preferences', function () {

    //whitelisted column names
    $updates = array(
        "notification_frequency" => $_POST['notification_frequency'],
        "send_emails_when_online" => $_POST['send_emails_when_online'],
        "real_time_notifications" => $_POST['real_time_notifications'],
        "desktop_notifications" => $_POST['desktop_notifications'],
        "notification_type_on_create_topic" => $_POST['notification_levels']['on_create_topic'],
        "notification_type_on_reply_topic" => $_POST['notification_levels']['on_reply_topic']
    );

    $user = CODOF\User\User::get();
    $user->updatePreferences($updates);
});

dispatch_get('Ajax/user/profile/:uid/get_recent_posts', function ($uid) {

    if (Request::valid($_GET['token'])) {

        $profile = new \Controller\Ajax\user\profile();

        echo json_encode($profile->get_recent_posts($uid));
    }
});

dispatch_post('Ajax/user/edit/change_pass', function () {

    if (Request::valid($_POST['token'])) {

        $old_pass = $_POST['curr_pass'];
        $new_pass = $_POST['new_pass'];

        //$db = \DB::getPDO();
        $me = CODOF\User\User::get();

        $constraints = new \CODOF\Constraints\User;
        $matched = $me->checkPassword($old_pass);

        if ($constraints->password($new_pass) && $matched) {

            $me->updatePassword($new_pass);
            $ret = array("status" => "success", "msg" => _t("Password updated successfully"));
        } else {

            $errors = $constraints->get_errors();

            if (!$matched) {

                $errors = array_merge($errors, array(_t("The current password given is incorrect")));
            }

            $ret = array("status" => "fail", "msg" => $errors);
        }

        echo json_encode($ret);
    }
});


dispatch_get('Ajax/cron/run', function () {
    //if (Request::valid($_GET['token']) && \CODOF\User\CurrentUser\CurrentUser::loggedIn()) {
    $cron = new \CODOF\Cron\Cron();
    $cron->run();
    //}
    //exit;
});

dispatch_get('Ajax/digest', function () {

    if (Request::valid($_GET['token']) && \CODOF\User\CurrentUser\CurrentUser::loggedIn()) {

        $digest = new \CODOF\Forum\Notification\Digest\Digest();
        $ion = $digest->fetch();

        echo json_encode($ion);
    }
    //exit;
});

//-------------PAGES--------------------------
dispatch_get('Ajax/cron/run/:name', function ($name) {

    $user = CODOF\User\User::get();

    if (Request::valid($_GET['token']) && $user->hasRoleId(ROLE_ADMIN)) {

        $cron = new \CODOF\Cron\Cron();
        if (!$cron->run($name)) {

            echo 'Unable to run cron ' . $name . ' because another cron is already running';
        }
    }
    //exit;
});
dispatch_get('/page/:id/:url', function (int $id, $url) {


    $pid = (int)$id;
    $user = \CODOF\User\User::get();

    $qry = 'SELECT title, content FROM ' . PREFIX . 'codo_pages p '
        . ' LEFT JOIN ' . PREFIX . 'codo_page_roles r ON r.pid=p.id '
        . ' WHERE (r.rid IS NULL OR  (r.rid IS NOT NULL AND r.rid IN (' . implode($user->rids) . ')))'
        . ' AND p.id=' . $pid;

    $res = \DB::getPDO()->query($qry);
    $row = $res->fetch();

    if ($row) {

        $title = $row['title'];
        $content = $row['content'];

        $smarty = CODOF\Smarty\Single::get_instance();
        $smarty->assign('contents', $content);
        \CODOF\Store::set('sub_title', $title);
        \CODOF\Smarty\Layout::load('page');
        \CODOF\Hook::call('on_page_load', array($pid));
    } else {

        $page = \DB::table(PREFIX . 'codo_pages')->where('id', $pid)->first();

        if ($page == null) {
            \CODOF\Smarty\Layout::not_found();
        } else {

            \CODOF\Smarty\Layout::access_denied();
        }
    }
});


//-------------USER-------------------------------------------------------------


dispatch_get('/user/logout', function () {

    $user = new \Controller\user();
    $user->logout();

    CODOF\Smarty\Layout::load($user->view, $user->css_files, $user->js_files);
});

dispatch_get('/user/profile', function () {

    $user = new \Controller\user();
    $user->profile(null, null);

    CODOF\Smarty\Layout::load($user->view, $user->css_files, $user->js_files);
});

dispatch_get('/user/avatar/', function () {

    CODOF\Smarty\Layout::not_found();
});


dispatch_get('/user/avatar/:id', function ($id) {

    $user = CODOF\User\User::get($id);

    if ($user->rawAvatar == null || $user->rawAvatar == '') {
        $avatar = new \CODOF\User\Avatar();
        $avatar->generate($id);
    } else {

        return $user->avatar;
    }
});


dispatch_post('/user/profile/:id/edit', function ($id) {

    if (Request::valid($_POST['token'])) {

        $user = new \Controller\user();
        $user->edit_profile($id);

        CODOF\Smarty\Layout::load($user->view, $user->css_files, $user->js_files);
    }
});


dispatch_get('/user/profile/:id/:action', function ($id, $action) {

    $user = new \Controller\user();
    $user->profile($id, $action);

    CODOF\Smarty\Layout::load($user->view, $user->css_files, $user->js_files);
});


dispatch_get('/user/confirm', function () {

    $user = new \Controller\user();
    $user->confirm();

    CODOF\Smarty\Layout::load($user->view, $user->css_files, $user->js_files);
});


dispatch_get('/user', function () {

    $user = new \Controller\user();

    if (isset($_SESSION[UID . 'USER']['id'])) {
        $user->profile($_SESSION[UID . 'USER']['id'], 'view');
    } else {
        $user->login();
    }

    CODOF\Smarty\Layout::load($user->view, $user->css_files, $user->js_files);
});

//DDDDDDDDDDDDDDDDDDDD    ----------------------------=-=-=


//-------------INDEX------------------------------------------------------------

dispatch_get('/', function () use ($user) {

    global $CF_installed;
    if (!$CF_installed) {

        $url = str_replace("index.php?u=/", "", RURI);
        header("Location: " . $url . "install/index.php");
    }

    if ($user->can('view forum')) {
        $forum = new \Controller\forum();
        $forum->topics(1);
        CODOF\Smarty\Layout::load($forum->view, $forum->css_files, $forum->js_files);
    } else {
        $user = new \Controller\user();
        $user->login();

        CODOF\Smarty\Layout::load($user->view, $user->css_files, $user->js_files);
    }
});


function not_found($errno, $errstr, $errfile = null, $errline = null)
{

    CODOF\Smarty\Layout::not_found();
}

Request::start();


