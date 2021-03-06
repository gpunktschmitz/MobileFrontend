<?php
/**
 * ResourceLoaderModule subclass for mobile
 * Allows basic parsing of messages without arguments
 */
class MFResourceLoaderParsedMessageModule extends ResourceLoaderFileModule {
	/** @var array Saves a list of messages which have been marked as needing parsing. */
	protected $parsedMessages = [];
	/** @var array Saves a list of message keys used by this module. */
	protected $messages = [];
	/** @var array Saves the target for the module (e.g. desktop and mobile). */
	protected $targets = [ 'mobile', 'desktop' ];
	/** @var boolean Whether the module abuses getScript. */
	protected $hasHackedScriptMode = false;

	/**
	 * Registers core modules and runs registration hooks.
	 * @param $options List of options; if not given or empty, an empty module will be constructed
	 */
	public function __construct( $options ) {
		foreach ( $options as $member => $option ) {
			switch ( $member ) {
				case 'messages':
					$this->processMessages( $option );
					$this->hasHackedScriptMode = true;
					// Prevent them being reinitialised when parent construct is called.
					unset( $options[$member] );
					break;
			}
		}

		parent::__construct( $options );
	}

	/**
	 * Process messages which have been marked as needing parsing
	 *
	 * @param ResourceLoaderContext $context
	 * @return string JavaScript code
	 */
	public function addParsedMessages( ResourceLoaderContext $context ) {
		if ( !$this->parsedMessages ) {
			return '';
		}
		$messages = [];
		foreach ( $this->parsedMessages as $key ) {
			$messages[ $key ] = $context->msg( $key )->parse();
		}
		return Xml::encodeJsCall( 'mw.messages.set', [ $messages ] );
	}

	/**
	 * Separate messages which have been marked as needing parsing from standard messages
	 * @param array $messages Array of messages to process
	 */
	private function processMessages( $messages ) {
		foreach ( $messages as $key => $value ) {
			if ( is_array( $value ) ) {
				foreach ( $value as $directive ) {
					if ( $directive == 'parse' ) {
						$this->parsedMessages[] = $key;
					}
				}
			} else {
				$this->messages[] = $value;
			}
		}
	}

	/**
	 * Gets all scripts for a given context concatenated together including processed messages
	 *
	 * @param ResourceLoaderContext $context Context in which to generate script
	 * @return string JavaScript code for $context
	 */
	public function getScript( ResourceLoaderContext $context ) {
		$script = parent::getScript( $context );
		return $this->addParsedMessages( $context ) . $script;
	}

	/**
	 * Get the URL or URLs to load for this module's JS in debug mode.
	 * @param ResourceLoaderContext $context
	 * @return array list of urls
	 * @see ResourceLoaderModule::getScriptURLsForDebug
	 */
	public function getScriptURLsForDebug( ResourceLoaderContext $context ) {
		if ( $this->hasHackedScriptMode ) {
			$derivative = new DerivativeResourceLoaderContext( $context );
			$derivative->setDebug( true );
			$derivative->setModules( [ $this->getName() ] );
			// @todo FIXME: Make this templates and update
			// makeModuleResponse so that it only outputs template code.
			// When this is done you can merge with parent array and
			// retain file names.
			$derivative->setOnly( 'scripts' );
			$rl = $derivative->getResourceLoader();
			$urls = [
				$rl->createLoaderURL( $this->getSource(), $derivative ),
			];
		} else {
			$urls = parent::getScriptURLsForDebug( $context );
		}
		return $urls;
	}
}
