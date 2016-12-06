<?php

namespace app\models\ar\Image;

use \app\models\ar;
use yii\helpers\Url;


class Model extends ar\_origin\CImage {

    public function getFullPath() {
        return Url::to('@app').'/public'.$this->getViewPath();
    }

    public function getViewPath() {
        return '/manga/image/'.$this->chapter->manga->url.'/'.$this->chapter->number.'/'.$this->filename;
    }

}