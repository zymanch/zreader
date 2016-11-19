<?php

namespace app\models\ar\task;

use app\models\ar\Chapter;
use app\models\ar\Image;
use app\models\ar\Task;
use app\models\Frame;
use app\models\ImageGrid;
use app\models\Point;
use Yii;
use yii\helpers\Url;


class ProcessChapter extends Task
{

    public function init() {
        parent::init();
        $this->task = self::TASK_PROCESS_CHAPTER;
    }

    protected function _process() {
        $files = $this->_getFiles();
        foreach ($this->chapter->getImages()->all() as $image) {
            $image->delete();
        }
        foreach ($files as $index => $file) {
            $fullName = $this->filename.$file;
            if (!in_array(pathinfo($file,PATHINFO_EXTENSION),array('jpg','png'))) {
                continue;
            }
            $frames = $this->_extractFrames($fullName);
            $gd = imagecreatefromjpeg($fullName);
            /** @var Frame $frame */
            foreach ($frames as $position => $frame) {
                $this->_saveFrame($this->chapter, $frame, $gd, $position, $index+1);
            }
            imagedestroy($gd);
        }
    }

    protected function _saveFrame(Chapter $chapter, Frame $frame, $gd, $position, $page) {
        $image = new Image();
        $image->position = $position;
        $image->page = $page;
        $image->chapter_id = $chapter->chapter_id;
        $image->left = $frame->getMinX();
        $image->top = $frame->getMinY();
        $image->width = $frame->getMaxX()-$frame->getMinX();
        $image->height = $frame->getMaxY()-$frame->getMinY();
        $image->filename = $chapter->chapter_id.'_'.$page.'_'.$position.'.jpg';
        if (!$image->save()) {
            throw new \Exception('Error create image:'.json_encode($image->getFirstErrors()));
        }
        $lastPoint = null;
        $frameGd = imagecreatetruecolor($image->width,$image->height);
        imagealphablending($frameGd, false);
        $transparency = imagecolorallocatealpha($frameGd, 255, 255, 255, 127);
        imagecolortransparent($frameGd, $transparency);
        imagefill($frameGd, 0, 0, $transparency);
        imagesavealpha($frameGd, true);
        $colors = array();
        $width = imagesx($gd);
        $height = imagesy($gd);
        for ($x=$frame->getMinX();$x<=$frame->getMaxX();$x++) {
            for ($y=$frame->getMinY();$y<=$frame->getMaxY();$y++) {
                $point = new Point($x, $y);
                if ($frame->inFrame($point)) {
                    if ($x<0 || $y<0 || $x>$width || $y > $height) {
                        $color = 0xffffff;
                    } else {
                        $color = imagecolorat($gd,$x,$y);
                    }

                    if (!isset($colors[$color])) {
                        $r = ($color >> 16) & 0xFF;
                        $g = ($color >> 8) & 0xFF;
                        $b = $color & 0xFF;
                        $colors[$color] = imagecolorallocate($frameGd,$r, $g, $b);
                    }
                    imagesetpixel($frameGd,$x-$frame->getMinX(),$y-$frame->getMinY(),$colors[$color]);
                }
            }
        }
        $fullPath = $image->getFullPath();
        $dirName = dirname($fullPath);
        if (!file_exists($dirName)) {
            mkdir($dirName,0777,true);
        }
        imagejpeg($frameGd,$fullPath,80);
        imagedestroy($frameGd);

    }

    protected function _extractFrames($fullName) {

        $grid = new ImageGrid($fullName);

        $width = $grid->getWidth();
        $height = $grid->getHeight();
        $frames = [];
        for ($y = 0; $y <= $height; $y++) {
            for ($x = $width; $x >= 0; $x--) {
                $point = new Point($x, $y);
                if (!$grid->isWhite($point)) {
                    $point = new Point($x+1, $y, ImageGrid::BOTTOM);
                    $frame = $grid->extractFrame($point);
                    $grid->markAsWhite($frame);
                    if (!$frame->isSmall()) {
                        $frames[] = $frame;
                    }
                }
            }
        }
        return $frames;
    }

    protected function _getFiles() {
        $files = scandir($this->filename);
        $files = array_slice($files,2);
        sort($files,SORT_NUMERIC);
        return $files;
    }
}
