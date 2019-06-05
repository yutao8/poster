<?php

class Poster{
	public  $backgroundConfig        = array(); //背景图配置
	public  $coverConfig             = array(); //水印配置
	public  $cutConfig               = array('x' => 1,'y' => 1); //切片配置
	public  $isUpdate                = true; //同名图片是否覆盖
	public  $isShowImage             = false; //输出效果图片
	public  $inputDir                = "input/"; //项目素材目录
	public  $outputDir               = "output/"; //生成的素材的目录
	public  $fontDir                 = "input/fonts/"; //字体默认目录
	public  $siteDir                 = '/'; //网站根目录
	public  $fileName                = ''; //生成的素材的文件名
	public  $fileExt                 = '.jpg'; //生成的素材文件名后缀
	public  $file                    = ''; //生成的素材的目录+文件名
	public  $files                   = array(); //切片生成的图片文件
	private $error                   = ''; //错误信息
	private $defaultBackgroundConfig = array(
		'file' => './input/image/bg_500x500.jpg',
		'width' => 500,
		'height' => 500,
	);
	private $defaultTextConfig       = array(
		'value' => 'hello php', //文字内容
		'font' => 'NotoSansCJKsc-Regular.otf', //字体文件路径
		'color' => '#000', //文字颜色
		'size' => 20, //文字字号
		'left' => 'center', //左边距(像素);'center'可居中;'小数':等分
		'top' => '0', //上边距(像素);'center'可居中;'小数':等分
		'angle' => 0, //斜率
		'minSize' => 12, //最小字号,水平居中时自动缩放字体大小
	); //默认文字水印样式
	private $defaultImageConfig      = array(
		'value' => './input/image/php.png', //图片路径
		'left' => 'center', //左边距(像素);'center'可居中;'小数':等分
		'top' => '0', //上边距(像素);'center'可居中;'小数':等分
		'isTransparent' => false, //是否透明
		'radius' => array(0,0), //圆角率(水平,垂直)
		'zoom' => 1, //缩放比例,1:原始比例
		'width' => '', //图片宽度(像素);非数字:自动获取
		'height' => '', //图片高度(像素);非数字:自动获取
	); //默认图片水印样式

	function __construct($param_name = null,$param_value = null){
		return $this->setParam($param_name,$param_value);
	}

	/**
	 * 设置配置参数
	 *
	 * @param array|string $param_name
	 * @param array|string $param_value
	 *
	 * @return $this
	 */
	function setParam($param_name = null,$param_value = null){
		if(is_array($param_name) && count($param_name) > 0){
			foreach($param_name as $key => $value){
				is_null($value) or $this->{$key} = $value;
			}
		}elseif(is_string($param_name) && $param_name){
			$this->{$param_name} = $param_value;
		}
		return $this;
	}

	//获取错误信息
	public function getError(){
		return $this->error;
	}

	//获取海报图片
	public function getPoster(){
		if(!$this->_fileInit()){
			return false;
		}
		if(!file_exists($this->file) or $this->isUpdate){
			if(!$this->_createFile()){
				$this->error('图片保存失败!');
				return false;
			}
		}
		if($this->cutConfig['x'] > 1 && $this->cutConfig['y'] > 1){
			$this->files = $this->cutImage();
			return ['file' => $this->file,'files' => $this->files];
		}else{
			return ['file' => $this->siteDir . $this->file];
		}
	}

	private function _fileInit(){
		$this->backgroundConfig = array_merge((array) $this->defaultBackgroundConfig,(array) $this->backgroundConfig);
		if(!$this->mkdirs($this->outputDir) or !is_writable($this->outputDir)){
			$this->error('生成文件夹失败或文件夹无写权限!' . $this->outputDir);
			return false;
		}
		$this->fileName or $this->fileName = md5(serialize($this->backgroundConfig) . serialize($this->coverConfig));
		$this->file = $this->outputDir . $this->fileName . $this->fileExt;
		return $this->file;
	}

	private function _createFile(){
		if(file_exists($this->backgroundConfig['file'])){
			$resource = $this->readImage($this->pathFormat($this->backgroundConfig['file'],$this->inputDir));
			if($resource){
				$this->backgroundConfig['width'] = imagesx($resource);
				$this->backgroundConfig['height'] = imagesy($resource);
			}
		}else{
			$resource = imagecreate($this->backgroundConfig['width'],$this->backgroundConfig['height']);
		}
		if(!is_resource($resource)){
			$this->error('背景图创建失败!');
			return false;
		}
		function_exists('imageantialias') && imageantialias($resource,true);
		if(is_array($this->coverConfig) && $this->coverConfig){
			foreach($this->coverConfig as $coverConfig){
				if($coverConfig['type'] == 'text'){
					$resource = $this->_mergeText($resource,array_merge((array) $this->defaultTextConfig,(array) $coverConfig));
				}elseif($coverConfig['type'] == 'image'){
					$resource = $this->_mergeImage($resource,array_merge((array) $this->defaultImageConfig,(array) $coverConfig));
				}
			}
		}
		if($this->isShowImage){
			$this->showImage($resource);
		}
		return stripos($this->fileExt,'png') === false ? imagejpeg($resource,$this->file) : imagepng($resource,$this->file);
	}

