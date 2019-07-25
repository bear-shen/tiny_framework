<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/7/25
 * Time: 9:54
 */

namespace ControllerCli;

use ControllerCli\Kernel as O;

class Scanner extends Kernel {
    public function Scan() {
        self::line('scanner:scan', 2);
    }

    public function Check() {
        self::line('scanner:check', 2);
    }

    public function Operate() {
        self::line('scanner:operate', 2);
    }
}