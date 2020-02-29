<?php

/*
 * @CODOLICENSE
 */

class Notify
{

    /**
     *
     * @var \PDO
     */
    private $db;

    public function __construct()
    {

        $this->db = \DB::getPDO();
    }

    /**
     * Gets all data for queueing emails. This may similar to functions in Subscriber::ofTopic
     * but that is specifically meant for bell notifications
     * @param $cid
     * @param $tid
     * @param $pid
     * @param $offset
     * @param $type
     */
    public function getCategoryOrTopicData($cid, $tid, $pid, $offset, $type, $notifyFrom)
    {
        $defaultSubscription = \DB::table(PREFIX . 'codo_categories')
            ->select('default_subscription_type')
            ->where('cat_id', '=', $cid)->pluck(1);

        if ($defaultSubscription == \CODOF\Forum\Notification\Subscriber::$NOTIFIED && $type == 'new_topic') {
            // all users expect those who explicitly have unsubscribed must be notified :(
            $data = \DB::table(PREFIX . 'codo_users AS u')
                ->select('u.id', 'u.username', 'u.mail', 't.title', 'p.imessage', 'p.omessage', 's.type', 'c.cat_name')
                ->leftJoin(PREFIX . 'codo_notify_subscribers AS s', function ($join) use ($cid) {
                    $join->on('s.uid', '=', 'u.id');
                    $join->on('s.cid', '=', \DB::raw($cid));
                    $join->on('s.tid', '=', \DB::raw(0));
                    $join->on('s.type', '<', \DB::raw(4));
                })
                ->leftJoin(PREFIX . 'codo_posts AS p', 'p.post_id', '=', \DB::raw($pid))
                ->leftJoin(PREFIX . 'codo_topics AS t', 't.topic_id', '=', \DB::raw($tid))
                ->leftJoin(PREFIX . 'codo_categories AS c', 'c.cat_id', '=', \DB::raw($cid))
                ->whereNull('s.uid')
                ->where('u.mail', '<>', 'anonymous@localhost')
                ->skip($offset)->take(400)->get();
        } else {
            // only users explicitly subscribed to category/topic must be notified
            $data = \DB::table(PREFIX . 'codo_notify_subscribers AS s')
                ->select('u.id', 'u.username', 'u.mail', 't.title', 'p.imessage', 'p.omessage', 's.type', 'c.cat_name')
                ->join(PREFIX . 'codo_users AS u', 's.uid', '=', 'u.id')
                ->leftJoin(PREFIX . 'codo_posts AS p', 'p.post_id', '=', \DB::raw($pid))
                ->leftJoin(PREFIX . 'codo_topics AS t', 't.topic_id', '=', \DB::raw($tid))
                ->leftJoin(PREFIX . 'codo_categories AS c', 'c.cat_id', '=', \DB::raw($cid))
                ->where('s.type', '=', CODOF\Forum\Notification\Subscriber::$NOTIFIED)
                ->where('s.cid', '=', $cid)
                ->where(function ($query) use ($tid) {

                    $query->where('s.tid', '=', 0)
                        ->orWhere('s.tid', '=', \DB::raw($tid));
                })
                ->where('p.topic_id', '=', $tid)
                ->where('s.uid', '<>', $notifyFrom)
                ->skip($offset)->take(400)->get();
        }

        return $data;
    }

    /**
     * This is called in the sam request of vote up/down so current user is actually the actor.
     * We get the post data and actor data and return it to generate the email text and queue it.
     * @param $cid
     * @param $tid
     * @param $pid
     * @param $offset
     * @return mixed
     */
    public function getVoteUpDownData($cid, $tid, $pid, $offset)
    {
        return \DB::table(PREFIX . 'codo_posts AS p')
            ->select('u.id', 'u.username', 'u.mail', 't.title', 'p.imessage', 'p.omessage', 'c.cat_name')
            ->join(PREFIX . 'codo_users AS u', 'p.uid', '=', 'u.id')
            ->leftJoin(PREFIX . 'codo_topics AS t', 't.topic_id', '=', \DB::raw($tid))
            ->leftJoin(PREFIX . 'codo_categories AS c', 'c.cat_id', '=', \DB::raw($cid))
            ->where('p.post_id', '=', $pid)
            ->skip($offset)->take(400)->get();
    }

    /**
     *
     *  cid tid   uid type
     *  10  null  1   2
     *  10  2     1   3
     *
     * @param int $cid
     * @param int $tid
     * @param int $pid
     * @param int $offset
     * @return array
     */
    public function getData($cid, $tid, $pid, $offset, $type, $notifyFrom)
    {

        if ($type == "new_topic" || $type == "new_reply") {
            $data = $this->getCategoryOrTopicData($cid, $tid, $pid, $offset, $type, $notifyFrom);
        } else {
            $data = $this->getVoteUpDownData($cid, $tid, $pid, $offset);
        }

        $mailData = [];
        foreach ($data as $datum) {
            $mailData[$datum['id']] = $datum;
        }
        return $mailData;
    }

    public function queue_mails($args)
    {
        $cid = (int)$args['cid'];
        $tid = (int)$args['tid'];
        $pid = (int)$args['pid'];
        $notifyFrom = (int)$args['notifyFrom'];
        $type = $args['type'];

        if ($type == 'new_topic') {
            $subject = \CODOF\Util::get_opt('topic_notify_subject');
            $message = \CODOF\Util::get_opt('topic_notify_message');
        } else if ($type == 'new_reply') {
            $subject = \CODOF\Util::get_opt('post_notify_subject');
            $message = \CODOF\Util::get_opt('post_notify_message');
        } else if ($type == 'vote_up') {
            $subject = \CODOF\Util::get_opt('vote_up_notify_subject');
            $message = \CODOF\Util::get_opt('vote_up_notify_message');
        } else {
            $subject = \CODOF\Util::get_opt('vote_down_notify_subject');
            $message = \CODOF\Util::get_opt('vote_down_notify_message');
        }


        $mail = new \CODOF\Forum\Notification\Mail();

        $actorUser = CODOF\User\User::get($notifyFrom);

        $mails = array();
        $offset = 0;
        while ($data = $this->getData($cid, $tid, $pid, $offset, $type, $notifyFrom)) {

            foreach ($data as $info) {

                //do not send email to the user making the post
                if ($actorUser->id == $info['id'] || $info['mail'] == null) {
                    continue;
                }

                $user = array(
                    "id" => $actorUser->id,
                    "username" => $actorUser->username
                );

                // BAD HACK :(
                $output = str_replace("<img", "<img style='width:100%'", $info['omessage']);

                $post = array(
                    "omessage" => $output,
                    "imessage" => $info['imessage'],
                    "url" => \CODOF\Forum\Forum::getPostURL($tid, $info['title'], $pid),
                    "id" => $info['id'],
                    "username" => $info['username'],
                    "title" => $info['title'],
                    "category" => $info['cat_name']
                );

                $mail->user = $user;
                $mail->post = $post;

                $mails[] = array(
                    "to_address" => $info['mail'],
                    "mail_subject" => html_entity_decode($mail->replace_tokens($subject), ENT_NOQUOTES, "UTF-8"),
                    "body" => html_entity_decode($mail->replace_tokens(($message)), ENT_QUOTES, "UTF-8")
                );
            }

            \DB::table(PREFIX . 'codo_mail_queue')->insert($mails);

            $offset += 400;
        }
    }

}

$pn = new Notify();
\CODOF\Hook::add('after_notify_insert', array($pn, 'queue_mails'));
