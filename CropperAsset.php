<?php

namespace yidashi\webuploader;

use yii\web\AssetBundle;


class CropperAsset extends AssetBundle
{
	public $css = [
		'cropper.css',
	];

	public $js = [
		'cropper.js',
	];

	public $depends = [
		'yidashi\webuploader\WebuploaderAsset'
	];
    public function init()
    {
        $this->sourcePath = __DIR__ . '/assets';
        parent::init();
    }
}
