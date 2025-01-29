<?php


// Include Composer's autoloader
require_once dirname(__DIR__) . '/vendor/autoload.php';


use GuzzleHttp\Client;
use Psr\Http\Message\ResponseInterface;

class DepositHandler
{
	private string $host;
	private string $username;
	private string $institutionCode;
	private string $password;
	private int $producerId;
	private int $materialFlowId;

	protected object $client;

	public string $userAgent = 'OJSRosettaExportPlugin';


	public function __construct(string $settingsFile)
	{
		$settings = json_decode(file_get_contents($settingsFile), true);
		$this->host = $settings['host'];
		$this->username = $settings['username'];
		$this->institutionCode = $settings['institutionCode'];
		$this->password = $settings['password'];
		$this->producerId = $settings['producerId'];
		$this->materialFlowId = $settings['materialFlowId'];

		$this->depositHistoryInDays = 730;
		$this->client = new Client([
			'headers' => ['User-Agent' => $this->userAgent],
			'verify' => false
		]);

	}

	private function getDepositEndpoint(string $apiType = ''): string
	{
		return $this->host . '/' . $apiType . '/v0/deposits/';
	}

	private function getBase64Credentials(): string
	{
		return base64_encode($this->username . '-institutionCode-' . $this->institutionCode . ':' . $this->password);
	}

	public function getDepositsFromRosettaApi(int $offset = 0): array
	{
		$deposits = [];
		$params = [
			'producer' => $this->producerId,
			'material_flow' => $this->materialFlowId,
			'creation_date_from' => date('d/m/Y', strtotime('-' . $this->depositHistoryInDays . ' days')),
			'creation_date_to' => date('d/m/Y', strtotime('+1 days')),
			'offset' => $offset
		];
		$endpoint = $this->getDepositEndpoint('rest') . '?' . http_build_query($params);

		$headers = [
			'Content-Type' => 'application/json',
			'Accept' => 'application/json',
			'Authorization' => 'local ' . $this->getBase64Credentials(),
			'accept-encoding' => 'gzip, deflate'
		];
		try {
			// Make a GET request to the Rosetta API with the specified parameters.
			$response = $this->apiRequest($endpoint, $headers);

			if ($response->getStatusCode() === 200) {
				$body = json_decode($response->getBody(), true);

				// Check if there are more records to fetch (pagination).
				if ($body['total_record_count'] > 0) {
					$deposits = array_merge($deposits, $this->getDepositsFromRosettaApi($offset + 1));
				}

				// Merge the fetched deposit records into the result array.
				if (!empty($body['deposit'])) {
					$deposits = array_merge($deposits, $body['deposit']);
				}
			}
		} catch (Exception $e) {
			error_log('Error fetching deposits: ' . $e->getMessage());
		}

		return $deposits;
	}

	public function apiRequest(string $endpoint, array $headers): ResponseInterface
	{
		return $this->client->get($endpoint, ['headers' => $headers]);
	}
}

$settingsFile = __DIR__ . DIRECTORY_SEPARATOR . 'RosettaSettings.json';
$depositHandler = new DepositHandler($settingsFile);
$deposits = $depositHandler->getDepositsFromRosettaApi(0);
print_r(json_encode($deposits));
