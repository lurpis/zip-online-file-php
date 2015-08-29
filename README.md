PHP - Zip online file
=================
## 简介
打包在线文件为Zip

## 使用方法
```php
require_once '../source/Zip.class.php';

$demo_package = new Zip('demo_package');
$data = array(
	array(
		'url'=>'http://static.blog.lurrpis.com/logo/500x500.png',
		'path'=>'/',
		'name'=>'logo'
		),
	array(
		'url'=>'http://static.blog.lurrpis.com/images/1.png',
		'path'=>'/images/dir',
		'name'=>'image_one'
		),
	array(
		'url'=>'http://static.blog.lurrpis.com/images/2.png',
		'path'=>'/images'
		)
	);

$demo_package->zip($data);
$demo_package->download();
```