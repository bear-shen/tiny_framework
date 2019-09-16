<?php
/**
 * DB Ver
 */

namespace Lib;

class StatisticsHelper {

    //对目标做枚举，从maxLevel往下写到minLevel
    private $availLevel = [
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

    const TABLE_NAME = 'sys_statistics';

    //
    private $conf = [
    ];

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

    public function get($codeList, $type, $from, $to) {
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
        $list          = DB::query("select * from sys_statistics where code in ($relCodeStr) and time_type=? and time_value between ? and ? order by time_value asc", $relDataList);
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
        return $list;
    }


    /**
     * 获取详细时间标签的方法，方便外部获取统计所以用public static
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
                $label   = date('Y\WW', $time);
                $timeOut = strtotime(date('Y-m-d 00:00:00', $time));
                break;
            case 'month':
                $label   = date('Ym', $time);
                $timeOut = strtotime(date('Y-m-01 00:00:00', $time));
                break;
            case 'season':
                $season  = floor(date('m', $time) / 3) + 1;
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

}