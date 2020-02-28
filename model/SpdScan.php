<?php namespace Model;

use Lib\CliHelper;
use Lib\DB;
use Lib\File;
use Lib\GenFunc;

class SpdScan extends Kernel {
    use CliHelper;

    public function __construct($config) {
        $this->tiebaConfig = $config;
        $this->header      = Settings::get('header');
    }

    /**
     * @var $header array
     * ['mobile'=>'','pc'=>'',]
     */
    public $header = ['mobile' => '', 'pc' => '',];
    /**
     * from settings::config
     */
    public $tiebaConfig = [
        'name'    => '',
        'kw'      => '',
        'fid'     => '',
        'user'    => '',
        'cookie'  => '',
        'scan'    => true,
        'operate' => true,
    ];

    public $resolve = [
        'tieba.baidu.com:443:180.97.104.167',
        'tieba.baidu.com:80:180.97.104.167',
    ];

    private $regList = [
        //列表页
        'list'               => '/<li'
                                . '[\s\S]+?' . 'data-tid="(.+?)"'
                                //            . '[\s\S]+?' . 'portrait\/item\/(.+?)[\?"]'
                                //            . '[\s\S]+?' . 'ti_author">\s*(.+?)\s*<\/span'
                                . '[\s\S]+?' . 'ti_title[\s\S]*?<span>(.+?)<\/span'
                                . '[\s\S]+?' . '<\/li>/im',
        'list_pc_ul'         => '/<ul id="thread_list"([\s\S]+?)thread_list_bottom/im',
        'list_pc'            => '/href="\/p\/(\d+)" title="(.*?)"/im',
        //帖子分页
        //        'postPager'   => '/<li.+?l_pager[\s\S]+?<\/li>/im',
        'postPager'          => '/<ul.+?l_posts_num[\s\S]+?<\/ul>/m',
        //分页索引
        //        'postPagerA'  => '/<a.+?href=".+?pn=(\d+)">/im',
        'postPagerA'         => '/<span class="red">(\d+)<\/span>/',//总数
        'postPagerB'         => '/tP">(\d+)<\//m',//当前，仅一页时不存在
        //帖子综合数据
        'postMeta'           => '/' .
                                'PageData\.thread.+?=.+?' .
                                'author.*?:.*?"(.*?)".+?' .
                                'thread_id.*?:.*?(\d+).+?' .
                                'title.*?:.*?"(.+?)"' .
                                '/m',
        //帖子数据
        'postContent'        => '/' .
                                'j_l_post[\s\S]+?data-field=\'([\s\S]+?)\'[\s\S]+?' .
                                'j_d_post_content[\s\S]+?>([\s\S]+?)<\/cc>' .
                                '(?:[\s\S]+?tail-info[\s\S]+?>(\d{4}-.+?)<){0,1}' .//这边看起来只能写成这样，用于匹配fid靠后的贴吧，老的贴吧这里是空的
                                '/m',
        //楼中楼2
        'commentPageContent' => '/'
                                . '<li.+?class=.+?lzl_single_post[\s\S]+?'
                                . 'data-field=\'(.+?)\'[\s\S]+?'
                                . '<span.+?class=.+?lzl_content_main.+?>(.*?)<\/span>[\s\S]+?'
                                . '<span class="lzl_time">(.+?)<\/span>'
                                . '/im'
    ];

