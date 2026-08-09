<?php

namespace TIBHannover\Rosetta\Droid;

use Exception;
use RosettaExportPlugin;

class DroidException extends Exception
{
}

class DroidResult
{
	public string $puid = '';
	public string $mime = '';
	public string $formatName = '';
	public bool $extensionMismatch = false;
	public bool $identified = false;
}

/**
 * Identifies a file's format with DROID (PRONOM PUID) before deposit.
 *
 * DROID no-profile mode (-Nr) emits only FILE_PATH,PUID; the EXTENSION_MISMATCH
 * column is a profile-mode feature. We therefore run DROID for the PUID and derive
 * the MIME, format name and extension match from the bundled signature file.
 */
class DroidService
{
	private RosettaExportPlugin $plugin;
	private int $contextId;

	/** @var array<string, array{name: string, mime: string, extensions: string[]}>|null */
	private ?array $formatMap = null;

	public function __construct(RosettaExportPlugin $plugin, int $contextId)
	{
		$this->plugin = $plugin;
		$this->contextId = $contextId;
	}

	public function identify(string $absoluteFilePath): DroidResult
	{
		if (!is_file($absoluteFilePath)) {
			throw new DroidException('DROID: file not found: ' . $absoluteFilePath);
		}

		$java = $this->plugin->getSetting($this->contextId, 'javaPath') ?: 'java';
		$droidDir = $this->getDroidDir();
		$jar = $this->plugin->getSetting($this->contextId, 'droidPath')
			?: $droidDir . '/droid-command-line-6.7.0.jar';
		$signatureFile = $droidDir . '/signatures/DROID_SignatureFile_V124.xml';
		$containerFile = $droidDir . '/signatures/container-signature-20260119.xml';

		foreach ([$jar => 'DROID jar', $signatureFile => 'signature file', $containerFile => 'container signature file'] as $path => $label) {
			if (!is_file($path)) {
				throw new DroidException('DROID: ' . $label . ' missing: ' . $path);
			}
		}

		// DROID's Spring context eagerly starts a Derby profile database under
		// user.home/.droid6 even in no-profile (-Nr) mode. Point user.home at a
		// private writable temp dir so identification never depends on the deposit
		// user's home being writable, and clean it up afterwards.
		$droidHome = $this->makeDroidHome();

		try {
			$command = escapeshellarg($java)
				. ' -Duser.home=' . escapeshellarg($droidHome)
				. ' -cp ' . escapeshellarg($jar) . ':' . escapeshellarg($droidDir . '/lib/*')
				. ' uk.gov.nationalarchives.droid.command.DroidCommandLine'
				. ' -Nr ' . escapeshellarg($absoluteFilePath)
				. ' -Ns ' . escapeshellarg($signatureFile)
				. ' -Nc ' . escapeshellarg($containerFile)
				. ' 2>&1';

			$output = [];
			$status = null;
			exec($command, $output, $status);
		} finally {
			$this->plugin->removeDirRecursively($droidHome);
		}

		if ($status !== 0) {
			throw new DroidException('DROID: exited with status ' . $status . ': ' . implode("\n", $output));
		}

		$puid = $this->parsePuid($output, $absoluteFilePath);

		$result = new DroidResult();
		$result->puid = $puid;
		$result->identified = $puid !== '';

		if ($result->identified) {
			$format = $this->getFormatMap()[$puid] ?? null;
			if ($format !== null) {
				$result->mime = $format['mime'];
				$result->formatName = $format['name'];
				$extension = strtolower(pathinfo($absoluteFilePath, PATHINFO_EXTENSION));
				$result->extensionMismatch = $extension !== ''
					&& !empty($format['extensions'])
					&& !in_array($extension, $format['extensions'], true);
			}
		}

		return $result;
	}

	private function getDroidDir(): string
	{
		return rtrim($this->plugin->getPluginPath(), '/') . '/droid';
	}

	private function makeDroidHome(): string
	{
		$home = sys_get_temp_dir() . '/rosetta-droid-' . getmypid() . '-' . uniqid();
		if (!mkdir($home, 0700, true) && !is_dir($home)) {
			throw new DroidException('DROID: cannot create working directory: ' . $home);
		}
		return $home;
	}

	/**
	 * @param string[] $output
	 */
	private function parsePuid(array $output, string $absoluteFilePath): string
	{
		$header = null;
		foreach ($output as $line) {
			$row = str_getcsv($line);
			if ($header === null) {
				if (in_array('PUID', $row, true)) {
					$header = array_flip($row);
				}
				continue;
			}
			$filePathIndex = $header['FILE_PATH'] ?? 0;
			$puidIndex = $header['PUID'] ?? 1;
			if (($row[$filePathIndex] ?? null) === $absoluteFilePath) {
				return (string) ($row[$puidIndex] ?? '');
			}
		}

		if ($header === null) {
			throw new DroidException('DROID: unparseable output: ' . implode("\n", $output));
		}

		return '';
	}

	/**
	 * @return array<string, array{name: string, mime: string, extensions: string[]}>
	 */
	private function getFormatMap(): array
	{
		if ($this->formatMap !== null) {
			return $this->formatMap;
		}

		$signatureFile = $this->getDroidDir() . '/signatures/DROID_SignatureFile_V124.xml';
		$xml = @simplexml_load_file($signatureFile);
		if ($xml === false) {
			throw new DroidException('DROID: cannot read signature file: ' . $signatureFile);
		}

		$map = [];
		foreach ($xml->xpath('//*[local-name()="FileFormat"]') as $format) {
			$puid = (string) $format['PUID'];
			if ($puid === '') {
				continue;
			}
			$extensions = [];
			foreach ($format->xpath('*[local-name()="Extension"]') as $extension) {
				$extensions[] = strtolower((string) $extension);
			}
			$map[$puid] = [
				'name' => (string) $format['Name'],
				'mime' => (string) $format['MIMEType'],
				'extensions' => $extensions,
			];
		}

		$this->formatMap = $map;
		return $map;
	}
}
