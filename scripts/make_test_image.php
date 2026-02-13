<?php
$img = imagecreatetruecolor(1,1);
$bg = imagecolorallocate($img, 255, 0, 0);
imagefill($img, 0, 0, $bg);
$path = __DIR__ . '/test-image.png';
imagepng($img, $path);
imagedestroy($img);
echo "WROTE:$path\n";