    /**
     * @return array
     *
     * [[
     * 'title'=>'',
     * 'tid'=>'',
     * ]]
     */
    public function getTid() {
        self::line('get tid', 1);
        self::tick();
        $name = $this->tiebaConfig['name'];
        $url  = "https://tieba.baidu.com/f?kw={$name}&ie=utf-8&tp=0";
        self::line('url from:' . $url);
//        exit();
        $html = GenFunc::curl(
            [
                CURLOPT_URL     => $url,
                CURLOPT_RESOLVE => $this->resolve,
                CURLOPT_COOKIE  => $this->tiebaConfig['cookie'],
            ]);
        self::line('curl executed');
        self::tick();
        $listHtml = [];
        preg_match(
            $this->regList['list_pc_ul'],
            $html, $listHtml);
        if (empty($listHtml)) {
            self::line('list not found');
            return [];
        }
        $list = [];
        preg_match_all(
            $this->regList['list_pc'],
            $listHtml[1],
            $list
        );
        if (empty($list)) {
            self::line('list content not found');
            return [];
        }
        //
        $result = [];
        for ($i1 = 0; $i1 < sizeof($list[0]); $i1++) {
            $result[] = [
                'title' => $list[2][$i1],
                'tid'   => $list[1][$i1],
            ];
        }
        self::line('get threads:' . sizeof($result));
        self::tick();
        self::line('get tid end', 1);
//        var_dump($result);
//        exit();
        return $result;
    }

    /**
     * @deprecated
     * @return array
     *
     * [[
     * 'title'=>'',
     * 'tid'=>'',
     * ]]
     */
    public function getTid_mobile() {
        self::line('get tid', 1);
        self::tick();
        $GBKKw = $this->tiebaConfig['kw'];
        $url   = "https://tieba.baidu.com/mo/q/m?kw={$GBKKw}&pn=0&forum_recommend=1&lm=0&cid=0&has_url_param=0&is_ajax=1";
        self::line('url from:' . $url);
//        exit();
        $html = GenFunc::curl(
            [
                CURLOPT_URL     => $url,
                CURLOPT_RESOLVE => $this->resolve,
                CURLOPT_COOKIE  => $this->tiebaConfig['cookie'],
            ]);
        self::line('curl executed');
        self::tick();
        $html = json_decode($html, true);
        if (empty($html['data'])) return [];
        if (empty($html['data']['content'])) return [];
        $content = $html['data']['content'];
        $list    = [];
        preg_match_all(
            $this->regList['list'],
            $content,
            $list
        );
        if (empty($list)) return [];
        //
        $result = [];
        for ($i1 = 0; $i1 < sizeof($list[0]); $i1++) {
            $result[] = [
                'title' => $list[2][$i1],
                'tid'   => $list[1][$i1],
            ];
        }
//        var_dump($result);
        self::line('get threads:' . sizeof($result));
        self::tick();
        self::line('get tid end', 1);
        return $result;
    }

