<?php
/**
 * @author lurrpis
 * @date 15/8/29 下午9:51
 * @blog http://lurrpis.com
 */

require_once '../source/Zip.class.php';

$zip = new Zip('DEMO_PKG');
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

$zip->zip($data);
$zip->download();

?>