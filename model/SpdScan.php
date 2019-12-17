<?php namespace Model;

use Lib\CliHelper;
use Lib\GenFunc;

class SpdScan {
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
        'tieba.baidu.com:443:180.97.34.146',
        'tieba.baidu.com:80:180.97.34.146',
    ];

    private $regList = [
        //列表页
        'list'        => '/<li'
                         . '[\s\S]+?' . 'data-tid="(.+?)"'
                         //            . '[\s\S]+?' . 'portrait\/item\/(.+?)[\?"]'
                         //            . '[\s\S]+?' . 'ti_author">\s*(.+?)\s*<\/span'
                         . '[\s\S]+?' . 'ti_title[\s\S]*?<span>(.+?)<\/span'
                         . '[\s\S]+?' . '<\/li>/im',
        //帖子分页
        //        'postPager'   => '/<li.+?l_pager[\s\S]+?<\/li>/im',
        'postPager'   => '/<ul.+?l_posts_num[\s\S]+?<\/ul>/m',
        //分页索引
        //        'postPagerA'  => '/<a.+?href=".+?pn=(\d+)">/im',
        'postPagerA'  => '/<span class="red">(\d+)<\/span>/',//总数
        'postPagerB'  => '/tP">(\d+)<\//m',//当前，仅一页时不存在
        //帖子综合数据
        'postMeta'    => '/' .
                         'PageData\.thread.+?=.+?' .
                         'author.*?:.*?"(.*?)".+?' .
                         'thread_id.*?:.*?(\d+).+?' .
                         'title.*?:.*?"(.+?)"' .
                         '/m',
        //帖子数据
        'postContent' => '/' .
                         'j_l_post[\s\S]+?data-field=\'([\s\S]+?)\'[\s\S]+?' .
                         'j_d_post_content[\s\S]+?>([\s\S]+?)<\/cc>' .
                         '(?:[\s\S]+?tail-info[\s\S]+?>(\d{4}-.+?)<){0,1}' .//这边看起来只能写成这样，用于匹配fid靠后的贴吧，老的贴吧这里是空的
                         '/m',
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
        $GBKKw = $this->tiebaConfig['kw'];
        $url   = "https://tieba.baidu.com/mo/q/m?kw={$GBKKw}&pn=0&forum_recommend=1&lm=0&cid=0&has_url_param=0&is_ajax=1";
        $html  = GenFunc::curl(
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
     *          'user_id'       =>  '',
     *          'user_portrait' =>  '',
     *          'index_p'       =>  '',
     *          'index_c'       =>  '',
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
        $htmlList     = [];
        $truncateSize = 25;
        for ($i1 = 0; $i1 < ceil(sizeof($urlList) / $truncateSize); $i1++) {
            self::line('truncating loading:' . $i1);
            $subUrlList = array_slice($urlList, $truncateSize * $i1, $truncateSize);
            self::line(implode("\r\n", $subUrlList));
            $truncateList = GenFunc::curlMulti($urlList, [
                CURLOPT_RESOLVE => $this->resolve,
                CURLOPT_COOKIE  => $this->tiebaConfig['cookie'],
            ]);
            self::tick();
            foreach ($truncateList as $item) {
                $htmlList[] = $item;
            }
        }
        $dataList = [];
        self::line('parsing html data:');
        foreach ($htmlList as $html) {
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
                'fid'         => $this->tiebaConfig['fid'],
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
                $base       = json_decode(html_entity_decode($postContent[1][$i1]), true);
                $content    = $postContent[2][$i1];
                $content    = $this->clearHtml($content);
                $dataList[] = [
                    'fid'           => (string)$postBasic['fid'],
                    'tid'           => (string)$postBasic['tid'],
                    'pid'           => (string)$base['content']['post_id'],
                    'cid'           => (string)$base['content']['post_id'],
                    'user_name'     => (string)$base['author']['user_name'],
                    'user_id'       => (string)$base['author']['user_id'],
                    'user_portrait' => (string)$this->filterPortrait($base['author']['portrait']),
                    'index_p'       => (string)$base['content']['post_no'],//楼层
                    'index_c'       => '0',//楼中楼的楼层
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
}