    /**
     * @param $tidDataList ['tid'=>'','page'=>'',]
     * @return array
     *
     * [
     *      'thread' => [[
     *          'fid'         => '',
     *          'tid'         => '',
     *          'poster_name' => '',
     *          'title'       => '',
     *          'page'        => '',
     *          'max'         => '',
     *      ]],
     *      'post'   => [[
     *          'fid'           =>  '',
     *          'tid'           =>  '',
     *          'pid'           =>  '',
     *          'cid'           =>  '',
     *          'user_name'     =>  '',
     *          'user_nickname' =>  '',
     *          'user_id'       =>  '',
     *          'user_portrait' =>  '',
     *          'index_p'       =>  '',
     *          'index_c'       =>  '',
     *          'is_lz'         =>  '',
     *          'time_pub'      =>  '',
     *          'content'       =>  '',
     *      ]],
     * ]
     */
    public function getPost($tidDataList = []) {
        self::line('get post', 1);
        self::tick();
        $fid = $this->tiebaConfig['fid'];
        //
        $urlList = [];
        foreach ($tidDataList as $tidData) {
            $tid  = $tidData['tid'];
            $page = isset($tidData['page']) ? $tidData['page'] : 1;
            //
            $url       = "https://tieba.baidu.com/p/{$tid}?pn={$page}&fid={$fid}";
            $urlList[] = $url;
        }
        //
        self::line('generate url');
        self::tick();
        $truncateSize = 10;
        $htmlList     = GenFunc::curlMulti($urlList, [
            CURLOPT_RESOLVE    => $this->resolve,
            CURLOPT_COOKIE     => $this->tiebaConfig['cookie'],
            CURLOPT_HTTPHEADER => $this->header['pc'],
        ], false, $truncateSize);
        self::tick();
        $dataList = [];
        self::line('parsing html data:');
        foreach ($htmlList as $html) {
//            File::write('cache/' . time() . '.html', $html);
//            exit();
            //分页
            $curPage      = 1;
            $maxPage      = 1;
            $pagerContent = [];
            preg_match($this->regList['postPager'], $html, $pagerContent);
//            var_dump($pagerContent);
            if (!empty($pagerContent)) {
                //
                $totalPageReg = [];
                preg_match($this->regList['postPagerA'], $pagerContent[0], $totalPageReg);
                if (!empty($totalPageReg)) {
                    $maxPage = intval($totalPageReg[1]);
                }
//                var_dump($totalPageReg);
                //
                $curPageReg = [];
                preg_match($this->regList['postPagerB'], $pagerContent[0], $curPageReg);
                if (!empty($curPageReg)) {
                    $curPage = intval($curPageReg[1]);
                }
//                var_dump($curPageReg);
            }
            //
            $postBasic   = [];
            $postContent = [];
            //帖子数据
            preg_match($this->regList['postMeta'], $html, $postBasic);
            if (empty($postBasic)) continue;
            $postBasic = [
                'fid'         => $fid,
                'tid'         => (string)$postBasic[2],
                'poster_name' => $postBasic[1],//这是源用户名
                'title'       => html_entity_decode($postBasic[3]),
                'page'        => $curPage ?: 1,
                'max'         => $maxPage ?: 1,
            ];
//            var_dump($postBasic);
            $threadList[] = $postBasic;
            //正文
            preg_match_all($this->regList['postContent'], $html, $postContent);
//		var_dump(sizeof($postContent));
//		var_dump(sizeof($postContent[1]));
            for ($i1 = 0; $i1 < sizeof($postContent[1]); $i1++) {
                $base    = json_decode(html_entity_decode($postContent[1][$i1]), true);
                $content = $postContent[2][$i1];
                $content = $this->clearHtml($content);
//                var_dump($base);
//                var_dump($content);
//                exit();
                $dataList[] = [
                    'fid'           => (string)$postBasic['fid'],
                    'tid'           => (string)$postBasic['tid'],
                    'pid'           => (string)$base['content']['post_id'],
                    'cid'           => (string)$base['content']['post_id'],
                    'user_name'     => (string)$base['author']['user_name'],
                    'user_nickname' => (string)$base['author']['user_nickname'],
                    'user_id'       => (string)$base['author']['user_id'],
                    'user_portrait' => (string)$base['author']['portrait'],
                    //'user_portrait' => (string)$this->filterPortrait($base['author']['portrait']),
                    'index_p'       => (string)$base['content']['post_no'],//楼层
                    'index_c'       => '0',//楼中楼的楼层
                    'is_lz'         => '0',
                    'time_pub'      => !empty($postContent[3][$i1]) ? $postContent[3][$i1] : $base['content']['date'],
                    'content'       => $content,
                ];
            }
//            var_dump($dataList);
            self::line(
                'tid:' . $postBasic['tid'] .
                ' p:' . $postBasic['page'] .
                ' page:' . $postBasic['max'] .
                ' size:' . sizeof($dataList)
            );
        }
        self::tick();
        return [
            'thread' => $threadList,
            'post'   => $dataList,
        ];
    }

