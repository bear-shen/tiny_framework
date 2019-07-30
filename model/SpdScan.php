<?php namespace Model;

use Lib\GenFunc;

class SpdScan {

    /**
     * @var $cookie string
    */
    public static $cookie = [];
    /**
     * @var $header array
     * ['mobile'=>'','pc'=>'',]
    */
    public static $header = [];

    /**
     * format:
     *    thread:[
     *        'fid'         =>'',
     *        'tid'         =>'',
     *        'poster_name' =>'',
     *        'title'       =>'',
     *        'page'        =>(int),
     *        'max'         =>(int),
     *    ]
     *    post:[
     *        'fid'            =>'',
     *        'tid'            =>'',
     *        'pid'            =>'',
     *        'cid'            =>'',
     *        'user_name'      =>'',//用户的原始用户名
     *        'user_id'        =>'',//一个目前没有用的数字id
     *        'user_portrait'  =>'', //这一项是用户的头像，但是作为封禁条件是必要的
     *        'index_p'        =>'',
     *        'index_c'        =>'',
     *        'time_pub'       =>'',
     *        'content'        =>'',
     *    ]
     * //nickname在楼中楼里不大好记录，所以不处理
     */


    /**
     * @return array
     *
     * [tid]
     */
    public static function getTid() {
        $requests = [
            [
                'uri' => 'https://tieba.baidu.com/mo/q/m?kw=火星笔记本&pn=0&is_ajax=1',
            ],
        ];
        $res      = self::request(
            [
                'request' => $requests,
                'header'  => self::$header['mobile'],
                'cookie'  => self::$cookie,
            ]
        );
        $result   = [];
        foreach ($res as $post) {
            $content = $post['body'];
            $content = json_decode($content, true);
            if (!$content) continue;
            if (empty($content['data']) || empty($content['data']['content'])) continue;
            $postListContent = $content['data']['content'];
            $tidList         = [];
            preg_match_all('/\/p\/(\d+)/im', $postListContent, $tidList);
            if (empty($tidList[1])) continue;
            $result = array_merge($tidList[1]);
        }
        return $result;
    }

    /**
     * @param $tidList array
     * @return array
     *
     * [
     *    'thread' => #thread,
     *    'post'   => #post,
     * ]
     */
    public static function getPost($tidList) {
        echo "=========== getPost first page ===========\n";
        $postData = self::getPostPageData($tidList);
        echo "=========== getPost first page finish ===========\n";
        /*$postData['thread'] = [
            [
                'tid'           => '5739033322',
                'poster_name' => '盗我原号的没J8',
                'title'       => '大概7月中旬把贴吧管理器用Python重写一遍',
                'page'        => '1',
                'max'         => '1',
            ],
            [
                'tid'         => '4824528997',
                'poster_name' => 'nted_shen',
                'title'       => '【主题测试】',
                'page'        => '1',
                'max'         => '1',
            ],
            [
                'tid'         => '6049614717',
                'poster_name' => '盗我原号的没J8',
                'title'       => '无名测试',
                'page'        => '1',
                'max'         => '1',
            ],
            [
                'tid'         => '5948504167',
                'poster_name' => '炙岳',
                'title'       => '【吧务】笔记本吧禁止任何形式的二手交易',
                'page'        => '1',
                'max'         => '4',
            ],
        ];*/
        $newThreadList = [];
        foreach ($postData['thread'] as $thread) {
            $max = intval($thread['max']);
            if ($max <= 1) continue;
            $pageArr = self::getAvailPageNo($max);
            foreach ($pageArr as $page) {
                $newThreadList[] = [
                    'tid'  => $thread['tid'],
                    'page' => $page,
                ];
            }
        }
//	var_dump($newThreadList);
//	return;
        echo 'thread count:' . sizeof($postData['thread']) . "\n";
        echo 'post count:' . sizeof($postData['post']) . "\n";
        GenFunc::getTick();
        echo "=========== getPost res page ===========\n";
        $newPostData = self::getPostPageData($newThreadList);
        foreach ($newPostData['post'] as $post) {
            $postData['post'][] = $post;
        }
        //
        echo 'thread count:' . sizeof($postData['thread']) . "\n";
        echo 'post count:' . sizeof($postData['post']) . "\n";
        GenFunc::getTick();
        return [
            'thread' => $postData['thread'],
            'post'   => $postData['post'],
        ];
    }

