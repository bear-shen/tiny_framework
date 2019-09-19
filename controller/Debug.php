<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/7/25
 * Time: 9:54
 */

namespace Controller;

use Lib\DB;
use Lib\GenFunc;
use Swlib\Http\ContentType;
use Swlib\Saber;
use Swlib\SaberGM;

class Debug extends Kernel {
    public function emptyAct() {
        var_dump(func_get_args());
        var_dump('executed');
        return 'this is response'."\r\n";
    }
}