    /**
     * @param $input array ['fid'=>'','tid'=>'','page'=>'',]
     * @return array
     *
     * [[
     *     'fid'           =>  '',
     *     'tid'           =>  '',
     *     'pid'           =>  '',
     *     'cid'           =>  '',
     *     'user_name'     =>  '',
     *     'user_nickname' =>  '',
     *     'user_id'       =>  '',
     *     'user_portrait' =>  '',
     *     'index_p'       =>  '',
     *     'index_c'       =>  '',
     *     'is_lz'         =>  '',
     *     'time_pub'      =>  '',
     *     'content'       =>  '',
     * ]]
     */
    public function getComment($input) {
        self::line('get comment', 1);
        self::line('get per post page');
        $requests = [];
        foreach ($input as $thread) {
            $query      = [
                'fid' => $thread['fid'],
                'tid' => $thread['tid'],
                'pn'  => $thread['page'],
            ];
            $requests[] = 'https://tieba.baidu.com/p/totalComment?' . http_build_query($query);
        }
        $res = GenFunc::curlMulti($requests, [
            CURLOPT_RESOLVE    => $this->resolve,
            CURLOPT_COOKIE     => $this->tiebaConfig['cookie'],
            CURLOPT_HTTPHEADER => $this->header['pc'],
        ], true, 10);
//        var_dump($res);
//        exit();
        $postList    = [];
        $commentList = [];
        foreach ($res as $data) {
            $content = $data['data'];
            $content = json_decode($content, true);
//            var_dump($content);
            //
            $urlData = parse_url($data['info']['url']);
            $query   = [];
            parse_str($urlData['query'], $query);
//            var_dump($query);
//            exit();
            if (empty($content)) {
                self::line('alert: invalid json!');
                var_dump($data['data']);
                continue;
            }
            if (!empty($content['errno'])) {
                self::line('alert: query error!');
                var_dump($content);
                continue;
            }
            $userList = $content['data']['user_list'];
            foreach ($content['data']['comment_list'] as $pid => $post) {
                $postBasic  = [
                    'fid'   => $query['fid'],
                    'tid'   => $query['tid'],
                    'pid'   => (string)$pid,
                    'page'  => 1,
                    'max'   => ceil($post['comment_num'] / 10.0),
                    //                    'current' => $post['comment_list_num'],
                    'total' => $post['comment_num'],
                ];
                $postList[] = $postBasic;
//                var_dump($post);
//                exit();
                foreach ($post['comment_info'] as $index => $comment) {
                    $curUserInfo   = empty($userList[$comment['user_id']]) ? false : $userList[$comment['user_id']];
                    $commentList[] = [
                        'fid'           => (string)$postBasic['fid'],
                        'tid'           => (string)$comment['thread_id'],
                        'pid'           => (string)$comment['post_id'],
                        'cid'           => (string)$comment['comment_id'],
                        'user_name'     => $comment['username'],
                        'user_nickname' => $curUserInfo ? $curUserInfo['nickname'] : null,
                        'user_id'       => (string)$comment['user_id'],
                        'user_portrait' => $curUserInfo ? $curUserInfo['portrait'] : null,
                        'index_p'       => '0',
                        'index_c'       => (string)($index + 1),
                        'is_lz'         => '0',
                        'time_pub'      => date('Y-m-d H:i:s', $comment['now_time']),
                        'content'       => self::clearHtml($comment['content']),
                    ];
                }
            }
        }
//        var_dump($postList);
//        var_dump($commentList);
//        exit();
        self::line('post count:' . sizeof($postList));
        self::line('comment count:' . sizeof($commentList));
        self::line('get comment v2', 1);
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
                $requests[] = 'https://tieba.baidu.com/p/comment?' . http_build_query($query);
            }
        }
