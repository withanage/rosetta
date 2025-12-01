<?php



class General
{

	public static function removeNodesListFromDom($dcDom, $nodeNames): void
	{
		foreach ($nodeNames as $nodeName) {
			self::removeNodeFromDom($dcDom, $nodeName);

		}
	}

	public static function removeNodeFromDom($dcDom, string $nodeName): void
	{
		$nodeModified = $dcDom->getElementsByTagName($nodeName)->item(0);
		if ($nodeModified) {
			$nodeModified->parentNode->removeChild($nodeModified);
		}
	}
}
