<?php
require_once './Poster.php';
$poster = new Poster();
$coverConfig = array(
	array('type' => 'text','value' => 'hello','top' => '0','left' => 'center','color' => '#ff0000'),
	array('type' => 'image','value' => 'input/image/php.png','top' => 'center','left' => 'center','zoom' => 0.5),
);
$result = $poster->setParam('siteDir',dirname($_SERVER['SCRIPT_NAME']) . '/')
                 ->setParam('isShowImage',false)
                 ->setParam('cutConfig',['x' => 2,'y' => 2])
                 ->setParam('coverConfig',$coverConfig)
                 ->getPoster();
$file = isset($result['file']) ? $result['file'] : '';
$files = isset($result['files']) ? $result['files'] : array();
echo <<<EOT
	<style> *{margin:0px;padding:0px} img{max-width:200px;border:1px solid red}</style>
EOT;
if($file){
	echo <<<EOT
	<img src="{$file}"  title='原图:{$file}'> <br/>
EOT;
}
if($files){
	foreach($files as $i => $value){
		echo <<<EOT
	<img src="{$value}" title='切片{$i}:{$value}'>
EOT;
		if((($i + 1) % $poster->cutConfig['x']) == 0){
			echo '<br/>';
		}
	}
}
echo $poster->getError();