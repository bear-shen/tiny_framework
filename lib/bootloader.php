<?php
//启动时加载的一些基础函数
function errHandler($errno, $errstr, $errfile, $errline) {
    $result = [
        'code' => 100,
        'msg'  => 'error occurred',
        'data' => [
            'code' => $errno,
            'msg'  => $errstr,
            'file' => $errfile,
            'line' => $errline,
        ],
    ];
    if (PHP_SAPI === 'cli') {
        echo "------------------ Err ------------------\r\n" .
             ":: {$errno} {$errstr}\r\n" .
             ":: {$errfile} {$errline}\r\n";
        exit();
    }
    echo json_encode($result, JSON_UNESCAPED_UNICODE);
    exit();
}

/**
 * @see https://www.php.net/manual/en/function.debug-backtrace.php
 */
function exceptionHandler(Exception $ex) {
    $trace      = $ex->getTrace();
    $tracePrint = [];
    foreach ($trace as $row) {
        $i       = $row + [
                'file'     => '',
                'line'     => '',
                'function' => '',
                'class'    => '',
                'object'   => null,
                'type'     => '',
                'args'     => [],
            ];
        $argSize = sizeof($i['args']);
        for ($i1 = 0; $i1 < $argSize; $i1++) {
            $str  = '';
            $type = gettype($i['args'][$i1]);
            switch ($type) {
                case 'boolean':
                    $str = $i['args'][$i1] ? 'TRUE' : 'FALSE';
                    break;
                case 'integer':
                case 'double':
                    $str = (string)$i['args'][$i1];
                    break;
                    break;
                case 'string':
                    $str = '"' . $i['args'][$i1] . '"';
                    break;
                case 'NULL':
                    $str = 'NULL';
                    break;
                case 'array':
                case 'object':
                case 'resource':
                case 'resource (closed)':
                case 'unknown ':
                default:
                    $str = 'res.<' . $type . '>';
                    break;
            }
            $i['args'][$i1] = $str;
        }
        $j             =
            $i['file'] . ':' .
            $i['line'] . '@' .
            $i['class'] . '' .
            $i['type'] . '' .
            $i['function'] . '' .
            implode(',', $i['args']);
        $tracePrint [] = $j;
    }
    if (PHP_SAPI === 'cli') {
        $traceStr = "------------------ Err ------------------\r\n" .
                    ":: " . $ex->getCode() . " " . $ex->getMessage() . "\r\n" .
                    ":: " . $ex->getFile() . " " . $ex->getLine() . "\r\n";
        foreach ($tracePrint as $trace) {
            $traceStr .= ":: " . $trace . "\r\n";
        }
        echo $traceStr;
        exit();
    }

    $result = [
        'code' => 101,
        'msg'  => 'error occurred',
        'data' => [
            'code'  => $ex->getCode(),
            'msg'   => $ex->getMessage(),
            'file'  => $ex->getFile(),
            'line'  => $ex->getLine(),
            'trace' => $tracePrint,
        ],
    ];
    echo json_encode($result, JSON_UNESCAPED_UNICODE);
    exit();
}

set_error_handler('errHandler');
set_exception_handler('exceptionHandler');
var_dump($a);