    /**
     * @param $input array
     *
     * [[
     *    'tid'  =>'',
     *    'page' =>'',
     * ]]
     * OR
     * [tid,tid,tid]
     *
     * @return array
     * [
     *    'thread' => #thread,
     *    'post'   => #post,
     * ]
     */
    public static function getPostPageData($input) {
        echo "=========== getPostPageData ===========\n";
        $requests = [];
        foreach ($input as $postInfo) {
            $uri = 'https://tieba.baidu.com/p/' .
                   (is_array($postInfo) ? $postInfo['tid'] : $postInfo) .
                   '?' . http_build_query(
                       [
                           'pn'  => (is_array($postInfo) ? $postInfo['page'] : 1),
                           'fid' => Config::read('basic.fid'),
                       ]);
//			echo "{$uri}\n";
            $requests[] = ['uri' => $uri];
        }
        $res        = self::request(
            [
                'request' => $requests,
                'header'  => self::$header['pc'],
                'cookie'  => self::$cookie,
            ]
        );
        $threadList = [];
        $postList   = [];
        foreach ($res as $post) {
            $content = $post['body'];
            $query   = [];
            parse_str($post['query'], $query);
            if (!$content) {
                echo 'alert: no content' . "\n";
                continue;
            }
            //分页
            $maxPage      = 1;
            $pagerContent = [];
            preg_match('/<li.+?l_pager[\s\S]+?<\/li>/im', $content, $pagerContent);
            if (!empty($pagerContent)) {
                preg_match_all('/<a.+?href=".+?pn=(\d+)">/im', $pagerContent[0], $pagerContent);
                if (!empty($pagerContent[1])) {
                    $pagerContent = intval(array_pop($pagerContent[1]));
                    if (!empty($pagerContent)) {
                        $maxPage = $pagerContent;
                    }
                }
            }
            /*file_put_contents(
                'zzz/' . microtime(true) . '.html',
                $content
            );*/
            $postBasic   = [];
            $postContent = [];
            //帖子数据
            preg_match('/' .
                       'PageData\.thread.+?=.+?' .
                       'author.*?:.*?"(.*?)".+?' .
                       'thread_id.*?:.*?(\d+).+?' .
                       'title.*?:.*?"(.+?)"' .
                       '/im', $content, $postBasic);
            if (empty($postBasic)) continue;
            $postBasic    = [
                'fid'         => $query['fid'],
                'tid'         => (string)$postBasic[2],
                'poster_name' => $postBasic[1],//这是源用户名
                'title'       => html_entity_decode($postBasic[3]),
                'page'        => intval($query['pn']),
                'max'         => intval($maxPage),
            ];
            $threadList[] = $postBasic;
            //正文
            preg_match_all('/' .
                           'j_l_post[\s\S]+?data-field=\'([\s\S]+?)\'[\s\S]+?' .
                           'j_d_post_content[\s\S]+?>([\s\S]+?)<\/cc>' .
                           '(?:[\s\S]+?tail-info[\s\S]+?>(\d{4}-.+?)<){0,1}' .//这边看起来只能写成这样，用于匹配fid靠后的贴吧，老的贴吧这里是空的
                           '/im', $content, $postContent);
//		var_dump(sizeof($postContent));
//		var_dump(sizeof($postContent[1]));
            for ($i1 = 0; $i1 < sizeof($postContent[1]); $i1++) {
                $base       = json_decode(html_entity_decode($postContent[1][$i1]), true);
                $content    = $postContent[2][$i1];
                $content    = self::clearHtml($content);
                $postList[] = [
                    'fid'           => $postBasic['fid'],
                    'tid'           => (string)$postBasic['tid'],
                    'pid'           => (string)$base['content']['post_id'],
                    'cid'           => (string)$base['content']['post_id'],
                    'user_name'     => (string)$base['author']['user_name'],
                    'user_id'       => (string)$base['author']['user_id'],
                    'user_portrait' => (string)self::filterPortrait($base['author']['portrait']),
                    'index_p'       => (string)$base['content']['post_no'],//楼层
                    'index_c'       => '0',//楼中楼的楼层
                    'time_pub'      => !empty($postContent[3][$i1]) ? $postContent[3][$i1] : $base['content']['date'],
                    'content'       => $content,
                ];
            }
        }
//	var_dump($threadList);
        return [
            'thread' => $threadList,
            'post'   => $postList,
        ];
    }

