<?php
/* @var $this yii\web\View */
?>
<div id="<?= $options['boxId'] ?>">
    <?php if($image): ?><?= $image ?><?php else: ?><button class="btn btn-primary" type="button">选择文件</button><?php endif; ?>
    <div class="uploader-list"></div>
</div>
<?= $hiddenInput ?>
<div class="cropper-wraper webuploader-element-invisible">
    <div class="img-container">
        <img src="" alt="" />
    </div>
    <div class="upload-btn">上传</div>
</div>
<?php $this->registerJs(<<<Js

    // ---------------------------------
    // ---------  Uploader -------------
    // ---------------------------------
    var Uploader = (function() {

        // -------setting-------
        // 如果使用原始大小，超大的图片可能会出现 Croper UI 卡顿，所以这里建议先缩小后再crop.
        var FRAME_WIDTH = 1600;


        var _ = WebUploader;
        var Uploader = _.Uploader;
        var uploaderContainer = $('#{$options['boxId']}');
        var uploader, file;

        if ( !Uploader.support() ) {
            alert( 'Web Uploader 不支持您的浏览器！');
            throw new Error( 'WebUploader does not support the browser you are using.' );
        }

        // hook,
        // 在文件开始上传前进行裁剪。
        Uploader.register({
            'before-send-file': 'cropImage'
        }, {

            cropImage: function( file ) {

                var data = file._cropData,
                    image, deferred;

                file = this.request( 'get-file', file );
                deferred = _.Deferred();

                image = new _.Lib.Image();

                deferred.always(function() {
                    image.destroy();
                    image = null;
                });
                image.once( 'error', deferred.reject );
                image.once( 'load', function() {
                    image.crop( data.x, data.y, data.width, data.height, data.scale );
                });

                image.once( 'complete', function() {
                    var blob, size;

                    // 移动端 UC / qq 浏览器的无图模式下
                    // ctx.getImageData 处理大图的时候会报 Exception
                    // INDEX_SIZE_ERR: DOM Exception 1
                    try {
                        blob = image.getAsBlob();
                        size = file.size;
                        file.source = blob;
                        file.size = blob.size;

                        file.trigger( 'resize', blob.size, size );

                        deferred.resolve();
                    } catch ( e ) {
                        console.log( e );
                        // 出错了直接继续，让其上传原始图片
                        deferred.resolve();
                    }
                });

                file._info && image.info( file._info );
                file._meta && image.meta( file._meta );
                image.loadFromBlob( file.source );
                return deferred.promise();
            }
        });

        return {
            init: function( selectCb ) {
                uploader = new Uploader({
                    pick: {
                        id: '#{$options['boxId']}',
                    },

                    // 设置用什么方式去生成缩略图。
                    thumb: {
                        quality: 70,

                        // 不允许放大
                        allowMagnify: false,

                        // 是否采用裁剪模式。如果采用这样可以避免空白内容。
                        crop: false
                    },

                    // 禁掉分块传输，默认是开起的。
                    chunked: false,

                    // 禁掉上传前压缩功能，因为会手动裁剪。
                    compress: false,

                    // fileSingleSizeLimit: 2 * 1024 * 1024,

                    server: '{$server}',
                    swf: 'dist/Uploader.swf',
                    onError: function() {
                        var args = [].slice.call(arguments, 0);
                        alert(args.join('\\n'));
                    }
                });

                uploader.on('fileQueued', function( _file ) {
                    file = _file;

                    uploader.makeThumb( file, function( error, src ) {

                        if ( error ) {
                            alert('不能预览');
                            return;
                        }

                        selectCb( src );

                    }, FRAME_WIDTH, 1 );   // 注意这里的 height 值是 1，被当成了 100% 使用。
                }).on('uploadSuccess', function(file, data) {
                    var url = '{$web}/' + data.url;
                    $( '#'+file.id ).find('p.state').text('上传成功').fadeOut();
                    uploaderContainer.removeClass('webuploader-element-invisible');
                    $('.cropper-wraper').addClass('webuploader-element-invisible');
                    $( '#{$options['boxId']} .webuploader-pick' ).html('<img src="'+url+'" width="{$options['previewWidth']}" height="{$options['previewHeight']}"/>');
                    $( '#{$options['id']}' ).val(url);
                    $( '#{$options['boxId']} .webuploader-pick' ).siblings('div').width("{$options['previewWidth']}").height("{$options['previewHeight']}");
                });
            },

            crop: function( data ) {

                var scale = Croper.getImageSize().width / file._info.width;
                data.scale = scale;

                file._cropData = {
                    x: data.x,
                    y: data.y,
                    width: data.width,
                    height: data.height,
                    scale: data.scaleX / data.scaleY
                };
            },

            upload: function() {
                uploader.upload();
            },
        }
    })();

    // ---------------------------------
    // ---------  Crpper ---------------
    // ---------------------------------
    var Croper = (function() {
        var container = $('.cropper-wraper');
        var image = container.find('.img-container img');
        var btn = $('.upload-btn');
        var isBase64Supported, callback;



        function srcWrap( src, cb ) {

            // we need to check this at the first time.
            if (typeof isBase64Supported === 'undefined') {
                (function() {
                    var data = new Image();
                    var support = true;
                    data.onload = data.onerror = function() {
                        if( this.width != 1 || this.height != 1 ) {
                            support = false;
                        }
                    }
                    data.src = src;
                    isBase64Supported = support;
                })();
            }

            if ( isBase64Supported ) {
                cb( src );
            } else {
                // otherwise we need server support.
                // convert base64 to a file.
                $.ajax('preview.php', {
                    method: 'POST',
                    data: src,
                    dataType:'json'
                }).done(function( response ) {
                    if (response.result) {
                        cb( response.result );
                    } else {
                        alert("预览出错");
                    }
                });
            }
        }

        btn.on('click', function() {
            callback && callback(image.cropper('getData'));
            return false;
        });

        return {
            setSource: function( src ) {
                // 处理 base64 不支持的情况。
                // 一般出现在 ie6-ie8
                srcWrap( src, function( src ) {
                    $('#cropperModal').modal('show');
                    image.attr('src', src);
                    image.cropper({
                        viewMode:1,
                        dragMode:'move',
                        aspectRatio: {$options['previewWidth']} / {$options['previewHeight']},
                        done: function(data) {
                            // console.log(data);
                        }
                    });
                });

                container.removeClass('webuploader-element-invisible');

                return this;
            },

            getImageSize: function() {
                var img = image.get(0);
                return {
                    width: img.naturalWidth,
                    height: img.naturalHeight
                }
            },

            setCallback: function( cb ) {
                callback = cb;
                return this;
            },

            disable: function() {
                image.cropper("disable");
                return this;
            },

            enable: function() {
                image.cropper("enable");
                return this;
            }
        }

    })();
    var container = $('.uploader-container');
    Uploader.init(function( src ) {
        Croper.setSource( src );
        // 隐藏选择按钮。
        container.addClass('webuploader-element-invisible');
        // 当用户选择上传的时候，开始上传。
        Croper.setCallback(function( data ) {
            Uploader.crop(data);
            Uploader.upload();
        });
    });
Js
);
