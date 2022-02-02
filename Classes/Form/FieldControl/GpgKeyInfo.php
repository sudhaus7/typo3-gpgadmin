<?php

namespace SUDHAUS7\Sudhaus7Gpgadmin\Form\FieldControl;

use TYPO3\CMS\Backend\Form\Element\TextElement;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use function file_put_contents;
use function htmlentities;
use function is_executable;
use function pclose;
use function sys_get_temp_dir;

class GpgKeyInfo extends TextElement
{
	public function render()
	{
		$gpgbinary = GeneralUtility::makeInstance(ExtensionConfiguration::class)->get('sudhaus7_gpgadmin','gpgbinary');
		$row = $this->data['databaseRow'];
		if (!empty($gpgbinary) && is_executable( $gpgbinary) && !empty($row['pgp_public_key'])) {
			$tmpfile = tempnam( sys_get_temp_dir(),'k');

			file_put_contents( $tmpfile, $row['pgp_public_key']);
			$fp = popen($gpgbinary.' --with-fingerprint --with-colons '.$tmpfile,'r');
			$buf = '';
			while ($r = fgets($fp,256)) {
				$buf .= $r;
			}
			pclose( $fp);
			unlink($tmpfile);

			$key = $this->parse($buf);

			if (empty($key)) {
				$result = 'Can not read Key';
			} else {
				$result = sprintf('%s<br/>', htmlentities( $key['uid']));
				$result .= sprintf('Fingerprint: %s<br/>',$key['fingerprint']);
				$result .= sprintf('Valid %s - %s',$key['start'],$key['end']);
			}
		} else {
			$result = 'For detailed Information about this Key, please install and configure the pgp/gpg binary (optional)';
		}

		$return = parent::render();

		$return['html'].='<div class="pgp_key_info">'.$result.'</div>';

		return $return;
	}

	private function parse($buf):array
	{
		$buf = trim($buf);
		$key = [];
		$bufArray = preg_split( "/((\r?\n)|(\r\n?))/", $buf ) ;
		foreach ( $bufArray as $line ) {
			$line = explode(':',trim($line,': '));
			switch($line[0]) {
				case 'pub':
					$key['length'] = $line[2];
					$key['fingerprint'] = $line[4];
					$key['start'] = gmdate('Y-m-d',$line[5]);
					$key['end'] = gmdate('Y-m-d',$line[6]);
					break;
				case 'fpr':
					$key['fingerprint'] = $line[9];
					break;
				case 'uid':
					$key['uid'] = $line[9];
					break;
				default:
					break;
			}
		}
		return $key;
	}

}