    /**
     * @param $input array
     *
     * #thread
     *
     * @return array
     * [#post(comment),]
     */
    public static function getComment($input) {
        echo "=========== getComment ===========\n";
        echo "=========== get per post page\n";
        $requests      = [];
        $postList      = [];
        $commentResult = [];
        foreach ($input as $thread) {
            $pageArr = self::getAvailPageNo($thread['max'], 1);
            foreach ($pageArr as $page) {
                $query      = [
                    'fid' => $thread['fid'],
                    'tid' => $thread['tid'],
                    'pn'  => $page,
                ];
                $requests[] =
                    [
                        'uri' => 'https://tieba.baidu.com/p/totalComment?' . http_build_query($query),
                        //			'headers' => $header['mobile']
                    ];
            }
        }
        $res = self::request(
            [
                'request' => $requests,
                'header'  => self::$header['pc'],
                'cookie'  => self::$cookie,
            ]
        );
        foreach ($res as $data) {
            $content = $data['body'];
            $content = json_decode($content, true);
            $query   = [];
            parse_str($data['query'], $query);
            if (empty($content)) {
                echo 'alert: invalid json!';
                continue;
            }
            if (!empty($content['errno'])) {
                var_dump($content);
                echo 'alert: query error!';
                continue;
            }
            $userList = $content['data']['user_list'];
            foreach ($content['data']['comment_list'] as $pid => $post) {
                $postBasic  = [
                    'fid'     => $query['fid'],
                    'tid'     => $query['tid'],
                    'pid'     => (string)$pid,
                    'page'    => 1,
                    'max'     => ceil($post['comment_num'] / 10.0),
                    'current' => $post['comment_list_num'],
                    'total'   => $post['comment_num'],
                ];
                $postList[] = $postBasic;
                foreach ($post['comment_info'] as $comment) {
                    $commentResult[] = [
                        'fid'           => (string)$postBasic['fid'],
                        'tid'           => (string)$comment['thread_id'],
                        'pid'           => (string)$comment['post_id'],
                        'cid'           => (string)$comment['comment_id'],
                        'user_name'     => $comment['username'],
                        'user_id'       => (string)$comment['user_id'],
                        'user_portrait' => isset($userList[$comment['user_id']]) ?
                            self::filterPortrait($userList[$comment['user_id']]['portrait']) : '',
                        'index_p'       => '0',
                        'index_c'       => '0',
                        'time_pub'      => date('Y-m-d H:i:s', $comment['now_time']),
                        'content'       => self::clearHtml($comment['content']),
                    ];
                }
            }
        }
        echo 'post count:' . sizeof($postList) . "\n";
        echo 'comment count:' . sizeof($commentResult) . "\n";
        echo "=========== getComment P2===========\n";
        $requests = [];
        foreach ($postList as $post) {
            $pageArr = self::getAvailPageNo($post['max'], 2, 1, 3);
            foreach ($pageArr as $page) {
                $query      = [
                    'fid' => $post['fid'],
                    'tid' => $post['tid'],
                    'pid' => $post['pid'],
                    'pn'  => $page,
                ];
                $requests[] =
                    [
                        'uri' => 'https://tieba.baidu.com/p/comment?' . http_build_query($query),
                        //			'headers' => $header['mobile']
                    ];
            }
        }
        $res = self::request(
            [
                'request' => $requests,
                'header'  => self::$header['pc'],
                'cookie'  => self::$cookie,
            ]
        );
        foreach ($res as $data) {
            $content = $data['body'];
            $query   = [];
            parse_str($data['query'], $query);
            if (empty($content)) {
                echo 'alert: invalid json!';
                continue;
            }
            $commentBasic = [
                'fid' => $query['fid'],
                'tid' => $query['tid'],
                'pid' => $query['pid'],
            ];
            $commentList  = [];
            preg_match_all(
                '/<li.+?class=.+?lzl_single_post[\s\S]+?'
                . 'data-field=\'(.+?)\'[\s\S]+?'
                . '<span.+?class=.+?lzl_content_main.+?>(.*?)<\/span>[\s\S]+?'
                . '<span class="lzl_time">(.+?)<\/span>/im',
                $content, $commentList);
            for ($i1 = 0; $i1 < sizeof($commentList[0]); $i1++) {
                $commentContent  = [
                    'p1' => json_decode(html_entity_decode($commentList[1][$i1]), true),
                    'p2' => $commentList[2][$i1],
                    'p3' => $commentList[3][$i1],
                ];
                $commentResult[] = [
                    'fid'           => (string)$commentBasic['fid'],
                    'tid'           => (string)$commentBasic['tid'],
                    'pid'           => (string)$commentBasic['pid'],
                    'cid'           => (string)$commentContent['p1']['spid'],
                    'user_name'     => $commentContent['p1']['user_name'],
                    'user_id'       => '',
                    'user_portrait' => isset($commentContent['p1']['portrait']) ?
                        self::filterPortrait($commentContent['p1']['portrait']) : '',
                    'index_p'       => '0',
                    'index_c'       => '0',
                    'time_pub'      => $commentContent['p3'],
                    'content'       => self::clearHtml($commentContent['p2']),
                ];
            }
        }
        echo 'post count:' . sizeof($postList) . "\n";
        echo 'comment count:' . sizeof($commentResult) . "\n";
//	var_dump($result);
        return $commentResult;
    }

// ----------------------------
// lib
// ----------------------------

