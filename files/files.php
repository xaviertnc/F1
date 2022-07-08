<?php namespace F1;

/**
 * F1 - Files Class
 * 
 * Very basic file system class.
 * 
 * Just a more cohesive / logical file management library compared to PHP's hot mess.
 * 
 * @author  C. Moller <xavier.tnc@gmail.com> - 04 Jul 2022
 * 
 * @version 1.0.0 - 04 Jul 2022
 * 
 */

class Files {

	public $baseDir;


	public function __construct( $baseDir = null )
	{
		$this->baseDir = $baseDir ?: $_SERVER[ 'DOCUMENT_ROOT' ] ?? '';
	}


	public function create( $path, $content = null )
	{

	}


	public function read( $path )
	{

	}


	public function delete( $path )
	{

	}


	public function update( $path, $content = null, $append = false )
	{

	}


	public function rename( $path, $newName )
	{

	}


	public function move( $path, $dest )
	{

	}


	public function copy( $path, $dest )
	{

	}


	public function exists( $path )
	{
		return file_exists( $path );
	}


	public function name( $path )
	{
		return basename( $path );
	}


	public function dir( $path )
	{
		return dirname( $path );
	}


	public function mtime( $path )
	{
		return filemtime( $path );
	}


	public function ext( $path )
	{

	}


	public function size( $path )
	{

	}


	public function type( $path )
	{

	}


	public function isDir( $path )
	{
		return is_dir( $path );
	}


	public function list( $dir, $only = null, $extraFilter = null )
	{
		switch( $only )
		{
			case 'subdirs': return glob( $dir . DIRECTORY_SEPARATOR . '*' , GLOB_ONLYDIR );
			default: return glob( $dir . DIRECTORY_SEPARATOR . '*' );
		}
	}


	public function createDir( $dir )
	{

	}


	public function moveDir( $dir, $newParentDir )
	{

	}


	public function copyDir( $dir, $newParentDir )
	{
		
	}


	public function deleteDir( $dir )
	{

	}


	public function zip( array $paths, $zipName = null )
	{

	}


	public function upload()
	{

	}


	public function download()
	{
		
	}

}