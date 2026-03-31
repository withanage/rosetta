<?php

/**
 * @file plugins/importexport/rosetta/tests/Support/XmlTestHelper.php
 *
 * Copyright (c) 2014-2025 Simon Fraser University
 * Copyright (c) 2003-2025 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file LICENSE.
 *
 * @class XmlTestHelper
 *
 * @ingroup plugins_importexport_rosetta
 *
 * @brief Trait providing XML assertion helpers for test classes.
 *        Follows Interface Segregation: only XML-related helpers.
 */

namespace APP\plugins\importexport\rosetta\tests\Support;

use DOMDocument;

trait XmlTestHelper
{
	protected function removeNodesFromDom(DOMDocument $dom, array $nodeNames): void
	{
		foreach ($nodeNames as $nodeName) {
			$node = $dom->getElementsByTagName($nodeName)->item(0);
			if ($node) {
				$node->parentNode->removeChild($node);
			}
		}
	}

	protected function assertXmlHasElement(DOMDocument $dom, string $tagName, string $message = ''): void
	{
		$nodes = $dom->getElementsByTagName($tagName);
		$this->assertGreaterThan(0, $nodes->length, $message ?: "Expected XML to contain element <{$tagName}>");
	}

	protected function assertXmlElementValue(DOMDocument $dom, string $tagName, string $expectedValue, string $message = ''): void
	{
		$nodes = $dom->getElementsByTagName($tagName);
		$found = false;
		for ($i = 0; $i < $nodes->length; $i++) {
			if ($nodes->item($i)->textContent === $expectedValue) {
				$found = true;
				break;
			}
		}
		$this->assertTrue($found, $message ?: "Expected <{$tagName}> with value '{$expectedValue}'");
	}

	protected function assertXmlElementCount(DOMDocument $dom, string $tagName, int $expectedCount, string $message = ''): void
	{
		$nodes = $dom->getElementsByTagName($tagName);
		$this->assertEquals($expectedCount, $nodes->length, $message ?: "Expected {$expectedCount} <{$tagName}> elements");
	}

	protected function getTestDataPath(string $filename): string
	{
		return dirname(__DIR__) . '/data/' . $filename;
	}
}
