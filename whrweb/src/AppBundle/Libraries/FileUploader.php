<?php

namespace AppBundle\Libraries;

use Symfony\Component\HttpFoundation\File\UploadedFile,
	Symfony\Component\HttpFoundation\File\Exception\UnexpectedTypeException,
	Symfony\Component\HttpFoundation\File\Exception\UploadException;

class FileUploader{
	private $targetDir;
	private $balanceDirectories;
	private $balanceLevel;
	private $balanceStep = 3;
	private $maxImageSizeBytes;
	private $maxFileSizeBytes;
	private $maxImageWidth;
	private $maxImageHeight;
	private $allowedUploadMIME;
	private $allowedImageExt = [
		'png',
		'jpeg',
		'jpg',
		'jpe',
		'gif',
		'bmp',
		'tif',
		'tiff',
		'ico'
	];

	public function __construct(
		$targetDir,
		$maxImageWidth = 1020,
		$maxImageHeight = 1020,
		$maxFileSizeBytes = 4096000,
		$maxImageSizeBytes = 200000,
		$allowedUploadMIME = [
			'pdf' => 'application/pdf',
			'txt' => 'text/plain',
			'png' => 'image/png',
			'jpeg' => 'image/jpeg',
			'jpg' => 'image/jpeg',
			'jpe' => 'image/jpeg',
			'gif' => 'image/gif',
			'bmp' => 'image/bmp',
			'tif' => 'image/tiff',
			'tiff' => 'image/tiff',
			'ico' => 'image/x-icon',
		]
	){
		$this->setTargetDirectory( $targetDir );
		$this->balanceDirectories();
		$this->maxImageSizeBytes = $maxImageSizeBytes;
		$this->maxFileSizeBytes = $maxFileSizeBytes;
		$this->maxImageWidth = $maxImagewidth;
		$this->maxImageHeight = $maxImageHeight;
		$this->allowedUploadMIME = $allowedUploadMIME;
	}

	public function setTargetDirectory( $targetDir ){
		$this->targetDir = realpath( $targetDir );
	}

	public function balanceDirectories( $flag = true, $dirlev = 3; ){
		$this->balanceDirectories = $flag;
		$this->balanceLevel = $dirlev;
	}

	public function setAllowedMime( $ext, $MIME ){
		$this->allowedUploadMIME[ $ext ] = $MIME;
	}

	public function setImageExt( $ext ){
		self::allowedImageExt[] = $ext;
	}

	public function getAllowedMimes(){
		return $this->allowedUploadMIME;
	}

	public function upload( UploadedFile $file, $dirBalance = true ){
		$ext = $file->guessExtension();
		$isImage = in_array( $ext, $this->allowedImageExt );
		$mime = $file->getMimeType();

		if(
			!isset( $this->allowedUploadMIME[ $ext ] )
			|| $this->allowedUploadMIME[ $ext ] !== $mime
		)
			throw new UnexpectedTypeException( $ext, $this->allowedUploadMIME );

		if(
			$file->getSize() > $this->maxFileSizeBytes
			|| ( $isImage && $file->getSize() > $this->maxImageSizeBytes )
		)
			throw new UploadException( 'app.invalid.uploadsize' );

		if( $isImage ){
			$tmp = getimagesize( $file->getRealPath() );
			if(
				$tmp === false
				|| $tmp[ 0 ] > $this->maxImageWidth
				|| $tmp[ 1 ] > $this->maxImageHeight
			)
				throw new UploadException( 'app.invalid.imagedimension' );
		}

		$fileName = md5( $file->getClientOriginalName() . $file->getClientOriginalExtension() . $file->getClientMimeType() );
		$targetDir = $this->targetDir;
		$d = '';

		if( $dirBalance ){

			$d = [];
			$len = strlen( $fileName ) - $this->balanceStep;
			$c = 0;
			$lev = $this->balanceLevel;

			do{
				$d[] = substr( $fileName, $c, $this->balanceStep );
			}while( --$lev > 0 && $c < $len );

			array_unshift( $d, '' );
			$d = implode( '/', $d );

			$targetDir .= $d;
			$d .= '/';
		}

		$fileName .= ( '.' . $ext );

		$file = $file->move( $targetDir, $fileName );

		$fileName = $d . $fileName;

		return array( $fileName, $file );
	}

}