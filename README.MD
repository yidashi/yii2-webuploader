## 1.安装  
```
composer require yidashi/yii2-webuploader:"*"
```
## 2.使用

需要两个别名

a.`@static` 访问上传图片的域名,默认`@web/static`

b.`@staticroot` 图片保存路径,默认`@webroot/static`

> 直接使用
  
```
<?= \yidashi\webuploader\Webuploader::widget(['name' => 'xxx', 'options'=>['boxId' => 'picker', 'previewWidth'=>200, 'previewHeight'=>150]])?>
```
> 或者在activeForm里使用
  
```
<?= $form->field($model,'attributeName')->widget('yidashi\webuploader\Webuploader',['options'=>['boxId' => 'picker', 'previewWidth'=>200, 'previewHeight'=>150]]); ?>
```
options非必填

默认使用site/webuploader处理上传逻辑，siteController需要添加
```
public function actions()
{
    return [
        'webupload' => 'yidashi\webuploader\Action'
    ];
}
```  
如果需要使用自己的上传程序处理需添加server属性
```
<?= $form->field($model,'attributeName')->widget('yidashi\webuploader\Webuploader',['server'=>'你自己的处理路由']); ?>
```
> 带裁剪功能

`yidashi\webuploader\Webuploader` 替换成 `yidashi\webuploader\Cropper`


写的很烂,凑合能用