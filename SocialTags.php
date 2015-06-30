<?php
/**
 * 'Social Tags' extension
 *
 * @file
 * @ingroup Extensions
 * 
 * @author Stanisław Gackowski
 * @license https://www.gnu.org/copyleft/gpl.html GNU General Public License 2.0 or later
 */

if ( !defined( 'MEDIAWIKI' ) ) {
	die( -1 );
}

$GLOBALS['wgExtensionCredits']['parserhook'][] = array(
	'path'				=> __FILE__,
	'name'				=> 'Social Tags',
	'author'			=> 'Stanisław Gackowski',
	'version'			=> '0.1',
	'url'				=> 'https://github.com/PCGamingWiki/socialtags',
	'descriptionmsg'	=> 'socialtags-desc',
	'license-name'		=> 'GPL-2.0+',
);

$GLOBALS['wgExtensionMessagesFiles']['SocialTagsMagic']	= dirname( __FILE__ ) . '/SocialTags.magic.php';
$GLOBALS['wgMessagesDirs']['SocialTags']				= __DIR__ . '/i18n';

$GLOBALS['wgSocialTagsImage']				= $wgLogo;
$GLOBALS['wgSocialTagsImageSize']			= 300;
$GLOBALS['wgSocialTagsTwitterSupport']		= true;
$GLOBALS['wgSocialTagsTwitterHandle']		= "@mediawiki";
$GLOBALS['wgSocialTagsTwitterCardType']		= "summary";
$GLOBALS['wgSocialTagsTwitterDescription']	= $wgSitename;

$GLOBALS['wgHooks']['ParserFirstCallInit'][]		= 'ExtSocialTags::init';
$GLOBALS['wgParserOutputHooks']['ogpimage']			= 'ExtSocialTags::ph_image';
$GLOBALS['wgParserOutputHooks']['ogpdescription']	= 'ExtSocialTags::ph_description';
$GLOBALS['wgHooks']['BeforePageDisplay'][]			= 'ExtSocialTags::pageHook';

class ExtSocialTags {
	public static function init( Parser &$parser ) {
		$parser->setFunctionHook( 'ogpimage', 'ExtSocialTags::pf_image' );
		$parser->setFunctionHook( 'ogpdescription', 'ExtSocialTags::pf_description');
		return true;
	}

	public static function pf_image( Parser &$parser, $ogimage ) {
		$pout = $parser->getOutput();
		if ( isset( $pout->hasOGImage ) && $pout->hasOGImage ) {
			return $ogimage;
		}

		$file = Title::newFromText( $ogimage, NS_FILE );
		$pout->addOutputHook( 'ogpimage', array( 'dbkey' => $file->getDBkey() ) );
		$pout->hasOGImage = true;

		return $ogimage;
	}

	public static function pf_description( Parser &$parser, $ogdescription ) {
		$pout = $parser->getOutput();
		$pout->addOutputHook( 'ogpdescription', $ogdescription );
		return '';
	}

	public static function ph_image( $out, $pout, $data ) {
		$out->mOGImage = wfFindFile( Title::newFromDBkey( "File:" . $data['dbkey'] ) );
	}

	public static function ph_description( $out, $pout, $data ) {
		$out->mOGDescription = $data;
	}

	public static function pageHook( &$out, &$sk ) {
		global $wgSitename, $wgXhtmlNamespaces, $wgSocialTagsImage,
			$wgSocialTagsImageSize, $wgSocialTagsTwitterSupport,
			$wgSocialTagsTwitterDescription, $wgSocialTagsTwitterHandle,
			$wgSocialTagsTwitterCardType;

		$wgXhtmlNamespaces["og"] = "http://opengraphprotocol.org/schema/";
		$title = $out->getTitle();
		$meta = array();

		if ( $title->isContentPage() ) {
			if ( $title->isMainPage() ) {
				$meta["og:type"]	= "website";
				$meta["og:title"]	= $wgSitename;
				$meta["og:image"]	= wfExpandUrl( $wgSocialTagsImage );
			}
			else {
				$meta["og:type"]		= "article";
				$meta["og:site_name"]	= $wgSitename;
				$meta["og:title"]		= $title->getText() . " | " . $wgSitename;

				if ( isset( $out->mOGImage ) && ( $out->mOGImage !== false ) ) {
					$meta["og:image"] = wfExpandUrl( $out->mOGImage->createThumb( $wgSocialTagsImageSize ) );
				}
				else {
					$meta["og:image"] = wfExpandUrl( $wgSocialTagsImage );
				}
			}

			if ( $wgSocialTagsTwitterSupport ) {
				$meta["twitter:card"]	= $wgSocialTagsTwitterCardType;
				$meta["twitter:site"]	= $wgSocialTagsTwitterHandle;
				$meta["twitter:image"]	= $meta["og:image"];
				$meta["twitter:title"]	= $meta["og:title"];


				if ( isset( $out->mOGDescription ) && ( $out->mOGDescription !== false ) ) {
					$meta["twitter:description"] = $out->mOGDescription;
				}
				else {
					$meta["twitter:description"] = $wgSocialTagsTwitterDescription;
				}
			}
		}
		else {
			$meta["og:type"]		= "article";
			$meta["og:site_name"]	= $wgSitename;
			$meta["og:title"]		= $title->getText() . " | " . $wgSitename;
			$meta["og:image"]		= wfExpandUrl( $wgSocialTagsImage );

			if ( $wgSocialTagsTwitterSupport ) {
				$meta["twitter:card"]			= "summary";
				$meta["twitter:site"]			= $wgSocialTagsTwitterHandle;
				$meta["twitter:image"]			= $meta["og:image"];
				$meta["twitter:title"]			= $meta["og:title"];
				$meta["twitter:description"]	= $wgSocialTagsTwitterDescription;
			}
		}

		$meta["og:url"] = $title->getFullURL();

		foreach( $meta as $property => $value ) {
			if ( $value ) {
				if ( isset( OutputPage::$metaAttrPrefixes ) && isset( OutputPage::$metaAttrPrefixes['property'] ) ) {
					$out->addMeta( "property:$property", $value );
				}
				else {
					$out->addHeadItem( "meta:property:$property", " " . Html::element( 'meta', array( 'property' => $property, 'content' => $value ) ) );
				}
			}
		}

		return true;
	}
}