    /**
     * saber方法，需要写在携程里
     * @param  array $input
     * [
     *    'header'  => [],
     *    'cookie'  => [],
     *    'request' => [],
     * ]
     * @return array
     * [[
     *    'path'   =>'',
     *    'query'  =>'',
     *    'status' =>'',
     *    'size'   =>'',
     *    'body'   =>'',
     * ]]
     */
    public static function request($input) {
        $input += [
            'header'  => [],
            'cookie'  => [],
            'request' => [],
        ];
        $res   = \Swlib\SaberGM::requests($input['request'], [
            //		'max_co'  => 999,
            'headers' => $input['header'],
            'cookies' => $input['cookie'],
        ]);
        echo "=========== request finished\n";
        echo "use {$res->time}s\n";
        echo "success: $res->success_num, error: $res->error_num\n";
        echo "=========== page\n";
        $result = [];
        foreach ($res as $data) {
            $body = $data->getBody();
            $req  = [
                'path'   => $data->getUri()->getPath(),
                'query'  => $data->getUri()->getQuery(),
                'status' => $data->statusCode,
                'size'   => $body->getSize(),
                'body'   => $body->read($body->getSize()),
            ];
            echo $req['path'] . '?' . $req['query'] . ' code:' . $req['status'] . ' len:' . $req['size'] . "\n";
            $result[] = $req;
        }
        return $result;
    }

    /**
     * 输出可选的分页
     *
     * @param int $max
     * @param int $start
     * @param int $pre
     * @param int $aft
     * @return array [1,2,3,4,5]
     */
    public static function getAvailPageNo($max, $start = 2, $pre = 3, $aft = 3) {
        $max   = intval($max);
        $start = intval($start);
        $pre   = intval($pre);
        $aft   = intval($aft);
        //获取第二/第一页开始的其他页面数据
        $pageArr = [];
        $preTo   = min($pre, $max);
        $aftTo   = max($start, $max - $aft + 1);
        //累计新的页数，从指定页开始，到设定的值为止
        for ($i1 = $start; $i1 <= $preTo; $i1++) $pageArr[] = $i1;
        for ($i1 = $aftTo; $i1 <= $max; $i1++) $pageArr[] = $i1;
//	var_dump($pageArr);
        $pageArr = array_values(array_flip(array_flip($pageArr)));
        return $pageArr;
    }

