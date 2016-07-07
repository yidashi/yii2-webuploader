<div id="<?= $boxId ?>">
    <?php if($image): ?><?= $image ?><?php else: ?><button class="btn btn-primary">选择文件</button><?php endif; ?>
    <div class="uploader-list"></div>
</div>
<?= $hiddenInput ?>