//        var_dump($requests);
        $res = GenFunc::curlMulti($requests, [
            CURLOPT_RESOLVE    => $this->resolve,
            CURLOPT_COOKIE     => $this->tiebaConfig['cookie'],
            CURLOPT_HTTPHEADER => $this->header['pc'],
        ], true, 10);
        foreach ($res as $data) {
            $content = $data['data'];
            //
            $urlData = parse_url($data['info']['url']);
            $query   = [];
            parse_str($urlData['query'], $query);
            //
            if (empty($content)) {
                self::line('alert: invalid html!');
                continue;
            }
            //返回请求信息
            $commentBasic = [
                'fid' => $query['fid'],
                'tid' => $query['tid'],
                'pid' => $query['pid'],
                'pn'  => $query['pn'],
            ];
            $matchResult  = [];
            preg_match_all($this->regList['commentPageContent'],
                           $content, $matchResult);
            for ($i1 = 0; $i1 < sizeof($matchResult[0]); $i1++) {
//                var_dump($matchResult);
                $commentContent = [
                    'p1' => json_decode(html_entity_decode($matchResult[1][$i1]), true),
                    'p2' => $matchResult[2][$i1],
                    'p3' => $matchResult[3][$i1],
                ];
//                var_dump($commentContent);
//                exit();
                $commentList[] = [
                    'fid'           => (string)$commentBasic['fid'],
                    'tid'           => (string)$commentBasic['tid'],
                    'pid'           => (string)$commentBasic['pid'],
                    'cid'           => (string)$commentContent['p1']['spid'],
                    'user_name'     => $commentContent['p1']['user_name'],
                    'user_nickname' => '',
                    'user_id'       => '',
                    'user_portrait' => isset($commentContent['p1']['portrait']) ? $commentContent['p1']['portrait'] : '',
                    'index_p'       => '0',
                    'index_c'       => (string)(intval($commentBasic['pn']) + $i1 + 1),
                    'is_lz'         => '0',
                    'time_pub'      => $commentContent['p3'],
                    'content'       => self::clearHtml($commentContent['p2']),
                ];
            }
        }
//        var_dump($commentList);
//        exit();
        self::line('post count:' . sizeof($postList));
        self::line('comment count:' . sizeof($commentList));
