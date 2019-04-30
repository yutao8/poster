# PHP生成海报

### 效果图

![](https://i.loli.net/2019/04/30/5cc7aa7e4d4ae.png)

### 使用示例

```php
require_once './Poster.php';
$poster = new Poster();
$coverConfig = array(
	array('type' => 'image','value' => 'input/image/php.png','top' => 'center','left' => 'center','zoom' => 0.5),
	array('type' => 'text','value' => 'hello','top' => '0','left' => 'center','color' => '#ff0000'),
);
$result = $poster->setParam('siteDir',dirname($_SERVER['SCRIPT_NAME']) . '/')
                 ->setParam('isShowImage',false)
                 ->setParam('cutConfig',['x' => 2,'y' => 2])
                 ->setParam('coverConfig',$coverConfig)
                 ->getPoster();
$file = isset($result['file']) ? $result['file'] : ''; //生成的原图
$files = isset($result['files']) ? $result['files'] : array();//切片生成的图片

```

#### 配置说明

```php
//默认文字水印样式
$defaultTextConfig  = array(
	'value' => 'hello php', //文字内容
	'font' => 'NotoSansCJKsc-Regular.otf', //字体文件路径
	'color' => '#000', //文字颜色
	'size' => 20, //文字字号
	'left' => 'center', //左边距(像素);'center'可居中;'小数':等分
	'top' => '0', //上边距(像素);'center'可居中;'小数':等分
	'angle' => 0, //斜率
	'minSize' => 12, //最小字号,水平居中时自动缩放字体大小
); 
	
//默认图片水印样式
 $defaultImageConfig = array(
	'value' => './input/image/php.png', //图片路径
	'left' => 'center', //左边距(像素);'center'可居中;'小数':等分
	'top' => '0', //上边距(像素);'center'可居中;'小数':等分
	'isTransparent' => false, //是否透明
	'radius' => array(1,1), //圆角率(水平,垂直)
	'zoom' => 1, //缩放比例,1:原始比例
	'width' => '', //图片宽度(像素);非数字:自动获取
	'height' => '', //图片高度(像素);非数字:自动获取
); 

```


