<?php
$out = [];
//全输出到out
exec('ffprobe -hide_banner -show_format \
-i out.x264.aac.mp4 2>&1', $out);
//2不输出到out
exec('ffprobe -hide_banner -show_format \
-i out.x264.aac.mp4 2>/dev/null', $out);
var_dump($out);