//	var_dump($result);
        return $commentList;
    }

    /**
     * 输出可选的分页
     *
     * @param int $max
     * @param int $start 第x页开始
     * @param int $pre 前x页
     * @param int $aft 最后x页
     * @return array [1,2,3,4,5]
     */
    public static function getAvailPageNo($max, $start = 2, $pre = 3, $aft = 3) {
        $max   = intval($max);
        $start = intval($start);
        $pre   = intval($pre);
        $aft   = intval($aft);
        //
        $arr = [];
        //1开始计数，<=
        for ($i1 = $start; $i1 <= $pre; $i1++) {
            if ($pre > $max) continue;
            $arr[] = $i1;
        }
        //
        for ($i1 = $max - $aft; $i1 <= $max; $i1++) {
            if ($i1 < $start) continue;
            $arr[] = $i1;
        }
        //
        $arr = array_keys(array_flip($arr));
        //
        return $arr;
    }

    /**
     * 清理多余html代码
     * @param string $content
     * @return string
     */
    private static function clearHtml($content = '') {
        if (empty($content)) return '';
        $regex   = [
            '/<div[^>]*?p_forbidden_tip[^>]*?>[\s\S]+?<\/div>/i',
            '/(?:on[a-z]*|width|height|target|class|changedsize|style|rel)="[^">]*?"\s+/i',
            '/<\/*div[^>]*?>/i',
        ];
        $content = trim(preg_replace($regex, [], $content));
        return $content;
    }

    private static function filterPortrait($content = '') {
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

    /**
     * @param $threadList array
     *
     * [[
     *     'fid'         => '',
     *     'tid'         => '',
     *     'poster_name' => '',
     *     'title'       => '',
     *     'page'        => '',
     *     'max'         => '',
     * ]]
     *
     * @return bool
     */
    public function writeThread($threadList) {
        self::line('scanner writing thread', 1);
        //去重
        $targetThreadList = [];
        foreach ($threadList as $item) {
            $targetThreadList[$item['tid']] = $item;
        }
        self::line('checking thread, size:' . sizeof($threadList));
        self::tick();
        $tidList = DB::query('select tid from spd_post_title where tid in (:v)', [], array_keys($targetThreadList));
        self::tick();
        $tidList = array_column($tidList, 'tid');
        foreach ($tidList as $tid) {
            if (isset($targetThreadList[$tid]))
                unset($targetThreadList[$tid]);
        }
        $targetThreadList = array_values($targetThreadList);
        //准备写入
        $threadDataToFill = [];
        foreach ($targetThreadList as $item) {
            $threadDataToFill[] = [
                'tid'         => $item['tid'],
                'poster_name' => $item['poster_name'],
                'title'       => $item['title'],
            ];
        }
        self::line('writing new thread, size:' . sizeof($threadDataToFill));
        self::tick();
//        DB::$logging = true;
        //这两种方法都可以，看心情哪个好用切换哪个
//        DB::query('insert ignore into spd_post_title(tid, poster_name, title) VALUES (:v)', [], $threadDataToFill);
        DB::query('insert ignore into spd_post_title(:k) VALUES (:v)', [], $threadDataToFill);
//        var_dump(DB::$log);
//        var_dump(DB::getPdo()->errorInfo());
        self::tick(true);
        return true;
    }


    /**
     * @param $threadList array
     *
     * [[
     *     'fid'           =>  '',
     *     'tid'           =>  '',
     *     'pid'           =>  '',
     *     'cid'           =>  '',
     *     'user_name'     =>  '',
     *     'user_nickname' =>  '',
     *     'user_id'       =>  '',
     *     'user_portrait' =>  '',
     *     'index_p'       =>  '',
     *     'index_c'       =>  '',
     *     'is_lz'         =>  '',
     *     'time_pub'      =>  '',
     *     'content'       =>  '',
     * ]]
     *
     * @return bool
     */
    public function writePost($postList) {
        self::line('scanner writing post', 1);
        //去重
        $targetPostList = [];
        foreach ($postList as $item) {
            $targetPostList [$item['cid']] = $item;
        }
        self::line('checking post, size:' . sizeof($targetPostList));
        self::tick();
        $cidList = DB::query('select cid from spd_post where cid in (:v)', [], array_keys($targetPostList));
        self::tick();
        $cidList = array_column($cidList, 'tid');
        foreach ($cidList as $cid) {
            if (isset($targetPostList[$cid]))
                unset($targetPostList[$cid]);
        }
        $targetPostList = array_values($targetPostList);
        //转储用户id
        //所有可能的用户字段做 or in
        $userSignatureList = [
            'username' => [],
            'nickname' => [],
            'userid'   => [],
            'portrait' => [],
        ];
        foreach ($postList as $item) {
            if (!empty($item['user_name'])) {
                $userSignatureList[] = $item['user_name'];
            }
            if (!empty($item['nickname'])) {
                $userSignatureList[] = $item['user_nickname'];
            }
            if (!empty($item['userid'])) {
                $userSignatureList[] = $item['user_id'];
            }
            if (!empty($item['portrait'])) {
                $userSignatureList[] = $item['user_portrait'];
            }
        }
        self::line('getting user id');
        self::tick();
        $userInfoListFrDB = DB::query('select id,nickname,username,portrait,userid from spd_user_signature where 
false 
or username in (:v)
or nickname in (:v)
or userid   in (:v)
or portrait in (:v)
;', []
            , $userSignatureList['username']
            , $userSignatureList['nickname']
            , $userSignatureList['userid']
            , $userSignatureList['portrait']
        );
        $userInfoList     = [];
        //uid分组
        foreach ($userInfoListFrDB as $user) {
            $userInfoList[(string)$user['id']] = $user;
        }
        self::tick();
        $counter = [
            'append'   => 0,
            'username' => 0,
            'nickname' => 0,
            'userid'   => 0,
            'portrait' => 0,
        ];
        foreach ($targetPostList as $k => $post) {
            //给每个post分配uid
            $curUid = false;
            foreach ($userInfoList as $uid => $user) {
                if (!empty($post['user_name']) && $post['user_name'] == $user['username']) {
                    $curUid = $uid;
                    break;
                }
                if (!empty($post['user_nickname']) && $post['user_nickname'] == $user['nickname']) {
                    $curUid = $uid;
                    break;
                }
                if (!empty($post['user_id']) && $post['user_id'] == $user['userid']) {
                    $curUid = $uid;
                    break;
                }
                if (!empty($post['user_portrait']) && $post['user_portrait'] == $user['portrait']) {
                    $curUid = $uid;
                    break;
                }
            }
            //判断是否需要追加或者修补用户数据
            if ($curUid) {
                $curUserInDB = $userInfoList[$curUid];
                //补全数据
                if (empty($curUserInDB['username']) && !empty($post['user_name'])) {
                    DB::query('update spd_user_signature set username=:val where id=:id', ['val' => $post['user_name'], 'id' => $curUid]);
                    ++$counter['username'];
                    $userInfoList[$curUid]['username'] = $post['user_name'];
                }
                if (empty($curUserInDB['nickname']) && !empty($post['user_nickname'])) {
                    DB::query('update spd_user_signature set nickname=:val where id=:id', ['val' => $post['user_nickname'], 'id' => $curUid]);
                    ++$counter['nickname'];
                    $userInfoList[$curUid]['nickname'] = $post['user_nickname'];
                }
                if (empty($curUserInDB['userid']) && !empty($post['user_id'])) {
                    DB::query('update spd_user_signature set userid=:val where id=:id', ['val' => $post['user_id'], 'id' => $curUid]);
                    ++$counter['userid'];
                    $userInfoList[$curUid]['userid'] = $post['user_id'];
                }
                if (empty($curUserInDB['portrait']) && !empty($post['user_portrait'])) {
                    DB::query('update spd_user_signature set portrait=:val where id=:id', ['val' => $post['user_portrait'], 'id' => $curUid]);
                    ++$counter['portrait'];
                    $userInfoList[$curUid]['portrait'] = $post['user_portrait'];
                }
            } else {
                //写入新数据
                $toFill = [
                    'username' => $post['user_name'],
                    'nickname' => $post['user_nickname'],
                    'userid'   => $post['user_id'],
                    'portrait' => $post['user_portrait'],
                ];
                DB::query(
                    'insert ignore into spd_user_signature (:k) VALUES (:v);',
                    [], [array_filter($toFill)]);
                $curUid = (string)DB::lastInsertId();
                ++$counter['append'];
                $userInfoList[$curUid] = $toFill;
            }
            $targetPostList[$k]['uid'] = $curUid;
        }
        self::line('filling user id finished');
        self::line('user append       : ' . $counter['append']);
        self::line('user mod username : ' . $counter['username']);
        self::line('user mod nickname : ' . $counter['nickname']);
        self::line('user mod userid   : ' . $counter['userid']);
        self::line('user mod portrait : ' . $counter['portrait']);
        self::tick();
        $toWrite = [
            'post'    => [],
            'content' => [],
        ];
        $time    = date('Y-m-d H:i:s');
        foreach ($targetPostList as $post) {
            $toWrite['post'][]    = [
                'fid'       => $post['fid'],
                'tid'       => $post['tid'],
                'pid'       => $post['pid'],
                'cid'       => $post['cid'],
                'uid'       => $post['uid'],//追加的
                'index_p'   => $post['index_p'],
                'index_c'   => $post['index_c'],
                'is_lz'     => $post['is_lz'],
                'time_pub'  => $post['time_pub'],
                'time_scan' => $time,
            ];
            $toWrite['content'][] = [
                'cid'     => $post['cid'],
                'content' => $post['content'],
            ];
        }
        DB::query('insert ignore into spd_post (fid,tid, pid, cid, uid, index_p, index_c, is_lz, time_pub, time_scan) values (:v);', [], $toWrite['post']);
        DB::query('insert ignore into spd_post_content (cid, content) values (:v)', [], $toWrite['content']);
//        var_dump(DB::getPdo()->errorInfo());
        self::line('filling post finished');
        self::tick(true);
        return true;
    }
}