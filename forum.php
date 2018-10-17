<?php
include 'library/init.inc.php';

$act = !empty($_GET['act']) ? $_GET['act'] : 'view';
$opera = !empty($_POST['opera']) ? $_POST['opera'] : '';
$back_act = isset($_SERVER['HTTP_REFERER']) ? 'javascript:window.history.go(-1);' : 'index.php';

$template = 'forum.phtml';
if('up' == $opera)
{
    $response = array('error'=>1);
    $id = isset($_POST['topic_id']) ? intval($_POST['topic_id']) : 0;
    if($id <= 0)
    {
        $response['msg'] = '参数错误';
    }

    $checkUp = 'select id from '.$db->table('forum_up').' where account=\''.$_SESSION['account'].'\' and forum_id='.$id;
    if($uid = $db->fetchOne($checkUp))
    {
        $response['opera'] = 'cancel';
        $deleteUp = 'delete from '.$db->table('forum_up').' where id='.$uid.' limit 1';
        if($db->query($deleteUp))
        {
            $response['error'] = 0;
        } else {
            $response['msg'] = '取消点赞失败';
        }
    } else {
        $response['opera'] = 'add';
        $addUp = 'insert into '.$db->table('forum_up').' (account, add_time, forum_id) values (\''.
                 $_SESSION['account'].'\','.time().','.$id.')';
        if($db->query($addUp))
        {
            $response['error'] = 0;
        } else {
            $response['msg'] = '点赞成功';
        }
    }

    $getCount = 'select count(*) from '.$db->table('forum_up').' where forum_id='.$id;
    $response['count'] = $db->fetchOne($getCount);
    $response['id'] = $id;

    echo json_encode($response);
    exit;
}

if('topic' == $opera)
{
    $content = isset($_POST['content']) ? addslashes($_POST['content']) : '';
    $title = isset($_POST['title']) ? addslashes($_POST['title']) : '';
    $type = isset($_POST['type']) ? addslashes($_POST['type']) : 'topic';
    $response = array('error'=>1);

    if($title == '')
    {
        $response['msg'] = '标题不能为空<br/>';
    } else {
        $title = $db->escape($title);
    }

    if($content == '')
    {
        $response['msg'] .= '话题内容不能为空';
    } else {
        $content = $db->escape($content);
    }

    if(!isset($response['msg']))
    {
        $addTopic = 'insert into '.$db->table('forum').' (account, content, add_time, topic_id, title) values (\''.
                    $_SESSION['account'].'\',\''.$content.'\','.time().',\''.$type.'\', \''.$title.'\')';
        if($db->query($addTopic))
        {
            $response['error'] = 0;
        } else {
            $response['msg'] = '发布新话题失败'.$db->errmsg();
        }
    }

    echo json_encode($response);
    exit;
}

if('do_post' == $opera)
{
    $response = array('error'=>1);
    $commentFormat =<<<HTML
<li id="forum-comment-%d" class="forum-comment-item">
    <img src="%s" class="forum-comment-photo"/>
    <p class="forum-comment-id">%s:</p>
    <span class="forum-comment-content">%s</span>
</li>
HTML;

    $comment = isset($_POST['comment']) ? addslashes($_POST['comment']) : '';
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;

    if($comment == '')
    {
        $response['msg'] = '评论内容不能为空';
    } else {
        $comment = $db->escape($comment);
    }

    if($id <= 0)
    {
        $response['msg'] = '参数错误';
    }

    if(!isset($response['msg']))
    {
        $addComment = 'insert into '.$db->table('forum_comment').' (account,comment,forum_id,add_time,status) values (\''.
                      $_SESSION['account'].'\',\''.$comment.'\','.$id.','.time().', 1)';
        if($db->query($addComment))
        {
            $getName = 'select nickname from '.$db->table('member').' where account=\''.$_SESSION['account'].'\'';
            $name = $db->fetchOne($getName);

            $getPhoto = 'select headimg from '.$db->table('member').' where account=\''.$_SESSION['account'].'\'';
            $photo = $db->fetchOne($getPhoto);

            if($photo == '')
            {
                $photo = 'themes/sbx/images/wang.jpg';
            }
            $response['comment'] = sprintf($commentFormat, $db->get_last_id(), $photo, $name, htmlspecialchars($comment));
            $response['error'] = 0;
            $response['comment_id'] = $id;
        } else {
            $response['msg'] = '发表评论失败';
        }
    }

    echo json_encode($response);
    exit;
}