	private function _mergeText($resource,$config){
		$config['font'] = $this->pathFormat($config['font'],$this->fontDir);
		if(!file_exists($config['font'])){
			$this->error('字体文件不存在!' . $config['font']);
			return $resource;
		}
		$config = $this->_autoTextSize($config,$config['value']);
		$colors = is_array($config['color']) ? $config['color'] : $this->hex2rgb($config['color']);
		$color = imagecolorallocate($resource,$colors['red'],$colors['green'],$colors['blue']);
		imagettftext($resource,$config['size'],$config['angle'],$config['left'],$config['top'] + $config['size'] + $config['size'] * 0.15,$color,realpath($config['font']),$config['value']);
		return $resource;
	}

	private function _autoTextSize($conf,$text,$config = array()){
		if(empty($config)){
			$config = $conf;
		}
		$fontBox = imagettfbbox($config['size'],0,$config['font'],$text);
		$font_width = $fontBox['4'] - $fontBox['6']; // 右上角 X - 左上角 X
		$font_height = $fontBox['1'] - $fontBox['7']; // 左下角 Y - 左上角 Y
		if($conf['top'] === 'center'){
			$config['top'] = ($this->backgroundConfig['height'] - $font_height) / 2;
		}elseif(is_numeric($conf['top']) && $conf['top'] < 1){
			$config['top'] = ($this->backgroundConfig['height'] - $font_height) * $conf['top'];
		}
		if($conf['left'] === 'center'){
			$config['left'] = ($this->backgroundConfig['width'] - $font_width) / 2;
			if($config['left'] < 0 && ($config['size'] > $this->$config['minSize'])){
				$config['size'] = $config['size'] - 2;
				return $this->_autoTextSize($conf,$text,$config);
			}else{
				return $config;
			}
		}else{
			if($config['left'] < 0 && ($config['size'] > $this->$config['minSize'])){
				$config['size'] = $config['size'] - 2;
				return $this->_autoTextSize($conf,$text,$config);
			}else{
				return $config;
			}
		}
	}

	//拼接图片(图片资源)
	private function _mergeImage($resource,$config){
		$config['value'] = $this->pathFormat($config['value'],$this->inputDir);
		if(file_exists($config['value'])){
			$image = $this->readImage($config['value']);
		}
		if(!is_resource($image)){
			return $resource;
		}
		if(isset($config['radius']) && (!is_numeric($config['radius'][0]) or !is_numeric($config['radius'][1]) or $config['radius'][0] <= 0 or $config['radius'][1] <= 0 or $config['radius'][0] > 1 && $config['radius'][1] > 1)){
			unset($config['radius']);
		}
		$image_w = imagesx($image);
		$image_h = imagesy($image);
		$config['width'] = is_numeric($config['width']) ? $config['width'] : $image_w * $config['zoom'];
		$config['height'] = is_numeric($config['height']) ? $config['height'] : $image_h * $config['zoom'];
		if($config['left'] === 'center'){
			$config['left'] = ($this->backgroundConfig['width'] - $config['width']) / 2;
		}elseif(is_numeric($config['left']) && $config['left'] < 1){
			$config['left'] = ($this->backgroundConfig['width'] - $config['width']) * $config['left'];
		}
		if($config['top'] === 'center'){
			$config['top'] = ($this->backgroundConfig['height'] - $config['height']) / 2;
		}elseif(is_numeric($config['top']) && $config['top'] < 1){
			$config['top'] = ($this->backgroundConfig['height'] - $config['height']) * $config['top'];
		}
		$config['left'] > 0 or $config['left'] = 0;
		$config['top'] > 0 or $config['top'] = 0;
		if(isset($config['radius']) or $config['isTransparent']){
			if($config['width'] != $image_w or $config['height'] != $image_h){
				$image_new = imagecreatetruecolor($config['width'],$config['height']);
				imagecopyresampled($image_new,$image,0,0,0,0,$config['width'],$config['height'],$image_w,$image_h);
				imagedestroy($image);
				$image = $image_new;
				$image_w = $config['width'];
				$image_h = $config['height'];
			}
			if(isset($config['radius'])){
				$image = $this->_creatCircleImage($image,$image_w,$image_h,$config['radius']);
			}
			imagecopymerge($resource,$image,$config['left'],$config['top'],0,0,$image_w,$image_h,100);
		}else{
			imagecopyresampled($resource,$image,$config['left'],$config['top'],0,0,$config['width'],$config['height'],$image_w,$image_h);
		}
		imagedestroy($image);
		return $resource;
	}

