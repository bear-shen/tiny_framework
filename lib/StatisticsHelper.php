<?php
/**
 * DB Ver
 */

namespace Lib;

class StatisticsHelper {

    //对目标做枚举，从maxLevel往下写到minLevel

    protected $availLevel = [
        'global',
        'year',
        'season',
        'month',
        'week',
        'day',
        'hour',
        'minute',
        'second',
    ];

    //
    protected $conf = [
    ];

    const TABLE_NAME = 'sys_statistics';

    public function __construct($config = []) {
    }

    /**
     * @param array|string $code “:”作为分组符号，长度上限参考数据库
     * @param int|float $value 值
     * @param array $timeLevels
     * @param int $time
     * @return bool
     */
    public function write($code, $value, $timeLevels = ['global'], $time = 0) {
//        DB::enableQueryLog();
//        var_dump(GenFunc::getTick());
        if (empty($time)) $time = time();
        if (empty($timeLevels)) $timeLevels = [
            'global',
            'year',
            'month',
            'week',
            'day',
        ];
        $timeLabels = [];
        foreach ($timeLevels as $timeLabel) {
            $timeLabels[] = self::getLabel($timeLabel, $time);
        }
        //
        if (!is_array($code)) {
            $code = [$code];
        }
        $axisYList = [];
        foreach ($code as $codeItem) {
            $axisStr = '';
            $axisPrt = explode(':', $codeItem);
            foreach ($axisPrt as $k => $axisEle) {
                if ($k != 0) $axisStr .= ':';
                $axisStr     .= $axisEle;
                $axisYList[] = $axisStr;
            }
        }
        //
        $toOpDataList = [];
        $toOperate    = [
            'select' => [],
            'insert' => [],
            'update' => [],
            'delete' => [],
        ];
        //
        foreach ($axisYList as $axisY) {
            foreach ($timeLabels as $timeLabel) {
                $timeType              = $timeLabel['type'];
                $timeValue             = $timeLabel['value'];
                $toOperate['select'][] = "\r\n or (ss.`code`='$axisY' and ss.`time_type`='$timeType' and ss.time_value='$timeValue') ";
                $toOpDataList
                ["$axisY|$timeType|$timeValue"]
                                       = [
                    'code'       => $axisY,
                    'time_type'  => $timeType,
                    'time_value' => $timeValue,
                    'value'      => $value,
                ];
            }
        }
        $selectDt = implode('', $toOperate['select']);
        $dataInDB = DB::query("select 
`id`, `time_type`, `time_value`, `code`, `value`
from sys_statistics ss use index (time) where false $selectDt");
        //处理已存在的数据
        foreach ($dataInDB as $data) {
            $key = $data->code . '|' . $data->time_type . '|' . $data->time_value;
            if (isset($toOperate['update'][$key])) {
                $toOperate['delete'][]              = $data->id;
                $toOperate['update'][$key]['value'] += $data->value;
            } else {
                $toOperate['update'][$key] = [
                    'id'    => $data->id,
                    //'time_type'  => $data->time_type,
                    //'time_value' => $data->time_value,
                    //'code'       => $data->code,
                    'value' => 0,/*$data->value*/

                ];
            }
            $toOperate['update'][$key]['value'] += $toOpDataList[$key]['value'];
            unset($toOpDataList[$key]);
        }
        //添加不存在的数据
        foreach ($toOpDataList as $data) {
            $toOperate['insert'][] = [
                'time_type'  => $data['time_type'],
                'time_value' => $data['time_value'],
                'code'       => $data['code'],
                'value'      => $data['value'],
            ];
        }

        //var_dump(GenFunc::getTick());
        if (!empty($toOperate['delete'])) {
            $deleteDt = implode(',', $toOperate['delete']);
            DB::query("delete from sys_statistics where id in ($deleteDt)");
        }
        //var_dump(GenFunc::getTick());
        if (!empty($toOperate['update'])) {
            $updateDt = [];
            $updateId = [];
            foreach ($toOperate['update'] as $item) {
                $updateDt[] = ' when ' . $item['id'] . ' then `value`+' . $item['value'] . ' ';
                $updateId[] = $item['id'];
            }
            $updateDt = implode('', $updateDt);
            $updateId = implode(',', $updateId);
            DB::query("update sys_statistics set value = case id $updateDt end where id in ($updateId)");
        }
        //var_dump(GenFunc::getTick());
        if (!empty($toOperate['insert'])) {
            $insertDt = [];
            foreach ($toOperate['insert'] as $item) {
                $insertDt[] = '("' . $item['time_type'] . '","' . $item['time_value'] . '","' . $item['code'] . '","' . $item['value'] . '")';
            }
            $insertDt = implode(',', $insertDt);
            DB::query("insert into sys_statistics (time_type, time_value, code, value) values $insertDt");
        }
        //var_dump(GenFunc::getTick());
        return true;
    }

    /**
     * @param $codeList array|string 输入为字符串，返回单个数据，输入为数组，返回分组的多行数据
     * @param $type string 时间轴的类型
     * @param $from int|string 开始时间，输入为int就用int，输入为可以被转换的时间字符串，会自己转换
     * @param $to int|string 结束时间，计算同上
     * @param $zeroFill boolean true时可以自动填充整个时间段，无论是否存在数据，默认false是以防意外情况
     * @return array|SysS
     *
     * [[
     *      'id'         => 0,
     *      'time_type'  => '',
     *      'time_value' => 0,
     *      'code'       => '',
     *      'value'      => 0,
     * ]]
     *
     */
    public function get($codeList, $type, $from, $to, $zeroFill = false) {
        $from = is_numeric($from) ? $from : strtotime($from);
        $to   = is_numeric($to) ? $to : strtotime($to);
        if ($to < $from) {
            list($from, $to) = [$to, $from];
        }
        if (empty($to)) $to = $from;
//        $isStr = false;
        if (is_string($codeList)) {
//            $isStr    = true;
            $codeList = [$codeList];
        }
        $relCodeStr  = [];
        $relDataList = [];
        foreach ($codeList as $code) {
            $relCodeStr[]  = '?';
            $relDataList[] = $code;
        }
        $relCodeStr = implode(',', $relCodeStr);
        //
        $relDataList[] = $type;
        $relDataList[] = $from;
        $relDataList[] = $to;
        $list          = DB::query("select * from sys_statistics where code in ($relCodeStr) and time_type=? and time_value between ? and ? order by time_value asc", [], $relDataList);
        /*$result        = [];
        foreach ($list as $item) {
            $time     = self::getLabel($type, $item->time_value);
            $result[] = [
                'time'  => $time['label'],
                'type'  => $time['type'],
                'code'  => $item->code,
                'value' => $item->value,
            ];
        }*/
        $resultData = [];
        foreach ($list as $item) {
            $resultData[] = [
                'id'         => $item->id,
                'time_type'  => $item->time_type,
                'time_value' => $item->time_value,
                'code'       => $item->code,
                'value'      => $item->value,
            ];
        }
//        var_dump($resultData);

        /*if ($isStr) {
            return (empty($resultData[0]) ? [] : $resultData[0]) + [
                    'id'         => 0,
                    'time_type'  => '',
                    'time_value' => 0,
                    'code'       => '',
                    'value'      => 0,
                ];
        }*/
        if (!$zeroFill) {
            return $resultData;
        }
        $keys  = self::enumPossibleTimestamp($type, $from, $to);
        $group = [];
        foreach ($codeList as $code) {
            if (empty($group[$code])) $group[$code] = [];
        }
        foreach ($resultData as $item) {
            $code                              = $item['code'];
            $group[$code][$item['time_value']] = $item;
        }
        $target = [];
        foreach ($group as $code => $itemList) {
            foreach ($keys as $key) {
                $target[] = isset($itemList[$key]) ?
                    $itemList[$key] : [
                        'id'         => 0,
                        'time_type'  => $type,
                        'time_value' => $key,
                        'code'       => $code,
                        'value'      => 0,
                    ];
            }
        }
//        var_dump($target);
        return $target;
    }


    /**
     * 获取详细时间标签的方法，方便外部获取统计所以用public static
     *
     * @param $type
     * @param $time
     * @return array ['type'=>'hour','label'=>'2010-01-01 00','value'=>100000000,]
     */
    public static function getLabel($type, $time) {
        $label   = 'global';
        $timeOut = 0;
        switch ($type) {
            case 'second':
                $label   = date('Y-m-d H:i:s', $time);
                $timeOut = $time;
                break;
            case 'minute':
                $label   = date('Y-m-d H:i', $time);
                $timeOut = strtotime(date('Y-m-d H:i:00', $time));
                break;
            case 'hour':
                $label   = date('Y-m-d H', $time);
                $timeOut = strtotime(date('Y-m-d H:00:00', $time));
                break;
            case 'day':
                $label   = date('Y-m-d', $time);
                $timeOut = strtotime(date('Y-m-d 00:00:00', $time));
                break;
            case 'week':
                $wCode   = intval(date('W', $time));
                $label   = date('Y', $time) . 'W' . $wCode;
                $timeOut = strtotime(date('Y-01-01 00:00:00', $time)) + ($wCode - 1) * 86400 * 7;
                break;
            case 'month':
                $label   = date('Ym', $time);
                $timeOut = strtotime(date('Y-m-01 00:00:00', $time));
                break;
            case 'season':
                $season  = floor((date('m', $time) / 4)) + 1;
                $label   = date('Y', $time) . 'S' . $season;
                $timeOut = strtotime(date('Y-' . str_pad($season, 2, '0', STR_PAD_LEFT) . '-01 00:00:00', $time));
                break;
            case 'year':
                $label   = date('Y', $time);
                $timeOut = strtotime(date('Y-01-01 00:00:00', $time));
                break;
            case 'global':
                $label   = 'global';
                $timeOut = 0;
                break;
        }
        return [
            'type'  => $type,
            'label' => $label,
            'value' => $timeOut,
        ];
    }

    /**
     * 枚举时间区段内可能的时间戳
     * @param $type string 时间轴类型
     * @param $fromTime int 开始时间
     * @param $to int 结束时间
     * @return array
     *
     * [0,0,0,0]
     */
    public static function enumPossibleTimestamp($type, $fromTime, $to) {
        $from = [];
        list($from['y'], $from['m'], $from['d'], $from['h'], $from['i'], $from['s']) = explode('-', date('Y-m-d-H-i-s', $fromTime));
//        var_dump($fromTime);
//        var_dump(date('Y-m-d-H-i-s', $fromTime));
        $availDateList = [];
        switch ($type) {
            case 'global':
                $availDateList[] = self::getLabel('global', 0);
                break;
            case 'year':
                $toY = intval(date('Y', $to));
                for ($i1 = intval($from['y']); $i1 <= $toY; $i1++) {
                    $availDateList[] = strtotime($i1 . '-01-01 00:00:00');
                }
                break;
            case 'season':
                $dtList            = [
                    'from_s'  => intval(date('m', $from) / 4) + 1,
                    'from_sw' => 0,
                ];
                $dtList['from_sw'] = ($dtList['from_s'] - 1) * 4 + 1;
                $i                 = 0;
                do {
                    $time            = strtotime("{$from['y']}-{$dtList['from_sw']}-01 00:00:00 +{$i} month");
                    $i               += 3;
                    $availDateList[] = $time;
                } while ($to > $time);
                break;
            case 'month':
                $i = 0;
                do {
                    $time = strtotime("{$from['y']}-{$from['m']}-01 00:00:00 +{$i} month");
                    $i++;
                    $availDateList[] = $time;
                } while ($to > $time);
                break;
            case 'week':
                $i = 0;
                do {
                    $time = strtotime("{$from['y']}-{$from['m']}-{$from['d']} 00:00:00 +{$i} week monday this week");
                    $i++;
                    $availDateList[] = $time;
                } while ($to > $time);
                break;
            case 'day':
                $i = 0;
                do {
//                    var_dump('================');
                    $time = strtotime("{$from['y']}-{$from['m']}-{$from['d']} 00:00:00 +{$i} day");
//                    var_dump("{$from['y']}-{$from['m']}-{$from['d']} 00:00:00 +{$i} day");
//                    var_dump($time);
                    $i++;
                    $availDateList[] = $time;
                } while ($to > $time);
                break;
            case 'hour':
                $i = 0;
                do {
                    $time = strtotime("{$from['y']}-{$from['m']}-{$from['d']} {$from['h']}:00:00 +{$i} hour");
                    $i++;
                    $availDateList[] = $time;
                } while ($to > $time);
                break;
            case 'minute':
                $i = 0;
                do {
                    $time = strtotime("{$from['y']}-{$from['m']}-{$from['d']} {$from['h']}:{$from['i']}:00 +{$i} minute");
                    $i++;
                    $availDateList[] = $time;
                } while ($to > $time);
                break;
            case 'second':
                $i = 0;
                do {
                    $time = strtotime("{$from['y']}-{$from['m']}-{$from['d']} {$from['h']}:{$from['i']}:{$from['s']} +{$i} second");
                    $i++;
                    $availDateList[] = $time;
                } while ($to > $time);
                break;
        }
        return $availDateList;
    }

}