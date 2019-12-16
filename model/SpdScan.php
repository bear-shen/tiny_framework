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

    /**
     * @param string $GBKKw
     * @return array
     *
     * [[
     * 'title'=>'',
     * 'tid'=>'',
     * ]]
     */
    public static function getTid($GBKKw) {
        self::line('get tid', 1);
        self::tick();
        $url  = "https://tieba.baidu.com/mo/q/m?kw={$GBKKw}&pn=0&forum_recommend=1&lm=0&cid=0&has_url_param=0&is_ajax=1";
        $html = GenFunc::curl(
            [
                CURLOPT_URL     => $url,
                CURLOPT_RESOLVE => [
                    'tieba.baidu.com:443:180.97.104.167',
                    'tieba.baidu.com:80:180.97.104.167',
                ],
            ]);
        self::line('curl executed');
        self::tick();
        $html = json_decode($html, true);
        if (empty($html['data'])) return [];
        if (empty($html['data']['content'])) return [];
        $content = $html['data']['content'];
        $list    = [];
        preg_match_all(
            '/<li'
            . '[\s\S]+?' . 'data-tid="(.+?)"'
            //            . '[\s\S]+?' . 'portrait\/item\/(.+?)[\?"]'
            //            . '[\s\S]+?' . 'ti_author">\s*(.+?)\s*<\/span'
            . '[\s\S]+?' . 'ti_title[\s\S]*?<span>(.+?)<\/span'
            . '[\s\S]+?' . '<\/li>/im',
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
     */
    public static function getPost($fid, $tidDataList) {
        self::line('get post', 1);
        self::tick();
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
        $truncateSize = 1;
        for ($i1 = 0; $i1 < ceil(sizeof($urlList) / $truncateSize); $i1++) {
            self::line('truncating loading:' . $i1);
            $subUrlList = array_slice($urlList, $truncateSize * $i1, $truncateSize);
            foreach ($subUrlList as $url) {
                self::line($url);
            }
            $htmlList = GenFunc::curlMulti($urlList);
            self::tick();
        }
        file_put_contents('cache', $htmlList);

        self::line('get post end', 1);
        exit();
        return [];
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
    private static function getAvailPageNo($max, $start = 2, $pre = 3, $aft = 3) {
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