if($act == 'list')
{
    $id = intval(getGET('id'));

    if($id <= 0)
    {
        die('参数错误');
    }

    $getForum = 'select * from '.$db->table('forum').' where topic_id='.$id.' and `status`=1 order by add_time desc';
    $forums = $db->fetchAll($getForum);

    if($forums)
    {
        foreach($forums as $key=>$val)
        {
            $getPhoto = 'select headimg from '.$db->table('member').' where account=\''.$val['account'].'\'';
            $val['photo'] = $db->fetchOne($getPhoto);
            $getUsername = 'select nickname from '.$db->table('member').' where account=\''.$val['account'].'\'';
            $val['name'] = $db->fetchOne($getUsername);
            if($val['name'] == '')
            {
                $val['name'] = '管理员';
            }

            if($val['img'])
            {
                $val['img'] = unserialize($val['img']);
            } else {
                $val['img'] = array();
            }

            $val['date'] = date('Y-m-d H:i:s', $val['add_time']);

            $getCommentCount = 'select count(*) from '.$db->table('forum_comment').' where `status`=1 and forum_id='.$val['id'];
            $val['comment_count'] = $db->fetchOne($getCommentCount);

            $getComment = 'select a.*,b.nickname as name from '.$db->table('forum_comment').' as a join '.$db->table('member').' as b using(account) where a.forum_id='.$val['id'].' and a.status=1 order by add_time desc';
            $val['comment'] = $db->fetchAll($getComment);

            if($val['comment'])
            {
                foreach($val['comment'] as $k=>$v)
                {
                    $_getPhoto = 'select headimg from '.$db->table('member').' where account=\''.$v['account'].'\'';
                    $v['photo'] = $db->fetchOne($_getPhoto);
                    $val['comment'][$k] = $v;
                }
            }

            $checkUp = 'select id from '.$db->table('forum_up').' where forum_id='.$val['id'].' and account=\''.$_SESSION['account'].'\'';
            $val['up'] = $db->fetchOne($checkUp);
    
            $countUp = 'select count(*) from '.$db->table('forum_up').' where forum_id='.$val['id'];
            $val['up_count'] = $db->fetchOne($countUp);

            $forums[$key] = $val;
        }
    }

    $smarty->assign('forums', $forums);
    assign('id', $id);

    $get_topic_name = 'select name from '.$db->table('topic').' where `id`='.$id;
    $smarty->assign('page_title', $db->fetchOne($get_topic_name));
}

if('detail' == $act)
{
    $template = 'forum_detail.phtml';

    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;

    if($id <= 0)
    {
        header("Location: forum.php");
    }

    $getForum = 'select * from '.$db->table('forum').' where status=1 and id='.$id;
    if($forum = $db->fetchRow($getForum))
    {
        $getPhoto = 'select headimg from '.$db->table('member').' where account=\''.$forum['account'].'\'';
        $forum['photo'] = $db->fetchOne($getPhoto);

        $getUsername = 'select nickname as name from '.$db->table('member').' where account=\''.$forum['account'].'\'';
        $forum['name'] = $db->fetchOne($getUsername);
        if($forum['name'] == '')
        {
            $forum['name'] = '管理员';
        }

        if($forum['img'])
        {
            $forum['img'] = unserialize($val['img']);
        } else {
            $forum['img'] = array();
        }

        $forum['date'] = date('Y-m-d H:i:s', $forum['add_time']);

        $getCommentCount = 'select count(*) from '.$db->table('forum_comment').' where status=1 and forum_id='.$forum['id'];
        $forum['comment_count'] = $db->fetchOne($getCommentCount);

        $getComment = 'select a.*,b.nickname as name from '.$db->table('forum_comment').' as a join '.$db->table('member').' as b using(account) where a.forum_id='.$forum['id'].' and a.status=1 order by add_time desc';
        $forum['comment'] = $db->fetchAll($getComment);

        if($forum['comment'])
        {
            foreach($forum['comment'] as $k=>$v)
            {
                $_getPhoto = 'select headimg from '.$db->table('member').' where account=\''.$v['account'].'\'';
                $v['photo'] = $db->fetchOne($_getPhoto);

                $forum['comment'][$k] = $v;
            }
        }

        $checkUp = 'select id from '.$db->table('forum_up').' where forum_id='.$forum['id'].' and account=\''.$_SESSION['account'].'\'';
        $forum['up'] = $db->fetchOne($checkUp);

        $countUp = 'select count(*) from '.$db->table('forum_up').' where forum_id='.$forum['id'];
        $forum['up_count'] = $db->fetchOne($countUp);
    }

    $smarty->assign('forums', array($forum));
    $smarty->assign('page_title', '快来分享这个话题吧');
}

if('post' == $act)
{
    $id = intval(getGET('id'));

    $smarty->assign('page_title', '发表新话题');
    $template = 'forum_post.phtml';

    $get_topic = 'select `id`,`name` from '.$db->table('topic');
    $topic = $db->fetchAll($get_topic);
    assign('topic', $topic);

    assign('topic_id', $id);
}

if('view' == $act)
{
    $get_topic_list = 'select `id`,`name`,`img` from '.$db->table('topic').' order by `order_view`';
    $topic_list = $db->fetchAll($get_topic_list);
    assign('topic_list', $topic_list);
    assign('page_title', '互动专区');

    $template = 'forum_topic.phtml';
}

$smarty->assign('back_act', $back_act);
$smarty->display($template);
