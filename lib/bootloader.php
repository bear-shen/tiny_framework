<?php
require_once __DIR__ . '/../config.php';
//基础配置
mb_internal_encoding( 'UTF-8');
mb_regex_encoding( 'UTF-8');
//启动时加载的一些基础函数

//----------------------------------
// 错误处理部分
//----------------------------------

function errHandler($errno, $errstr, $errfile, $errline) {
    if (strpos($errfile, BASE_PATH) !== false)
        $errfile = substr($errfile, strlen(BASE_PATH));
    //
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
             ":: {$errno}:{$errstr}\r\n" .
             ":: {$errfile}:{$errline}\r\n";
        exit();
    }
    echo json_encode($result, JSON_UNESCAPED_UNICODE);
    exit();
}

/**
 * @see https://www.php.net/manual/en/function.debug-backtrace.php
 * @see http://www.blogdaren.com/post-2535.html
 * php7 中使用 \Throwable
 * 之前是 \Exception
 * @param Throwable|Exception $ex
 */
function exceptionHandler($ex) {
    $baseLen = strlen(BASE_PATH);
    //trace
    $trace      = $ex->getTrace();
    $tracePrint = [];
    foreach ($trace as $row) {
        $i = $row + [
                'file'     => '',
                'line'     => '',
                'function' => '',
                'class'    => '',
                'object'   => null,
                'type'     => '',
                'args'     => [],
            ];
        if (strpos($i['file'], BASE_PATH) !== false)
            $i['file'] = substr($i['file'], $baseLen);
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
        $i['args'] = implode(',', $i['args']);
        if (!empty($i['args'])) $i['args'] = '(' . $i['args'] . ')';
        $j             =
            $i['file'] . ':' .
            $i['line'] . '@' .
            $i['class'] . '' .
            $i['type'] . '' .
            $i['function'] . '' .
            $i['args'];
        $tracePrint [] = $j;
    }
    //print
    $file = $ex->getFile();
    if (strpos($file, BASE_PATH) !== false)
        $file = substr($file, $baseLen);
    if (PHP_SAPI === 'cli') {
        $traceStr = "------------------ Err ------------------\r\n" .
                    ":: " . $ex->getCode() . ":" . $ex->getMessage() . "\r\n" .
                    ":: " . $file . ":" . $ex->getLine() . "\r\n";
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
            'file'  => $file,
            'line'  => $ex->getLine(),
            'trace' => $tracePrint,
        ],
    ];
    echo json_encode($result, JSON_UNESCAPED_UNICODE);
    exit();
}

set_error_handler('errHandler');
set_exception_handler('exceptionHandler');
//throw new DOMException();