	// 圆角图片
	private function _creatCircleImage($resource,$width = 0,$height = 0,$radius = array()){
		$bgcolor_rgb = array(255,0,0);
		$fgcolor_rgb = array(0,0,0);
		$cover_image = imagecreatetruecolor($width,$height); // 创建一个边长为直径的正方形的图像
		$bgcolor = imagecolorallocate($cover_image,$bgcolor_rgb[0],$bgcolor_rgb[1],$bgcolor_rgb[2]); // 图像的背景
		imagefill($cover_image,0,0,$bgcolor); //填充背景颜色
		$fgcolor = imagecolorallocate($cover_image,$fgcolor_rgb[0],$fgcolor_rgb[1],$fgcolor_rgb[2]); //覆盖层背景颜色
		imagefilledarc($cover_image,$width / 2,$height / 2,$width * $radius[0],$height * $radius[1],0,0,$fgcolor,IMG_ARC_PIE); // 画圆并填色
		imagecolortransparent($cover_image,$fgcolor); //颜色转为透明
		imagecopymerge($resource,$cover_image,0,0,0,0,$width,$height,100);
		$fgcolor = imagecolorallocate($resource,$bgcolor_rgb[0],$bgcolor_rgb[1],$bgcolor_rgb[2]); // 颜色转换
		imagecolortransparent($resource,$fgcolor);
		return $resource;
	}

	//图片平均切片
	private function cutImage(){
		$config = $this->cutConfig;
		$imgFile = $this->file;
		$outputDir = $this->outputDir . $this->fileName . '/';
		if(!file_exists($imgFile) or !$this->mkdirs($outputDir)){
			return false;
		}
		isset($config['x']) or $config['x'] = $config['0'];
		isset($config['y']) or $config['y'] = $config['1'];
		$config['x'] > 1 or $config['x'] = 1;
		$config['y'] > 1 or $config['y'] = 1;
		if($config['x'] <= 1 && $config['y'] <= 1){
			return array($this->siteDir . $imgFile);
		}
		$image = imagecreatefromjpeg($imgFile);
		$width = imagesx($image);
		$height = imagesy($image);
		$per_x = $width / $config['x'];
		$per_y = $height / $config['y'];
		$positions = array();
		for($y = 1;$y <= $config['y'];$y ++){
			for($x = 1;$x <= $config['x'];$x ++){
				$now = ($y - 1) * $config['x'] + $x;
				$positions[$now]['w'] = $per_x;
				$positions[$now]['h'] = $per_y;
				$positions[$now]['x'] = ($x - 1) * $per_x;
				$positions[$now]['y'] = ($y - 1) * $per_y;
			}
		}
		$cutting = array();
		foreach($positions as $i => $position){
			$cutFile = $outputDir . (int) $per_x . '_' . (int) $per_y . "_" . $i . $this->fileExt;
			if(!file_exists($cutFile) or $this->isUpdate){
				$temp_image = imagecreatetruecolor($position['w'],$position['h']);
				imagecopy($temp_image,$image,0,0,$position['x'],$position['y'],$position['w'],$position['h']);
				if(strpos(strtolower($this->fileExt),'png') === false){
					imagejpeg($temp_image,$cutFile);
				}else{
					imagepng($temp_image,$cutFile);
				}
				if(file_exists($cutFile)){
					$cutting[] = $this->siteDir . $cutFile;
				}
			}
		}
		return $cutting;
	}

	//根据图片资源(GD)输出显示图片内容
	private function showImage($resource = null){
		if(gettype($resource) != 'resource'){
			return false;
		}else{
			header("Content-type: image/jpg");
			imagePng($resource);
			die;
		}
	}

	//获取图片对象 返回图片资源(GD)
	private function readImage($imgFile = ''){
		if(strpos($imgFile,'http') !== 0){
			if(strpos($imgFile,'.') === 0){
				$imgFile = substr($imgFile,1);
			}
			if(strpos($imgFile,'/') === 0){
				$imgFile = substr($imgFile,1);
			}
			if(!file_exists($imgFile)){
				$this->error("$imgFile is not exists!");
				return false;
			}
		}
		return imagecreatefromstring(file_get_contents($imgFile));
	}

	//颜色值转换
	private function hex2rgb($color){
		if($color[0] == '#'){
			$color = substr($color,1);
		}
		if(strlen($color) == 6){
			list($r,$g,$b) = array(
				$color[0] . $color[1],
				$color[2] . $color[3],
				$color[4] . $color[5],
			);
		}elseif(strlen($color) == 3){
			list($r,$g,$b) = array(
				$color[0] . $color[0],
				$color[1] . $color[1],
				$color[2] . $color[2],
			);
		}else{
			return false;
		}
		$r = hexdec($r);
		$g = hexdec($g);
		$b = hexdec($b);
		return array('red' => $r,'green' => $g,'blue' => $b);
	}

	//删除空格换行
	private function clearBlank($str = ''){
		return trim($str);
	}

	// 创建多级目录
	private function mkdirs($dir){
		if(!is_dir($dir)){
			if(!$this->mkdirs(dirname($dir))){
				$this->error("$dir dir create error!");
				return false;
			}
			if(!mkdir($dir,0777)){
				$this->error("$dir dir create error!");
				return false;
			}
		}
		return true;
	}

	private function error($error = ''){
		$this->error = $this->error . '\r\n' . $error;
	}

	// 文件夹/文件名格式化
	private function pathFormat($path = '',$path_prefix = ''){
		return is_readable($path) ? $path : $path_prefix . $path;
	}
}
