<?php

namespace yidashi\webuploader;

use Yii;
use yii\helpers\Html;

/**
 * 图片裁剪上传
 *  为了能够预览它，需要在你想要预览的地方放下如下代码：
 *  <div class="fileupload fileupload-new">
 *      <div class="img-preview"></div>
 *  </div>
 * 在你想要出现“选择文件”的地方，放下如下代码：
 * <?= Cropper::widget() ?>
 *
 * @author Shiyang <dr@shiyang.me>
 */
class Cropper extends Webuploader
{
	/**
	 * @inheritdoc
	 */
	public function init()
	{
        $this->driver = 'local';
		parent::init();
	}

	/**
	 * @inheritdoc
	 */
	public function run()
	{
        $web = rtrim(\Yii::getAlias('@static'), '/');
        $value = $this->hasModel() ? Html::getAttributeValue($this->model, $this->attribute) : $this->value;
        $image = $value ? Html::img(
            strpos($value, 'http:') === false ? (\Yii::getAlias('@static') . '/' . $value) : $value,
            ['width'=>$this->options['previewWidth'],'height'=>$this->options['previewHeight']]
        ) : '';
		$this->registerClientScript();
		return $this->render('cropper', [
            'options' => $this->options,
            'image' => $image,
            'server' => $this->server,
            'hiddenInput' => $this->hiddenInput,
            'web' => $web
        ]);
	}

	/**
	 * Registers Webuploader assets
	 */
	protected function registerClientScript()
	{
		$view = $this->getView();
		CropperAsset::register($view);
	}
}
