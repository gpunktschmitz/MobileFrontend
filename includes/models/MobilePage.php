<?php
/**
 * MobilePage.php
 */

/**
 * Retrieves information specific to a mobile page
 * Currently this only provides helper functions for loading PageImage associated with a page
 * @todo FIXME: Rename when this class when its purpose becomes clearer
 */
class MobilePage {
	const SMALL_IMAGE_WIDTH = 150;
	const TINY_IMAGE_WIDTH = 80;

	/**
	 * @var Title: Title for page
	 */
	private $title;
	/**
	 * @var Revision|bool
	 */
	private $rev;
	/**
	 * @var string|bool
	 */
	private $revisionTimestamp;
	/**
	 * @var File Associated page image file (see PageImages extension)
	 */
	private $file;
	/**
	 * @var boolean Whether to use page images
	 */
	private $usePageImages;

	/**
	 * Constructor
	 * @param Title $title
	 * @param File|bool $file
	 */
	public function __construct( Title $title, $file = false ) {
		$this->title = $title;
		// @todo FIXME: check existence
		if ( defined( 'PAGE_IMAGES_INSTALLED' ) ) {
			$this->usePageImages = true;
			$this->file = $file ? $file : PageImages::getPageImage( $title );
		}
	}

	/**
	 * @return Revision|bool
	 */
	private function getRevision() {
		if ( $this->rev === null ) {
			$this->rev = Revision::newKnownCurrent(
				wfGetDB( DB_REPLICA ),
				$this->title->getArticleID(),
				$this->title->getLatestRevID()
			);
		}
		return $this->rev;
	}

	/**
	 * Retrieve timestamp when the page content was last modified. Does not reflect null edits.
	 * @return string|bool Timestamp (MW format) or false
	 */
	public function getLatestTimestamp() {
		if ( $this->revisionTimestamp === null ) {
			$rev = $this->getRevision();
			$this->revisionTimestamp = $rev ? $rev->getTimestamp() : false;
		}
		return $this->revisionTimestamp;
	}

	/**
	 * Set rev_timestamp of latest edit to this page
	 * @param string Timestamp (MW format)
	 */
	public function setLatestTimestamp( $timestamp ) {
		$this->revisionTimestamp = $timestamp;
	}

	/**
	 * Retrieve the last edit to this page.
	 * @return array defining edit with keys:
	 * - string name
	 * - string timestamp (Unix format)
	 * - string gender
	 */
	public function getLatestEdit() {
		$rev = $this->getRevision();
		$edit = [
			'timestamp' => false,
			'name' => '',
			'gender' => '',
		];
		if ( $rev ) {
			$edit['timestamp'] = wfTimestamp( TS_UNIX, $rev->getTimestamp() );
			$userId = $rev->getUser();
			if ( $userId ) {
				$revUser = User::newFromId( $userId );
				$edit['name'] = $revUser->getName();
				$edit['gender'] = $revUser->getOption( 'gender' );
			}
		}
		return $edit;
	}

	/**
	 * Get the title of the page
	 *
	 * @return Title
	 */
	public function getTitle() {
		return $this->title;
	}

	/**
	 * Get a placeholder div container for thumbnails
	 * @param string $className
	 * @param string $iconClassName controls size of thumbnail, defaults to icon-32px
	 * @return string
	 */
	public static function getPlaceHolderThumbnailHtml( $className, $iconClassName = 'icon-32px' ) {
		return Html::element( 'div', [
			'class' => 'list-thumb list-thumb-placeholder ' . $iconClassName . ' ' . $className,
		] );
	}

	/**
	 * Check whether a page has a thumbnail associated with it
	 *
	 * @return Boolean whether the page has an image associated with it
	 */
	public function hasThumbnail() {
		return $this->file ? true : false;
	}

	/**
	 * Get a small sized thumbnail in div container.
	 *
	 * @param boolean $useBackgroundImage Whether the thumbnail should have a background image
	 * @return string
	 */
	public function getSmallThumbnailHtml( $useBackgroundImage = false ) {
		return $this->getPageImageHtml( self::SMALL_IMAGE_WIDTH, $useBackgroundImage );
	}

	/**
	 * Get the thumbnail container for getMediumThumbnailHtml() and getSmallThumbnailHtml().
	 *
	 * @param integer $size the width of the thumbnail
	 * @param boolean $useBackgroundImage Whether the thumbnail should have a background image
	 * @return string
	 */
	private function getPageImageHtml( $size, $useBackgroundImage = false ) {
		$imageHtml = '';
		// FIXME: Use more generic classes - no longer restricted to lists
		if ( $this->usePageImages ) {
			$file = $this->file;
			if ( $file ) {
				$thumb = $file->transform( [ 'width' => $size ] );
				if ( $thumb && $thumb->getUrl() ) {
					$className = 'list-thumb ';
					$className .= $thumb->getWidth() > $thumb->getHeight()
						? 'list-thumb-y'
						: 'list-thumb-x';
					$props = [
						'class' => $className,
					];

					$imgUrl = wfExpandUrl( $thumb->getUrl(), PROTO_CURRENT );
					if ( $useBackgroundImage ) {
						$props['style'] = 'background-image: url("' . wfExpandUrl( $imgUrl, PROTO_CURRENT ) . '")';
						$text = '';
					} else {
						$props['src'] = $imgUrl;
						$text = $this->title->getText();
					}
					$imageHtml = Html::element( $useBackgroundImage ? 'div' : 'img', $props, $text );
				}
			}
		}
		return $imageHtml;
	}
}