    /**
     * 清理多余html代码
     * @param string $content
     * @return string
     */
    public static function clearHtml($content = '') {
        if (empty($content)) return '';
        $regex   = [
            '/<div[^>]*?p_forbidden_tip[^>]*?>[\s\S]+?<\/div>/i',
            '/(?:on[a-z]*|width|height|target|class|changedsize|style|rel)="[^">]*?"\s+/i',
            '/<\/*div[^>]*?>/i',
        ];
        $content = trim(preg_replace($regex, [], $content));
        return $content;
    }

    public static function filterPortrait($content = '') {
        if (empty($content)) return '';
        $regex   = [
            '/\?.*/i'
        ];
        $content = trim(preg_replace($regex, [], $content));
        return $content;
    }

// ----------------------------
// writer
// ----------------------------

    public static function writeThreads($data) {
        echo '=========== write thread start ===========' . "\n";
        $data = self::group_only($data, ['tid', 'poster_name', 'title',]);
        //排除重复数据
        $idList    = array_column($data, 'tid');
        $oldId     = [];
        $queryData = self::tileData([$idList]);
        if (!empty($queryData['data'])) {
            $query = DB::select(
                'select * from spd_post_title where tid in ' . $queryData['bracket'],
                $queryData['data']
            );
            echo 'duplicated keys:' . sizeof($query) . "\n";
            foreach ($query as $row) {
                $oldId['_' . $row->tid] = true;
            }
        }
//		var_dump($oldId);
        //清理
        $data = array_values(array_filter($data, function ($item) use ($oldId) {
            return empty($oldId['_' . $item['tid']]);
        }));
        echo 'new keys:' . sizeof($data) . "\n";
        if (empty($data)) {
            echo '=========== write thread finished, nothing written ===========' . "\n";
            return false;
        }
        self::bathQuery('insert ignore into spd_post_title (tid, poster_name, title) VALUES ', $data);
        echo '=========== write thread finished ===========' . "\n";
        return true;
    }

    public static function writePost($data) {
        echo '=========== write post start ===========' . "\n";
        $cidList   = array_column($data, 'cid');
        $queryData = self::tileData([$cidList]);
        var_dump($queryData);
        $oldId = [];
        if (!empty($queryData['data'])) {
            $query = DB::select(
                'select cid from spd_post where cid in ' . $queryData['bracket'],
                $queryData['data']
            );
            echo 'duplicated keys:' . sizeof($query) . "\n";
            foreach ($query as $row) {
                $oldId['_' . $row->cid] = true;
            }
        }
        //清理
        $data = array_values(array_filter($data, function ($item) use ($oldId) {
            return empty($oldId['_' . $item['cid']]);
        }));
        if (empty($data)) {
            echo '=========== write post finished, nothing written ===========' . "\n";
            return false;
        }
//		var_dump($data);
//		var_dump(sizeof($data));
//		return;
        //添加时间
        $time = date('Y-m-d H:i:s');
        for ($i1 = 0; $i1 < sizeof($data); $i1++) {
            $data[$i1]['time_scan'] = $time;
        }
        //写入
        $toPost = self::group_only($data, [
            'tid',
            'pid',
            'cid',
            //
            'user_name',
            'user_id',
            'user_portrait',
            //
            'index_p',
            'index_c',
            'time_pub',
            'time_scan',
            //			'time_operate',
        ]);
        DB::insert('');
        self::bathQuery(
            'insert ignore into spd_post (tid, pid, cid, user_name, user_id, user_portrait, index_p, index_c, time_pub, time_scan) values ',
            $toPost
        );
//		var_dump($toPost);
//		return false;
        $toContent = self::group_only($data, [
            'cid',
            'content',
            'time_scan',
        ]);
        self::bathQuery(
            'insert ignore into spd_post_content (cid, content, time_scan)  values ',
            $toContent
        );
        echo '=========== write post finished ===========' . "\n";
        return true;
    }

    public static function getUid($content) {
        $nameList = self::group_only($content, [
            'user_name',
            'user_id',
            'user_portrait',
        ]);
    }
}